<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\LoginOtpService;
use App\Support\AuthRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, LoginOtpService $loginOtp): RedirectResponse
    {
        $user = $request->authenticate();
        $redirect = AuthRedirect::pathFromRequestOrDefault($request, $user);

        $request->session()->regenerate();

        $loginOtp->start($user, $request, $request->boolean('remember'), $redirect);

        return redirect()->route('two-factor.login')
            ->with('status', 'Kode OTP sudah dikirim ke email Anda.');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
