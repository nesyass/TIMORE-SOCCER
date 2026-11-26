<section class="stats students-stats" aria-label="Ringkasan siswa dan akun">
    @foreach ([
        ['users', 'Siswa aktif', 'active', []],
        ['check', 'Akun tersedia', 'with_account', ['account' => 'issued']],
        ['clock', 'Belum punya akun', 'without_account', ['account' => 'awaiting']],
    ] as [$icon, $label, $key, $filters])
        <a class="stat" href="{{ route('admin.index', ['section' => 'students', 'status' => 'active', ...$filters]) }}">
            <span class="stat-label"><span class="stat-icon"><x-admin.icon :name="$icon" /></span>{{ $label }}</span><strong>{{ $studentStats[$key] }}</strong>
        </a>
    @endforeach
</section>