@extends('admin.layouts.admin')

@php
    $state = $tournament->teamRegistrationState();
    $configuredOpen = (bool) data_get($tournament->payload, 'content.registration_open');
    $info = data_get($tournament->payload, 'content', []);
    $teamStatuses = ['waiting_payment' => 'Menunggu pembayaran', 'payment_review' => 'Perlu verifikasi', 'active' => 'Terkonfirmasi', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan'];
    $rupiah = fn ($amount) => 'Rp '.\Illuminate\Support\Number::format((float) $amount, maxPrecision: 2, locale: 'id');
    $date = fn ($day) => $day ? \Illuminate\Support\Carbon::parse($day)->locale('id')->translatedFormat('d M Y') : '—';
@endphp
@section('title', $tournament->title)
@section('breadcrumb', 'Turnamen TSS')
@section('description', 'Atur publikasi, periode pendaftaran, dan pembayaran tim dari satu halaman.')
@section('header-action')
    <a class="btn" href="{{ route('admin.index', 'tournaments') }}">Semua turnamen</a>
@endsection
@section('content')
    <div class="dashboard-grid tournament-overview">
        <section class="panel">
            <div class="panel-head"><h2>Pengaturan turnamen</h2><x-admin.status-badge :status="$tournament->status" /></div>
            <dl class="student-details">
                <div><dt>Lokasi</dt><dd>{{ $tournament->subtitle ?: '—' }}</dd></div>
                <div><dt>Tanggal turnamen</dt><dd>{{ $date($tournament->date) }}</dd></div>
                <div><dt>Pendaftaran WIT</dt><dd>{{ $date($info['opens_on'] ?? null) }} – {{ $date($info['closes_on'] ?? null) }}</dd></div>
                <div><dt>Biaya per tim</dt><dd>{{ isset($info['entry_fee']) ? $rupiah($info['entry_fee']) : 'Belum diatur' }}</dd></div>
                <div><dt>Kategori</dt><dd>{{ implode(', ', $tournament->tournamentCategories()) ?: 'Belum diatur' }}</dd></div>
                <div><dt>Status pendaftaran</dt><dd><span class="pill {{ $state === 'open' ? 'pill-success' : ($state === 'scheduled' ? 'pill-info' : '') }}">{{ ['open' => 'Terbuka', 'scheduled' => 'Terjadwal', 'closed' => 'Tertutup'][$state] }}</span></dd></div>
            </dl>
            <div class="actions tournament-actions">
                <button class="btn" type="button" data-open-dialog="edit-tournament">Edit detail</button>
                <button class="btn" type="button" data-open-dialog="spotlight-{{ $tournament->id }}">Pratinjau poster</button>
                @foreach([
                    $tournament->status === 'published'
                        ? ['draft', 'Jadikan draf', 'Publikasi dan pendaftaran tim baru akan ditutup. Data tim tetap tersimpan.']
                        : ['publish', 'Terbitkan', null],
                    $configuredOpen
                        ? ['close', 'Tutup pendaftaran', 'Tim baru tidak bisa mendaftar. Pembayaran tim yang sudah masuk masih bisa diperiksa.']
                        : ['open', 'Buka pendaftaran', null],
                ] as [$action, $label, $confirm])
                    <form method="POST" class="data-form" action="{{ route('admin.tournaments.state', $tournament) }}"
                        @if($confirm) data-confirm-title="{{ $label }}?" data-confirm-text="{{ $confirm }}" data-confirm-action="{{ $label }}" @endif>
                        @csrf <input type="hidden" name="action" value="{{ $action }}">
                        <button class="btn {{ $action === 'open' ? 'primary' : '' }}" type="submit" @disabled($action === 'open' && !$tournament->canOpenTeamRegistration())>{{ $label }}</button>
                    </form>
                @endforeach
            </div>
            @if(!$configuredOpen && !$tournament->canOpenTeamRegistration())<p class="section-caption">Untuk membuka pendaftaran, terbitkan turnamen dan isi tanggal, periode pendaftaran, kategori, serta biaya per tim.</p>@endif
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Ringkasan tim</h2></div>
            <dl class="student-details">
                <div><dt>Total pendaftar</dt><dd>{{ $counts->sum() }}</dd></div>
                <div><dt>Menunggu pembayaran</dt><dd>{{ $counts->get('waiting_payment', 0) }}</dd></div>
                <div><dt>Perlu verifikasi</dt><dd>{{ $counts->get('payment_review', 0) }}</dd></div>
                <div><dt>Tim terkonfirmasi</dt><dd>{{ $counts->get('active', 0) }}</dd></div>
            </dl>
        </section>
    </div>

    <section class="panel tournament-teams" aria-labelledby="team-list-heading">
        <div class="panel-head">
            <div><h2 id="team-list-heading">Tim pendaftar</h2><p class="section-caption">Daftar tim dari SSB atau klub eksternal.</p></div>
            @if($state === 'open')<button type="button" class="btn primary" data-open-dialog="add-team">Catat tim</button>@endif
        </div>
        <form class="filters" method="GET" action="{{ route('admin.tournaments.show', $tournament) }}" aria-label="Filter tim pendaftar">
            <div class="field"><label for="team-query">Cari tim atau SSB</label><input id="team-query" type="search" name="q" value="{{ $filters['q'] ?? '' }}"></div>
            <div class="field"><label for="team-status">Status pembayaran</label><select id="team-status" name="status">
                <option value="">Semua status</option>
                @foreach($teamStatuses as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach
            </select></div>
            <button type="submit" class="btn">Cari</button>
            @if(array_filter($filters))<a class="btn ghost" href="{{ route('admin.tournaments.show', $tournament) }}">Reset</a>@endif
        </form>
        @if($teams->isEmpty())
            <div class="empty compact"><p>{{ array_filter($filters) ? 'Tidak ada tim yang cocok dengan filter.' : 'Belum ada tim yang tercatat di turnamen ini.' }}</p></div>
        @else
            <div class="table-wrap">
                <table class="records-table" aria-labelledby="team-list-heading">
                    <thead><tr><th scope="col">Tim dan SSB</th><th scope="col">Kategori</th><th scope="col">Kontak PIC</th><th scope="col">Status</th><th scope="col">Batas bayar (WIT)</th><th scope="col" class="money">Biaya</th><th scope="col">Tindakan</th></tr></thead>
                    <tbody>
                        @foreach($teams as $entry)
                            <tr>
                                <td class="record-name"><strong>{{ $entry->title }}</strong><small class="subtitle">{{ $entry->subtitle }}</small></td>
                                <td data-label="Kategori">{{ data_get($entry->payload, 'category') ?: '—' }}</td>
                                <td data-label="Kontak PIC"><span>{{ data_get($entry->payload, 'contact_name') ?: '—' }}<small class="subtitle">{{ data_get($entry->payload, 'whatsapp') }}</small></span></td>
                                <td data-label="Status">@if($entry->status === 'active')<span class="pill pill-success">Terkonfirmasi</span>@else<x-admin.status-badge :status="$entry->status" />@endif</td>
                                <td data-label="Batas bayar">{{ $entry->created_at->copy()->addDay()->timezone('Asia/Jayapura')->locale('id')->translatedFormat('d M Y, H.i') }}</td>
                                <td data-label="Biaya" class="money">{{ $entry->amount !== null ? $rupiah($entry->amount) : '—' }}</td>
                                <td class="record-actions"><div class="actions">
                                    @if($entry->status === 'waiting_payment')
                                        <form class="data-form" method="POST" action="{{ route('admin.tournaments.teams.review', [$tournament, $entry]) }}">
                                            @csrf <button class="btn mini" type="submit">Tandai perlu cek</button>
                                        </form>
                                    @endif
                                    @if(in_array($entry->status, ['waiting_payment', 'payment_review', 'expired'], true))
                                        <button class="btn mini primary" type="button" data-open-dialog="verify-{{ $entry->id }}">{{ $entry->status === 'expired' ? 'Cek pembayaran' : 'Verifikasi' }}</button>
                                    @endif
                                    @if(data_get($entry->payload, 'payment_receipt'))
                                        <a class="btn mini" href="{{ route('admin.tournaments.teams.receipt', [$tournament, $entry]) }}">Unduh bukti</a>
                                    @endif
                                </div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('admin.pagination', ['items' => $teams])
        @endif
    </section>

    @foreach($teams as $entry)
        @if(in_array($entry->status, ['waiting_payment', 'payment_review', 'expired'], true))
            @include('admin.verify-registration', ['record' => $entry])
        @endif
    @endforeach
    @include('admin.content-preview', ['record' => $tournament, 'overview' => true])

    @if($state === 'open')
    <dialog class="modal" id="add-team" aria-labelledby="add-team-heading" @if($errors->any() && old('_form') === 'add-team') data-restore-form @endif>
        <div class="modal-content">
            <h2 id="add-team-heading">Catat pendaftaran tim</h2>
            <p>Jumlah yang ditagihkan adalah {{ $rupiah($info['entry_fee']) }}. Batas pembayaran 24 jam sejak dicatat.</p>
            <form class="data-form" method="POST" action="{{ route('admin.tournaments.teams.store', $tournament) }}" aria-labelledby="add-team-heading">
                @csrf <input type="hidden" name="_form" value="add-team">
                @foreach(['club' => 'Nama SSB / klub', 'team' => 'Nama tim', 'contact_name' => 'Nama penanggung jawab', 'whatsapp' => 'Nomor WhatsApp PIC'] as $field => $label)
                    <div class="field"><label for="team-{{ $field }}">{{ $label }}</label>
                        <input id="team-{{ $field }}" name="{{ $field }}" type="{{ $field === 'whatsapp' ? 'tel' : 'text' }}" value="{{ old('_form') === 'add-team' ? old($field) : '' }}" required maxlength="{{ $field === 'whatsapp' ? 22 : 120 }}">
                        @if(old('_form') === 'add-team') @error($field)<small class="field-error">{{ $message }}</small>@enderror @endif
                    </div>
                @endforeach
                <div class="field wide"><label for="team-category">Kategori tim</label><select id="team-category" name="category" required>
                    <option value="">Pilih kategori</option>
                    @foreach($tournament->tournamentCategories() as $category)<option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>@endforeach
                </select>@if(old('_form') === 'add-team') @error('category')<small class="field-error">{{ $message }}</small>@enderror @endif</div>
                <div class="wide form-actions"><button type="button" class="btn" data-close-dialog>Batal</button><button type="submit" class="btn primary">Catat pendaftaran</button></div>
            </form>
        </div>
    </dialog>
    @endif
    <dialog class="modal" id="edit-tournament" aria-labelledby="edit-tournament-heading" @if($errors->any() && old('_form') === 'edit-'.$tournament->id) data-restore-form @endif>
        <div class="modal-content">
            <h2 id="edit-tournament-heading">Edit detail turnamen</h2>
            <form method="POST" action="{{ route('admin.update', ['tournaments', $tournament]) }}" enctype="multipart/form-data" class="data-form" aria-labelledby="edit-tournament-heading">
                @csrf @method('PUT')
                @include('admin.record-fields', ['record' => $tournament, 'section' => 'tournaments', 'meta' => ['Turnamen TSS', 'trophy', 'Nama turnamen', 'draft'], 'covers' => true,
                    'allowedStatuses' => ['draft', 'published', 'archived'], 'statuses' => ['draft' => 'Draf', 'published' => 'Terbit', 'archived' => 'Arsip']])
                <div class="wide form-actions"><button type="button" class="btn" data-close-dialog>Batal</button><button type="submit" class="btn primary">Simpan detail</button></div>
            </form>
        </div>
    </dialog>
    @include('admin.confirm-dialog')
@endsection
