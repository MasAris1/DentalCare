# Panduan Demo Publik DentalCare

Panduan ini untuk presentasi kampus dengan aplikasi berjalan dari laptop memakai Docker Compose dan dibuka publik lewat tunnel.

## Pilihan Tunnel

Untuk tanpa domain, gunakan ngrok:

```bash
ngrok http 8080
```

Ambil URL `https://...` dari output ngrok, lalu jalankan:

```bash
make presentation-apply
make public-url URL=https://contoh-url-ngrok.ngrok-free.app
make demo
make presentation-check
```

Untuk Cloudflare Tunnel, paling nyaman jika sudah punya domain di Cloudflare. Jika belum punya domain, ngrok lebih praktis untuk presentasi.

## Syarat Agar Dosen Bisa Buka Kapan Saja

Laptop harus tetap menyala, Docker Desktop/Engine tetap berjalan, container tetap running, dan proses tunnel tetap aktif. Jika laptop mati, sleep, restart, atau koneksi internet putus, link publik tidak bisa diakses.

Cek aplikasi lokal:

```bash
curl -I http://localhost:8080
```

Cek container:

```bash
docker compose ps
```

## Setup Gmail SMTP untuk OTP

Gunakan Gmail App Password, bukan password utama akun Gmail.

1. Aktifkan 2-Step Verification di akun Google.
2. Buat App Password untuk aplikasi mail.
3. Isi `.env`:

```env
APP_ENV=production
APP_DEBUG=false

MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME="emailkamu@gmail.com"
MAIL_PASSWORD="app-password-16-karakter"
MAIL_FROM_ADDRESS="emailkamu@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Jika Gmail menolak pengiriman, pastikan `MAIL_FROM_ADDRESS` sama dengan `MAIL_USERNAME`, kecuali akun Gmail sudah punya alias pengirim yang terverifikasi.

Untuk mengisi default aman presentasi secara otomatis, jalankan:

```bash
make presentation-apply
```

Setelah itu edit hanya bagian ini di `.env`:

```env
MAIL_USERNAME="emailkamu@gmail.com"
MAIL_PASSWORD="app-password-16-karakter"
MAIL_FROM_ADDRESS="emailkamu@gmail.com"
```

## Setup URL Publik

Setiap kali URL tunnel berubah, jalankan:

```bash
make public-url URL=https://url-baru.example
```

Command ini akan memperbarui:

```env
APP_URL
GOOGLE_REDIRECT_URI
MIDTRANS_CALLBACK_URL
```

Lalu restart demo:

```bash
make demo
```

## Midtrans Sandbox

Untuk presentasi sederhana, key Midtrans boleh dikosongkan. Aplikasi akan memakai fallback mock dan booking tetap bisa dibuat tanpa redirect pembayaran nyata.

Jika ingin demo checkout Midtrans sandbox sungguhan, isi:

```env
MIDTRANS_MERCHANT_ID="merchant-id-sandbox"
MIDTRANS_SERVER_KEY="server-key-sandbox"
MIDTRANS_CLIENT_KEY="client-key-sandbox"
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_CALLBACK_URL="https://url-tunnel/payments/midtrans/callback"
```

Di dashboard Midtrans sandbox, arahkan payment notification URL ke nilai `MIDTRANS_CALLBACK_URL`.

## Google Login

Google login opsional untuk presentasi. Jika belum dibutuhkan, kosongkan:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

Jika ingin mengaktifkan Google login, buat OAuth Client dan masukkan redirect URL ini:

```text
https://url-tunnel/auth/google/callback
```

Jika URL ngrok berubah, update OAuth redirect di Google Cloud juga. Untuk menghindari repot, gunakan static ngrok domain atau matikan Google login saat presentasi.

## Checklist Sebelum Presentasi

```bash
make verify
make presentation-apply
make public-url URL=https://url-tunnel-kamu
make demo
make presentation-check
curl -I http://localhost:8080
```

Buka URL tunnel di browser lain atau HP dengan jaringan berbeda untuk memastikan link publik benar-benar bisa diakses.

## Command Harian

```bash
make demo
make logs
make verify
make down
```
