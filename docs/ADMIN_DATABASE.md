# Database Dashboard Admin Timore Soccer School

Dashboard admin memakai MySQL Community Server 8.4.13 di laptop. Basis data bernama `timore_admin`, berjalan di `127.0.0.1:3306`, dan berisi **11 tabel**. Seluruh tabel menggunakan InnoDB dan `utf8mb4_unicode_ci`. Jumlah baris di bawah diambil pada 26 November 2026 dari database lokal yang dipakai dashboard.

Pendaftaran, siswa, kegiatan, konten, dan keuangan saat ini disimpan di satu tabel bernama `records`. Kolom `section` menunjukkan modulnya. Akun admin berada di `users`, sementara akun siswa berada di `student_accounts`.

## Daftar tabel

| Tabel | Baris | Isi dan fungsi |
|---|---:|---|
| `records` | 312 | Catatan operasional dan konten dashboard. |
| `student_accounts` | 48 | Username siswa, hash sandi, dan penanda ganti sandi sementara. |
| `users` | 1 | Login admin, hash sandi, dan penanda `is_admin`. |
| `migrations` | 9 | Nama dan batch perubahan struktur yang sudah diterapkan Laravel. |
| `cache` | 2 | Nilai sementara Laravel, misalnya pembatasan percobaan login. |
| `cache_locks` | 0 | Kunci sementara untuk koordinasi proses. |
| `jobs` | 0 | Pekerjaan yang menunggu diproses di antrean database. |
| `job_batches` | 0 | Ringkasan kelompok pekerjaan antrean. |
| `failed_jobs` | 0 | Pekerjaan antrean yang gagal beserta informasi error. |
| `password_reset_tokens` | 0 | Tabel bawaan Laravel untuk token pemulihan akun. Reset sandi siswa dari admin memperbarui `student_accounts`. |
| `sessions` | 0 | Skema bawaan untuk sesi database. Dashboard lokal saat ini memakai `SESSION_DRIVER=file`. |

## Bagian di dalam tabel records

| Nilai `section` | Baris | Contoh isi |
|---|---:|---|
| `registrations` | 70 | Nama calon siswa, status, nominal, kelompok, kontak, waktu verifikasi, dan rujukan bukti bayar. |
| `students` | 48 | Profil siswa, kelompok usia, status aktif, tanggal bergabung, dan rujukan pendaftaran. |
| `coaches` | 8 | Nama, jabatan, foto, ringkasan, pengalaman, lisensi, dan tautan sosial. |
| `schedules` | 7 | Catatan jadwal latihan rutin dan informasi sesi. |
| `schedule-changes` | 6 | Penyesuaian jadwal dan tanggal terkait. |
| `events` | 2 | Kegiatan umum, lokasi, poster, dan status publikasi. |
| `tournaments` | 8 | Turnamen TSS, tanggal, tempat, kategori, biaya, dan periode pendaftaran. |
| `team-registrations` | 13 | Tim dari SSB eksternal, kategori, PIC, status pembayaran, dan bukti bayar jika ada. Empat baris sudah terkait langsung ke turnamen. |
| `cashbook` | 79 | Pemasukan dan pengeluaran, termasuk posting dari pembayaran siswa, tim, dan iuran. |
| `dues` | 42 | Tagihan dan status pembayaran iuran bulanan. Seluruh baris pada snapshot ini terkait ke siswa. |
| `certificates` | 6 | Catatan sertifikat untuk daftar dan pratinjau admin. |
| `articles` | 6 | Judul, isi, ringkasan, kategori, penulis, dan gambar sampul berita. |
| `achievements` | 6 | Prestasi, peringkat, kompetisi, kelompok usia, ringkasan, dan dokumentasi. |
| `gallery` | 5 | Kartu album, gambar sampul, keterangan, dan kredit foto. |
| `site-content` | 6 | Draf atau catatan konten per bagian website, termasuk tujuan tombol jika diisi. |

## Kolom utama dan hubungan data

### records

| Kolom | Tipe MySQL | Keterangan |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | Primary key dan auto-increment. |
| `section` | `VARCHAR(50)` | Jenis catatan. Diindeks untuk filter per modul. |
| `title` | `VARCHAR(255)` | Nama siswa, tim, kegiatan, atau judul konten. |
| `subtitle` | `VARCHAR(255)`, opsional | Keterangan atau referensi yang tampil di admin. |
| `status` | `VARCHAR(30)` | Diindeks. Nilai awal bawaan skema `draft`; nilai yang boleh dipakai ditentukan per modul. |
| `amount` | `DECIMAL(14,2)`, opsional | Nominal tagihan, biaya, atau transaksi dalam rupiah. |
| `date` | `DATE`, opsional | Tanggal bisnis. Artinya mengikuti modul, misalnya tanggal dana diterima untuk Buku Kas. |
| `payload` | `JSON`, opsional | Atribut tambahan tiap modul, seperti waktu verifikasi, lokasi file, pengalaman pelatih, atau ID sumber transaksi. |
| `created_at`, `updated_at` | `TIMESTAMP`, opsional | Waktu pencatatan dan perubahan. |
| `student_id` | `BIGINT UNSIGNED`, opsional | Pada iuran, merujuk ke baris siswa di `records.id`. |
| `billing_month` | `DATE`, opsional | Periode iuran, disimpan sebagai tanggal pertama bulan. |
| `tournament_id` | `BIGINT UNSIGNED`, opsional | Pada pendaftaran tim, merujuk ke baris turnamen di `records.id`. |
| `team_entry_key` | `CHAR(64)`, opsional | Nilai unik dari turnamen, klub, tim, dan kategori yang dinormalisasi untuk mencegah pendaftaran ganda. |
| `student_registration_source` | `VARCHAR(20)`, dihasilkan MySQL | ID pendaftaran yang diambil dari JSON pada baris siswa. Indeks unik memastikan satu pendaftaran menghasilkan maksimal satu siswa. |
| `cash_payment_source` | `VARCHAR(32)`, dihasilkan MySQL | Kunci sumber pembayaran otomatis, seperti `REG-12`. Indeks unik memastikan satu sumber menghasilkan maksimal satu pemasukan. Referensi yang diketik di `subtitle` tidak ikut menentukan kunci ini. |

Kombinasi `student_id` dan `billing_month` unik ketika keduanya diisi. Satu siswa bisa punya iuran di banyak bulan. Satu turnamen bisa punya banyak tim.

### student_accounts dan users

| Tabel | Kolom | Keterangan |
|---|---|---|
| `student_accounts` | `id` | `BIGINT UNSIGNED`, primary key, auto-increment. |
| `student_accounts` | `student_id` | `BIGINT UNSIGNED`, unik, foreign key ke `records.id`. |
| `student_accounts` | `username` | `VARCHAR(32)`, unik. |
| `student_accounts` | `password` | `VARCHAR(255)`, berisi hash sandi. |
| `student_accounts` | `must_change_password` | Boolean, bawaan aktif untuk akses sementara. |
| `student_accounts` | `created_at`, `updated_at` | Timestamp Laravel. |
| `users` | `id` | `BIGINT UNSIGNED`, primary key, auto-increment. |
| `users` | `name`, `email`, `password` | Masing-masing `VARCHAR(255)`. Email unik dan password tersimpan sebagai hash. |
| `users` | `is_admin` | Boolean. Laravel menggunakan nilainya untuk izin dashboard admin. |
| `users` | `email_verified_at`, `remember_token` | Metadata autentikasi Laravel, opsional. |
| `users` | `created_at`, `updated_at` | Timestamp Laravel. |

### Foreign key dan rujukan JSON

`student_accounts.student_id`, `records.student_id`, dan `records.tournament_id` adalah foreign key MySQL dengan aturan `RESTRICT` saat baris induk akan dihapus. Rujukan lain di `payload` dikelola Laravel. Contohnya, `payload.registration_id` pada siswa atau transaksi mengarah ke ID pendaftaran, `payload.team_registration_id` mengarah ke ID pendaftaran tim, dan `payload.dues_id` mengarah ke ID tagihan iuran. Integrasi antarmodul perlu mempertahankan pemisahan antara foreign key dan rujukan JSON ini.

### Kolom tabel pendukung Laravel

| Tabel | Kolom |
|---|---|
| `migrations` | `id`, `migration`, `batch` |
| `cache` | `key`, `value`, `expiration` |
| `cache_locks` | `key`, `owner`, `expiration` |
| `jobs` | `id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at` |
| `job_batches` | `id`, `name`, `total_jobs`, `pending_jobs`, `failed_jobs`, `failed_job_ids`, `options`, `cancelled_at`, `created_at`, `finished_at` |
| `failed_jobs` | `id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at` |
| `password_reset_tokens` | `email`, `token`, `created_at` |
| `sessions` | `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity` |

## Contoh perpindahan data saat admin bekerja

1. **Pendaftaran siswa dibayar.** Laravel mengunci baris pendaftaran selama verifikasi. Saat berhasil, satu transaksi database memperbarui status, membuat profil, menerbitkan akun, dan mencatat pemasukan.
2. **Pendaftaran tim dibayar.** Sistem memperbarui baris tim yang terkait ke turnamennya, menyimpan bukti jika diunggah, dan mencatat pemasukan. Alur tim berakhir pada status tim terkonfirmasi.
3. **Iuran dibayar.** Baris tagihan menjadi lunas dan satu pemasukan terkait ditambahkan. Tagihan yang belum lunas tetap menjadi piutang.

Foto dan bukti bayar disimpan sebagai file pada disk privat Laravel di `storage/app/private`. MySQL menyimpan rujukan lokasinya. Saat menyiapkan backup yang bisa dipulihkan, salin database **dan** file unggahan bersama-sama. Untuk membuat skema baru, gunakan migrasi di [database/migrations](../database/migrations).

## Melihat isi database sendiri

### Opsi grafis dengan MySQL Workbench

1. Buka MySQL Workbench dan klik **+** di sebelah **MySQL Connections**.
2. Pilih **Standard (TCP/IP)**. Isi hostname `127.0.0.1`, port `3306`, username sesuai `DB_USERNAME`, dan default schema sesuai `DB_DATABASE`.
3. Saat diminta kata sandi koneksi, pakai nilai `DB_PASSWORD` dari file `.env` di root Laravel.
4. Pilih **Test Connection**, buka koneksi, lalu cari **Schemas → timore_admin → Tables**. Untuk belajar, pakai query `SELECT` di bawah agar tampilan hasil hanya memuat kolom yang diperlukan.

[Unduhan resmi MySQL Workbench](https://dev.mysql.com/downloads/workbench/) · [Panduan koneksi TCP/IP](https://dev.mysql.com/doc/workbench/en/wb-mysql-connections-methods-standard.html)

### Opsi terminal dengan klien MySQL

Jalankan dari PowerShell. Opsi `-p` membuat MySQL meminta sandi tanpa menuliskannya di perintah. Server MySQL lokal perlu aktif.

```powershell
mysql --host=127.0.0.1 --port=3306 --user=timore_app --get-server-public-key -p timore_admin
```

```sql
SHOW TABLES;
DESCRIBE records;
DESCRIBE student_accounts;

SELECT section, COUNT(*) AS jumlah
FROM records
GROUP BY section
ORDER BY section;

SELECT s.id, s.title AS nama_siswa, s.status, a.username
FROM records AS s
LEFT JOIN student_accounts AS a ON a.student_id = s.id
WHERE s.section = 'students'
ORDER BY s.id DESC
LIMIT 10;

SELECT e.title AS tim, t.title AS turnamen, e.status
FROM records AS e
JOIN records AS t ON t.id = e.tournament_id
WHERE e.section = 'team-registrations'
ORDER BY e.id DESC
LIMIT 10;

SELECT date, status, title, amount
FROM records
WHERE section = 'cashbook'
ORDER BY date DESC, id DESC
LIMIT 10;
```
