<?php

namespace Database\Seeders;

use App\Models\AdminRecord;
use App\Models\AdminStudentAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminDemoStudentAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) return;

        DB::transaction(function (): void {
            AdminRecord::where('section', 'students')->whereDoesntHave('account')->lockForUpdate()->get()->each(
                function (AdminRecord $student): void {
                    $registrationId = data_get($student->payload, 'registration_id');
                    if ($registrationId) {
                        $verified = AdminRecord::whereKey($registrationId)->where('section', 'registrations')->where('status', 'active')->exists();
                        $payment = AdminRecord::where('section', 'cashbook')->where('subtitle', 'REG-'.$registrationId)
                            ->where('status', 'income')->where('amount', '>', 0)->exists();
                        if ($verified && $payment) AdminStudentAccount::issueFor($student);
                        return;
                    }

                    $baseFixtures = ['Arka Pradipta', 'Nara Maheswari', 'Bima Adikara', 'Sena Wicaksana', 'Raka Baskara'];
                    $notes = (string) data_get($student->payload, 'notes', '');
                    if (!in_array($student->title, $baseFixtures, true) || !str_starts_with($notes, 'Data fiktif untuk presentasi.')) return;

                    $paidAt = ($student->date ?? $student->created_at)->copy()->setTime(12, 0);
                    $submitted = $paidAt->copy()->subHours(2);
                    $registration = AdminRecord::firstOrCreate(
                        ['section' => 'registrations', 'subtitle' => 'SIM-SISWA-'.$student->id],
                        ['title' => $student->title, 'status' => 'active', 'amount' => 250000, 'date' => $submitted->toDateString(),
                            'created_at' => $submitted, 'updated_at' => $paidAt,
                            'payload' => ['paid_at' => $paidAt->toIso8601String(), 'notes' => 'Data fiktif untuk presentasi. Riwayat pendaftaran awal.']],
                    );
                    $student->update(['payload' => [...($student->payload ?? []), 'registration_id' => $registration->id]]);
                    AdminRecord::firstOrCreate(
                        ['section' => 'cashbook', 'subtitle' => 'REG-'.$registration->id],
                        ['title' => 'Pembayaran pendaftaran: '.$student->title, 'status' => 'income', 'amount' => 250000,
                            'date' => $paidAt->toDateString(), 'created_at' => $paidAt, 'updated_at' => $paidAt,
                            'payload' => ['registration_id' => $registration->id, 'notes' => 'Data fiktif untuk presentasi.']],
                    );
                    AdminStudentAccount::issueFor($student);
                }
            );
        });
    }
}