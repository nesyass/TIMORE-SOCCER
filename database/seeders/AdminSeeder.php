<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;
class AdminSeeder extends Seeder
{
 public function run():void {
  $email=env('TIMORE_ADMIN_EMAIL'); $password=env('TIMORE_ADMIN_PASSWORD');
  if (!$email || !$password || strlen($password)<12) { $this->command?->warn('Admin tidak dibuat. Isi TIMORE_ADMIN_EMAIL dan TIMORE_ADMIN_PASSWORD minimal 12 karakter di .env.'); return; }
  $user = User::firstOrNew(['email' => $email]);
  $user->forceFill([
      'name' => 'Timore Administrator',
      'password' => $password,
      'is_admin' => true,
  ])->save();
 }
}
