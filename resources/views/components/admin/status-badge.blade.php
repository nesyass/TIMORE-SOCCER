@props(['status'])
@php
    $tone = match ($status) {
        'active', 'published', 'paid', 'completed', 'income' => 'success',
        'pending', 'waiting_payment', 'unpaid', 'postponed' => 'warning',
        'cancelled', 'expired', 'rejected', 'expense' => 'danger',
        'payment_review', 'scheduled' => 'info',
        default => 'neutral',
    };
    $label = match ($status) {
        'active' => 'Aktif', 'inactive' => 'Nonaktif', 'published' => 'Terbit', 'draft' => 'Draf',
        'paid' => 'Lunas', 'unpaid' => 'Belum lunas', 'completed' => 'Selesai',
        'income' => 'Pemasukan', 'expense' => 'Pengeluaran', 'pending' => 'Menunggu',
        'waiting_payment' => 'Menunggu pembayaran', 'payment_review' => 'Perlu verifikasi',
        'postponed' => 'Ditunda', 'cancelled' => 'Dibatalkan', 'expired' => 'Kedaluwarsa',
        'rejected' => 'Ditolak', 'scheduled' => 'Terjadwal', 'posted' => 'Tercatat', 'archived' => 'Arsip',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
@endphp
<span {{ $attributes->class(['pill', 'pill-'.$tone]) }}>{{ $label }}</span>
