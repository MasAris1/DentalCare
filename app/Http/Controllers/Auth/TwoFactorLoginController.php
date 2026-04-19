<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\LoginOtpService;
use App\Support\AuthRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TwoFactorLoginController extends Controller
{
    public function show(Request $request, LoginOtpService $loginOtp): View|RedirectResponse
    {
        $challenge = $loginOtp->pendingChallenge($request);

        if (! $challenge) {
            return redirect()->route('login')->with('error', 'Sesi verifikasi OTP sudah kedaluwarsa. Silakan login ulang.');
        }

        return view('auth.two-factor-challenge', [
            'email' => $this->maskEmail($challenge->user->email),
            'expiresAt' => $challenge->expires_at,
            'digits' => $this->digits(),
        ]);
    }

    public function store(Request $request, LoginOtpService $loginOtp): RedirectResponse
    {
        $digits = $this->digits();

        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:'.$digits],
        ]);

        [$user, $remember, $redirectPath] = $loginOtp->verify($request, $validated['code']);

        Auth::guard('web')->login($user, $remember);

        $request->session()->regenerate();

        return redirect()->intended($redirectPath ?: AuthRedirect::pathFor($user));
    }

    public function resend(Request $request, LoginOtpService $loginOtp): RedirectResponse
    {
        $challenge = $loginOtp->pendingChallenge($request);

        if (! $challenge) {
            return redirect()->route('login')->with('error', 'Sesi verifikasi OTP sudah kedaluwarsa. Silakan login ulang.');
        }

        $loginOtp->resend($challenge);

        return back()->with('status', 'Kode OTP baru sudah dikirim ke email Anda.');
    }

    public function destroy(Request $request, LoginOtpService $loginOtp): RedirectResponse
    {
        $loginOtp->cancel($request);

        return redirect()->route('login')->with('status', 'Verifikasi OTP dibatalkan. Silakan login ulang jika ingin melanjutkan.');
    }

    protected function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return Str::mask($email, '*', 2);
        }

        [$name, $domain] = explode('@', $email, 2);

        return Str::mask($name, '*', 2).'@'.$domain;
    }

    protected function digits(): int
    {
        return max(4, min(8, (int) config('two_factor.login_otp.digits', 6)));
    }
}
