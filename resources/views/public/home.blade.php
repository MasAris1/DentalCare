@extends('layouts.public')

@section('title', 'DentalCare Lite | Reservasi Klinik Gigi Modern')

@section('content')
    <section class="hero-section py-5 py-lg-6">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="eyebrow-pill mb-3">Reservasi digital untuk klinik gigi</span>
                    <h1 class="display-5 fw-bold mb-3">Rawat senyum keluarga dengan booking online, pembayaran instan, dan rekam medis yang rapi.</h1>
                    <p class="lead text-secondary mb-4">{{ config('clinic.tagline') }}</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a class="btn btn-primary btn-lg rounded-pill px-4" href="{{ route('home') }}#booking-section">Buat Reservasi</a>
                        <a class="btn btn-outline-dark btn-lg rounded-pill px-4" href="{{ route('doctors.index') }}">Lihat Dokter</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-card shadow-lg rounded-5 p-4 p-lg-5">
                        <div class="row g-3">
                            @foreach ([
                                ['label' => 'Dokter aktif', 'value' => $stats['doctors']],
                                ['label' => 'Layanan', 'value' => $stats['services']],
                                ['label' => 'Reservasi', 'value' => $stats['bookings']],
                                ['label' => 'Transaksi lunas', 'value' => $stats['payments']],
                            ] as $item)
                                <div class="col-6">
                                    <div class="stat-card h-100">
                                        <div class="text-secondary small mb-2">{{ $item['label'] }}</div>
                                        <div class="display-6 fw-bold mb-0">{{ $item['value'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('public.partials.booking-section')

    <section class="py-5 bg-white">
        <div class="container">
            <div class="section-heading d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                <div>
                    <div class="text-uppercase small text-secondary mb-2">Layanan unggulan</div>
                    <h2 class="h1 fw-bold mb-0">Perawatan yang jelas, nyaman, dan transparan.</h2>
                </div>
                <a class="btn btn-outline-primary rounded-pill px-4" href="{{ route('services.index') }}">Lihat semua</a>
            </div>
            <div class="row g-4">
                @foreach ($featuredServices as $service)
                    <div class="col-lg-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100 rounded-4">
                            <div class="card-body p-4">
                                <div class="service-icon mb-3">{{ str($service->name)->substr(0, 1) }}</div>
                                <h3 class="h5 fw-bold">{{ $service->name }}</h3>
                                <p class="text-secondary small">{{ $service->description ?: 'Perawatan profesional dengan standar klinik modern.' }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">Rp {{ number_format($service->price, 0, ',', '.') }}</span>
                                    <span class="badge rounded-pill text-bg-light">{{ $service->duration_minutes }} menit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="section-heading d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                <div>
                    <div class="text-uppercase small text-secondary mb-2">Tim dokter</div>
                    <h2 class="h1 fw-bold mb-0">Temukan dokter yang sesuai dengan kebutuhan Anda.</h2>
                </div>
                <a class="btn btn-outline-primary rounded-pill px-4" href="{{ route('doctors.index') }}">Jelajahi dokter</a>
            </div>
            <div class="row g-4">
                @foreach ($featuredDoctors as $doctor)
                    <div class="col-lg-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100 rounded-4">
                            <div class="card-body p-4">
                                <div class="doctor-avatar mb-3">{{ str($doctor->name)->substr(0, 1) }}</div>
                                <h3 class="h5 fw-bold mb-1">{{ $doctor->name }}</h3>
                                <div class="text-primary-emphasis fw-semibold small mb-3">{{ $doctor->doctorProfile?->specialization }}</div>
                                <p class="text-secondary small mb-3">
                                    {{ $doctor->doctorProfile?->biography ?: 'Dokter berpengalaman dengan pendekatan perawatan yang ramah pasien.' }}
                                </p>
                                <div class="small text-secondary d-grid gap-1">
                                    @foreach ($doctor->doctorSchedules->take(2) as $schedule)
                                        <span>{{ $schedule->dayLabel() }} • {{ $schedule->formattedTimeRange() }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container">
            <div class="cta-banner rounded-5 p-4 p-lg-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="text-uppercase small text-white-50 mb-2">Siap periksa gigi?</div>
                    <h2 class="display-6 fw-bold text-white mb-2">Booking sekarang dan amankan antrean Anda hari ini.</h2>
                    <p class="text-white-50 mb-0">Pilih dokter, tentukan jadwal, lalu selesaikan pembayaran secara otomatis lewat Midtrans.</p>
                </div>
                <a class="btn btn-light btn-lg rounded-pill px-4" href="{{ route('home') }}#booking-section">Mulai reservasi</a>
            </div>
        </div>
    </section>
@endsection
