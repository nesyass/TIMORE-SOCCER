@php
    $career = \App\Models\AdminContentFields::coachCareer($info);
    $licenses = preg_split('/\R/u', trim((string) ($info['licenses'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $platforms = \App\Models\AdminContentFields::for('coaches')['social_links'][2]['platform'][2];
    $socials = $info['social_links'] ?? [];
@endphp
<div class="coach-poster">
    <header class="coach-hero">
        <div class="coach-portrait">
            @if(data_get($record->payload, 'cover'))
                <img src="{{ route('admin.cover', [$record->section, $record]) }}" alt="{{ $info['image_alt'] ?? '' }}" width="280" height="340" loading="lazy">
            @else
                <x-admin.icon name="users" />
            @endif
        </div>
        <div class="coach-intro">
            <span class="coach-school">Timore Soccer School</span>
            <h2 id="{{ $previewId }}-heading">{{ $record->title }}</h2>
            @if($record->subtitle)<p class="coach-role">{{ $record->subtitle }}</p>@endif
            @if(!empty($info['summary']))<p class="coach-summary">{{ $info['summary'] }}</p>@endif
            @if(!empty($info['groups']) || !empty($info['specialties']))
                <dl class="coach-facts">
                    @if(!empty($info['specialties']))<div><dt>Spesialisasi</dt><dd>{{ $info['specialties'] }}</dd></div>@endif
                    @if(!empty($info['groups']))<div><dt>Kelompok binaan</dt><dd>{{ $info['groups'] }}</dd></div>@endif
                </dl>
            @endif
        </div>
    </header>

    @if($career)
        <section class="coach-section" aria-labelledby="{{ $previewId }}-career">
            <h3 id="{{ $previewId }}-career">Pengalaman melatih</h3>
            <ol class="coach-timeline">
                @foreach($career as $experience)
                    <li>
                        @if(!empty($experience['period']))<small>{{ $experience['period'] }}</small>@endif
                        <strong>{{ $experience['role'] }}</strong>
                        @if(!empty($experience['organization']))<span>{{ $experience['organization'] }}</span>@endif
                        @if(!empty($experience['detail']))<p>{{ $experience['detail'] }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
    @if($licenses)
        <section class="coach-section" aria-labelledby="{{ $previewId }}-licenses">
            <h3 id="{{ $previewId }}-licenses">Lisensi dan sertifikasi</h3>
            <ul class="coach-licenses">@foreach($licenses as $license)<li><x-admin.icon name="award" /> <span>{{ $license }}</span></li>@endforeach</ul>
        </section>
    @endif
    @if($socials)
        <section class="coach-section" aria-labelledby="{{ $previewId }}-social">
            <h3 id="{{ $previewId }}-social">Media sosial</h3>
            <ul class="coach-socials">
                @foreach($socials as $social)
                    <li><a class="btn mini" href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer">{{ $platforms[$social['platform']] ?? 'Profil' }}@if(!empty($social['handle'])) · {{ $social['handle'] }}@endif <x-admin.icon name="chevron" /><span class="sr-only">, buka di tab baru</span></a></li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
