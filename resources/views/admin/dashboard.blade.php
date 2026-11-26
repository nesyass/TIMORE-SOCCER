@extends('admin.layouts.admin')
@section('title', 'Dashboard Overview')
@section('breadcrumb', 'Dashboard')
@section('description', 'Selamat datang, Administrator. Berikut ringkasan operasional Timore Soccer School.')
@push('page-scripts')
    @if(is_file(public_path('assets/admin/js/enhancements/registration-chart.js')))
        <script type="module" src="{{ asset('assets/admin/js/enhancements/registration-chart.js') }}"></script>
    @endif
@endpush
@section('header-action')
    <form class="inline-filter" action="{{ route('admin.dashboard') }}">
        <input type="hidden" name="months" value="{{ $months }}">
        <label for="period">Periode</label>
        <select id="period" name="period">
            <option value="current" @selected($period === 'current')>Bulan ini</option>
            <option value="previous" @selected($period === 'previous')>Bulan lalu</option>
        </select>
        <button class="btn" type="submit">Terapkan</button>
    </form>
@endsection
@section('content')
    @php
        $cards = [
            ['users', 'Siswa aktif', $counts['students'], 'Siswa dengan status aktif', 'students', ['status' => 'active']],
            ['file', 'Pendaftaran', $counts['registrations'], 'Pendaftaran menunggu tindak lanjut', 'registrations', ['status' => 'attention', 'period' => $period]],
            ['wallet', 'Pembayaran', $counts['payments'], 'Menunggu verifikasi manual', 'registrations', ['status' => 'payment_review', 'period' => $period]],
            ['trophy', 'Event', $counts['events'], 'Turnamen terbit yang mendatang', 'tournaments', ['status' => 'upcoming']],
        ];
    @endphp
    <section class="stats" aria-label="Ringkasan operasional">
        @foreach ($cards as [$icon, $label, $value, $hint, $section, $filters])
            <a class="stat" href="{{ route('admin.index', ['section' => $section, ...$filters]) }}">
                <span class="stat-label"><span class="stat-icon"><x-admin.icon :name="$icon" /></span>{{ $label }}</span>
                <strong>{{ number_format($value, 0, ',', '.') }}</strong>
                <small>{{ $hint }}</small>
            </a>
        @endforeach
    </section>

    <div class="dashboard-grid">
        <section class="panel" aria-labelledby="actions-title">
            <div class="panel-head">
                <h2 id="actions-title">Perlu Tindakan</h2>
                <a class="text-link" href="{{ route('admin.index', ['section' => 'registrations', 'status' => 'attention']) }}">Lihat semua <x-admin.icon name="chevron" /></a>
            </div>
            @forelse ($pending as $registration)
                <div class="activity">
                    <span class="activity-icon {{ $registration->status === 'payment_review' ? 'tone-info' : 'tone-warning' }}">
                        <x-admin.icon :name="$registration->status === 'payment_review' ? 'alert' : 'clock'" />
                    </span>
                    <div class="activity-copy">
                        <strong>{{ $registration->title }}</strong>
                        <x-admin.status-badge :status="$registration->status" />
                    </div>
                    <a class="btn mini" href="{{ route('admin.index', ['section' => 'registrations', 'q' => $registration->title, 'status' => $registration->status]) }}">Periksa</a>
                </div>
            @empty
                <div class="empty compact"><x-admin.icon name="check" class="empty-icon" /><h3>Tidak ada tindakan mendesak</h3><p>Semua pekerjaan yang terpantau sudah tertangani.</p></div>
            @endforelse
        </section>

        <section class="panel" aria-labelledby="schedule-title">
            <div class="panel-head">
                <h2 id="schedule-title">Latihan Berikutnya</h2>
                <a class="text-link" href="{{ route('admin.index', 'schedules') }}">Lihat jadwal <x-admin.icon name="chevron" /></a>
            </div>
            @if ($nextChange)
                <div class="schedule-feature">
                    <span class="activity-icon tone-info"><x-admin.icon name="calendar" /></span>
                    <div><small>{{ $nextChange->date->locale('id')->translatedFormat('l, d M Y') }} · WIT</small>
                        <h3>{{ $nextChange->title }}</h3><p>{{ $nextChange->subtitle }}</p>
                        <a class="text-link" href="{{ route('admin.index', ['section' => 'schedule-changes', 'q' => $nextChange->title]) }}">Lihat perubahan <x-admin.icon name="chevron" /></a>
                    </div>
                </div>
            @endif
            @if ($routine->isNotEmpty())
                <p class="section-caption schedule-caption">Jadwal mingguan</p>
            @endif
            @forelse ($routine as $slot)
                <div class="activity">
                    <span class="activity-icon"><x-admin.icon name="calendar" /></span>
                    <div class="activity-copy"><strong>{{ $slot->title }}</strong><small>{{ $slot->subtitle }} · WIT</small></div>
                </div>
            @empty
                @unless($nextChange)<div class="empty compact"><h3>Jadwal latihan belum tersedia</h3><p>Tambahkan jadwal rutin agar dapat ditampilkan di website.</p></div>@endunless
            @endforelse
        </section>

        <section class="panel" aria-labelledby="trend-title">
            <div class="panel-head trend-header">
                <h2 id="trend-title">Tren Pendaftaran</h2>
                <form class="inline-filter" action="{{ route('admin.dashboard') }}">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <label for="months" class="sr-only">Rentang tren</label>
                    <select id="months" name="months">
                        @foreach ([3, 6, 12] as $range)
                            <option value="{{ $range }}" @selected($months === $range)>{{ $range }} bulan</option>
                        @endforeach
                    </select>
                    <button class="btn mini" type="submit">Tampilkan</button>
                </form>
            </div>
            <p class="section-caption">Jumlah pendaftaran yang diterima per bulan.</p>
            @php($peak = max(1, $trend->max('total')))
            <div class="trend-visual">
                <div id="registration-chart"></div>
                <ul class="chart" aria-label="Jumlah pendaftaran per bulan">
                    @foreach ($trend as $point)
                        <li data-trend-label="{{ $point['label'] }}" data-trend-total="{{ $point['total'] }}">
                            <span class="chart-total">{{ $point['total'] }}</span>
                            <span class="chart-track" aria-hidden="true"><span style="--bar: {{ $point['total'] / $peak * 100 }}%"></span></span>
                            <span class="chart-label">{{ $point['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="panel" aria-labelledby="activity-title">
            <div class="panel-head"><h2 id="activity-title">Aktivitas Terbaru</h2></div>
            @forelse ($recent as $record)
                <a class="activity activity-link" href="{{ route('admin.index', ['section' => $record->section, 'q' => $record->title]) }}">
                    <span class="activity-icon"><x-admin.icon name="file" /></span>
                    <div class="activity-copy"><strong>{{ $record->title }}</strong>
                        <small>{{ $sectionLabels[$record->section] ?? $record->section }} · {{ $record->updated_at->copy()->timezone('Asia/Jayapura')->locale('id')->translatedFormat('d M, H.i') }} WIT</small></div>
                    <x-admin.icon name="chevron" />
                </a>
            @empty
                <div class="empty compact"><p>Belum ada aktivitas tercatat.</p></div>
            @endforelse
        </section>
    </div>
    @if($spotlights->isNotEmpty())
        <section class="spotlight-section" aria-labelledby="spotlight-heading">
            <div class="panel-head"><h2 id="spotlight-heading">Sorotan Timore</h2></div>
            <div class="content-card-grid">
                @foreach($spotlights as $record)
                    @include('admin.content-card', ['overview' => true])
                    @include('admin.content-preview', ['overview' => true, 'contentFields' => \App\Models\AdminContentFields::for($record->section)])
                @endforeach
            </div>
        </section>
    @endif
@endsection
