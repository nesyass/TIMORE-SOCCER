@php
    $previewId = ($overview ?? false ? 'spotlight-' : 'poster-').$record->id;
    $info = data_get($record->payload, 'content', []);
    $story = data_get($record->payload, 'notes');
@endphp
<dialog class="modal content-preview" id="{{ $previewId }}" aria-labelledby="{{ $previewId }}-heading">
    <div class="modal-content">
        @if($record->section === 'coaches')
            @include('admin.coach-poster')
        @else
        <div class="panel-head"><h2 id="{{ $previewId }}-heading">{{ $record->title }}</h2><x-admin.status-badge :status="$record->status" /></div>
        @if(data_get($record->payload, 'cover'))
            <img class="preview-poster" src="{{ route('admin.cover', [$record->section, $record]) }}" alt="{{ $info['image_alt'] ?? '' }}" loading="lazy">
        @endif
        @if(!empty($info['summary']))<p>{{ $info['summary'] }}</p>@endif
        <dl class="student-details">
            @if($record->subtitle)<div><dt>{{ match ($record->section) { 'coaches' => 'Jabatan', 'achievements' => 'Kompetisi', default => 'Lokasi' } }}</dt><dd>{{ $record->subtitle }}</dd></div>@endif
            @if($record->date)<div><dt>Tanggal</dt><dd>{{ $record->date->locale('id')->translatedFormat('d M Y') }}</dd></div>@endif
            @foreach($contentFields as $key => $field)
                @continue(in_array($key, ['summary', 'image_alt'], true) || empty($info[$key]))
                <div><dt>{{ $field[0] }}</dt><dd class="preview-text">
                    @if($field[1] === 'select') {{ $field[2][$info[$key]] ?? $info[$key] }}
                    @elseif($field[1] === 'date') {{ \Illuminate\Support\Carbon::parse($info[$key])->locale('id')->translatedFormat('d M Y') }}
                    @elseif($field[1] === 'time') {{ str_replace(':', '.', $info[$key]) }} WIT
                    @else {{ $info[$key] }}
                    @endif
                </dd></div>
            @endforeach
        </dl>
        @if($story)<p class="preview-text">{{ $story }}</p>@endif
        @endif
        <div class="form-actions">
            <button type="button" class="btn" data-close-dialog>Tutup</button>
            @if(empty($overview))
                <form method="POST" action="{{ route('admin.destroy', [$record->section, $record]) }}" data-confirm-title="Hapus catatan ini?" data-confirm-text="Data akan dihapus dari daftar." data-confirm-action="Hapus" data-confirm-danger>
                    @csrf @method('DELETE') <button type="submit" class="btn danger">Hapus</button>
                </form>
            @endif
        </div>
    </div>
</dialog>
