@php
    $formId = 'verify-'.$record->id;
    $retry = $errors->any() && old('_form') === $formId;
    $deadline = $record->created_at->copy()->addDay()->timezone('Asia/Jayapura');
    $isTeam = isset($tournament);
    $verifyUrl = $isTeam
        ? route('admin.tournaments.teams.verify', [$tournament, $record])
        : route('admin.registrations.verify', $record);
@endphp
<dialog class="modal" id="{{ $formId }}" aria-labelledby="{{ $formId }}-heading" @if($retry) data-restore-form @endif>
    <div class="modal-content">
        <h2 id="{{ $formId }}-heading">Verifikasi pembayaran</h2>
        <p><strong>{{ $record->title }}</strong> · Batas pembayaran {{ $deadline->locale('id')->translatedFormat('d M Y, H.i') }} WIT.</p>
        @if($record->status === 'expired')<p>Pendaftaran ini masih bisa diverifikasi jika dana diterima sebelum batas pembayaran.</p>@endif
        <form method="POST" action="{{ $verifyUrl }}" enctype="multipart/form-data" class="data-form" aria-labelledby="{{ $formId }}-heading">
            @csrf
            <input type="hidden" name="_form" value="{{ $formId }}">
            <div class="field">
                <label for="{{ $formId }}-paid">Waktu dana diterima (WIT)</label>
                <input id="{{ $formId }}-paid" type="datetime-local" name="paid_at" required
                    value="{{ $retry ? old('paid_at') : ($record->status === 'expired' ? '' : now('Asia/Jayapura')->format('Y-m-d\TH:i')) }}"
                    @if($retry && $errors->has('paid_at')) aria-invalid="true" aria-describedby="{{ $formId }}-paid-error" @endif>
                @if($retry) @error('paid_at')<small id="{{ $formId }}-paid-error" class="field-error">{{ $message }}</small>@enderror @endif
            </div>
            <div class="field">
                <label for="{{ $formId }}-amount">Nominal diterima (Rp)</label>
                <input id="{{ $formId }}-amount" name="amount" type="number" min="{{ max(1, (float) $record->amount) }}" max="999999999999.99" step="0.01" required
                    value="{{ $retry ? old('amount') : $record->amount }}"
                    @if($retry && $errors->has('amount')) aria-invalid="true" aria-describedby="{{ $formId }}-amount-error" @endif>
                @if($retry) @error('amount')<small id="{{ $formId }}-amount-error" class="field-error">{{ $message }}</small>@enderror @endif
            </div>
            <div class="field wide">
                <label for="{{ $formId }}-receipt">Bukti bayar dari WhatsApp <span>(opsional)</span></label>
                <div class="upload-area" data-upload>
                    <img class="upload-preview" alt="Pratinjau bukti bayar" hidden>
                    <div class="field">
                        <input id="{{ $formId }}-receipt" name="receipt" type="file"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            aria-describedby="{{ $formId }}-receipt-hint @if($retry && $errors->has('receipt')){{ $formId }}-receipt-error @endif"
                            @if($retry && $errors->has('receipt')) aria-invalid="true" @endif>
                        <small id="{{ $formId }}-receipt-hint">Pilih atau seret foto atau PDF. Maksimal 2 MB.</small>
                        <small class="field-error" data-upload-error role="status"></small>
                        <button class="btn mini upload-clear" type="button" data-upload-clear hidden>Batalkan pilihan file</button>
                        @if($retry) @error('receipt')<small id="{{ $formId }}-receipt-error" class="field-error">{{ $message }}</small>@enderror @endif
                    </div>
                </div>
            </div>
            <div class="field wide">
                <label class="check-field"><input type="checkbox" name="confirmed" value="1" required @checked($retry && old('confirmed'))> Saya sudah memastikan dana diterima.</label>
                @if($retry) @error('confirmed')<small class="field-error">{{ $message }}</small>@enderror @endif
            </div>
            <div class="wide form-actions">
                <button type="button" class="btn" data-close-dialog>Batal</button>
                <button type="submit" class="btn primary">{{ $isTeam ? 'Verifikasi pembayaran tim' : 'Verifikasi dan buat akun' }}</button>
            </div>
        </form>
    </div>
</dialog>
