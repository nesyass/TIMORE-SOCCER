<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_prepare_and_preview_structured_content(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $date = now('Asia/Jayapura')->addDays(10)->toDateString();
        $event = ['title' => 'Klinik TSS', 'subtitle' => 'Lapangan A', 'status' => 'published', 'date' => $date];
        $this->post('/admin/events', $event + ['content' => ['end_date' => '2000-01-01']])->assertSessionHasErrors('content.end_date');
        $this->post('/admin/events', $event + ['content' => [
            'event_type' => 'workshop', 'start_time' => '08:30', 'summary' => 'Latihan teknik dasar.',
        ]])->assertSessionHasNoErrors();
        $this->post('/admin/coaches', ['title' => 'Pelatih QA', 'status' => 'active', 'content' => [
            'visibility' => 'published', 'career' => [['role' => 'Asisten pelatih', 'organization' => 'Klub QA', 'period' => '2026']],
        ]])->assertSessionHasNoErrors();
        $this->post('/admin/achievements', ['title' => 'Prestasi QA', 'status' => 'published', 'content' => [
            'ranking' => 'Juara II', 'category' => 'U-12',
        ]])->assertSessionHasNoErrors();
        $this->post('/admin/tournaments', ['title' => 'Turnamen QA', 'status' => 'draft', 'date' => $date,
            'content' => ['opens_on' => $date, 'closes_on' => '2000-01-01']])->assertSessionHasErrors('content.closes_on');
        $this->get('/admin/events')->assertOk()->assertSee('Latihan teknik dasar.')->assertSee('08.30 WIT');
        $this->get('/admin/events?view=table')->assertOk()->assertSee('Klinik TSS');
        $this->get('/admin')->assertOk()->assertSee('Sorotan Timore')->assertSee('Klinik TSS')->assertSee('Pelatih QA')->assertSee('Prestasi QA');
    }

    public function test_coach_poster_supports_multiple_experiences_and_safe_social_links(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $fields = ['title' => 'Mentor QA', 'subtitle' => 'Pelatih teknik', 'status' => 'active'];
        $details = [
            'summary' => 'Fokus teknik dasar untuk pemain muda.',
            'career' => [['role' => 'Asisten pelatih', 'organization' => 'Klub QA'], ['role' => 'Pelatih teknik']],
            'social_links' => [['platform' => 'instagram', 'handle' => '@mentorqa', 'url' => 'https://example.com/ig'],
                ['platform' => 'youtube', 'url' => 'https://example.com/video']],
        ];
        $this->post('/admin/coaches', $fields + ['content' => [
            'social_links' => [['platform' => 'instagram', 'url' => 'javascript:alert(1)']],
        ]])->assertSessionHasErrors('content.social_links.0.url');
        $this->post('/admin/coaches', $fields + ['content' => $details])->assertSessionHasNoErrors();
        $coach = AdminRecord::where('section', 'coaches')->sole();
        $this->get('/admin/coaches')->assertOk()->assertSee('Tambah pengalaman')->assertSee('Tambah akun')
            ->assertSee('Klub QA')->assertSee('https://example.com/ig')->assertSee('@mentorqa')->assertDontSee('Biografi lengkap');
        $this->assertCount(2, $coach->payload['content']['career']);
        $this->assertCount(2, $coach->payload['content']['social_links']);
        $this->put("/admin/coaches/{$coach->id}", $fields + ['content' => ['career' => null, 'social_links' => null]])->assertSessionHasNoErrors();
        $this->assertSame([], $coach->refresh()->payload['content']['career']);
        $this->assertSame([], $coach->payload['content']['social_links']);
    }

    public function test_admin_can_upload_replace_and_delete_an_event_cover(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['is_admin' => true]);
        $fields = ['title' => 'Timore QA Cup', 'status' => 'published', 'date' => '2026-12-20'];

        $this->actingAs($admin)->post('/admin/tournaments', $fields + ['cover' => UploadedFile::fake()->image('cover.jpg')])
            ->assertSessionHasNoErrors();
        $event = AdminRecord::where('title', $fields['title'])->firstOrFail();
        $first = $event->payload['cover'];
        Storage::disk('local')->assertExists($first);
        $this->get("/admin/tournaments/{$event->id}/cover")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get("/admin/achievements/{$event->id}/cover")->assertNotFound();
        $this->put("/admin/tournaments/{$event->id}", $fields + ['cover' => UploadedFile::fake()->create('invalid.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('cover');
        Storage::disk('local')->assertExists($first);
        $this->put("/admin/tournaments/{$event->id}", $fields + ['cover' => UploadedFile::fake()->image('new.png')])
            ->assertSessionHasNoErrors();
        $second = $event->refresh()->payload['cover'];
        Storage::disk('local')->assertMissing($first)->assertExists($second);
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get("/admin/tournaments/{$event->id}/cover")->assertForbidden();
        $this->actingAs($admin)->delete("/admin/tournaments/{$event->id}")->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($second);
    }

    public function test_monthly_dues_are_unique_and_payment_posts_to_the_ledger_once(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $active = AdminRecord::create(['section' => 'students', 'title' => 'Siswa QA', 'status' => 'active']);
        AdminRecord::create(['section' => 'students', 'title' => 'Siswa tidak aktif', 'status' => 'inactive']);

        $request = ['month' => '2026-11', 'amount' => '150000.50'];
        $this->post('/admin/dues/generate', $request)->assertRedirect('/admin/dues?month=2026-11');
        $this->post('/admin/dues/generate', ['amount' => '999000'] + $request)->assertRedirect('/admin/dues?month=2026-11');
        $due = AdminRecord::where('section', 'dues')->sole();
        $this->assertSame($active->id, $due->student_id);
        $this->assertSame('150000.50', $due->amount);
        $this->get('/admin/dues?month=2026-11&status=unpaid&q=Siswa')->assertOk()->assertSee('Siswa QA');

        $paidOn = now('Asia/Jayapura')->toDateString();
        $this->post("/admin/dues/{$due->id}/pay", ['paid_on' => $paidOn])->assertSessionHasNoErrors();
        $this->post("/admin/dues/{$due->id}/pay", ['paid_on' => $paidOn])->assertSessionHasNoErrors();
        $ledger = AdminRecord::where('section', 'cashbook')->sole();
        $this->assertSame('paid', $due->refresh()->status);
        $this->assertSame('150000.50', $ledger->amount);
        $this->assertSame($due->id, $ledger->payload['dues_id']);
        $this->put("/admin/cashbook/{$ledger->id}", ['status' => 'expense'])->assertForbidden();
        $this->delete("/admin/students/{$active->id}")->assertSessionHasErrors('student');
        $this->assertModelExists($active);
    }

    public function test_invalid_statuses_and_missing_cash_values_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post('/admin/tournaments', ['title' => 'Invalid', 'status' => 'paid'])->assertSessionHasErrors('status');
        $this->post('/admin/cashbook', ['title' => 'Invalid', 'status' => 'income'])->assertSessionHasErrors(['date', 'amount']);
        $this->get('/admin/tournaments?q[]=foo&status[]=foo')->assertOk();
        $this->get('/admin/dues?month[]=foo')->assertOk();
        $this->get('/admin/tournaments/not-a-number/cover')->assertNotFound();
    }
}
