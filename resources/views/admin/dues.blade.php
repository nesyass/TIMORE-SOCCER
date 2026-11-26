@extends('admin.layouts.admin')
@section('title', 'Iuran Siswa')
@section('description', 'Buat tagihan bulanan sekaligus dan catat pembayaran dari satu daftar.')
@section('header-action')
    <button type="button" class="btn primary" data-open-dialog="generate-dues" @disabled(!$unbilledCount)>Buat tagihan</button>
@endsection
@section('content')
    @php($rupiah = fn ($amount) => 'Rp '.\Illuminate\Support\Number::format((float)$amount, maxPrecision: 2, locale: 'id'))
    <nav class="tabs" aria-label="Keuangan">
        <a href="{{ route('admin.index', ['section' => 'cashbook', 'month' => $month]) }}">Buku kas</a>
        <a href="{{ route('admin.dues', ['month' => $month]) }}" aria-current="page">Iuran</a>
    </nav>
    <section class="stats dues-stats" aria-label="Ringkasan iuran bulan terpilih">
        @foreach ([
            ['users', 'Siswa aktif', $activeCount, $unbilledCount.' belum dibuatkan tagihan'],
            ['file', 'Tagihan terbit', $stats['issued'], 'Bulan terpilih'],
            ['check', 'Sudah lunas', $stats['paid'], ($stats['issued'] - $stats['paid']).' belum lunas'],
            ['wallet', 'Belum diterima', $rupiah($stats['outstanding']), 'Total tagihan belum lunas'],
        ] as [$icon, $label, $value, $hint])
            <div class="stat"><span class="stat-label"><span class="stat-icon"><x-admin.icon :name="$icon" /></span>{{ $label }}</span><strong>{{ $value }}</strong><small>{{ $hint }}</small></div>
        @endforeach
    </section>

    <section class="panel" aria-labelledby="dues-heading">
        <div class="panel-head"><h2 id="dues-heading">Pembayaran Bulanan</h2><span class="meta-count">{{ $dues->total() }} tagihan</span></div>
        <form method="GET" action="{{ route('admin.dues') }}" class="filters dues-filters" aria-labelledby="dues-heading">
            <div class="field"><label for="dues-q">Cari siswa</label><input id="dues-q" type="search" name="q" value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="Nama siswa"></div>
            <div class="field"><label for="dues-month">Bulan tagihan</label><input id="dues-month" type="month" name="month" value="{{ $month }}" required></div>
            <div class="field"><label for="dues-status">Status pembayaran</label><select id="dues-status" name="status">
                <option value="">Semua status</option><option value="unpaid" @selected(request('status') === 'unpaid')>Belum lunas</option><option value="paid" @selected(request('status') === 'paid')>Lunas</option>
            </select></div>
            <button class="btn" type="submit">Terapkan</button>
            @if(request()->anyFilled(['q', 'status']))<a class="btn ghost" href="{{ route('admin.dues', ['month' => $month]) }}">Reset filter</a>@endif
        </form>

        @if($dues->isEmpty())
            <div class="empty compact"><x-admin.icon name="file" class="empty-icon" />
                <h3>{{ request()->anyFilled(['q', 'status']) ? 'Tidak ada tagihan yang cocok' : 'Belum ada tagihan bulan ini' }}</h3>
            </div>
        @else
            <div class="table-wrap">
                <table class="records-table dues-table" aria-labelledby="dues-heading">
                    <thead><tr><th scope="col">Siswa</th><th scope="col">Status</th><th scope="col" class="money">Tagihan</th><th scope="col">Tanggal bayar</th><th scope="col">Tindakan</th></tr></thead>
                    <tbody>
                        @foreach($dues as $due)
                            <tr>
                                <td class="record-name"><strong>{{ $due->student->title }}</strong><small class="subtitle">{{ $due->student->subtitle }}</small></td>
                                <td data-label="Status"><x-admin.status-badge :status="$due->status" /></td>
                                <td class="money" data-label="Tagihan">{{ $rupiah($due->amount) }}</td>
                                <td data-label="Tanggal bayar">{{ data_get($due->payload, 'paid_on') ? \Illuminate\Support\Carbon::parse($due->payload['paid_on'])->locale('id')->translatedFormat('d M Y') : '—' }}</td>
                                <td class="record-actions">
                                    @if($due->status === 'unpaid')
                                        <button type="button" class="btn mini primary" data-open-dialog="pay-{{ $due->id }}">Catat pembayaran</button>
                                    @else
                                        <span class="meta-count">Pembayaran tercatat</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('admin.pagination', ['items' => $dues])
        @endif
    </section>

    @if($legacy->total())
        <details class="panel legacy-dues">
            <summary>Catatan iuran lama ({{ $legacy->total() }})</summary>
            <p>Catatan lama ini belum terhubung ke siswa atau bulan tagihan.</p>
            @foreach($legacy as $due)
                <div class="activity"><div class="activity-copy"><strong>{{ $due->title }}</strong><small>{{ $due->subtitle }}</small></div>
                    <x-admin.status-badge :status="$due->status" /><span>{{ $rupiah($due->amount) }}</span></div>
            @endforeach
            @include('admin.pagination', ['items' => $legacy])
        </details>
    @endif

    <dialog class="modal" id="generate-dues" aria-labelledby="generate-heading" @if($errors->any() && old('_form') === 'generate-dues') data-restore-form @endif>
        <div class="modal-content">
            <h2 id="generate-heading">Buat tagihan bulanan</h2>
            <p>Pilih siswa aktif yang akan ditagih untuk periode {{ $month }}.</p>
            <form class="data-form" method="POST" action="{{ route('admin.dues.generate') }}" aria-labelledby="generate-heading">
                @csrf
                <input type="hidden" name="_form" value="generate-dues"><input type="hidden" name="month" value="{{ $month }}">
                <div class="field wide">
                    <label for="dues-student">Terapkan untuk</label>
                    <select id="dues-student" name="student_id">
                        <option value="">Semua {{ $unbilledCount }} siswa yang belum ditagih</option>
                        @foreach($unbilled as $student)
                            <option value="{{ $student->id }}" @selected(old('_form') === 'generate-dues' && (string)old('student_id') === (string)$student->id)>{{ $student->title }}{{ $student->subtitle ? ' · '.$student->subtitle : '' }}</option>
                        @endforeach
                    </select>
                    @if(old('_form') === 'generate-dues') @error('student_id')<small class="field-error">{{ $message }}</small>@enderror @endif
                </div>
                <div class="field wide"><label for="dues-amount">Nominal per siswa (Rp)</label>
                    <input id="dues-amount" type="number" name="amount" inputmode="numeric" min="1" max="999999999999.99" step="0.01" value="{{ old('_form') === 'generate-dues' ? old('amount') : '' }}" required>
                    @if(old('_form') === 'generate-dues') @error('amount')<small class="field-error">{{ $message }}</small>@enderror @endif
                </div>
                <div class="wide form-actions"><button type="button" class="btn" data-close-dialog>Batal</button><button type="submit" class="btn primary">Simpan tagihan</button></div>
            </form>
        </div>
    </dialog>

    @foreach($dues->where('status', 'unpaid') as $due)
        <dialog class="modal" id="pay-{{ $due->id }}" aria-labelledby="pay-heading-{{ $due->id }}" @if($errors->any() && old('_form') === 'pay-'.$due->id) data-restore-form @endif>
            <div class="modal-content">
                <h2 id="pay-heading-{{ $due->id }}">Catat pembayaran</h2>
                <p>{{ $due->student->title }} · {{ $rupiah($due->amount) }}. Pastikan dana sudah diterima sebelum menyimpan.</p>
                <form class="data-form" method="POST" action="{{ route('admin.dues.pay', $due) }}" aria-labelledby="pay-heading-{{ $due->id }}">
                    @csrf
                    <input type="hidden" name="_form" value="pay-{{ $due->id }}">
                    @foreach(request()->only(['q', 'status']) as $key => $value)
                        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <div class="field wide"><label for="paid-on-{{ $due->id }}">Tanggal pembayaran</label>
                        <input id="paid-on-{{ $due->id }}" type="date" name="paid_on" max="{{ now('Asia/Jayapura')->toDateString() }}" value="{{ old('_form') === 'pay-'.$due->id ? old('paid_on') : now('Asia/Jayapura')->toDateString() }}" required>
                        @if(old('_form') === 'pay-'.$due->id) @error('paid_on')<small class="field-error">{{ $message }}</small>@enderror @endif
                    </div>
                    <div class="wide form-actions"><button type="button" class="btn" data-close-dialog>Batal</button><button type="submit" class="btn primary">Simpan pembayaran</button></div>
                </form>
            </div>
        </dialog>
    @endforeach
@endsection
