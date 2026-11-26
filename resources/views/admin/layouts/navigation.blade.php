<a href="{{ route('admin.dashboard') }}" class="brand">
    <img class="brand-mark" src="{{ asset('assets/admin/images/timore-crest.png') }}" alt="" width="48" height="48">
    <span><strong>Timore Soccer School</strong><small>Administrator</small></span>
</a>
<nav class="admin-nav" aria-label="Navigasi admin">
    <a class="nav-item {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard*')) aria-current="page" @endif><x-admin.icon name="dashboard" /> Dashboard</a>
    @php
        $groups = [
            ['Pendaftaran', 'file', ['registrations' => 'Pendaftaran siswa']],
            ['Siswa & Akun', 'users', ['students' => 'Data siswa', 'coaches' => 'Pelatih & pengurus']],
            ['Jadwal Latihan', 'calendar', ['schedules' => 'Jadwal rutin', 'schedule-changes' => 'Perubahan jadwal']],
            ['Event & Turnamen', 'trophy', ['events' => 'Event umum', 'tournaments' => 'Turnamen TSS', 'team-registrations' => 'Pendaftaran tim']],
            ['Sertifikat', 'award', ['certificates' => 'Daftar sertifikat']],
            ['Keuangan', 'wallet', ['cashbook' => 'Buku kas', 'dues' => 'Iuran siswa']],
            ['Konten Website', 'file', ['articles' => 'Berita', 'achievements' => 'Prestasi', 'gallery' => 'Galeri', 'site-content' => 'Bagian website']],
        ];
        $current = request()->routeIs('admin.dues') ? 'dues' : request()->route('section');
    @endphp
    @foreach ($groups as [$group, $icon, $links])
        @if (count($links) === 1)
            @php($slug = array_key_first($links))
            <a href="{{ route('admin.index', $slug) }}" class="nav-item {{ $current === $slug ? 'active' : '' }}" @if($current === $slug) aria-current="page" @endif><x-admin.icon :name="$icon" /> {{ $group }}</a>
        @else
            <details @if(isset($links[$current])) open @endif>
                <summary class="nav-item"><x-admin.icon :name="$icon" /> {{ $group }} <x-admin.icon name="chevron" class="nav-caret" /></summary>
                <div class="nav-submenu">
                    @foreach ($links as $slug => $label)
                        <a href="{{ route('admin.index', $slug) }}" class="nav-item {{ $current === $slug ? 'active' : '' }}" @if($current === $slug) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </div>
            </details>
        @endif
        @if ($group === 'Event & Turnamen')
            <a href="{{ route('admin.progress') }}" class="nav-item {{ request()->routeIs('admin.progress') ? 'active' : '' }}" @if(request()->routeIs('admin.progress')) aria-current="page" @endif><x-admin.icon name="chart" /> Penilaian & Progress</a>
        @endif
    @endforeach
</nav>
<div class="sidebar-foot">
    <span class="avatar" aria-hidden="true">{{ strtoupper(substr(auth('web')->user()->name, 0, 1)) }}</span>
    <div><strong>{{ auth('web')->user()->name }}</strong><small>Administrator</small></div>
</div>
