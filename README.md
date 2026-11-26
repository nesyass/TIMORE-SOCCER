# Timore Soccer School

Repositori ini menjalankan satu aplikasi Laravel. File dashboard admin memakai penanda `Admin` pada nama class PHP dan `admin` pada folder tampilan serta aset. Website publik dan portal lain bisa memakai route, controller, dan tampilan mereka sendiri dalam aplikasi yang sama.

| Bagian | Letak |
| --- | --- |
| Route dashboard | [routes/admin.php](routes/admin.php), dipanggil dari `routes/web.php` |
| Backend | `app/Http/Controllers/Admin*.php`, `app/Http/Middleware/EnsureAdmin.php`, `app/Models/Admin*.php`, `app/Services/Admin*.php` |
| Tampilan dan aset | `resources/views/admin`, `resources/views/components/admin`, `resources/js/admin`, `public/assets/admin` |
| Database dan tes | `database/migrations`, `database/seeders/Admin*.php`, `tests/Feature/Admin*Test.php` |

Migrasi memakai nama tabel atau perubahan skema dan nama file yang sudah tercatat di MySQL. Lihat [skema database admin](docs/ADMIN_DATABASE.md).

## Menjalankan dashboard admin secara lokal

1. Siapkan PHP 8.3 dengan ekstensi `pdo_mysql`, Composer, dan server MySQL 8.4. Buat database dan pengguna MySQL lokal.
2. Jalankan `composer install`. Salin `.env.example` menjadi `.env`, lalu isi `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`. Isi juga `TIMORE_ADMIN_EMAIL` dan `TIMORE_ADMIN_PASSWORD` (minimal 12 karakter) untuk membuat admin lokal.
3. Jalankan perintah berikut dari root Laravel.

   ```sh
   php artisan key:generate
   php artisan migrate
   php artisan db:seed --class="Database\Seeders\AdminSeeder"
   php artisan serve
   ```

4. Buka `/admin/login` pada URL yang ditampilkan Artisan.

Untuk menjalankan jadwal kedaluwarsa pendaftaran saat pengembangan, jalankan `php artisan schedule:work` di terminal kedua. Untuk membangun ulang JavaScript admin dari root Laravel, jalankan `npm --prefix resources/js/admin ci` lalu `npm --prefix resources/js/admin run build`. Hasil build berada di `public/assets/admin/js/enhancements`.

Jalankan `php artisan test --filter=Admin` dengan database MySQL khusus tes, misalnya `timore_admin_test`, dan akun yang hanya punya izin pada database tes. Isi variabel koneksi MySQL tes di terminal sebelum menjalankan perintah.

## Berkolaborasi lewat GitHub

Integrasikan file admin melalui branch yang dibuat dari versi terbaru repo tim. Gabungkan pemanggilan route admin pada `routes/web.php` dengan route publik yang sudah ada. Dashboard menggunakan model `App\Models\User` dan guard sesi `web`. Pertahankan cast boolean `is_admin` dan biarkan atribut itu di luar `$fillable`. Cocokkan migrasi dengan skema tim sebelum dijalankan. Simpan `.env`, data MySQL, file unggahan, dan dependensi hasil instalasi di lingkungan masing-masing.
