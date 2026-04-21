@extends('layouts.app')

@section('title', 'Dashboard Pasien | '.config('clinic.name'))
@section('page_kicker', 'Area Pasien')
@section('page_title', 'Dashboard Pasien')

@section('content')
    <div class="row g-4 mb-4">
        @foreach ([
            ['label' => 'Total reservasi', 'value' => $stats['total_bookings']],
            ['label' => 'Menunggu pembayaran', 'value' => $stats['pending_payment']],
            ['label' => 'Terkonfirmasi', 'value' => $stats['confirmed']],
            ['label' => 'Sudah dibayar', 'value' => $stats['paid']],
        ] as $item)
            <div class="col-xl-3 col-sm-6">
                <div class="metric-card h-100">
                    <div class="small text-secondary mb-2">{{ $item['label'] }}</div>
                    <div class="display-6 fw-bold mb-0">{{ $item['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">Reservasi terbaru</h2>
                            <p class="text-secondary small mb-0">Pantau status kunjungan dan pembayaran Anda.</p>
                        </div>
                        <a class="btn btn-outline-primary rounded-pill px-3" href="{{ route('history.index') }}">Lihat semua</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Dokter</th>
                                    <th>Jadwal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bookings as $booking)
                                    <tr>
                                        <td class="fw-semibold">{{ $booking->booking_code }}</td>
                                        <td>
                                            <div>{{ $booking->doctor->name }}</div>
                                            <div class="small text-secondary">{{ $booking->doctor->doctorProfile?->specialization }}</div>
                                        </td>
                                        <td>{{ $booking->scheduleLabel() }}</td>
                                        <td>
                                            <span class="badge text-bg-{{ $booking->badgeClass() }}">{{ $booking->statusLabel() }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-secondary">Belum ada reservasi. Yuk buat reservasi pertama Anda.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-2">Langkah berikutnya</h2>
                    <p class="text-secondary">Pilih dokter, tentukan jadwal, lalu lanjutkan pembayaran untuk mengunci slot antrean Anda.</p>
                    <div class="d-grid gap-2">
                        <a class="btn btn-primary rounded-pill" href="{{ route('home') }}#booking-section">Buat reservasi baru</a>
                        <a class="btn btn-outline-secondary rounded-pill" href="{{ route('profile.edit') }}">Lengkapi profil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
