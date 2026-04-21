<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\LoginOtpService;
use App\Support\AuthRedirect;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->hasGoogleConfiguration()) {
            return redirect()->route('login')->with('error', 'Konfigurasi login Google belum lengkap.');
        }

        if (! $this->requestMatchesGoogleRedirect(request())) {
            return redirect()->route('login')->with('error', 'Buka aplikasi melalui '.config('services.google.redirect').' agar login Google dapat diproses.');
        }

        $redirect = AuthRedirect::sanitizePath(request()->string('redirect')->toString());

        if ($redirect !== null) {
            request()->session()->put('auth.redirect_after_login', $redirect);
        }

        try {
            return Socialite::driver('google')->redirect();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->with('error', 'Gagal menghubungkan ke Google. Silakan coba lagi.');
        }
    }

    public function callback(Request $request, LoginOtpService $loginOtp): RedirectResponse
    {
        if (! $this->hasGoogleConfiguration()) {
            return redirect()->route('login')->with('error', 'Konfigurasi login Google belum lengkap.');
        }

        if ($request->filled('error')) {
            return redirect()->route('login')->with('error', 'Login Google dibatalkan atau gagal di sisi Google.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $exception) {
            report($exception);

            return redirect()->route('login')->with('error', 'Sesi login Google sudah kedaluwarsa. Silakan coba lagi.');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->with('error', 'Login Google gagal. Silakan coba lagi.');
        }

        $googleId = $googleUser->getId();
        $email = Str::lower((string) $googleUser->getEmail());

        if (blank($googleId) || blank($email)) {
            return redirect()->route('login')->with('error', 'Akun Google harus membagikan email yang valid.');
        }

        if (data_get($googleUser->getRaw(), 'verified_email') === false) {
            return redirect()->route('login')->with('error', 'Email Google harus sudah terverifikasi.');
        }

        $user = User::query()->firstWhere('google_id', $googleId);

        if (! $user) {
            $user = User::query()->firstWhere('email', $email);

            if ($user && filled($user->google_id) && $user->google_id !== $googleId) {
                return redirect()->route('login')->with('error', 'Email ini sudah terhubung ke akun Google lain.');
            }
        }

        if ($user && $user->role !== UserRole::Patient) {
            return redirect()->route('login')->with('error', 'Login Google hanya tersedia untuk akun pasien.');
        }

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'role' => UserRole::Patient,
                'password' => Hash::make(Str::random(32)),
                'google_id' => $googleId,
                'google_avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);

            event(new Registered($user));
        } else {
            if (blank($user->google_id)) {
                $user->google_id = $googleId;
            }

            if (filled($googleUser->getAvatar())) {
                $user->google_avatar = $googleUser->getAvatar();
            }

            if (blank($user->name) && filled($googleUser->getName())) {
                $user->name = $googleUser->getName();
            }

            if (blank($user->email_verified_at)) {
                $user->email_verified_at = now();
            }

            if ($user->isDirty()) {
                $user->save();
            }
        }

        $redirect = $request->session()->pull('auth.redirect_after_login') ?: AuthRedirect::pathFor($user);

        $request->session()->regenerate();

        $loginOtp->start($user, $request, true, $redirect);

        return redirect()->route('two-factor.login')
            ->with('status', 'Kode OTP sudah dikirim ke email Anda.');
    }

    protected function hasGoogleConfiguration(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    protected function requestMatchesGoogleRedirect(Request $request): bool
    {
        $redirectUri = parse_url((string) config('services.google.redirect'));

        if (! is_array($redirectUri) || blank($redirectUri['host'] ?? null)) {
            return false;
        }

        return ($redirectUri['scheme'] ?? 'http') === $request->getScheme()
            && ($redirectUri['host'] ?? null) === $request->getHost()
            && $this->normalizePort($redirectUri['scheme'] ?? 'http', $redirectUri['port'] ?? null) === $request->getPort();
    }

    protected function normalizePort(string $scheme, int|string|null $port): int
    {
        if ($port !== null) {
            return (int) $port;
        }

        return $scheme === 'https' ? 443 : 80;
    }
}
