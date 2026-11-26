@php
    $isEditing = isset($record);
    $prefix = $isEditing ? 'edit-'.$record->id : 'create';
    $recover = $errors->any() && old('_form') === $prefix;
    $value = fn ($key, $default = '') => $recover ? old($key, $default) : $default;
    $defaultStatus = $isEditing ? $record->status : $meta[3];
    $options = $section === 'registrations' && $defaultStatus === 'active'
        ? ['active'] : array_unique([$defaultStatus, ...$allowedStatuses]);
    if ($section === 'students' && $defaultStatus === 'inactive' && !$record?->account) $options = ['inactive'];
    $hasAmount = in_array($section, ['registrations', 'cashbook', 'team-registrations'], true) || ($record->amount ?? null) !== null;
    $existingCover = data_get($record ?? null, 'payload.cover');
    $subtitleLabel = match ($section) {
        'tournaments', 'events' => 'Nama lokasi / lapangan', 'achievements' => 'Nama kompetisi',
        'coaches' => 'Jabatan atau peran', 'schedules' => 'Hari, jam WIT, dan lapangan',
        'articles', 'gallery', 'site-content' => 'Subjudul singkat',
        'team-registrations' => 'Turnamen atau kelompok usia', default => 'Keterangan atau referensi',
    };
@endphp
<input type="hidden" name="_form" value="{{ $prefix }}">
<div class="field">
    <label for="{{ $prefix }}-title">{{ $meta[2] }}</label>
    <input id="{{ $prefix }}-title" name="title" value="{{ $value('title', $record->title ?? '') }}" required maxlength="255" @if($recover && $errors->has('title')) aria-invalid="true" aria-describedby="{{ $prefix }}-title-error" @endif>
    @if($recover && $errors->has('title'))<small class="field-error" id="{{ $prefix }}-title-error">{{ $errors->first('title') }}</small>@endif
</div>
<div class="field">
    <label for="{{ $prefix }}-subtitle">{{ $subtitleLabel }}</label>
    <input id="{{ $prefix }}-subtitle" name="subtitle" value="{{ $value('subtitle', $record->subtitle ?? '') }}" maxlength="255" @if($recover && $errors->has('subtitle')) aria-invalid="true" aria-describedby="{{ $prefix }}-subtitle-error" @endif>
    @if($recover && $errors->has('subtitle'))<small class="field-error" id="{{ $prefix }}-subtitle-error">{{ $errors->first('subtitle') }}</small>@endif
</div>
@if(in_array($section, ['registrations', 'students'], true))
    <div class="field">
        <label for="{{ $prefix }}-group">Kelompok usia</label>
        <select id="{{ $prefix }}-group" name="group">
            <option value="">Belum ditentukan</option>
            @foreach(['U-8', 'U-10', 'U-12', 'U-14', 'U-16'] as $group)
                <option value="{{ $group }}" @selected($value('group', data_get($record ?? null, 'payload.group')) === $group)>{{ $group }}</option>
            @endforeach
        </select>
        @if($recover) @error('group')<small class="field-error">{{ $message }}</small>@enderror @endif
    </div>
@endif
@if($section === 'registrations')
    <div class="field">
        <label for="{{ $prefix }}-whatsapp">Nomor WhatsApp siswa atau wali</label>
        <input id="{{ $prefix }}-whatsapp" name="whatsapp" type="tel" maxlength="22" value="{{ $value('whatsapp', data_get($record ?? null, 'payload.whatsapp')) }}">
        @if($recover) @error('whatsapp')<small class="field-error">{{ $message }}</small>@enderror @endif
    </div>
@endif
<div class="field">
    <label for="{{ $prefix }}-status">{{ in_array($section, ['events', 'tournaments', 'achievements'], true) ? 'Status publikasi' : 'Status' }}</label>
    <select id="{{ $prefix }}-status" name="status" required @if($recover && $errors->has('status')) aria-invalid="true" aria-describedby="{{ $prefix }}-status-error" @endif>
        @foreach ($options as $status)
            <option value="{{ $status }}" @selected($value('status', $defaultStatus) === $status)>{{ $statuses[$status] ?? ucfirst($status) }}</option>
        @endforeach
    </select>
    @if($recover && $errors->has('status'))<small class="field-error" id="{{ $prefix }}-status-error">{{ $errors->first('status') }}</small>@endif
</div>
@unless($section === 'coaches')
<div class="field">
    <label for="{{ $prefix }}-date">{{ match ($section) { 'events', 'tournaments' => 'Tanggal mulai', 'achievements' => 'Tanggal pencapaian', 'cashbook' => 'Tanggal transaksi', default => 'Tanggal' } }} @unless($section === 'cashbook')<span>(opsional)</span>@endunless</label>
    <input id="{{ $prefix }}-date" type="date" name="date" value="{{ $value('date', isset($record) ? $record->date?->format('Y-m-d') : ($section === 'cashbook' ? now('Asia/Jayapura')->toDateString() : '')) }}" @required($section === 'cashbook') @if($recover && $errors->has('date')) aria-invalid="true" aria-describedby="{{ $prefix }}-date-error" @endif>
    @if($recover && $errors->has('date'))<small class="field-error" id="{{ $prefix }}-date-error">{{ $errors->first('date') }}</small>@endif
</div>
@endunless
@if($hasAmount)
<div class="field">
    <label for="{{ $prefix }}-amount">Nominal (Rp) @unless($section === 'cashbook')<span>(opsional)</span>@endunless</label>
    <input id="{{ $prefix }}-amount" type="number" min="{{ $section === 'cashbook' ? 1 : 0 }}" max="999999999999.99" step="0.01" name="amount" value="{{ $value('amount', $record->amount ?? '') }}" @required($section === 'cashbook') @readonly($section === 'registrations' && $defaultStatus === 'active') @if($recover && $errors->has('amount')) aria-invalid="true" aria-describedby="{{ $prefix }}-amount-error" @endif>
    @if($recover && $errors->has('amount'))<small class="field-error" id="{{ $prefix }}-amount-error">{{ $errors->first('amount') }}</small>@endif
</div>
@endif
@if($contentFields)
    @include('admin.content-fields')
@endif
@if($covers)
<div class="field wide">
    <label for="{{ $prefix }}-cover">{{ match($section) { 'events', 'tournaments' => 'Poster kegiatan', 'coaches' => 'Foto profil pelatih', default => 'Foto utama' } }} <span>(opsional)</span></label>
    <div class="upload-area" data-upload>
        <img class="upload-preview" alt="Pratinjau foto utama" @if($existingCover) src="{{ route('admin.cover', [$section, $record]) }}" @else hidden @endif>
        <div class="field">
            <input id="{{ $prefix }}-cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" aria-describedby="{{ $prefix }}-cover-hint @if($recover && $errors->has('cover')){{ $prefix }}-cover-error @endif" @if($recover && $errors->has('cover')) aria-invalid="true" @endif>
            <small id="{{ $prefix }}-cover-hint">Pilih atau seret satu foto. JPG, PNG, atau WebP, maksimal 2 MB dan 4096 × 4096 piksel.</small>
            <small class="field-error" data-upload-error role="status"></small>
            <button class="btn mini upload-clear" type="button" data-upload-clear hidden>Batalkan pilihan foto</button>
            @if($recover && $errors->has('cover'))<small class="field-error" id="{{ $prefix }}-cover-error">{{ $errors->first('cover') }}</small>@endif
            @if($existingCover)<label class="check-field"><input type="checkbox" name="remove_cover" value="1"> Hapus foto saat menyimpan</label>@endif
        </div>
    </div>
</div>
@endif
@unless($section === 'coaches')
<div class="field wide">
    <label for="{{ $prefix }}-notes">{{ match ($section) { 'achievements' => 'Cerita pencapaian', 'articles' => 'Isi berita', 'site-content' => 'Isi bagian website', 'gallery' => 'Deskripsi kegiatan', 'events', 'tournaments' => 'Deskripsi', default => 'Catatan' } }}</label>
    <textarea id="{{ $prefix }}-notes" name="notes" rows="3" maxlength="3000" @if($recover && $errors->has('notes')) aria-invalid="true" aria-describedby="{{ $prefix }}-notes-error" @endif>{{ $value('notes', data_get($record ?? null, 'payload.notes')) }}</textarea>
    @if($recover && $errors->has('notes'))<small class="field-error" id="{{ $prefix }}-notes-error">{{ $errors->first('notes') }}</small>@endif
</div>
@endunless
