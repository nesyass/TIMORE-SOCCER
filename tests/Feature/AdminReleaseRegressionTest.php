<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReleaseRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_rejects_non_admins_and_non_string_passwords(): void
    {
        $other = User::factory()->create(['is_admin' => false]);
        $this->post('/admin/login', ['email' => $other->email, 'password' => ['password']])->assertSessionHasErrors('password');
        $this->post('/admin/login', ['email' => $other->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_partial_payments_cannot_finalize_student_or_team_registration(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $registration = AdminRecord::create(['section' => 'registrations', 'title' => 'Siswa QA', 'status' => 'waiting_payment', 'amount' => 250000]);
        $tournament = AdminRecord::create(['section' => 'tournaments', 'title' => 'Turnamen QA', 'status' => 'published']);
        $team = $tournament->teamEntries()->create(['section' => 'team-registrations', 'title' => 'Tim QA', 'status' => 'payment_review', 'amount' => 250000]);
        $partial = ['paid_at' => now('Asia/Jayapura')->format('Y-m-d\TH:i'), 'amount' => '249999.99', 'confirmed' => '1'];

        $this->post("/admin/registrations/{$registration->id}/verify", $partial)->assertSessionHasErrors('amount');
        $this->post("/admin/tournaments/{$tournament->id}/teams/{$team->id}/verify", $partial)->assertSessionHasErrors('amount');
        $this->assertSame('waiting_payment', $registration->refresh()->status);
        $this->assertSame('payment_review', $team->refresh()->status);
        $this->assertDatabaseCount('student_accounts', 0);
        $this->assertDatabaseMissing('records', ['section' => 'cashbook']);
    }

    public function test_cashbook_rejects_future_dates_and_date_outside_mysql_range(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $entry = ['title' => 'Transaksi QA', 'status' => 'income', 'amount' => '100000'];
        $tomorrow = now('Asia/Jayapura')->addDay()->toDateString();
        $this->post('/admin/cashbook', $entry + ['date' => $tomorrow])->assertSessionHasErrors('date');
        $this->post('/admin/cashbook', $entry + ['date' => '0001-01-01'])->assertSessionHasErrors('date');
        $this->assertDatabaseMissing('records', ['section' => 'cashbook']);
    }

    public function test_mysql_rejects_duplicate_generated_postings_independently_of_the_controller(): void
    {
        $data = ['section' => 'cashbook', 'title' => 'Pembayaran QA', 'status' => 'income', 'amount' => 250000, 'payload' => ['registration_id' => 12]];
        AdminRecord::create($data);
        $this->expectException(QueryException::class);
        AdminRecord::create(['title' => 'Pembayaran ganda'] + $data);
    }

    public function test_mysql_rejects_two_students_from_one_registration(): void
    {
        $data = ['section' => 'students', 'title' => 'Siswa QA', 'status' => 'active', 'payload' => ['registration_id' => 12]];
        AdminRecord::create($data);
        $this->expectException(QueryException::class);
        AdminRecord::create(['title' => 'Siswa ganda'] + $data);
    }

    public function test_tournament_query_parameters_cannot_trigger_validation_redirects(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $tournament = AdminRecord::create(['section' => 'tournaments', 'title' => 'Turnamen QA', 'status' => 'draft']);
        $this->get("/admin/tournaments/{$tournament->id}?q[]=foo&status[]=bar")->assertOk()->assertSee('Turnamen QA');
    }
    public function test_partial_tournament_edits_validate_effective_dates(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $tournament = AdminRecord::create([
            'section' => 'tournaments',
            'title' => 'Turnamen QA',
            'status' => 'published',
            'date' => '2027-02-20',
            'payload' => ['content' => ['opens_on' => '2027-02-04', 'closes_on' => '2027-02-18']],
        ]);
        $url = '/admin/tournaments/'.$tournament->id;
        $base = ['title' => $tournament->title, 'status' => 'published'];

        $this->put($url, $base + ['date' => '2027-02-10'])->assertSessionHasErrors('content.closes_on');
        $this->put($url, $base + ['date' => null])->assertSessionHasErrors('date');
        $this->put($url, $base + ['content' => ['opens_on' => '2027-02-19']])->assertSessionHasErrors('content.closes_on');
        $this->assertSame('2027-02-20', $tournament->fresh()->date->toDateString());

        $this->put($url, $base + ['content' => ['closes_on' => '2027-02-17']])->assertSessionHasNoErrors();
        $this->assertSame('2027-02-17', data_get($tournament->fresh()->payload, 'content.closes_on'));
    }

    public function test_upcoming_tournament_count_and_list_exclude_cancelled_or_finished_events(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $record = ['section' => 'tournaments', 'status' => 'published', 'date' => today('Asia/Jayapura')->addDays(2)->toDateString()];
        AdminRecord::create($record + ['title' => 'Turnamen Berikutnya']);
        foreach (['cancelled', 'finished'] as $status) {
            AdminRecord::create($record + ['title' => 'Turnamen '.$status, 'payload' => ['content' => ['event_status' => $status]]]);
        }

        $this->get('/admin')->assertOk()->assertViewHas('counts', fn ($counts) => $counts['events'] === 1);
        $this->get('/admin/tournaments?status=upcoming')->assertOk()
            ->assertSee('Turnamen Berikutnya')->assertDontSee('Turnamen cancelled')->assertDontSee('Turnamen finished');
    }

}
