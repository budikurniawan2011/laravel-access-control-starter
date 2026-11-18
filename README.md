# Laravel Access Control Starter

Starter template untuk aplikasi web dengan panel administrasi berbasis Filament. Fitur utamanya adalah pengelolaan user, role, permission, dan audit aktivitas.

## Teknologi

- PHP `^8.3`
- Laravel `^13.17`
- Filament `^5.11`
- Livewire `^4.1`
- Spatie Laravel Permission `^8.5`
- Spatie Laravel Activitylog `^5.1`
- Tailwind CSS `^4`
- Vite Plus `1.4`

Versi paket yang benar-benar terpasang tercatat di `composer.lock` dan `package-lock.json`.

## Menjalankan secara lokal

Prasyarat: PHP 8.3+, Composer, Node.js, dan npm.

1. Pasang dependensi, buat file environment dan application key, jalankan migrasi, serta build aset:

   ```powershell
   composer run setup
   ```

2. Pastikan `.env` memakai `APP_ENV=local`. Secara default, proyek menggunakan SQLite (`DB_CONNECTION=sqlite`). Atur koneksi database lain di `.env` bila diperlukan.
3. Jalankan aplikasi:

   ```powershell
   composer run dev
   ```

4. Buka `http://localhost:8000/setup`, lalu buat akun super_admin pertama. Password setup harus sedikitnya 12 karakter dan mengandung huruf besar, huruf kecil, angka, serta simbol.
5. Setelah setup, masuk melalui `http://localhost:8000/admin/login`.

Setup wizard hanya dapat diselesaikan satu kali. Setelah selesai, kunjungan ke `/setup` akan diarahkan ke login panel.

## Role dan akses

| Role | Akses |
| --- | --- |
| `super_admin` | Akses penuh ke panel; dapat mengelola user, role, dan permission serta melihat activity log. Dapat memberikan role `admin` atau `user` kepada user lain. |
| `admin` | Dapat masuk ke panel, mengelola akun ber-role `user`, melihat daftar role dan activity log. Hanya dapat memberikan role `user`; tidak dapat mengelola definisi role atau permission, maupun mengedit atau menghapus akun admin/super_admin. |
| `user` | Tidak memiliki akses ke panel `/admin` secara default. |

Semua akun harus aktif untuk mengakses panel. Super_admin dibuat oleh setup wizard; form pengelolaan user tidak menyediakan role `super_admin`.

Dashboard menampilkan statistik jumlah user, role, dan permission untuk super_admin. Dashboard admin menampilkan jumlah user aktif dan nonaktif. Tidak ada panel terpisah untuk role `user`.

### Password user

Email belum dikonfigurasi untuk alur reset password. Untuk sementara, pengelola yang berwenang dapat menetapkan password baru melalui form edit user:

- Admin hanya dapat mereset password akun `user`.
- Super_admin dapat mereset password akun `user` dan `admin`, serta password akunnya sendiri.
- Password akun super_admin lain tidak dapat direset melalui form.
- Password lama tidak ditampilkan. Kosongkan field password saat mengedit jika tidak ingin menggantinya.

Field password pada form pengelolaan user mensyaratkan minimal 12 karakter. Ini berbeda dari validasi password yang lebih ketat pada setup awal.

## Setup untuk production

Sebelum membuka aplikasi ke publik:

1. Siapkan database production dan atur `DB_*` di `.env`.
2. Atur nilai production, termasuk:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.example
   ```

3. Atur `INSTALLATION_SETUP_KEY` ke nilai rahasia yang kuat melalui environment server atau secret manager. Jangan commit nilai rahasia ke repository.
4. Jalankan migrasi:

   ```powershell
   php artisan migrate --force
   ```

5. Buka `/setup`, buat akun super_admin pertama, dan masukkan setup key.
6. Setelah setup, endpoint akan terkunci oleh status instalasi. Hapus atau rotasi `INSTALLATION_SETUP_KEY` dari konfigurasi server setelah selesai.
7. Pastikan HTTPS aktif dan backup database tersimpan dengan aman.

Di production, setup wizard menolak proses setup jika `INSTALLATION_SETUP_KEY` belum disetel. Jangan menggunakan database SQLite lokal atau kredensial pengembangan untuk production.

## Role dan permission bawaan

Daftar role serta permission bawaan didefinisikan di `database/seeders/AccessControlSeeder.php`. Initial setup menjalankan seeder akses secara otomatis sebelum membuat super_admin.

Untuk mengisi ulang atau menyelaraskan role dan permission bawaan pada lingkungan yang sudah ada:

```powershell
php artisan db:seed --class=AccessControlSeeder
```

Perintah tersebut tidak menghapus data user. Tinjau perubahan seeder dan cadangkan database sebelum menjalankannya di production.

## Audit log

Activity log mencatat perubahan atribut user (tanpa password), perubahan role/permission, serta kejadian login, logout, dan kegagalan login. Activity log tersedia bagi admin dan super_admin. Data kredensial login tidak disimpan dalam log.

## Email dan reset mandiri

Konfigurasi email belum disiapkan. `.env.example` menggunakan `MAIL_MAILER=log`, sehingga email tidak dikirim ke alamat tujuan. Sebelum mengaktifkan reset password mandiri, konfigurasikan mailer dan uji pengiriman email terlebih dahulu.

## Pengujian dan pemeriksaan

Jalankan seluruh tes:

```powershell
php artisan test --compact
```

Pemeriksaan format dan tipe:

```powershell
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress
```

Atau gunakan script Composer yang menjalankan pemeriksaan proyek:

```powershell
composer test
```

## Lokasi kode penting

- `app/Providers/Filament/AdminPanelProvider.php` — konfigurasi panel admin.
- `app/Filament/Resources/Users/UserResource.php` — form dan tabel user.
- `app/Filament/Resources/Roles/RoleResource.php` — pengelolaan role.
- `app/Filament/Resources/Permissions/PermissionResource.php` — pengelolaan permission.
- `app/Filament/Resources/Activities/ActivityResource.php` — activity log.
- `app/Policies/` — kebijakan akses model.
- `app/Rules/RestrictUserRoleAssignment.php` — batas role yang dapat diberikan melalui form user.
- `app/Http/Controllers/SetupController.php` — setup super_admin pertama.
- `app/Filament/Widgets/` — statistik dashboard berdasarkan role.
- `database/seeders/AccessControlSeeder.php` — role dan permission bawaan.
