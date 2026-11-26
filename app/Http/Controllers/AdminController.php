<?php
namespace App\Http\Controllers;

use App\Models\AdminRecord;
use App\Models\AdminContentFields;
use App\Models\AdminStudentAccount;
use App\Services\AdminManualPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends \App\Http\Controllers\Controller
{

    private const SECTIONS = [
        'registrations'=>['Pendaftaran Siswa','users-round','Nama calon siswa','waiting_payment'],
        'students'=>['Data Siswa','graduation-cap','Nama siswa','active'],
        'coaches'=>['Pelatih & Pengurus','users','Nama pelatih','active'],
        'schedules'=>['Jadwal Latihan Rutin','calendar-days','Kelompok / sesi','active'],
        'schedule-changes'=>['Perubahan Jadwal','calendar-clock','Keterangan perubahan','scheduled'],
        'events'=>['Event Umum','calendar','Judul kegiatan','draft'],
        'tournaments'=>['Turnamen TSS','trophy','Nama turnamen','draft'],
        'team-registrations'=>['Pendaftaran Tim SSB','shield','Nama tim / SSB','pending'],
        'cashbook'=>['Buku Kas','wallet','Keterangan transaksi','income'],
        'dues'=>['Iuran Siswa','receipt','Nama siswa / periode','unpaid'],
        'certificates'=>['Sertifikat','award','Nama penerima','draft'],
        'articles'=>['Berita & Publikasi','newspaper','Judul berita','draft'],
        'achievements'=>['Prestasi','medal','Nama prestasi','published'],
        'gallery'=>['Galeri','images','Judul galeri','draft'],
        'site-content'=>['Konten Website','panels-top-left','Nama bagian website','draft'],
    ];
    private const SECTION_STATUSES = [
        'registrations' => ['pending', 'waiting_payment', 'payment_review', 'cancelled', 'expired'],
        'students' => ['active', 'inactive'],
        'coaches' => ['active', 'inactive'],
        'schedules' => ['active', 'inactive'],
        'schedule-changes' => ['scheduled', 'postponed', 'cancelled', 'completed'],
        'events' => ['draft', 'published', 'archived'],
        'tournaments' => ['draft', 'published', 'archived'],
        'team-registrations' => ['pending', 'waiting_payment', 'payment_review', 'active', 'expired', 'cancelled'],
        'cashbook' => ['income', 'expense'],
        'certificates' => ['draft', 'published', 'cancelled'],
        'articles' => ['draft', 'published', 'archived'],
        'achievements' => ['draft', 'published'],
        'gallery' => ['draft', 'published', 'archived'],
        'site-content' => ['draft', 'published', 'archived'],
    ];
    private function section(string $section):array { abort_unless(isset(self::SECTIONS[$section]),404); return self::SECTIONS[$section]; }
    public function dashboard(Request $request)
    {
        AdminRecord::expireRegistrations();
        $now = Carbon::now('Asia/Jayapura');
        $period = $request->query('period') === 'previous' ? 'previous' : 'current';
        $months = in_array($request->query('months'), ['3', '6', '12'], true) ? (int) $request->query('months') : 3;
        $month = $now->copy()->startOfMonth()->subMonths($period === 'previous' ? 1 : 0);
        $registrations = AdminRecord::where('section', 'registrations');
        $periodRegistrations = (clone $registrations)->whereBetween('created_at', [
            $month->copy()->utc(), $month->copy()->endOfMonth()->utc(),
        ]);
        $monthCounts = (clone $periodRegistrations)->whereIn('status', AdminRecord::REGISTRATIONS_AWAITING_ACTION)
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts = [
            'students' => AdminRecord::where('section', 'students')->where('status', 'active')->count(),
            'registrations' => $monthCounts->sum(),
            'payments' => $monthCounts->get('payment_review', 0),
            'events' => AdminRecord::where('section', 'tournaments')->upcomingEvents($now->toDateString())->count(),
        ];
        $pending = (clone $registrations)->whereIn('status', AdminRecord::REGISTRATIONS_AWAITING_ACTION)
            ->orderByRaw("CASE WHEN status = 'payment_review' THEN 0 ELSE 1 END")->oldest()->limit(5)->get();
        $nextChange = AdminRecord::where('section', 'schedule-changes')->where('status', 'scheduled')
            ->whereDate('date', '>=', $now->toDateString())->orderBy('date')->first();
        $routine = AdminRecord::where('section', 'schedules')->where('status', 'active')->orderBy('id')->limit(2)->get();
        $recent = AdminRecord::orderByDesc('updated_at')->orderByDesc('id')->limit(5)->get();
        $sectionLabels = array_map(fn ($meta) => $meta[0], self::SECTIONS);
        $trendStart = $now->copy()->startOfMonth()->subMonths($months - 1);
        $trendCounts = (clone $registrations)->where('created_at', '>=', $trendStart->copy()->utc())
            ->get(['created_at'])->countBy(fn ($record) => $record->created_at->copy()->timezone('Asia/Jayapura')->format('Y-m'));
        $trend = collect(range(0, $months - 1))->map(function ($offset) use ($trendStart, $trendCounts) {
            $date = $trendStart->copy()->addMonths($offset);
            return ['label' => $date->locale('id')->translatedFormat('M y'), 'total' => $trendCounts->get($date->format('Y-m'), 0)];
        });

        $spotlights = collect([
            AdminRecord::whereIn('section', ['events', 'tournaments'])->upcomingEvents($now->toDateString())->orderBy('date')->first(),
            AdminRecord::where('section', 'coaches')->where('status', 'active')->where('payload->content->visibility', 'published')
                ->orderByDesc('updated_at')->orderByDesc('id')->first(),
            AdminRecord::where('section', 'achievements')->where('status', 'published')->orderByDesc('date')->orderByDesc('id')->first(),
        ])->filter();

        return view('admin.dashboard', compact('counts', 'pending', 'nextChange', 'routine', 'recent', 'sectionLabels', 'period', 'months', 'trend', 'spotlights'));
    }
    public function index(Request $request,string $section)
    {
        $meta=$this->section($section);
        $query=AdminRecord::where('section',$section);
        if ($section === 'registrations') AdminRecord::expireRegistrations();
        if ($section === 'team-registrations') {
            AdminRecord::expireTeamRegistrations();
            $query->with('tournament');
        }
        $studentStats = [];
        if ($section === 'students') {
            $active = (clone $query)->where('status', 'active');
            $studentStats = [
                'active' => (clone $active)->count(),
                'with_account' => (clone $active)->whereHas('account')->count(),
                'without_account' => (clone $active)->whereDoesntHave('account')->count(),
            ];
            $query->with('account');
            if ($request->query('account') === 'issued') $query->whereHas('account');
            if ($request->query('account') === 'awaiting') $query->whereDoesntHave('account');
        }
        $term = $request->query('q');
        if (is_string($term) && trim($term) !== '') {
            $term = trim($term);
            $query->where(function ($search) use ($section, $term) {
                $search->where('title', 'like', "%{$term}%")->orWhere('subtitle', 'like', "%{$term}%");
                if ($section === 'students') {
                    $search->orWhere('payload->group', 'like', "%{$term}%")
                        ->orWhereHas('account', fn ($account) => $account->where('username', 'like', "%{$term}%"));
                }
            });
        }
        if ($section === 'registrations' && in_array($request->query('period'), ['current', 'previous'], true)) {
            $month = Carbon::now('Asia/Jayapura')->startOfMonth()->subMonths($request->query('period') === 'previous' ? 1 : 0);
            $query->whereBetween('created_at', [$month->copy()->utc(), $month->copy()->endOfMonth()->utc()]);
        }
        $tabStatuses = match ($section) {
            'registrations' => ['waiting_payment', 'payment_review', 'active', 'expired'],
            'students' => ['active', 'inactive'],
            'certificates' => ['draft', 'published', 'cancelled'],
            'articles' => ['draft', 'published'],
            default => [],
        };
        $statusTabs = [];
        if ($tabStatuses) {
            $counts = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
            $statusTabs[''] = $counts->sum();
            foreach ($tabStatuses as $tabStatus) {
                $statusTabs[$tabStatus] = $counts->get($tabStatus, 0);
            }
        }
        $status = $request->query('status');
        if ($section === 'registrations' && $status === 'attention') {
            $query->whereIn('status', AdminRecord::REGISTRATIONS_AWAITING_ACTION);
        } elseif ($section === 'tournaments' && $status === 'upcoming') {
            $query->upcomingEvents(Carbon::today('Asia/Jayapura')->toDateString());
        } elseif (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }
        $records=$query->latest()->orderByDesc('id')->paginate(12)->withQueryString();
        $finance = [];
        if ($section === 'cashbook') {
            $ledger = AdminRecord::where('section', 'cashbook');
            $now = Carbon::now('Asia/Jayapura');
            $month = $request->query('month');
            $selected = is_string($month) && preg_match('/^[1-9]\d{3}-(0[1-9]|1[0-2])$/D', $month)
                ? Carbon::createFromFormat('!Y-m', $month, 'Asia/Jayapura')
                : $now->copy()->startOfMonth();
            $monthStart = $selected->toDateString();
            $monthEnd = $selected->copy()->endOfMonth()->toDateString();
            $finance = [
                'month' => $selected->format('Y-m'),
                'balance' => (clone $ledger)->where('status', 'income')->sum('amount')
                    - (clone $ledger)->where('status', 'expense')->sum('amount'),
                'income' => (clone $ledger)->where('status', 'income')->whereBetween('date', [$monthStart, $monthEnd])->sum('amount'),
                'unpaid' => AdminRecord::where('section', 'dues')->where('status', 'unpaid')->where('billing_month', $monthStart)->count(),
                'students' => AdminRecord::where('section', 'students')->where('status', 'active')->count(),
            ];
        }
        $allowedStatuses = self::SECTION_STATUSES[$section] ?? [];
        $covers = in_array($section, AdminRecord::COVER_SECTIONS, true);
        $contentFields = AdminContentFields::for($section);
        return view('admin.index',compact('section','meta','records','finance','statusTabs','allowedStatuses','covers','studentStats','contentFields'));
    }
    public function store(Request $request, string $section)
    {
        abort_if($section === 'students', 405, 'Data siswa dibuat setelah pembayaran pendaftaran diverifikasi.');
        abort_if($section === 'team-registrations', 405, 'Tambahkan tim melalui detail turnamen.');
        $this->saveRecord($request, $section);
        return back()->with('success', 'Data berhasil ditambahkan.');
    }

    public function update(Request $request, string $section, int $id)
    {
        $this->saveRecord($request, $section, $id);
        return back()->with('success', 'Data berhasil diperbarui.');
    }

    private function saveRecord(Request $request, string $section, ?int $id = null): void
    {
        $this->section($section);
        abort_if($section === 'dues', 405);
        $supportsCover = in_array($section, AdminRecord::COVER_SECTIONS, true);
        $newCover = null;
        $oldCover = null;
        try {
            DB::transaction(function () use ($request, $section, $id, $supportsCover, &$newCover, &$oldCover): void {
                $record = $id === null ? new AdminRecord(['section' => $section])
                    : AdminRecord::where('section', $section)->lockForUpdate()->findOrFail($id);
                abort_if(($section === 'cashbook' && $record->isLinkedPayment()) || $section === 'team-registrations', 403);
                $statuses = self::SECTION_STATUSES[$section];
                if ($record->exists) $statuses[] = $record->status;
                if ($section === 'registrations' && $record->status === 'active') $statuses = ['active'];
                if ($section === 'students' && $record->status !== 'active' && !$record->account()->exists()) {
                    $statuses = ['inactive'];
                }
                if ($section === 'registrations' && $record->exists && $record->created_at->copy()->addDay()->isPast() && $record->status !== 'active') {
                    $statuses = array_unique([$record->status, 'expired', 'cancelled']);
                }
                $contentRules = [];
                $contentFields = AdminContentFields::for($section);
                foreach ($contentFields as $key => $field) {
                    $type = $field[1];
                    $argument = $field[2] ?? null;
                    if ($type === 'repeat') {
                        $contentRules['content.'.$key] = ['nullable', 'array', 'max:10'];
                        $contentRules['content.'.$key.'.*'] = ['required', 'array:'.implode(',', array_keys($argument))];
                        foreach ($argument as $subKey => [, $subType, $limit, $required]) {
                            $contentRules['content.'.$key.'.*.'.$subKey] = [$required ? 'required' : 'nullable', ...match ($subType) {
                                'select' => [Rule::in(array_keys($limit))],
                                'url' => ['url:http,https', 'max:'.$limit],
                                default => ['string', 'max:'.$limit],
                            }];
                        }
                        continue;
                    }
                    $contentRules['content.'.$key] = ['nullable', ...match ($type) {
                        'select' => [Rule::in(array_keys($argument))],
                        'time' => ['date_format:H:i'],
                        'date' => ['date_format:Y-m-d', 'after_or_equal:1000-01-01'],
                        'url' => ['url:http,https', 'max:'.$argument],
                        'money' => ['numeric', 'decimal:0,2', 'min:1', 'max:999999999999.99'],
                        default => ['string', 'max:'.$argument],
                    }];
                }
                $data = $request->validate([
                    'title' => ['required', 'string', 'max:255'],
                    'subtitle' => ['nullable', 'string', 'max:255'],
                    'status' => ['required', Rule::in($statuses)],
                    'date' => [$section === 'cashbook' ? 'required' : 'nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01',
                        ...($section === 'cashbook' ? ['before_or_equal:'.Carbon::today('Asia/Jayapura')->toDateString()] : [])],
                    'amount' => [$section === 'cashbook' ? 'required' : 'nullable', 'numeric', 'decimal:0,2', $section === 'cashbook' ? 'min:1' : 'min:0', 'max:999999999999.99'],
                    'notes' => ['nullable', 'string', 'max:3000'],
                    'whatsapp' => $section === 'registrations' ? ['nullable', 'regex:/^\+?[0-9][0-9 ()-]{7,20}$/'] : ['prohibited'],
                    'group' => in_array($section, ['registrations', 'students'], true) ? ['nullable', Rule::in(['U-8', 'U-10', 'U-12', 'U-14', 'U-16'])] : ['prohibited'],
                    'cover' => $supportsCover
                        ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096']
                        : ['prohibited'],
                    'remove_cover' => $supportsCover ? ['nullable', 'boolean'] : ['prohibited'],
                    'content' => $contentFields ? ['nullable', 'array:'.implode(',', array_keys($contentFields))] : ['prohibited'],
                    ...$contentRules,
                ]);

                $payload = $record->payload ?? [];
                if (array_key_exists('notes', $data)) $payload['notes'] = $data['notes'] ?? '';
                if (array_key_exists('whatsapp', $data)) $payload['whatsapp'] = $data['whatsapp'];
                if (array_key_exists('group', $data)) $payload['group'] = $data['group'];
                if (isset($data['content'])) {
                    foreach (['career', 'social_links'] as $key) {
                        if (array_key_exists($key, $data['content'])) {
                            $data['content'][$key] = array_values($data['content'][$key] ?? []);
                        }
                    }
                    $payload['content'] = [...($payload['content'] ?? []), ...$data['content']];
                }
                $date = array_key_exists('date', $data) ? $data['date'] : $record->date?->toDateString();
                $dateFields = array_intersect_key($data['content'] ?? [], array_flip(['end_date', 'opens_on', 'closes_on']));
                if (array_key_exists('date', $data) || $dateFields) {
                    $this->validateActivityDates($date, $payload['content'] ?? []);
                }
                if ($section === 'tournaments' && $data['status'] !== 'published') {
                    $payload['content']['registration_open'] = false;
                }
                if ($request->hasFile('cover')) {
                    $newCover = $request->file('cover')->store('record-covers', 'local');
                    if ($newCover === false) {
                        throw ValidationException::withMessages(['cover' => 'Foto gagal disimpan. Silakan unggah ulang.']);
                    }
                    $oldCover = $payload['cover'] ?? null;
                    $payload['cover'] = $newCover;
                } elseif ($request->boolean('remove_cover')) {
                    $oldCover = $payload['cover'] ?? null;
                    unset($payload['cover']);
                }
                if ($section !== 'registrations' || $record->status !== 'active') {
                    $record->amount = $data['amount'] ?? null;
                }
                $record->fill([
                    'title' => $data['title'], 'subtitle' => $data['subtitle'] ?? null,
                    'status' => $data['status'], 'date' => $date, 'payload' => $payload,
                ])->save();
            });
        } catch (\Throwable $error) {
            if ($newCover) Storage::disk('local')->delete($newCover);
            throw $error;
        }
        if ($oldCover) Storage::disk('local')->delete($oldCover);
    }

    private function validateActivityDates(?string $date, array $content): void
    {
        $end = $content['end_date'] ?? null;
        $opens = $content['opens_on'] ?? null;
        $closes = $content['closes_on'] ?? null;

        $errors = [];
        if ($date === null && ($end || $opens || $closes)) {
            $errors['date'] = 'Isi tanggal mulai kegiatan terlebih dahulu.';
        }
        if ($date !== null && $end && $end < $date) {
            $errors['content.end_date'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
        }
        if ($date !== null && $opens && $opens > $date) {
            $errors['content.opens_on'] = 'Pendaftaran tim harus dibuka paling lambat pada tanggal turnamen.';
        }
        if ($opens && $closes && $closes < $opens) {
            $errors['content.closes_on'] = 'Pendaftaran tim harus ditutup setelah atau pada tanggal pembukaan.';
        } elseif ($date !== null && $closes && $closes > $date) {
            $errors['content.closes_on'] = 'Pendaftaran tim harus ditutup paling lambat pada tanggal turnamen.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function cover(string $section, int $id)
    {
        abort_unless(in_array($section, AdminRecord::COVER_SECTIONS, true), 404);
        $path = data_get(AdminRecord::where('section', $section)->findOrFail($id)->payload, 'cover');
        abort_unless(is_string($path) && str_starts_with($path, 'record-covers/') && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->response($path, headers: ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function destroy(string $section, int $id)
    {
        $this->section($section);
        abort_if(in_array($section, ['cashbook', 'registrations', 'dues', 'team-registrations'], true), 403);
        $cover = DB::transaction(function () use ($section, $id) {
            $record = AdminRecord::where('section', $section)->lockForUpdate()->findOrFail($id);
            if ($section === 'tournaments' && $record->teamEntries()->exists()) {
                throw ValidationException::withMessages(['tournament' => 'Turnamen sudah punya pendaftar. Tutup pendaftaran atau arsipkan turnamennya.']);
            }
            if ($section === 'students' && (AdminRecord::where('student_id', $id)->exists() || $record->account()->exists())) {
                throw ValidationException::withMessages(['student' => 'Siswa punya akun atau riwayat iuran. Ubah statusnya menjadi Nonaktif untuk menyimpan riwayat tersebut.']);
            }
            $record->delete();
            return data_get($record->payload, 'cover');
        });
        if ($cover) Storage::disk('local')->delete($cover);

        return back()->with('success', 'Data dihapus.');
    }

    public function registrationReceipt(int $id)
    {
        $registration = AdminRecord::where('section', 'registrations')->findOrFail($id);
        return AdminManualPayment::download($registration, 'REG');
    }

    public function verifyRegistration(Request $request, int $id)
    {
        $receiptPath = null;
        try {
            $access = DB::transaction(function () use ($request, $id, &$receiptPath) {
                $record = AdminRecord::where('section', 'registrations')->lockForUpdate()->findOrFail($id);
                if ($record->status === 'active') return null;
                abort_unless(in_array($record->status, ['waiting_payment', 'payment_review', 'expired'], true), 409);

                $payment = AdminManualPayment::confirm($request, $record, $receiptPath);
                $record->update([
                    'status' => 'active',
                    'amount' => $payment['amount'],
                    'payload' => $payment['payload'],
                ]);
                $student = AdminRecord::where('student_registration_source', (string) $record->id)->first()
                    ?? AdminRecord::create([
                        'section' => 'students', 'subtitle' => 'REG-'.$record->id, 'title' => $record->title, 'status' => 'active',
                        'date' => Carbon::today('Asia/Jayapura')->toDateString(),
                        'payload' => ['registration_id' => $record->id, 'whatsapp' => data_get($record->payload, 'whatsapp'), 'group' => data_get($record->payload, 'group')],
                    ]);
                AdminManualPayment::postIncome($record, $payment);

                return $student->account()->exists() ? null : AdminStudentAccount::issueFor($student);
            });
        } catch (\Throwable $error) {
            if ($receiptPath) Storage::disk('local')->delete($receiptPath);
            throw $error;
        }

        if ($access === null) return back()->with('success', 'Pendaftaran ini sudah terverifikasi.');
        return back()->with('success', 'Pembayaran terverifikasi. Akses siswa siap disalin.')->with('student_access', $access);
    }
}
