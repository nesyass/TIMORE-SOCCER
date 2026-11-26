<?php
namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }
    public function test_admin_login_keeps_public_login_redirect_separate(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->withSession(['url.intended' => url('/portal')])
            ->get('/admin/registrations?status=pending')
            ->assertRedirect(route('admin.login'));

        $this->post(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(url('/admin/registrations?status=pending'))
            ->assertSessionHas('url.intended', url('/portal'));

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest('web');
    }

    public function test_user_mass_assignment_cannot_grant_admin_access(): void
    {
        $user = new User(['is_admin' => true]);

        $this->assertNull($user->is_admin);
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $user=User::factory()->create(['is_admin'=>false]);
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
    public function test_admin_can_access_dashboard_and_create_record(): void
    {
        $user=User::factory()->create(['is_admin'=>true]);
        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->post('/admin/coaches',['title'=>'Coach Example','status'=>'active'])->assertRedirect();
        $this->assertDatabaseHas('records',['section'=>'coaches','title'=>'Coach Example']);
    }
    public function test_registration_verification_is_idempotent(): void
    {
        $user=User::factory()->create(['is_admin'=>true]);
        $r=AdminRecord::create(['section'=>'registrations','title'=>'Calon Siswa','status'=>'waiting_payment','amount'=>250000]);
        $this->actingAs($user)->post("/admin/registrations/{$r->id}/verify", [
            'paid_at' => now('Asia/Jayapura')->format('Y-m-d\TH:i'), 'amount' => '250000', 'confirmed' => '1',
        ])->assertRedirect()->assertSessionHas('student_access');
        $this->actingAs($user)->post("/admin/registrations/{$r->id}/verify")->assertRedirect();
        $this->assertSame(1,AdminRecord::where('section','students')->where('subtitle','REG-'.$r->id)->count());
        $this->assertSame(1,AdminRecord::where('section','cashbook')->where('subtitle','REG-'.$r->id)->count());
        $this->assertDatabaseCount('student_accounts', 1);
    }

    public function test_overview_and_filtered_lists_count_the_same_registrations(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-23 12:00:00', 'UTC'));
        $admin = User::factory()->create(['is_admin' => true]);
        AdminRecord::create(['section' => 'registrations', 'title' => 'Perlu dicek', 'status' => 'payment_review']);
        AdminRecord::create(['section' => 'registrations', 'title' => 'Menunggu', 'status' => 'pending']);
        AdminRecord::create(['section' => 'registrations', 'title' => 'Sudah aktif', 'status' => 'active']);

        $this->actingAs($admin)->get('/admin?months=6')
            ->assertOk()
            ->assertViewHas('counts', fn ($counts) => $counts['registrations'] === 2 && (int) $counts['payments'] === 1)
            ->assertViewHas('trend', fn ($points) => $points->count() === 6 && $points->sum('total') === 3);
        $this->get('/admin/registrations?status=attention&period=current')
            ->assertViewHas('records', fn ($records) => $records->total() === 2);
        $this->get('/admin/registrations?status=pending&period=current')
            ->assertViewHas('records', fn ($records) => $records->total() === 1);
        $this->get('/admin?period=previous')
            ->assertViewHas('counts', fn ($counts) => $counts['registrations'] === 0);
    }
}
