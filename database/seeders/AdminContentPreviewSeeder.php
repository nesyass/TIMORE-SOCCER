<?php

namespace Database\Seeders;

use App\Models\AdminRecord;
use Illuminate\Database\Seeder;

class AdminContentPreviewSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) return;

        $today = today('Asia/Jayapura');
        $examples = [
            ['events', 'Klinik Teknik Dasar', 'Lapangan A', 'published', 4, [
                'event_type' => 'workshop', 'event_status' => 'upcoming', 'start_time' => '08:00',
                'organizer' => 'Timore Soccer School', 'summary' => 'Sesi pengenalan kontrol bola dan koordinasi untuk kelompok usia muda.',
            ]],
            ['events', 'Laga Persahabatan Akhir Pekan', 'Lapangan B', 'draft', 11, [
                'event_type' => 'friendly', 'event_status' => 'upcoming', 'start_time' => '15:30',
                'summary' => 'Pertandingan persahabatan untuk melatih kerja sama tim.',
            ]],
            ['coaches', 'Ari Samudranta', 'Pelatih teknik usia dini', 'active', -14, [
                'summary' => 'Mendampingi pengembangan teknik dasar dan koordinasi pemain muda.',
                'specialties' => 'Kontrol bola dan koordinasi', 'groups' => 'U-8, U-10', 'visibility' => 'published',
                'experiences' => "2023–2025 · Akademi Nusantara (fiktif) · Asisten pelatih\n2026 · Timore Soccer School (simulasi) · Pelatih teknik",
                'licenses' => 'Lisensi pelatih tingkat dasar (contoh data)',
            ]],
            ['achievements', 'Penghargaan Tim Paling Sportif', 'Festival Bola Pesisir (fiktif)', 'published', -2, [
                'ranking' => 'Penghargaan Fair Play', 'category' => 'U-12', 'location' => 'Lapangan Pesisir (fiktif)',
                'coach' => 'Ari Samudranta', 'summary' => 'Apresiasi atas sportivitas, kerja sama, dan disiplin tim selama festival.',
            ]],
        ];
        foreach ($examples as [$section, $title, $subtitle, $status, $offset, $content]) {
            AdminRecord::firstOrCreate(['section' => $section, 'title' => $title], [
                'subtitle' => $subtitle, 'status' => $status, 'date' => $today->copy()->addDays($offset)->toDateString(),
                'payload' => ['notes' => 'Data fiktif untuk presentasi.', 'content' => $content],
            ]);
        }
    }
}