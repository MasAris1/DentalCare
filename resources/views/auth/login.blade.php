@extends('layouts.guest')

@section('title', 'Masuk | '.config('clinic.name'))
@section('auth_title', 'Masuk ke akun Anda')
@section('auth_subtitle', 'Masuk untuk melanjutkan reservasi, pembayaran, atau akses internal sesuai peran Anda.')

@section('content')
    @php($googleAuthEnabled = filled(config('services.google.client_id')) && filled(config('services.google.client_secret')) && filled(config('services.google.redirect')))

    @if ($googleAuthEnabled)
        @include('auth.partials.google-button', [
            'label' => 'Masuk dengan Google',
        ])

        <div class="d-flex align-items-center gap-3 my-4">
            <div class="border-top flex-grow-1"></div>
            <span class="text-secondary small text-uppercase">atau</span>
            <div class="border-top flex-grow-1"></div>
        </div>
    @else
        <div class="alert alert-warning border-0 shadow-sm" role="alert">
            Masuk dengan Google belum aktif di environment ini. Isi <code>GOOGLE_CLIENT_ID</code> dan
            <code>GOOGLE_CLIENT_SECRET</code> di file <code>.env</code> untuk mengaktifkannya.
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="d-grid gap-3">
        @csrf
        <input type="hidden" name="redirect" value="{{ request('redirect') }}">

        <div>
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <div>
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" required autocomplete="current-password">
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                <label class="form-check-label" for="remember_me">Ingat saya</label>
            </div>
            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request') }}">Lupa password?</a>
            @endif
        </div>

        <button class="btn btn-primary rounded-pill w-100" type="submit">Masuk</button>
    </form>

    <div class="text-center text-secondary small mt-4">
        Belum punya akun?
        <a class="text-decoration-none fw-semibold" href="{{ route('register', request()->filled('redirect') ? ['redirect' => request('redirect')] : []) }}">Daftar sebagai pasien</a>
    </div>
@endsection
