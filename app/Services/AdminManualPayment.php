<?php

namespace App\Services;

use App\Models\AdminRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminManualPayment
{
    public static function confirm(Request $request, AdminRecord $record, ?string &$receiptPath): array
    {
        $data = $request->validate([
            'paid_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:999999999999.99'],
            'confirmed' => ['accepted'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ], [
            'receipt.mimes' => 'Unggah bukti bayar dalam format JPG, PNG, WebP, atau PDF.',
            'receipt.max' => 'Ukuran bukti bayar maksimal 2 MB.',
        ]);
        if ($record->amount !== null && (float) $data['amount'] < (float) $record->amount) {
            throw ValidationException::withMessages(['amount' => 'Nominal diterima masih kurang dari biaya yang tercatat. Verifikasi dilakukan setelah lunas.']);
        }
        $paidAt = Carbon::createFromFormat('Y-m-d\TH:i', $data['paid_at'], 'Asia/Jayapura')->utc();
        if ($paidAt->lt($record->created_at->copy()->startOfMinute()) || $paidAt->gt(now())) {
            throw ValidationException::withMessages(['paid_at' => 'Waktu pembayaran harus berada di antara pendaftaran masuk dan waktu sekarang.']);
        }
        if ($paidAt->gt($record->created_at->copy()->addDay())) {
            throw ValidationException::withMessages(['paid_at' => 'Pembayaran ini melewati batas 24 jam untuk pendaftaran tersebut.']);
        }
        $payload = [...($record->payload ?? []), 'paid_at' => $paidAt->toIso8601String(),
            'verified_at' => now()->toIso8601String(), 'verified_by' => $request->user('web')->id];
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('payment-receipts', 'local') ?: null;
            if (!$receiptPath) {
                throw ValidationException::withMessages(['receipt' => 'Bukti bayar gagal disimpan. Pilih ulang file dan coba lagi.']);
            }
            $payload['payment_receipt'] = $receiptPath;
        }

        return ['payload' => $payload, 'amount' => $data['amount'], 'date' => $paidAt->timezone('Asia/Jayapura')->toDateString()];
    }

    public static function download(AdminRecord $record, string $prefix)
    {
        $path = data_get($record->payload, 'payment_receipt');
        $disk = Storage::disk('local');
        abort_unless(is_string($path) && str_starts_with($path, 'payment-receipts/') && $disk->exists($path), 404);

        return $disk->download($path, 'bukti-bayar-'.$prefix.'-'.$record->id.'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // The caller holds the source row lock throughout verification and posting.
    public static function postIncome(AdminRecord $source, array $payment): void
    {
        $team = $source->section === 'team-registrations';
        $key = $team ? 'team_registration_id' : 'registration_id';
        $reference = ($team ? 'TEAM-' : 'REG-').$source->id;
        if (AdminRecord::where('cash_payment_source', $reference)->exists()) return;

        AdminRecord::create([
            'section' => 'cashbook',
            'subtitle' => $reference,
            'title' => ($team ? 'Pendaftaran tim: ' : 'Pembayaran pendaftaran: ').$source->title,
            'status' => 'income',
            'amount' => $payment['amount'],
            'date' => $payment['date'],
            'payload' => $team ? [$key => $source->id, 'tournament_id' => $source->tournament_id] : [$key => $source->id],
        ]);
    }
}
