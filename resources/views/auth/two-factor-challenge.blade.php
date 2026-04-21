@extends('layouts.guest')

@section('title', 'Verifikasi OTP | '.config('clinic.name'))
@section('auth_title', 'Verifikasi OTP')
@section('auth_subtitle', 'Masukkan kode '.$digits.' digit yang kami kirim ke '.$email.'.')

@section('content')
    <form method="POST" action="{{ route('two-factor.verify') }}" class="d-grid gap-3">
        @csrf

        <div>
            <label class="form-label" for="code">Kode OTP</label>
            <input
                class="form-control text-center fw-semibold fs-4"
                id="code"
                type="text"
                name="code"
                value="{{ old('code') }}"
                required
                autofocus
                inputmode="numeric"
                autocomplete="one-time-code"
                pattern="[0-9]{{ '{'.$digits.'}' }}"
                maxlength="{{ $digits }}"
            >
            <div class="form-text">
                Kode berlaku sampai {{ $expiresAt->timezone(config('app.timezone'))->format('H:i') }} WIB.
            </div>
        </div>

        <button class="btn btn-primary rounded-pill w-100" type="submit">Verifikasi dan masuk</button>
    </form>

    <div class="d-grid gap-2 mt-3">
        <form method="POST" action="{{ route('two-factor.resend') }}">
            @csrf
            <button class="btn btn-outline-primary rounded-pill w-100" type="submit">Kirim ulang kode</button>
        </form>

        <form method="POST" action="{{ route('two-factor.cancel') }}">
            @csrf
            @method('DELETE')
            <button class="btn btn-link text-secondary text-decoration-none w-100" type="submit">Batalkan login</button>
        </form>
    </div>
@endsection
