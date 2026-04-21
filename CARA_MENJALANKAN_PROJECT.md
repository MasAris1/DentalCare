# Panduan Internal Menjalankan Project DentalCare Lite

Dokumen ini untuk tim internal development. Isinya mencakup cara menjalankan project dengan Docker Engine, Docker Desktop, tanpa Docker, cara menghentikan project, quick start, dan hasil testing yang sudah dilakukan.

## Quick Start

### Dengan Docker

```bash
cd /media/arismaulana/Linux/Project/Project/Putri Alisha/DentalCare
cp .env.example .env
docker compose up -d --build
docker compose exec app sh -lc 'composer install && npm install && php artisan key:generate && php artisan migrate --seed'
```

Buka aplikasi di `http://localhost:8080`.

Service `queue` ikut berjalan untuk memproses email OTP dan notifikasi. Jika ada perubahan kode notifikasi atau deploy production, restart worker:

```bash
docker compose exec app php artisan queue:restart
```

Untuk menghentikan:

```bash
docker compose down
```

### Tanpa Docker

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Buka aplikasi di `http://127.0.0.1:8000`.

Untuk menghentikan:
- Tekan `Ctrl+C` pada terminal `php artisan serve`
- Jika `npm run dev` aktif, tekan `Ctrl+C` juga pada terminal Vite

## Informasi Port dan Service

Saat berjalan dengan Docker, service utamanya:
- `app`: PHP-FPM dan Node.js
- `nginx`: web server
- `mysql`: database

Port default:
- aplikasi web: `8080`
- MySQL host: `3307`
- Vite dev server: `5173`

## Menjalankan Dengan Docker Engine

Metode ini cocok untuk Linux atau environment yang memakai Docker Engine dan Docker Compose Plugin.

### Prasyarat

- Docker Engine terpasang
- Docker Compose Plugin tersedia
- Port `8080`, `3307`, dan `5173` tidak dipakai aplikasi lain

### Langkah

1. Masuk ke folder project:

```bash
cd /media/arismaulana/Linux/Project/Project/Putri Alisha/DentalCare
```

2. Siapkan environment:

```bash
cp .env.example .env
```

3. Build image dan nyalakan container:

```bash
docker compose up -d --build
```

4. Install dependency dan siapkan Laravel:

```bash
docker compose exec app sh -lc 'composer install && npm install && php artisan key:generate && php artisan migrate --seed'
```

5. Verifikasi container:

```bash
docker compose ps
```

6. Buka aplikasi di `http://localhost:8080`.

### Perintah Penting

```bash
docker compose ps
docker compose logs -f
docker compose exec app sh
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app npm run build
docker compose exec app php artisan queue:restart
```

### Mode Development Frontend Dengan Docker

Jika ingin hot reload frontend:

```bash
docker compose exec app npm run dev -- --host 0.0.0.0
```

Catatan:
- Laravel tetap diakses lewat `http://localhost:8080`
- Vite dev server tersedia di `http://localhost:5173`
- `npm run dev` tidak wajib untuk mode biasa

### Cara Menghentikan Docker Engine

1. Hentikan Vite jika sedang berjalan dengan `Ctrl+C`
2. Turunkan stack:

```bash
docker compose down
```

Alternatif:

```bash
docker compose stop
```

Jika ingin reset volume database:

```bash
docker compose down -v
```

Gunakan `down -v` dengan hati-hati karena akan menghapus volume database container.

## Menjalankan Dengan Docker Desktop

Metode ini cocok untuk Windows, macOS, atau Linux yang memakai Docker Desktop.

### Prasyarat

- Docker Desktop terpasang
- Status Docker Desktop `Running`
- Docker Compose aktif
- Folder project dapat diakses Docker Desktop

### Langkah

1. Buka Docker Desktop dan pastikan engine aktif
2. Buka terminal:
- Windows: PowerShell, Command Prompt, atau WSL
- macOS/Linux: Terminal biasa
3. Masuk ke folder project
4. Siapkan `.env`
5. Jalankan container
6. Setup Laravel

Perintahnya:

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app sh -lc 'composer install && npm install && php artisan key:generate && php artisan migrate --seed'
```

Buka aplikasi di `http://localhost:8080`.

### Catatan Khusus Windows

- Anda boleh memakai PowerShell, CMD, atau WSL
- Jika perlu, di PowerShell bisa memakai:

```powershell
Copy-Item .env.example .env
```

- Pastikan folder project tidak diblok oleh file sharing Docker Desktop

### Mode Development Frontend Dengan Docker Desktop

```bash
docker compose exec app npm run dev -- --host 0.0.0.0
```

Akses:
- aplikasi utama: `http://localhost:8080`
- vite dev server: `http://localhost:5173`

### Cara Menghentikan Docker Desktop

Pilihan terminal:

```bash
docker compose down
```

Pilihan UI:
- buka tab Containers
- pilih stack project
- tekan Stop untuk menghentikan

## Menjalankan Tanpa Docker

Metode ini cocok jika Laravel dijalankan langsung di mesin lokal.

### Prasyarat

- PHP 8.2 atau lebih baru
- Composer
- Node.js dan npm
- MySQL 8 atau kompatibel

Ekstensi PHP penting:
- `bcmath`
- `exif`
- `gd`
- `intl`
- `pdo_mysql`
- `xml`
- `zip`

Tambahan untuk PHPUnit:
- `dom`
- `xml`
- `xmlwriter`

### Langkah

```bash
cd /media/arismaulana/Linux/Project/Project/Putri Alisha/DentalCare
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Atur database lokal di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dentalcare
DB_USERNAME=nama_user_mysql
DB_PASSWORD=password_mysql
```

Lanjutkan:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Buka aplikasi di `http://127.0.0.1:8000`.

### Mode Development Frontend Tanpa Docker

Terminal 1:

```bash
npm run dev
```

Terminal 2:

```bash
php artisan serve
```

Catatan:
- `npm run dev` dipakai untuk hot reload frontend
- `php artisan serve` tetap diperlukan untuk backend Laravel

### Cara Menghentikan Tanpa Docker

- Tekan `Ctrl+C` pada terminal `php artisan serve`
- Tekan `Ctrl+C` pada terminal `npm run dev` jika sedang aktif
- Jika MySQL lokal dinyalakan manual, hentikan sesuai mekanisme OS Anda

## Akun Demo

- Admin: `admin@dentalcare.test` / `password`
- Pasien: `patient@dentalcare.test` / `password`
- Dokter 1: `dr.aji@dentalcare.test` / `password`
- Dokter 2: `dr.salsa@dentalcare.test` / `password`
- Dokter 3: `dr.rizky@dentalcare.test` / `password`

## Environment Penting

- `MIDTRANS_SERVER_KEY`
- `MIDTRANS_CLIENT_KEY`
- `MIDTRANS_IS_PRODUCTION`
- `MIDTRANS_CALLBACK_URL`
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`
- `MAIL_MAILER`
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `APPOINTMENT_SLOT_MINUTES`

Catatan:
- Jika Midtrans belum diisi, booking demo tetap bisa dipakai, tetapi pembayaran eksternal tidak berjalan penuh
- Jika Google login ingin diaktifkan, isi credential OAuth dan arahkan callback ke URL aplikasi yang aktif

## Testing Yang Sudah Dilakukan

Testing dilakukan pada `2026-04-10`.

Langkah yang dijalankan:

```bash
docker compose up -d --build
docker compose exec app sh -lc 'composer install && npm install && php artisan key:generate && php artisan migrate --seed'
curl -I http://localhost:8080
docker compose exec app sh -lc 'curl -I http://nginx'
docker compose exec app php artisan test
docker compose down
```

Hasil:
- aplikasi merespons `HTTP 200` pada `http://localhost:8080`
- nginx di jaringan Docker juga merespons `HTTP 200`
- test suite Laravel lulus
- hasil akhir test suite: `42 passed, 131 assertions`

Catatan:
- `composer install` menampilkan warning Git `dubious ownership` di `/var/www/html`, tetapi setup tetap berhasil
- `npm install` melaporkan 2 vulnerability dependency, tetapi tidak menghalangi setup dan testing

## Troubleshooting Singkat

### Port 8080 sudah dipakai

- Ganti port mapping di `docker-compose.yml`
- Atau hentikan aplikasi lain yang memakai port `8080`

### Port 3307 sudah dipakai

- Ganti port host MySQL di `docker-compose.yml`

### Project Docker jalan tapi halaman belum tampil

```bash
docker compose ps
docker compose logs -f
```

Pastikan setup Laravel sudah dijalankan.

### Tanpa Docker tidak bisa konek database

- Cek `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`
- Pastikan MySQL lokal aktif

### Asset frontend tidak tampil

```bash
npm install
npm run build
```

### Hot reload tidak jalan

Jika pakai Docker:

```bash
docker compose exec app npm run dev -- --host 0.0.0.0
```
