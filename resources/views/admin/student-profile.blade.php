<dialog class="modal" id="student-{{ $record->id }}" aria-labelledby="student-heading-{{ $record->id }}">
    <div class="modal-content">
        <h2 id="student-heading-{{ $record->id }}">{{ $record->title }}</h2>
        <dl class="student-details">
            <div><dt>Status siswa</dt><dd><x-admin.status-badge :status="$record->status" /></dd></div>
            <div><dt>Username</dt><dd>{{ $record->account?->username ?? 'Belum tersedia' }}</dd></div>
            <div><dt>Kelompok atau referensi</dt><dd>{{ data_get($record->payload, 'group', $record->subtitle) ?: '—' }}</dd></div>
            <div><dt>WhatsApp siswa atau wali</dt><dd>{{ data_get($record->payload, 'whatsapp') ?: '—' }}</dd></div>
            <div><dt>Tanggal bergabung</dt><dd>{{ $record->date?->locale('id')->translatedFormat('d M Y') ?? '—' }}</dd></div>
            <div><dt>Akses</dt><dd>{{ $record->account ? ($record->status === 'active' ? 'Akun tersedia' : 'Siswa nonaktif') : 'Belum punya akun' }}</dd></div>
        </dl>
        <div class="form-actions">
            <button type="button" class="btn" data-close-dialog>Tutup</button>
        </div>
    </div>
</dialog>