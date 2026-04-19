@extends('layouts.base')

@php
    $user = auth()->user();
    $menu = match (true) {
        $user?->isAdmin() => [
            ['label' => 'Laporan', 'route' => 'admin.reports.index'],
            ['label' => 'Pembayaran', 'route' => 'admin.payments.index'],
            ['label' => 'Layanan', 'route' => 'admin.services.index'],
            ['label' => 'Dokter', 'route' => 'admin.doctors.index'],
            ['label' => 'Jadwal', 'route' => 'admin.schedules.index'],
        ],
        $user?->isDoctor() => [
            ['label' => 'Dashboard', 'route' => 'doctor.dashboard'],
            ['label' => 'Rekam Medis', 'route' => 'doctor.medical-notes.index'],
        ],
        default => [
            ['label' => 'Buat Reservasi', 'route' => 'home', 'url' => route('home').'#booking-section'],
            ['label' => 'Riwayat', 'route' => 'history.index'],
            ['label' => 'Dashboard', 'route' => 'dashboard'],
            ['label' => 'Profil', 'route' => 'profile.edit'],
        ],
    };
@endphp

@section('body')
    <div class="dashboard-shell">
        <div class="dashboard-sidebar">
            <div class="p-4 border-bottom">
                <a class="text-decoration-none text-reset" href="{{ route($user->homeRouteName()) }}">
                    <div class="fw-bold h5 mb-1">{{ config('clinic.name') }}</div>
                    <div class="text-secondary small">{{ $user->role->label() }}</div>
                </a>
            </div>

            <div class="p-3 d-grid gap-2">
                @foreach ($menu as $item)
                    <a
                        class="sidebar-link {{ request()->routeIs($item['route']) ? 'active' : '' }}"
                        href="{{ $item['url'] ?? route($item['route']) }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="dashboard-content">
            <header class="dashboard-topbar border-bottom bg-white">
                <div class="container-fluid px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="text-secondary small">@yield('page_kicker', 'DentalCare Lite')</div>
                        <h1 class="h4 fw-bold mb-0">@yield('page_title', 'Dashboard')</h1>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end">
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="small text-secondary">{{ $user->email }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-secondary rounded-pill px-3" type="submit">Keluar</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="container-fluid px-4 py-4">
                @include('layouts.partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
@endsection
