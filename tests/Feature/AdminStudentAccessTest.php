<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\AdminStudentAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminStudentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_cannot_be_created_directly_from_the_admin_directory(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post('/admin/students', ['title' => 'Belum dibayar', 'status' => 'active'])->assertStatus(405);
        $this->assertDatabaseMissing('records', ['section' => 'students']);
        $this->assertDatabaseCount('student_accounts', 0);
    }

    public function test_manual_reference_cannot_shadow_a_student_or_its_income_posting(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $registration = AdminRecord::create(['section' => 'registrations', 'title' => 'Siswa baru', 'status' => 'waiting_payment']);
        $reference = 'REG-'.$registration->id;
        $unrelatedStudent = AdminRecord::create(['section' => 'students', 'title' => 'Siswa berbeda', 'subtitle' => $reference, 'status' => 'active']);
        AdminRecord::create(['section' => 'cashbook', 'title' => 'Referensi manual', 'subtitle' => $reference, 'status' => 'expense', 'amount' => '100']);

        $this->actingAs($admin)->post("/admin/registrations/{$registration->id}/verify", [
            'paid_at' => now('Asia/Jayapura')->format('Y-m-d\TH:i'), 'amount' => '250000', 'confirmed' => '1',
        ])->assertSessionHasNoErrors();
        $student = AdminRecord::where('section', 'students')->where('payload->registration_id', $registration->id)->sole();
        $this->assertNotSame($unrelatedStudent->id, $student->id);
        $this->assertTrue($student->account()->exists());
        $this->assertFalse($unrelatedStudent->account()->exists());
        $income = AdminRecord::where('section', 'cashbook')->where('payload->registration_id', $registration->id)->sole();
        $this->assertSame('250000.00', $income->amount);
        $this->assertSame('income', $income->status);
    }

    public function test_receipt_is_private_and_verification_posts_confirmed_income_only_once(): void
    {
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-23 12:00:00', 'UTC'));
        $admin = User::factory()->create(['is_admin' => true]);
        $registration = AdminRecord::create(['section' => 'registrations', 'title' => 'Siswa Bukti Bayar', 'status' => 'payment_review', 'amount' => 250000]);
        $verify = "/admin/registrations/{$registration->id}/verify";
        $download = "/admin/registrations/{$registration->id}/receipt";
        $payment = ['paid_at' => '2026-11-23T21:00', 'amount' => '275000', 'confirmed' => '1'];
        $this->actingAs($admin)->post($verify, $payment + ['receipt' => UploadedFile::fake()->create('invalid.txt', 1, 'text/plain')])
            ->assertSessionHasErrors('receipt');
        $this->assertDatabaseCount('student_accounts', 0);
        $this->assertDatabaseMissing('records', ['section' => 'cashbook']);

        $this->post($verify, $payment + ['receipt' => UploadedFile::fake()->image('bukti.png')])->assertSessionHasNoErrors();
        $receipt = $registration->refresh()->payload['payment_receipt'];
        Storage::disk('local')->assertExists($receipt);
        $this->get($download)->assertDownload("bukti-bayar-REG-{$registration->id}.png");
        $this->post($verify, ['amount' => '555000'] + $payment + ['receipt' => UploadedFile::fake()->image('duplikat.png')])->assertSessionHasNoErrors();
        $this->assertSame([$receipt], Storage::disk('local')->allFiles('payment-receipts'));
        $entry = AdminRecord::where('section', 'cashbook')->sole();
        $this->assertSame('income', $entry->status);
        $this->assertSame('275000.00', $entry->amount);
        $this->assertSame('2026-11-23', $entry->date->toDateString());
        $this->get('/admin/cashbook?month=2026-11')->assertOk()
            ->assertViewHas('finance', fn ($finance) => (float) $finance['income'] === 275000.0 && (float) $finance['balance'] === 275000.0);
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get($download)->assertForbidden();
    }

    public function test_expiry_preserves_registration_and_allows_a_late_review_of_timely_payment(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 12:00:00', 'UTC'));
        $admin = User::factory()->create(['is_admin' => true]);
        $record = AdminRecord::create([
            'section' => 'registrations', 'title' => 'Siswa QA', 'status' => 'waiting_payment',
            'payload' => ['group' => 'U-12', 'whatsapp' => '+1 202 555 0100'],
        ]);
        $record->forceFill(['created_at' => now()->subHours(25)])->save();

        $this->actingAs($admin)->get('/admin/registrations?status=expired')->assertOk()->assertSee('Siswa QA');
        $this->assertSame('expired', $record->refresh()->status);
        $this->assertDatabaseCount('student_accounts', 0);
        $url = "/admin/registrations/{$record->id}/verify";
        $this->post($url, ['paid_at' => now('Asia/Jayapura')->format('Y-m-d\TH:i'), 'amount' => '250000', 'confirmed' => '1'])
            ->assertSessionHasErrors('paid_at');
        $this->assertDatabaseCount('student_accounts', 0);
        $this->from('/admin/registrations')->post($url, [
            'paid_at' => now('Asia/Jayapura')->subHours(2)->format('Y-m-d\TH:i'), 'amount' => '250000', 'confirmed' => '1',
        ])->assertRedirect('/admin/registrations')->assertSessionHas('student_access');
        $access = session('student_access');
        $account = AdminStudentAccount::sole();
        $this->assertTrue(Hash::check($access['password'], $account->password));
        $this->assertSame('active', $record->refresh()->status);
        $this->assertSame('U-12', AdminRecord::findOrFail($account->student_id)->payload['group']);

        $this->get('/admin/registrations')->assertSee($access['username'])->assertSee($access['password']);
        $this->get('/admin/registrations')->assertDontSee($access['password']);
        $this->get('/admin/students?account=issued&q='.$account->username)->assertSee($account->username)->assertDontSee($access['password']);

        $this->from('/admin/students')->post("/admin/students/{$account->student_id}/account/reset")
            ->assertRedirect('/admin/students')->assertSessionHas('student_access');
        $this->assertFalse(Hash::check($access['password'], $account->refresh()->password));
        $this->assertTrue(Hash::check(session('student_access.password'), $account->password));
        $this->assertDatabaseCount('student_accounts', 1);
    }
}
