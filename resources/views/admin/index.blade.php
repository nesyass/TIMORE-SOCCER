@extends('admin.layouts.admin')
@php
    $statuses = [
        'draft' => 'Draf', 'published' => 'Terbit', 'active' => 'Aktif', 'inactive' => 'Nonaktif',
        'pending' => 'Menunggu', 'waiting_payment' => 'Menunggu pembayaran', 'payment_review' => 'Perlu verifikasi',
        'scheduled' => 'Terjadwal', 'postponed' => 'Ditunda', 'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai', 'income' => 'Pemasukan', 'expense' => 'Pengeluaran',
        'paid' => 'Lunas', 'unpaid' => 'Belum lunas',
        'expired' => 'Kedaluwarsa', 'posted' => 'Tercatat', 'archived' => 'Arsip',
    ];
    $filterStatuses = array_intersect_key($statuses, array_flip($allowedStatuses));
    $filterStatuses += array_intersect_key($statuses, array_flip(array_keys($statusTabs)));
    if ($section === 'cashbook') $filterStatuses['posted'] = $statuses['posted'];
    if ($section === 'registrations') {
        $filterStatuses['attention'] = 'Semua yang menunggu';
        $filterStatuses['expired'] = 'Kedaluwarsa';
    }
    if ($section === 'tournaments') $filterStatuses['upcoming'] = 'Akan datang';
    $addLabel = match ($section) {
        'registrations' => 'Tambah pendaftaran', 'students' => 'Tambah siswa', 'coaches' => 'Tambah pelatih',
        'schedules' => 'Tambah jadwal', 'schedule-changes' => 'Buat perubahan', 'tournaments' => 'Buat turnamen',
        'team-registrations' => 'Tambah tim', 'cashbook' => 'Tambah transaksi', 'dues' => 'Tambah iuran',
        'events' => 'Tambah event',
        'certificates' => 'Tambah sertifikat', 'articles' => 'Tambah berita', 'achievements' => 'Tambah prestasi',
        'gallery' => 'Tambah album', default => 'Tambah bagian',
    };
    [$emptyTitle, $emptyDescription] = match ($section) {
        'registrations' => ['Belum ada pendaftaran', 'Pendaftaran yang masuk melalui website akan tampil di sini.'],
        'students' => ['Belum ada siswa', 'Siswa yang diverifikasi akan muncul secara otomatis.'],
        'schedules' => ['Jadwal latihan belum tersedia', 'Tambahkan jadwal rutin agar dapat ditampilkan di website.'],
        'schedule-changes' => ['Tidak ada perubahan jadwal', 'Seluruh latihan mengikuti jadwal rutin.'],
        'tournaments' => ['Belum ada event', 'Buat turnamen atau kegiatan untuk ditampilkan di website.'],
        'team-registrations' => ['Belum ada tim mendaftar', 'Tim dari SSB lain akan muncul setelah mengirim formulir.'],
        'certificates' => ['Belum ada sertifikat diterbitkan', 'Sertifikat terbit akan tersedia bagi siswa/wali terkait.'],
        'cashbook' => ['Belum ada transaksi', 'Catat penerimaan atau pengeluaran yang sudah terjadi.'],
        'articles' => ['Belum ada berita', 'Publikasikan kegiatan Timore Soccer School.'],
        default => ['Belum ada data', 'Tambahkan data untuk mengisi daftar ini.'],
    };
    $filtering = request()->anyFilled(['q', 'status', 'period', 'account']);
    $reset = route('admin.index', ['section' => $section, ...($finance ? ['month' => $finance['month']] : [])]);
@endphp
@section('title', match ($section) { 'cashbook' => 'Keuangan Sekolah', 'students' => 'Siswa & Akun', default => $meta[0] })
@section('description', match ($section) {
    'registrations' => 'Tinjau pembayaran dalam 24 jam dan terbitkan akses siswa setelah verifikasi.',
    'students' => 'Cari profil siswa, periksa username, dan kelola akses siswa.',
    'cashbook' => 'Pantau saldo dan catat penerimaan serta pengeluaran sekolah.',
    'schedules' => 'Kelola jadwal mingguan tetap untuk setiap kelompok latihan.',
    'schedule-changes' => 'Kelola penyesuaian sesi tanpa mengubah jadwal mingguan.',
    'team-registrations' => 'Tinjau pendaftaran tim dari luar Timore Soccer School.',
    'articles' => 'Siapkan judul, ringkasan, artikel lengkap, dan foto sampul.',
    'gallery' => 'Siapkan kartu album dengan foto sampul dan keterangan kegiatan.',
    'site-content' => 'Simpan naskah, gambar, dan tujuan tombol untuk bagian website yang disiapkan.',
    default => 'Cari, tinjau, dan perbarui catatan pada modul ini.',
})
@section('header-action')
    @if($section === 'students')
        <a class="btn" href="{{ route('admin.index', 'registrations') }}">Lihat pendaftaran</a>
    @elseif($section === 'team-registrations')
        <a class="btn primary" href="{{ route('admin.index', 'tournaments') }}">Pilih turnamen</a>
    @else
        <button class="btn primary" type="button" data-open-dialog="create-record"><span aria-hidden="true">+</span> {{ $addLabel }}</button>
    @endif
@endsection
@section('content')
    @if ($finance) @include('admin.finance-summary') @endif
    @if ($studentStats) @include('admin.student-summary') @endif
    @php
        $related = match ($section) {
            'schedules', 'schedule-changes' => ['schedules' => 'Jadwal Rutin', 'schedule-changes' => 'Perubahan Jadwal'],
            'events', 'tournaments', 'team-registrations' => ['events' => 'Event Umum', 'tournaments' => 'Turnamen TSS', 'team-registrations' => 'Pendaftaran Tim'],
            default => [],
        };
    @endphp
    @if ($related)
        <nav class="tabs" aria-label="Halaman terkait">
            @foreach ($related as $slug => $label)
                <a href="{{ route('admin.index', $slug) }}" @if($slug === $section) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    @endif

    <section class="panel" aria-labelledby="list-heading">
        <div class="panel-head">
            <h2 id="list-heading">{{ $section === 'cashbook' ? 'Daftar Transaksi' : 'Daftar '.$meta[0] }}</h2>
            <span class="meta-count">{{ $records->total() }} data</span>
        </div>
        @if($contentFields)
            <nav class="tabs" aria-label="Tampilan daftar">
                <a href="{{ route('admin.index', ['section' => $section, ...request()->only(['q', 'status'])]) }}" @if(request('view') !== 'table') aria-current="page" @endif>Kartu</a>
                <a href="{{ route('admin.index', ['section' => $section, ...request()->only(['q', 'status']), 'view' => 'table']) }}" @if(request('view') === 'table') aria-current="page" @endif>Tabel</a>
            </nav>
        @endif

        @if ($statusTabs)
            <nav class="tabs status-tabs" aria-label="Filter status">
                @foreach ($statusTabs as $state => $count)
                    <a href="{{ route('admin.index', ['section' => $section, ...request()->only(['q', 'period', 'account']), ...($state === '' ? [] : ['status' => $state])]) }}" @if(request('status', '') === $state) aria-current="page" @endif>{{ $state === '' ? 'Semua' : $filterStatuses[$state] }} <span>{{ $count }}</span></a>
                @endforeach
            </nav>
        @endif

        <form method="GET" action="{{ route('admin.index', $section) }}" class="filters">
            @if (in_array(request('period'), ['current', 'previous'], true))<input type="hidden" name="period" value="{{ request('period') }}">@endif
            @if ($finance)<input type="hidden" name="month" value="{{ $finance['month'] }}">@endif
            @if ($contentFields && request('view') === 'table')<input type="hidden" name="view" value="table">@endif
            <div class="field"><label for="record-query">{{ $section === 'students' ? 'Cari nama, kelompok, atau username' : 'Cari nama atau keterangan' }}</label><input id="record-query" type="search" name="q" value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="{{ $section === 'students' ? 'Nama siswa atau username' : 'Contoh: nama atau referensi' }}"></div>
            <div class="field"><label for="record-status">Status</label><select id="record-status" name="status">
                <option value="">Semua status</option>
                @foreach ($filterStatuses as $state => $name)
                    <option value="{{ $state }}" @selected(request('status') === $state)>{{ $name }}</option>
                @endforeach
            </select></div>
            @if($section === 'students')
                <div class="field"><label for="record-account">Akses siswa</label><select id="record-account" name="account">
                    <option value="">Semua akun</option>
                    <option value="issued" @selected(request('account') === 'issued')>Akun tersedia</option>
                    <option value="awaiting" @selected(request('account') === 'awaiting')>Belum punya akun</option>
                </select></div>
            @endif
            <button class="btn" type="submit">Cari</button>
            @if ($filtering)<a class="btn ghost" href="{{ $reset }}">Reset filter</a>@endif
        </form>

        @if ($records->isEmpty())
            <div class="empty">
                <x-admin.icon name="file" class="empty-icon" />
                <h3>{{ $filtering ? 'Tidak ada hasil' : $emptyTitle }}</h3>
                <p>{{ $filtering ? 'Coba kata kunci atau status lain.' : $emptyDescription }}</p>
                @if (!$filtering && $section === 'students')
                    <a class="btn" href="{{ route('admin.index', 'registrations') }}">Lihat Pendaftaran</a>
                @endif
            </div>
        @elseif($contentFields && request('view') !== 'table')
            <div class="content-card-grid">
                @foreach($records as $record) @include('admin.content-card') @endforeach
            </div>
            @include('admin.pagination', ['items' => $records])
        @else
            <div class="table-wrap">
                <table class="records-table" aria-labelledby="list-heading">
                    <thead><tr><th scope="col">ID</th><th scope="col">{{ $meta[2] }}</th><th scope="col">Status</th>
                        <th scope="col">{{ match ($section) { 'registrations' => 'Batas bayar (WIT)', 'students' => 'Tanggal bergabung', default => 'Tanggal' } }}</th>
                        @if($section === 'students')<th scope="col">Username</th>@else<th scope="col" class="money">Nominal</th>@endif
                        <th scope="col">Tindakan</th></tr></thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr>
                                <td class="record-id" data-label="ID">#{{ $record->id }}</td>
                                <td class="record-name"><div class="record-identity">
                                    @if($covers && data_get($record->payload, 'cover'))<img class="record-thumbnail" src="{{ route('admin.cover', [$section, $record]) }}" alt="" width="48" height="40" loading="lazy">@endif
                                    <div><strong>{{ $record->title }}</strong>@if($groupLabel = data_get($record->payload, 'group') ?: $record->subtitle)<small class="subtitle">{{ $groupLabel }}</small>@endif</div>
                                </div></td>
                                <td data-label="Status"><x-admin.status-badge :status="$record->status" /></td>
                                @if($section === 'registrations')
                                    <td data-label="Batas bayar">{{ $record->created_at->copy()->addDay()->timezone('Asia/Jayapura')->locale('id')->translatedFormat('d M Y, H.i') }}</td>
                                @else
                                    <td data-label="Tanggal">{{ $record->date?->locale('id')->translatedFormat('d M Y') ?? '—' }}</td>
                                @endif
                                @if($section === 'students')
                                    <td data-label="Username">{{ $record->account?->username ?? 'Belum punya akun' }}</td>
                                @else
                                    <td class="money" data-label="Nominal">{{ $record->amount !== null ? 'Rp '.\Illuminate\Support\Number::format((float)$record->amount, maxPrecision: 2, locale: 'id') : '—' }}</td>
                                @endif
                                <td class="record-actions">
                                    <div class="actions">
                                        @if ($section === 'registrations' && in_array($record->status, ['waiting_payment', 'payment_review', 'expired'], true))
                                            <button class="btn mini {{ $record->status === 'expired' ? '' : 'primary' }}" type="button" data-open-dialog="verify-{{ $record->id }}">{{ $record->status === 'expired' ? 'Cek pembayaran' : 'Verifikasi' }}</button>
                                        @endif
                                        @if($section === 'registrations' && data_get($record->payload, 'payment_receipt'))
                                            <a class="btn mini" href="{{ route('admin.registrations.receipt', $record) }}">Unduh bukti bayar</a>
                                        @endif
                                        @if($section === 'students')
                                            <button class="btn mini" type="button" data-open-dialog="student-{{ $record->id }}">Profil</button>
                                            @if(($record->account || data_get($record->payload, 'registration_id')) && $record->status === 'active')
                                                <form method="POST" action="{{ route('admin.students.account.reset', $record) }}"
                                                    data-confirm-title="{{ $record->account ? 'Buat ulang kata sandi?' : 'Terbitkan akses siswa?' }}"
                                                    data-confirm-text="{{ $record->account ? 'Kata sandi lama akan langsung berhenti berlaku.' : 'Akun dibuat untuk siswa yang pendaftarannya sudah terverifikasi.' }}"
                                                    data-confirm-action="{{ $record->account ? 'Buat ulang' : 'Terbitkan' }}">
                                                    @csrf<button class="btn mini" type="submit">{{ $record->account ? 'Reset sandi' : 'Terbitkan akses' }}</button>
                                                </form>
                                            @endif
                                        @endif
                                        @if ($section === 'certificates')
                                            <button class="btn mini" type="button" data-open-dialog="certificate-{{ $record->id }}">Pratinjau</button>
                                        @endif
                                        @if($contentFields)
                                            <button type="button" class="btn mini" data-open-dialog="poster-{{ $record->id }}">Pratinjau</button>
                                        @endif
                                        @if($section === 'tournaments')
                                            <a class="btn mini primary" href="{{ route('admin.tournaments.show', $record) }}">Kelola tim</a>
                                        @endif
                                        @if($section === 'team-registrations')
                                            @if($record->tournament)
                                                <a class="btn mini" href="{{ route('admin.tournaments.show', $record->tournament) }}">Buka turnamen</a>
                                            @else
                                                <span class="meta-count">Catatan lama tanpa turnamen</span>
                                            @endif
                                        @elseif($section === 'cashbook' && $record->isLinkedPayment())
                                            <span class="meta-count">Pencatatan otomatis</span>
                                        @else
                                            <button class="btn mini" type="button" data-open-dialog="edit-{{ $record->id }}">Edit</button>
                                        @endif
                                        @unless (in_array($section, ['registrations', 'cashbook', 'team-registrations'], true) || ($section === 'students' && $record->account))
                                            <form method="POST" action="{{ route('admin.destroy', [$section, $record->id]) }}" data-confirm-title="Hapus catatan ini?" data-confirm-text="Catatan akan dihapus dari daftar." data-confirm-action="Hapus" data-confirm-danger>
                                                @csrf @method('DELETE')<button class="btn mini danger" type="submit">Hapus</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($records->hasPages())
                <nav class="pagination" aria-label="Halaman daftar">
                    @if ($records->previousPageUrl())<a class="btn" href="{{ $records->previousPageUrl() }}">Sebelumnya</a>@endif
                    <span>Halaman {{ $records->currentPage() }} dari {{ $records->lastPage() }}</span>
                    @if ($records->nextPageUrl())<a class="btn" href="{{ $records->nextPageUrl() }}">Berikutnya</a>@endif
                </nav>
            @endif
        @endif
    </section>

    @unless(in_array($section, ['students', 'team-registrations'], true))
    <dialog class="modal" id="create-record" aria-labelledby="create-heading" @if($errors->any() && old('_form') === 'create') data-restore-form @endif>
        <div class="modal-content">
            <h2 id="create-heading">{{ $addLabel }}</h2>
            <form method="POST" action="{{ route('admin.store', $section) }}" class="data-form" aria-labelledby="create-heading" @if($covers) enctype="multipart/form-data" @endif>
                @csrf @include('admin.record-fields', ['record' => null])
                <div class="wide form-actions">
                    <button type="button" class="btn" data-close-dialog>Batal</button>
                    <button class="btn primary" type="submit">Simpan data</button>
                </div>
            </form>
        </div>
    </dialog>
    @endunless

    @foreach ($records as $record)
        @continue($section === 'team-registrations')
        @if($contentFields) @include('admin.content-preview') @endif
        @if($section === 'registrations' && in_array($record->status, ['waiting_payment', 'payment_review', 'expired'], true))
            @include('admin.verify-registration')
        @endif
        @if($section === 'students') @include('admin.student-profile') @endif
        @continue($section === 'cashbook' && $record->isLinkedPayment())
        @if ($section === 'certificates')
            <dialog class="modal certificate-modal" id="certificate-{{ $record->id }}" aria-labelledby="certificate-heading-{{ $record->id }}">
                <div class="modal-content">
                    <h2 id="certificate-heading-{{ $record->id }}">Pratinjau Sertifikat</h2>
                    <div class="certificate-paper">
                        <x-admin.icon name="award" />
                        <small>Timore Soccer School</small>
                        <h3>Certificate of Completion</h3>
                        <strong>{{ $record->title }}</strong>
                        @if ($record->subtitle)<span>{{ $record->subtitle }}</span>@endif
                        <footer><span>{{ $record->date?->locale('id')->translatedFormat('d M Y') ?? 'Tanggal belum ditetapkan' }}</span><span>Contoh tata letak</span></footer>
                    </div>
                    <p class="certificate-hint">Pratinjau ini hanya untuk melihat tata letak sertifikat.</p>
                    <div class="form-actions"><button type="button" class="btn" data-close-dialog>Tutup</button></div>
                </div>
            </dialog>
        @endif
        <dialog class="modal" id="edit-{{ $record->id }}" aria-labelledby="edit-heading-{{ $record->id }}" @if($errors->any() && old('_form') === 'edit-'.$record->id) data-restore-form @endif>
            <div class="modal-content">
                <h2 id="edit-heading-{{ $record->id }}">Edit {{ $record->title }}</h2>
                <form method="POST" action="{{ route('admin.update', [$section, $record->id]) }}" class="data-form" aria-labelledby="edit-heading-{{ $record->id }}" @if($covers) enctype="multipart/form-data" @endif>
                    @csrf @method('PUT') @include('admin.record-fields')
                    <div class="wide form-actions">
                        <button type="button" class="btn" data-close-dialog>Batal</button>
                        <button class="btn primary" type="submit">Simpan perubahan</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endforeach

    @include('admin.confirm-dialog')
@endsection
