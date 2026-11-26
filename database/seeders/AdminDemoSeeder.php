<?php

namespace Database\Seeders;

use App\Models\AdminRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command?->warn('Data demo hanya dapat ditambahkan pada lingkungan lokal.');
            return;
        }

        $sections = [
            'students' => [
                ['Arka Pradipta', 'U-10 · sayap kanan', 'active', -42],
                ['Nara Maheswari', 'U-12 · gelandang', 'active', -35],
                ['Bima Adikara', 'U-12 · penjaga gawang', 'active', -28],
                ['Sena Wicaksana', 'U-14 · bek tengah', 'active', -22],
                ['Raka Baskara', 'U-10 · penyerang', 'inactive', -16],
            ],
            'coaches' => [
                ['Damar Prasetya', 'Pelatih kepala · U-12', 'active', -45],
                ['Laras Wening', 'Pelatih teknik · U-10', 'active', -38],
                ['Bayu Cakrawala', 'Pelatih penjaga gawang · U-14', 'active', -31],
            ],
            'schedules' => [
                ['Latihan dasar U-10', 'Sabtu 08.00–09.30 · Lapangan A', 'active'],
                ['Latihan teknik U-12', 'Sabtu 10.00–11.30 · Lapangan A', 'active'],
                ['Latihan taktik U-14', 'Minggu 08.00–10.00 · Lapangan B', 'active'],
            ],
            'schedule-changes' => [
                ['Pemindahan latihan U-12', 'Sesi Sabtu pindah ke Lapangan B', 'scheduled', 5, null, 'Lapangan A dipakai untuk perawatan rumput. Jadwal rutin tetap berlaku pada pekan berikutnya.'],
                ['Latihan tambahan U-14', 'Persiapan Timore Junior Cup', 'scheduled', 12],
                ['Penundaan latihan U-10', 'Sesi Minggu · cuaca buruk', 'postponed', -7],
            ],
            'tournaments' => [
                ['Timore Junior Cup U-12', 'Turnamen antarsekolah bola · penyelenggara TSS', 'published', 28],
                ['Timore Festival Bola U-10', 'Festival mini soccer · penyelenggara TSS', 'draft', 56],
                ['Timore Friendly Series U-14', 'Turnamen persahabatan · penyelenggara TSS', 'completed', -21],
            ],
            'team-registrations' => [
                ['SSB Garuda Muda U-12', 'Timore Junior Cup U-12 · tim eksternal', 'pending', -1],
                ['SSB Cakrawala U-12', 'Timore Junior Cup U-12 · tim eksternal', 'payment_review', -3],
                ['SSB Bintang Selatan U-12', 'Timore Junior Cup U-12 · tim eksternal', 'active', -5],
            ],
            'cashbook' => [
                ['Iuran latihan kelompok U-12', 'Penerimaan iuran bulanan', 'income', -10, 750000],
                ['Kontribusi kegiatan persahabatan', 'Penerimaan kegiatan U-14', 'income', -8, 450000],
                ['Sewa Lapangan B', 'Latihan akhir pekan', 'expense', -6, 300000],
                ['Pembelian rompi latihan', '12 rompi untuk kelompok U-10', 'expense', -3, 180000],
            ],
            'dues' => [
                ['Arka Pradipta · iuran bulan ini', 'U-10 · pembayaran tercatat', 'paid', -8, 150000],
                ['Nara Maheswari · iuran bulan ini', 'U-12 · menunggu pembayaran', 'unpaid', 5, 150000],
                ['Bima Adikara · iuran bulan ini', 'U-12 · pembayaran tercatat', 'paid', -4, 150000],
            ],
            'certificates' => [
                ['Bima Adikara', 'Partisipasi Timore Friendly Series · TSS-DEMO-001', 'published', -18],
                ['Sena Wicaksana', 'Partisipasi Timore Friendly Series · TSS-DEMO-002', 'draft', -17],
            ],
            'articles' => [
                ['Pendaftaran Timore Junior Cup dibuka', 'Informasi untuk SSB dan tim eksternal', 'published', -2],
                ['Kegiatan latihan teknik U-12', 'Liputan kegiatan akhir pekan', 'draft', 3],
            ],
            'achievements' => [
                ['Juara II Festival Sepak Bola Usia Dini', 'Skuad U-12 · Festival Lapangan Merdeka', 'published', -28],
                ['Penghargaan Fair Play', 'Skuad U-14 · Timore Friendly Series', 'published', -21],
            ],
            'gallery' => [
                ['Latihan teknik kelompok U-10', 'Album dokumentasi latihan rutin', 'published', -6],
                ['Persiapan Timore Junior Cup', 'Album dokumentasi panitia', 'draft', -1],
            ],
            'site-content' => [
                ['Beranda · pengumuman pendaftaran', 'Ajakan mendaftar kelas usia U-10 dan U-12', 'published', -9],
                ['Tentang sekolah · program latihan', 'Ringkasan kelompok usia dan fokus program', 'published', -11],
                ['Kontak · informasi lapangan', 'Draf informasi lokasi sesi latihan', 'draft', -2],
            ],
            'registrations' => [
                ['Aksa Mandala', 'U-10 · formulir masuk', 'waiting_payment', -6, 250000],
                ['Kirana Aulia', 'U-12 · pembayaran dalam pemeriksaan', 'payment_review', -4, 250000],
                ['Galih Samudra', 'U-14 · pendaftaran dibatalkan', 'cancelled', -3, 250000],
                ['Tara Ambar', 'U-10 · menunggu pembayaran', 'waiting_payment', -2, 250000],
                ['Rafi Dananjaya', 'U-12 · siap untuk simulasi verifikasi', 'payment_review', -1, 250000, 'Simulasi presentasi. Klik Verifikasi untuk menambahkan satu siswa dan satu transaksi pemasukan.'],
            ],
        ];

        $today = today();
        $createdAt = now()->subMinutes(array_sum(array_map('count', $sections)));

        DB::transaction(function () use ($sections, $today, $createdAt): void {
            foreach ($sections as $section => $entries) {
                foreach ($entries as $entry) {
                    [$title, $subtitle, $status] = $entry;
                    $dateOffset = $entry[3] ?? null;
                    $timestamp = $createdAt->addMinute()->copy();
                    $student = $section === 'dues'
                        ? AdminRecord::where('section', 'students')->where('title', strstr($title, ' ·', true))->first()
                        : null;

                    AdminRecord::firstOrCreate(
                        ['section' => $section, 'title' => $title],
                        [
                            'subtitle' => $subtitle,
                            'status' => $status,
                            'date' => $dateOffset === null ? null : $today->copy()->addDays($dateOffset),
                            'amount' => $entry[4] ?? null,
                            'student_id' => $student?->id,
                            'billing_month' => $student ? $today->copy()->startOfMonth() : null,
                            'payload' => ['notes' => 'Data fiktif untuk presentasi. '.($entry[5] ?? '')],
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ]
                    );
                }
            }
        });
    }
}
