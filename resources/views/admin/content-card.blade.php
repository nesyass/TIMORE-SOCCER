@php
    $info = data_get($record->payload, 'content', []);
    $label = match ($record->section) { 'events' => 'Event umum', 'tournaments' => 'Turnamen TSS', 'coaches' => 'Pelatih', 'achievements' => 'Prestasi', 'articles' => 'Berita', 'gallery' => 'Galeri', 'site-content' => 'Bagian website', default => 'Konten' };
    $icon = match ($record->section) { 'events' => 'calendar', 'coaches' => 'users', 'achievements' => 'award', default => 'trophy' };
    $summary = $info['summary'] ?? null;
    $cover = data_get($record->payload, 'cover');
    $previewId = ($overview ?? false ? 'spotlight-' : 'poster-').$record->id;
@endphp
<article class="content-card content-card-{{ $record->section }}">
    <div class="poster-art {{ $cover ? 'has-cover' : '' }}">
        @if($cover)
            <img src="{{ route('admin.cover', [$record->section, $record]) }}" alt="{{ $info['image_alt'] ?? '' }}" loading="lazy" width="480" height="300">
        @else
            <span class="poster-glyph"><x-admin.icon :name="$icon" /></span>
        @endif
        <span class="poster-tag">{{ $label }}</span>
    </div>
    <header class="poster-heading">
        @if(in_array($record->section, ['events', 'tournaments'], true) && $record->date)
            <small class="poster-date">{{ $record->date->locale('id')->translatedFormat('d M Y') }}{{ isset($info['start_time']) ? ' · '.str_replace(':', '.', $info['start_time']).' WIT' : '' }}</small>
        @elseif($record->section === 'achievements' && !empty($info['ranking']))
            <small class="poster-date">{{ $info['ranking'] }}</small>
        @endif
        <h3>{{ $record->title }}</h3>
        @if($record->subtitle)<p>{{ $record->subtitle }}</p>@endif
    </header>
    @if($summary)<p class="poster-summary">{{ $summary }}</p>@endif
    @if($record->section === 'coaches' && (!empty($info['specialties']) || !empty($info['groups'])))
        <div class="coach-card-tags">
            @if(!empty($info['specialties']))<span>{{ $info['specialties'] }}</span>@endif
            @if(!empty($info['groups']))<span>{{ $info['groups'] }}</span>@endif
        </div>
    @endif
    <footer class="poster-actions">
        @if($record->section === 'tournaments')
            <a class="btn mini primary" href="{{ route('admin.tournaments.show', $record) }}">Kelola tim<span class="sr-only"> {{ $record->title }}</span></a>
        @else
            <button type="button" class="btn mini" data-open-dialog="{{ $previewId }}">Pratinjau<span class="sr-only"> {{ $record->title }}</span></button>
        @endif
        @if(empty($overview))
            <button type="button" class="btn mini" data-open-dialog="edit-{{ $record->id }}">Edit<span class="sr-only"> {{ $record->title }}</span></button>
        @elseif($record->section !== 'tournaments')
            <a class="btn mini ghost" href="{{ route('admin.index', ['section' => $record->section, 'q' => $record->title]) }}">Kelola</a>
        @endif
        <x-admin.status-badge :status="$record->status" />
    </footer>
</article>
