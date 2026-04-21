# DentalCare Lite

DentalCare Lite adalah aplikasi reservasi klinik gigi berbasis Laravel 11 dengan tiga area utama:

- Publik: landing page, katalog layanan, daftar dokter
- Pasien: booking, pembayaran Midtrans, riwayat, invoice, resume medis
- Internal: dashboard dokter, input resume medis, admin report, master data dokter, layanan, dan jadwal

## Fitur utama

- Reservasi online berdasarkan dokter, layanan, tanggal, dan slot yang tersedia
- Validasi slot dengan mempertimbangkan durasi layanan dan kuota harian dokter
- Integrasi pembayaran Midtrans Snap dan webhook callback
- Autentikasi dua faktor via OTP email pada setiap login
- Login dan register pasien via Google
- Invoice PDF dan resume medis PDF
- Notifikasi database dan email untuk booking, pembayaran, dan resume medis
- Dashboard admin dengan statistik dan grafik pendapatan
- Audit fields standar perusahaan pada tabel utama

## Stack

- PHP 8.2+
- Laravel 11
- Blade + Bootstrap 5 + Vite
- MySQL 8
- Midtrans Snap
- DomPDF
- Docker Compose

## Quick start dengan Docker

1. Salin environment:

```bash
cp .env.example .env
```

2. Build dan jalankan container:

```bash
make build
make up
```

3. Install dependency dan siapkan database:

```bash
make install
```

4. Akses aplikasi di `http://localhost:8080`

Container `queue` ikut berjalan saat `docker compose up -d` dan memproses email OTP serta notifikasi lain dari tabel `jobs`.

## Menjalankan tanpa Docker

1. Install dependency:

```bash
composer install
npm install
```

2. Siapkan environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Atur database MySQL di `.env`, lalu jalankan:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

## Akun demo hasil seeder

- Admin: `admin@dentalcare.test` / `password`
- Pasien: `patient@dentalcare.test` / `password`
- Dokter 1: `dr.aji@dentalcare.test` / `password`
- Dokter 2: `dr.salsa@dentalcare.test` / `password`
- Dokter 3: `dr.rizky@dentalcare.test` / `password`

## Environment penting

- `MIDTRANS_SERVER_KEY`
- `MIDTRANS_CLIENT_KEY`
- `MIDTRANS_IS_PRODUCTION`
- `MIDTRANS_CALLBACK_URL`
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`
- `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS` gunakan alamat resmi yang sudah diverifikasi di provider email, misalnya `noreply@dentalcarelite.id`
- `LOGIN_OTP_DIGITS`, `LOGIN_OTP_EXPIRES_MINUTES`, `LOGIN_OTP_MAX_ATTEMPTS`
- `APPOINTMENT_SLOT_MINUTES`

Jika key Midtrans belum diisi, aplikasi tetap bisa membuat booking untuk demo, tetapi pembayaran eksternal tidak akan berjalan penuh.

Untuk Google login, buat OAuth Client di Google Cloud Console lalu arahkan callback ke URL absolut aplikasi Anda, misalnya `http://localhost:8080/auth/google/callback`.

Setiap login password atau Google akan meminta OTP email. Untuk production, gunakan mailer SMTP/transactional email sungguhan dan isi `MAIL_FROM_ADDRESS`, `MAIL_USERNAME`, serta `MAIL_PASSWORD` di `.env`. Jika memakai Gmail, gunakan App Password, bukan password akun utama. Untuk klinik/domain publik, provider seperti Amazon SES, Mailgun, SendGrid, Brevo, atau SMTP hosting domain lebih disarankan daripada akun Gmail personal.

Deploy production wajib menjalankan migration dan restart queue:

```bash
make deploy
```

Jika tidak memakai `make`, jalankan:

```bash
docker compose up -d --build mysql app nginx
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan queue:restart
docker compose up -d --build queue
```

## Testing

Menjalankan test suite:

```bash
php artisan test
```

Ekstensi PHP yang dibutuhkan PHPUnit antara lain `dom`, `xml`, dan `xmlwriter`.

## Struktur area

- `/` halaman publik
- `/booking/create` form booking
- `/history` riwayat booking pasien
- `/doctor/dashboard` dashboard dokter
- `/doctor/medical-notes` resume medis
- `/admin/reports` laporan admin
- `/admin/payments` monitoring pembayaran

## Catatan implementasi

- Semua tabel utama memakai 7 audit fields melalui helper schema dan trait model.
- Booking aktif dibatasi unique slot per dokter, tanggal, dan jam untuk mengurangi double booking.
- Invoice hanya bisa diunduh setelah pembayaran berstatus `paid`.

## Demo Publik untuk Presentasi

Untuk membuka aplikasi dari laptop melalui ngrok atau Cloudflare Tunnel, gunakan panduan di `docs/PRESENTATION_DEMO.md`.

Command utama:

```bash
make presentation-apply
make public-url URL=https://url-tunnel-kamu
make demo
make presentation-check
```
