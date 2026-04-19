@extends('layouts.base')

@section('body')
    <div class="public-shell">
        <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
            <div class="container py-2">
                <a class="navbar-brand fw-bold text-primary-emphasis" href="{{ route('home') }}">
                    {{ config('clinic.name') }}
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="publicNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}" href="{{ route('services.index') }}">Layanan</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('doctors.*') ? 'active' : '' }}" href="{{ route('doctors.index') }}">Dokter</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}#booking-section">Reservasi</a>
                        </li>

                        @auth
                            @php($firstName = str(auth()->user()->name)->before(' ') ?: auth()->user()->name)

                            <li class="nav-item dropdown ms-lg-3">
                                <button
                                    class="btn btn-primary rounded-pill px-4 dropdown-toggle"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Hai, {{ $firstName }}
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                                    @php($dashboardRoute = auth()->user()->isPatient() ? route('dashboard') : route(auth()->user()->homeRouteName()))

                                    <li>
                                        <a class="dropdown-item" href="{{ $dashboardRoute }}">Dashboard</a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button class="dropdown-item text-danger" type="submit">Logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @else
                            <li class="nav-item ms-lg-3">
                                <a class="btn btn-outline-primary rounded-pill px-4" href="{{ route('login') }}">Masuk</a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-primary rounded-pill px-4" href="{{ route('register') }}">Daftar</a>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>

        <main>
            <div class="container pt-3">
                @include('layouts.partials.flash')
            </div>
            @yield('content')
        </main>

        <footer class="border-top bg-white">
            <div class="container py-5">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-5">
                        <h5 class="fw-bold">{{ config('clinic.name') }}</h5>
                        <p class="text-secondary mb-3">{{ config('clinic.tagline') }}</p>
                        <div class="small text-secondary">
                            <div>{{ config('clinic.address') }}</div>
                            <div>{{ config('clinic.phone') }}</div>
                            <div>{{ config('clinic.email') }}</div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="text-uppercase small text-secondary mb-2">Navigasi</div>
                                <div class="d-grid gap-2">
                                    <a class="link-secondary text-decoration-none" href="{{ route('home') }}">Beranda</a>
                                    <a class="link-secondary text-decoration-none" href="{{ route('services.index') }}">Layanan</a>
                                    <a class="link-secondary text-decoration-none" href="{{ route('doctors.index') }}">Dokter</a>
                                </div>
                            </div>
                            <div class="col-sm-8">
                                <div class="text-uppercase small text-secondary mb-2">Jam layanan</div>
                                <div class="small text-secondary d-grid gap-1">
                                    <span>Senin - Jumat: 08.00 - 20.00</span>
                                    <span>Sabtu: 09.00 - 17.00</span>
                                    <span>Minggu: By appointment</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
@endsection
