@extends('admin.layouts.admin')
@section('title', 'Penilaian & Progress')
@section('description', 'Rancangan evaluasi perkembangan siswa akan ditetapkan bersama pengelola TSS.')
@section('header-action')
    <span class="pill pill-neutral">Menunggu diskusi mitra</span>
@endsection
@section('content')
    <section class="panel empty">
        <x-admin.icon name="chart" class="empty-icon" />
        <h2>Belum ada skema penilaian</h2>
        <p>Indikator dan proses evaluasi menunggu kesepakatan mitra.</p>
    </section>
@endsection