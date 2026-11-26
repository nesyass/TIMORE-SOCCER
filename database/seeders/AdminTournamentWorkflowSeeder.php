<?php

namespace Database\Seeders;

use App\Models\AdminRecord;
use Illuminate\Database\Seeder;

class AdminTournamentWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) return;

        $now = now('Asia/Jayapura');
        $tournament = AdminRecord::firstOrCreate(
            ['section' => 'tournaments', 'title' => 'Piala Tunas Timore · Demo'],
            ['subtitle' => 'Lapangan A', 'status' => 'published', 'date' => $now->copy()->addDays(15)->toDateString(),
                'payload' => [
                    'notes' => 'Data fiktif untuk presentasi.',
                    'content' => [
                        'summary' => 'Simulasi turnamen antarsekolah bola untuk kategori U-10 dan U-12.',
                        'event_status' => 'upcoming', 'start_time' => '08:00', 'organizer' => 'Timore Soccer School',
                        'opens_on' => $now->copy()->subDay()->toDateString(),
                        'closes_on' => $now->copy()->addDays(9)->toDateString(),
                        'categories' => 'U-10, U-12', 'entry_fee' => '250000', 'registration_open' => true,
                    ],
                ]],
        );

        foreach ([
            ['Garuda Muda A', 'SSB Garuda Muda', 'U-12', 'payment_review', 45],
            ['Ombak Timur U-10', 'SSB Ombak Timur', 'U-10', 'waiting_payment', 80],
            ['Garuda Muda B', 'SSB Garuda Muda', 'U-12', 'waiting_payment', 150],
            ['Tunas Bukit U-10', 'SSB Tunas Bukit', 'U-10', 'expired', 1500],
        ] as $index => [$team, $club, $category, $status, $minutes]) {
            $submitted = $now->copy()->subMinutes($minutes);
            $key = $tournament->teamEntryKey($club, $team, $category);
            $tournament->teamEntries()->firstOrCreate(['section' => 'team-registrations', 'team_entry_key' => $key], [
                'title' => $team, 'subtitle' => $club, 'status' => $status, 'amount' => 250000,
                'date' => $submitted->toDateString(), 'created_at' => $submitted->copy()->utc(), 'updated_at' => $submitted->copy()->utc(),
                'payload' => ['category' => $category, 'contact_name' => 'PIC Demo '.($index + 1),
                    'whatsapp' => sprintf('+1 202 555 %04d', 170 + $index), 'notes' => 'Data fiktif untuk presentasi.'],
            ]);
        }
    }
}
