<?php

namespace App\Models;

class AdminContentFields
{
    public static function for(string $section): array
    {
        $event = [
            'summary' => ['Ringkasan untuk kartu', 'textarea', 180],
            'event_status' => ['Status kegiatan', 'select', ['upcoming' => 'Akan datang', 'ongoing' => 'Berlangsung', 'finished' => 'Selesai', 'cancelled' => 'Dibatalkan']],
            'start_time' => ['Jam mulai (WIT)', 'time'],
            'end_date' => ['Tanggal selesai', 'date'],
            'address' => ['Alamat lengkap', 'textarea', 300],
            'maps_url' => ['Tautan peta', 'url', 500],
            'organizer' => ['Penyelenggara', 'text', 120],
        ];

        $fields = match ($section) {
            'events' => [
                'event_type' => ['Jenis kegiatan', 'select', ['festival' => 'Festival', 'friendly' => 'Laga persahabatan', 'workshop' => 'Klinik sepak bola', 'community' => 'Kegiatan komunitas']],
                ...$event,
            ],
            'tournaments' => [
                ...$event,
                'opens_on' => ['Pendaftaran tim dibuka', 'date'],
                'closes_on' => ['Pendaftaran tim ditutup', 'date'],
                'categories' => ['Kategori tim', 'text', 160, 'Pisahkan dengan koma. Contoh: U-10, U-12, U-14.'],
                'entry_fee' => ['Biaya pendaftaran per tim (Rp)', 'money'],
                'contact' => ['Kontak resmi panitia', 'text', 80],
                'rules' => ['Ketentuan pendaftaran tim', 'textarea', 2000],
            ],
            'coaches' => [
                'summary' => ['Bio singkat untuk kartu', 'textarea', 180],
                'specialties' => ['Spesialisasi latihan', 'text', 160],
                'groups' => ['Kelompok usia binaan', 'text', 120],
                'career' => ['Pengalaman melatih', 'repeat', [
                    'role' => ['Jabatan atau peran', 'text', 180, true],
                    'organization' => ['Klub atau organisasi', 'text', 150, false],
                    'period' => ['Periode', 'text', 60, false],
                    'detail' => ['Keterangan singkat', 'text', 280, false],
                ], 'Tambah pengalaman'],
                'social_links' => ['Media sosial', 'repeat', [
                    'platform' => ['Platform', 'select', ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'x' => 'X', 'website' => 'Website'], true],
                    'handle' => ['Nama akun', 'text', 100, false],
                    'url' => ['Tautan profil', 'url', 500, true],
                ], 'Tambah akun'],
                'licenses' => ['Lisensi dan sertifikasi', 'textarea', 2000, 'Satu lisensi per baris. Cantumkan nama, penerbit, dan tahun.'],
                'visibility' => ['Publikasi profil', 'select', ['draft' => 'Draf', 'published' => 'Siap tampil', 'archived' => 'Arsip']],
            ],
            'achievements' => [
                'ranking' => ['Peringkat atau penghargaan', 'text', 100],
                'category' => ['Kategori / kelompok usia', 'text', 120],
                'location' => ['Lokasi kompetisi', 'text', 160],
                'coach' => ['Pelatih pendamping', 'text', 160],
                'summary' => ['Ringkasan pencapaian untuk kartu', 'textarea', 180],
            ],
            'articles' => [
                'summary' => ['Ringkasan berita', 'textarea', 180, 'Tampil pada kartu dan daftar berita.'],
                'category' => ['Kategori berita', 'select', ['pengumuman' => 'Pengumuman', 'kegiatan' => 'Kegiatan', 'turnamen' => 'Turnamen']],
                'author' => ['Nama penulis', 'text', 120],
            ],
            'gallery' => [
                'summary' => ['Keterangan album', 'textarea', 180],
                'photographer' => ['Kredit foto', 'text', 120],
            ],
            'site-content' => [
                'page' => ['Bagian yang disiapkan', 'select', ['home' => 'Beranda', 'about' => 'Tentang TSS', 'contact' => 'Kontak', 'footer' => 'Footer']],
                'summary' => ['Ringkasan bagian', 'textarea', 180],
                'link_label' => ['Teks tombol (jika ada)', 'text', 80],
                'link_url' => ['Alamat tujuan tombol', 'url', 500],
            ],
            default => [],
        };

        if ($fields) $fields['image_alt'] = ['Deskripsi gambar', 'text', 160, 'Jelaskan isi foto untuk pembaca layar.'];
        return $fields;
    }

    public static function coachCareer(array $profile): array
    {
        if (array_key_exists('career', $profile)) return $profile['career'] ?? [];

        $lines = preg_split('/\R/u', trim((string) ($profile['experiences'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_map(function (string $line): array {
            $parts = explode(' · ', $line, 3);
            if (count($parts) !== 3) return ['role' => $line];
            return ['period' => $parts[0], 'organization' => $parts[1], 'role' => $parts[2]];
        }, $lines);
    }
}
