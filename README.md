# Laravel + Vite via Docker di VS Code

Project ini disiapkan untuk development Laravel sepenuhnya lewat Docker menggunakan Laravel Sail. Target workflow-nya adalah buka folder ini di VS Code, masuk ke Dev Container, lalu jalankan semua command Laravel, Composer, dan Vite dari dalam container.

## Stack

- Laravel 13
- PHP 8.4 via Laravel Sail
- MySQL 8.4
- Vite dev server pada port `5173`
- VS Code Dev Containers

## Cara pakai

1. Copy env jika belum ada:

   ```bash
   cp .env.example .env
   ```

2. Build dan nyalakan container:

   ```bash
   docker compose up -d --build
   ```

3. Install dependency Node bila belum ada:

   ```bash
   docker compose exec laravel.test npm install
   ```

4. Jalankan migrasi:

   ```bash
   docker compose exec laravel.test php artisan migrate
   ```

5. Jalankan Vite:

   ```bash
   docker compose exec laravel.test npm run dev
   ```

## Akses service

- Laravel: `http://localhost:8000`
- Vite: `http://localhost:5173`
- MySQL host port: `3306`

## Workflow VS Code

1. Install extension `Dev Containers`.
2. Buka folder project ini di VS Code.
3. Jalankan command `Dev Containers: Reopen in Container`.
4. Setelah masuk container, buka terminal VS Code lalu jalankan:

   ```bash
   composer install
   npm install
   php artisan migrate
   npm run dev
   ```

Semua command harian sebaiknya dijalankan dari terminal di dalam container, bukan dari host, karena project ini menargetkan PHP 8.4.

## Command yang sering dipakai

```bash
docker compose up -d
docker compose down
docker compose exec laravel.test php artisan test
docker compose exec laravel.test php artisan make:controller ExampleController
docker compose exec laravel.test composer install
docker compose exec laravel.test npm run build
```
