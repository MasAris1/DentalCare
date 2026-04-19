@extends('layouts.guest')

@section('title', 'Daftar | '.config('clinic.name'))
@section('auth_title', 'Buat akun pasien')
@section('auth_subtitle', 'Daftar untuk mulai reservasi, memantau pembayaran, dan mengunduh resume medis.')

@section('content')
    @php($googleAuthEnabled = filled(config('services.google.client_id')) && filled(config('services.google.client_secret')) && filled(config('services.google.redirect')))

    @if ($googleAuthEnabled)
        @include('auth.partials.google-button', [
            'label' => 'Daftar dengan Google',
            'hint' => 'Akun Google baru otomatis dibuat sebagai pasien.',
        ])

        <div class="d-flex align-items-center gap-3 my-4">
            <div class="border-top flex-grow-1"></div>
            <span class="text-secondary small text-uppercase">atau isi form</span>
            <div class="border-top flex-grow-1"></div>
        </div>
    @else
        <div class="alert alert-warning border-0 shadow-sm" role="alert">
            Daftar dengan Google belum aktif di environment ini. Isi <code>GOOGLE_CLIENT_ID</code> dan
            <code>GOOGLE_CLIENT_SECRET</code> di file <code>.env</code> untuk mengaktifkannya.
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="row g-3">
        @csrf
        <input type="hidden" name="redirect" value="{{ request('redirect') }}">

        <div class="col-12">
            <label class="form-label">Nama lengkap</label>
            <input class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </div>
        <div class="col-12">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        </div>
        <div class="col-12">
            <label class="form-label">No. telepon</label>
            <input class="form-control" type="text" name="phone" value="{{ old('phone') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" required autocomplete="new-password">
        </div>
        <div class="col-md-6">
            <label class="form-label">Konfirmasi password</label>
            <input class="form-control" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <div class="col-12">
            <button class="btn btn-primary rounded-pill w-100" type="submit">Daftar sekarang</button>
        </div>
    </form>

    <div class="text-center text-secondary small mt-4">
        Sudah punya akun?
        <a class="text-decoration-none fw-semibold" href="{{ route('login', request()->filled('redirect') ? ['redirect' => request('redirect')] : []) }}">Masuk di sini</a>
    </div>
@endsection
