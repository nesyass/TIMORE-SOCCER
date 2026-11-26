<?php

namespace Database\Seeders;

use App\Models\AdminRecord;
use App\Models\AdminStudentAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminPresentationSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command?->warn('Simulasi hanya tersedia di lingkungan lokal.');
            return;
        }

        $this->call(AdminDemoSeeder::class);

        $firstNames = ['Daffa', 'Mika', 'Lintang', 'Hana', 'Rendra', 'Aluna', 'Farrel', 'Sasmi', 'Kenzo', 'Jihan', 'Naufal', 'Kania'];
        $familyNames = ['Halim', 'Wibawa', 'Nuraga', 'Sagara', 'Pranaya'];
        $groups = ['U-8', 'U-10', 'U-12', 'U-14', 'U-16'];
        $now = now('Asia/Jayapura');
        $month = $now->copy()->startOfMonth();

        DB::transaction(function () use ($firstNames, $familyNames, $groups, $now, $month): void {
            foreach ($familyNames as $family => $familyName) {
                foreach ($firstNames as $position => $firstName) {
                    $index = $family * count($firstNames) + $position;
                    $name = $firstName.' '.$familyName;
                    $group = $groups[$index % count($groups)];
                    $status = match (true) {
                        $index < 6 => 'payment_review',
                        $index < 13 => 'waiting_payment',
                        $index < 19 => 'expired',
                        $index === 19 || $index === 20 => 'cancelled',
                        default => 'active',
                    };
                    $submitted = $status === 'payment_review' || $status === 'waiting_payment'
                        ? $now->copy()->subMinutes(20 + $index * 29)
                        : $now->copy()->subMonths(intdiv($index, 20))->subDays(2 + $index % 10);
                    $received = $submitted->copy()->addHours(3);
                    $registration = AdminRecord::firstOrCreate(['section' => 'registrations', 'title' => $name, 'subtitle' => 'Simulasi · '.$group], [
                        'status' => $status, 'amount' => 250000, 'date' => $submitted->toDateString(),
                        'created_at' => $submitted->copy()->utc(), 'updated_at' => $submitted->copy()->utc(),
                        'payload' => [
                            'group' => $group, 'whatsapp' => sprintf('+1 202 555 %04d', 100 + $index),
                            'notes' => 'Data fiktif untuk presentasi. Nomor menggunakan rentang fiktif.',
                            'paid_at' => $status === 'active' ? $received->copy()->utc()->toIso8601String() : null,
                        ],
                    ]);
                    if ($status !== 'active' || $registration->status !== 'active') continue;

                    $student = AdminRecord::firstOrCreate(['section' => 'students', 'subtitle' => 'REG-'.$registration->id], [
                        'title' => $name, 'status' => $index % 11 === 0 ? 'inactive' : 'active', 'date' => $received->toDateString(),
                        'created_at' => $received->copy()->utc(), 'updated_at' => $received->copy()->utc(),
                        'payload' => ['registration_id' => $registration->id, 'group' => $group,
                            'whatsapp' => $registration->payload['whatsapp'], 'notes' => 'Data fiktif untuk presentasi.'],
                    ]);
                    if (!$student->account()->exists()) AdminStudentAccount::issueFor($student);

                    AdminRecord::firstOrCreate(['section' => 'cashbook', 'subtitle' => 'REG-'.$registration->id], [
                        'title' => 'Pembayaran pendaftaran: '.$name, 'status' => 'income', 'amount' => 250000,
                        'date' => $received->toDateString(), 'created_at' => $received->copy()->utc(), 'updated_at' => $received->copy()->utc(),
                        'payload' => ['registration_id' => $registration->id, 'notes' => 'Data fiktif untuk presentasi.'],
                    ]);

                    $paidOn = $month->copy()->addDays(2 + $index % 14);
                    $paid = $index % 3 !== 0 && $paidOn->copy()->startOfDay()->lte($now);
                    $due = AdminRecord::firstOrCreate(['section' => 'dues', 'student_id' => $student->id, 'billing_month' => $month->toDateString()], [
                        'title' => $name, 'status' => $paid ? 'paid' : 'unpaid', 'amount' => 150000, 'date' => $month->toDateString(),
                        'payload' => ['paid_on' => $paid ? $paidOn->toDateString() : null, 'notes' => 'Data fiktif untuk presentasi.'],
                    ]);
                    if ($due->wasRecentlyCreated && $paid) {
                        AdminRecord::firstOrCreate(['section' => 'cashbook', 'subtitle' => 'IURAN-'.$due->id], [
                            'title' => 'Iuran '.$name, 'status' => 'income', 'amount' => $due->amount, 'date' => $paidOn->toDateString(),
                            'payload' => ['dues_id' => $due->id, 'notes' => 'Data fiktif untuk presentasi.'],
                        ]);
                    }
                }
            }

            $fixtures = [
                'coaches' => ['Satria Pelangi', 'Reni Lazuardi', 'Fikri Angkasa', 'Vina Aditya'],
                'schedules' => ['Latihan koordinasi U-8', 'Latihan penyelesaian U-10', 'Latihan bertahan U-14', 'Latihan teknik U-16'],
                'schedule-changes' => ['Penyesuaian lapangan U-8', 'Latihan gabungan U-16', 'Pergantian jam U-12'],
                'tournaments' => ['Liga Mini Timore U-10', 'Piala Lapangan Utara U-14', 'Timore Development Cup U-16', 'Festival Sepak Bola U-8'],
                'team-registrations' => ['SSB Ombak Timur', 'SSB Nusa Kecil', 'SSB Tunas Bukit', 'SSB Sinar Selatan', 'SSB Mutiara Muda', 'SSB Arunika'],
                'certificates' => ['Naufal Pranaya', 'Kania Sagara', 'Sasmi Nuraga', 'Jihan Wibawa'],
                'articles' => ['Jadwal kegiatan Timore bulan depan', 'Cerita dari latihan akhir pekan', 'Kenali program U-8', 'Panduan persiapan turnamen'],
                'achievements' => ['Fair Play Piala Lapangan Utara', 'Finalis Liga Mini Timore', 'Juara III Festival Bola Anak'],
                'gallery' => ['Pembukaan Liga Mini', 'Sesi koordinasi U-8', 'Latihan gabungan U-16'],
                'site-content' => ['Beranda · program kelompok usia', 'Kontak · jam layanan admin', 'Tentang · kegiatan akhir pekan'],
            ];
            foreach ($fixtures as $section => $titles) {
                foreach ($titles as $index => $title) {
                    $status = match ($section) {
                        'coaches', 'schedules' => 'active',
                        'schedule-changes' => 'scheduled',
                        'team-registrations' => $index % 2 === 0 ? 'pending' : 'active',
                        default => $index % 3 === 2 ? 'draft' : 'published',
                    };
                    $subtitle = $section === 'schedules'
                        ? ['Jumat 15.00–16.00 · Lapangan A', 'Sabtu 09.00–10.00 · Lapangan B', 'Minggu 10.00–11.30 · Lapangan A', 'Minggu 16.00–17.30 · Lapangan B'][$index]
                        : 'Simulasi Timore';
                    AdminRecord::firstOrCreate(['section' => $section, 'title' => $title, 'subtitle' => $subtitle], [
                        'status' => $status, 'date' => $now->copy()->addDays(3 + $index * 4)->toDateString(),
                        'payload' => ['notes' => 'Data fiktif untuk presentasi.'],
                    ]);
                }
            }
        });

        $this->call(AdminDemoStudentAccountSeeder::class);
        $this->call(AdminContentPreviewSeeder::class);
        $this->call(AdminTournamentWorkflowSeeder::class);
    }
}
