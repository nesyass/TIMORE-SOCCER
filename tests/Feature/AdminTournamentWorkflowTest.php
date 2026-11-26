<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTournamentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_flow_is_scoped_to_the_tournament_and_posts_cash_once_without_an_account(): void
    {
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-23 12:00:00', 'UTC'));
        $admin = User::factory()->create(['is_admin' => true]);
        $tournament = AdminRecord::create(['section' => 'tournaments', 'title' => 'Piala QA', 'status' => 'draft', 'date' => '2026-12-01',
            'payload' => ['content' => ['opens_on' => '2026-11-22', 'closes_on' => '2026-11-30', 'categories' => 'U-10, U-12', 'entry_fee' => '250000']]]);
        $other = AdminRecord::create(['section' => 'tournaments', 'title' => 'Turnamen lain', 'status' => 'draft']);
        $url = "/admin/tournaments/{$tournament->id}";
        $fields = ['club' => 'SSB QA', 'team' => 'Garuda A', 'category' => 'U-12', 'contact_name' => 'PIC QA', 'whatsapp' => '+1 202 555 0100'];
        $this->actingAs($admin)->post($url.'/state', ['action' => 'open'])->assertSessionHasErrors('action');
        $this->post($url.'/state', ['action' => 'publish'])->assertSessionHasNoErrors();
        $this->post($url.'/state', ['action' => 'open'])->assertSessionHasNoErrors();
        $this->post($url.'/teams', $fields)->assertRedirect($url);
        $this->post($url.'/teams', ['team' => 'garuda   a'] + $fields)->assertSessionHasErrors('team');
        $this->post($url.'/teams', ['team' => 'Garuda B'] + $fields)->assertRedirect($url);
        $entry = $tournament->teamEntries()->where('title', 'Garuda A')->sole();
        $this->get($url)->assertOk()->assertSee('Garuda A')->assertSee('Garuda B');
        $this->get("/admin/tournaments/{$other->id}")->assertOk()->assertDontSee('Garuda A');
        $this->post($url."/teams/{$entry->id}/review")->assertSessionHasNoErrors();
        $this->assertSame('payment_review', $entry->refresh()->status);
        $this->post($url.'/state', ['action' => 'close'])->assertSessionHasNoErrors();
        $this->post($url.'/teams', ['team' => 'Garuda C'] + $fields)->assertSessionHasErrors('tournament');

        $payment = ['paid_at' => '2026-11-23T21:00', 'amount' => '250000', 'confirmed' => '1'];
        AdminRecord::create(['section' => 'cashbook', 'title' => 'Referensi manual', 'subtitle' => 'TEAM-'.$entry->id, 'status' => 'expense', 'amount' => '100']);
        $this->post("/admin/tournaments/{$other->id}/teams/{$entry->id}/verify", $payment)->assertNotFound();
        $this->post($url."/teams/{$entry->id}/verify", $payment + ['receipt' => UploadedFile::fake()->image('bukti.png')])->assertSessionHasNoErrors();
        $this->post($url."/teams/{$entry->id}/verify", ['amount' => '350000'] + $payment)->assertSessionHasNoErrors();
        $this->assertSame('active', $entry->refresh()->status);
        $ledger = AdminRecord::where('section', 'cashbook')->where('payload->team_registration_id', $entry->id)->sole();
        $this->assertSame(2, AdminRecord::where('section', 'cashbook')->count());
        $this->assertSame('250000.00', $ledger->amount);
        $this->assertSame($entry->id, $ledger->payload['team_registration_id']);
        $this->assertDatabaseCount('student_accounts', 0);
        $this->assertDatabaseMissing('records', ['section' => 'students']);

        $download = $url."/teams/{$entry->id}/receipt";
        $this->get($download)->assertDownload("bukti-bayar-TEAM-{$entry->id}.png");
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get($download)->assertForbidden();
        $this->actingAs($admin)->post($url.'/state', ['action' => 'draft'])->assertSessionHasNoErrors();
        $this->assertSame('draft', $tournament->refresh()->status);
        $this->assertSame('closed', $tournament->teamRegistrationState());
        $this->assertSame(2, $tournament->teamEntries()->count());
    }
}
