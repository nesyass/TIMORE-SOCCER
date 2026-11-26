<?php

namespace App\Http\Controllers;

use App\Models\AdminContentFields;
use App\Models\AdminRecord;
use App\Services\AdminManualPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminTournamentController extends \App\Http\Controllers\Controller
{
    public function show(Request $request, int $id)
    {
        $tournament = AdminRecord::where('section', 'tournaments')->findOrFail($id);
        AdminRecord::expireTeamRegistrations($id);
        $query = $tournament->teamEntries();
        $counts = (clone $query)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $term = $request->query('q');
        $status = $request->query('status');
        $filters = [
            'q' => is_string($term) ? mb_substr(trim($term), 0, 160) : null,
            'status' => in_array($status, ['waiting_payment', 'payment_review', 'active', 'expired', 'cancelled'], true) ? $status : null,
        ];
        if (filled($filters['q'] ?? null)) {
            $term = $filters['q'];
            $query->where(fn ($search) => $search->where('title', 'like', "%{$term}%")->orWhere('subtitle', 'like', "%{$term}%"));
        }
        if (filled($filters['status'] ?? null)) $query->where('status', $filters['status']);
        $teams = $query->latest()->orderByDesc('id')->paginate(15)->withQueryString();
        $contentFields = AdminContentFields::for('tournaments');

        return view('admin.tournament', compact('tournament', 'teams', 'counts', 'filters', 'contentFields'));
    }

    public function updateState(Request $request, int $id)
    {
        $action = $request->validate(['action' => ['required', Rule::in(['publish', 'draft', 'open', 'close'])]])['action'];
        DB::transaction(function () use ($id, $action) {
            $tournament = AdminRecord::where('section', 'tournaments')->lockForUpdate()->findOrFail($id);
            $content = data_get($tournament->payload, 'content', []);
            if ($action === 'open' && (!$tournament->canOpenTeamRegistration())) {
                throw ValidationException::withMessages(['action' => 'Terbitkan turnamen dan lengkapi tanggal, kategori, periode pendaftaran, dan biaya sebelum membuka pendaftaran.']);
            }
            if ($action === 'publish') $tournament->status = 'published';
            if ($action === 'draft') $tournament->status = 'draft';
            if (in_array($action, ['open', 'close', 'draft'], true)) {
                $content['registration_open'] = $action === 'open';
            }
            $tournament->payload = [...($tournament->payload ?? []), 'content' => $content];
            $tournament->save();
        });
        return back()->with('success', match ($action) {
            'publish' => 'Turnamen diterbitkan.',
            'draft' => 'Turnamen disimpan sebagai draf dan pendaftaran ditutup.',
            'open' => 'Pendaftaran diaktifkan sesuai periode yang ditetapkan.',
            'close' => 'Pendaftaran tim baru ditutup. Pembayaran tim yang sudah masuk tetap bisa ditinjau.',
        });
    }

    public function storeTeam(Request $request, int $id)
    {
        DB::transaction(function () use ($request, $id) {
            $tournament = AdminRecord::where('section', 'tournaments')->lockForUpdate()->findOrFail($id);
            if ($tournament->teamRegistrationState() !== 'open') {
                throw ValidationException::withMessages(['tournament' => 'Pendaftaran tim untuk turnamen ini sedang ditutup.']);
            }
            $data = $request->validate([
                'club' => ['required', 'string', 'max:120'],
                'team' => ['required', 'string', 'max:120'],
                'category' => ['required', Rule::in($tournament->tournamentCategories())],
                'contact_name' => ['required', 'string', 'max:120'],
                'whatsapp' => ['required', 'regex:/^\+?[0-9][0-9 ()-]{7,20}$/'],
            ]);
            $key = $tournament->teamEntryKey($data['club'], $data['team'], $data['category']);
            if (AdminRecord::where('team_entry_key', $key)->exists()) {
                throw ValidationException::withMessages(['team' => 'Tim ini sudah terdaftar pada kategori tersebut.']);
            }
            $tournament->teamEntries()->create([
                'section' => 'team-registrations', 'team_entry_key' => $key, 'title' => $data['team'], 'subtitle' => $data['club'],
                'status' => 'waiting_payment', 'amount' => data_get($tournament->payload, 'content.entry_fee'),
                'date' => today('Asia/Jayapura')->toDateString(),
                'payload' => ['category' => $data['category'], 'contact_name' => $data['contact_name'], 'whatsapp' => $data['whatsapp']],
            ]);
        });

        return redirect()->route('admin.tournaments.show', $id)->with('success', 'Pendaftaran tim dicatat. Pembayaran menunggu konfirmasi.');
    }

    public function review(int $id, int $team)
    {
        AdminRecord::expireTeamRegistrations($id);
        $entry = $this->team($id, $team);
        $changed = AdminRecord::whereKey($entry->id)->where('status', 'waiting_payment')->update(['status' => 'payment_review']);
        return back()->with('success', $changed ? 'Pembayaran ditandai perlu diperiksa.' : 'Status pembayaran sudah berubah.');
    }

    public function verify(Request $request, int $id, int $team)
    {
        $receiptPath = null;
        try {
            $created = DB::transaction(function () use ($request, $id, $team, &$receiptPath) {
                $entry = $this->team($id, $team, true);
                if ($entry->status === 'active') return false;
                abort_unless(in_array($entry->status, ['waiting_payment', 'payment_review', 'expired'], true), 409);
                $payment = AdminManualPayment::confirm($request, $entry, $receiptPath);
                $entry->update(['status' => 'active', 'amount' => $payment['amount'], 'payload' => $payment['payload']]);
                AdminManualPayment::postIncome($entry, $payment);
                return true;
            });
        } catch (\Throwable $error) {
            if ($receiptPath) Storage::disk('local')->delete($receiptPath);
            throw $error;
        }
        return back()->with('success', $created ? 'Pembayaran terverifikasi. Tim dikonfirmasi dan pemasukan tercatat di Buku Kas.' : 'Tim ini sudah terverifikasi.');
    }

    public function receipt(int $id, int $team)
    {
        return AdminManualPayment::download($this->team($id, $team), 'TEAM');
    }

    private function team(int $id, int $team, bool $lock = false): AdminRecord
    {
        $query = AdminRecord::where('section', 'team-registrations')->where('tournament_id', $id);
        if ($lock) $query->lockForUpdate();

        return $query->findOrFail($team);
    }
}
