<?php

namespace App\Http\Controllers;

use App\Models\AdminRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminDuesController extends \App\Http\Controllers\Controller
{
    public function index(Request $request)
    {
        $default = Carbon::now('Asia/Jayapura')->format('Y-m');
        $month = $request->query('month');
        $month = $this->validMonth($month) ? $month : $default;
        $query = AdminRecord::where('section', 'dues')->where('billing_month', $month.'-01');
        $stats = [
            'issued' => (clone $query)->count(),
            'paid' => (clone $query)->where('status', 'paid')->count(),
            'outstanding' => (clone $query)->where('status', 'unpaid')->sum('amount'),
        ];
        $status = $request->query('status');
        $term = $request->query('q');
        if (in_array($status, ['paid', 'unpaid'], true)) {
            $query->where('status', $status);
        }
        if (is_string($term) && trim($term) !== '') {
            $query->whereHas('student', fn ($students) => $students->where('title', 'like', '%'.trim($term).'%'));
        }
        $dues = $query->with('student')->orderBy('status', 'desc')->orderBy('title')->paginate(20)->withQueryString();
        $legacy = AdminRecord::where('section', 'dues')->whereNull('student_id')->orderByDesc('id')->paginate(12, ['*'], 'legacy_page')->withQueryString();
        $activeCount = AdminRecord::where('section', 'students')->where('status', 'active')->count();
        $unbilled = AdminRecord::where('section', 'students')->where('status', 'active')
            ->whereNotIn('id', AdminRecord::where('section', 'dues')->where('billing_month', $month.'-01')->whereNotNull('student_id')->select('student_id'))
            ->orderBy('title')->get(['id', 'title', 'subtitle']);
        $unbilledCount = $unbilled->count();

        return view('admin.dues', compact('month', 'dues', 'stats', 'legacy', 'activeCount', 'unbilled', 'unbilledCount'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m', 'regex:/^[1-9][0-9]{3}-/'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:999999999999.99'],
            'student_id' => ['nullable', 'integer', Rule::exists('records', 'id')->where('section', 'students')->where('status', 'active')],
        ]);
        $month = $data['month'].'-01';
        $count = 0;
        DB::transaction(function () use ($data, $month, &$count) {
            $students = AdminRecord::where('section', 'students')->where('status', 'active');
            if (isset($data['student_id'])) {
                $students->whereKey($data['student_id']);
            }
            $students->orderBy('id')->lockForUpdate()->chunkById(200, function ($batch) use ($data, $month, &$count) {
                foreach ($batch as $student) {
                    $due = AdminRecord::firstOrCreate(
                        ['section' => 'dues', 'student_id' => $student->id, 'billing_month' => $month],
                        ['title' => $student->title, 'status' => 'unpaid', 'amount' => $data['amount'], 'date' => $month]
                    );
                    $count += (int) $due->wasRecentlyCreated;
                }
            });
        });

        return redirect()->route('admin.dues', ['month' => $data['month']])->with('success', $count
            ? "{$count} tagihan iuran berhasil dibuat."
            : 'Semua siswa yang dipilih sudah punya tagihan untuk bulan ini.');
    }

    public function pay(Request $request, int $id)
    {
        $paidOn = $request->validate(['paid_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:'.Carbon::today('Asia/Jayapura')->toDateString()]])['paid_on'];
        $changed = false;
        $month = DB::transaction(function () use ($id, $paidOn, &$changed) {
            $due = AdminRecord::where('section', 'dues')->whereNotNull('student_id')->whereNotNull('billing_month')->lockForUpdate()->findOrFail($id);
            if ($due->status === 'paid') {
                return $due->billing_month->format('Y-m');
            }
            abort_unless($due->status === 'unpaid' && (float) $due->amount > 0, 409);
            AdminRecord::create([
                'section' => 'cashbook', 'title' => 'Iuran '.$due->title, 'subtitle' => 'IURAN-'.$due->id,
                'status' => 'income', 'amount' => $due->amount, 'date' => $paidOn, 'payload' => ['dues_id' => $due->id],
            ]);
            $due->update(['status' => 'paid', 'payload' => [...($due->payload ?? []), 'paid_on' => $paidOn]]);
            $changed = true;
            return $due->billing_month->format('Y-m');
        });

        $returnFilters = array_filter($request->only(['q', 'status']), 'is_string');
        return redirect()->route('admin.dues', ['month' => $month, ...$returnFilters])
            ->with('success', $changed ? 'Pembayaran iuran tercatat di Buku Kas.' : 'Pembayaran ini sudah tercatat.');
    }

    private function validMonth(mixed $month): bool
    {
        return is_string($month) && preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/D', $month) === 1;
    }
}
