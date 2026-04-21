<?php

namespace App\Services\Auth;

use App\Models\LoginOtpChallenge;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginOtpService
{
    public const SESSION_KEY = 'auth.two_factor.challenge_id';

    public function start(User $user, Request $request, bool $remember, ?string $redirectPath): LoginOtpChallenge
    {
        $this->ensureProductionMailerIsConfigured();
        $this->invalidatePendingChallenges($user);

        $code = $this->generateCode();

        $challenge = LoginOtpChallenge::create([
            'public_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'remember' => $remember,
            'redirect_path' => $redirectPath,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'last_sent_at' => now(),
            'expires_at' => now()->addMinutes($this->expiresMinutes()),
        ]);

        $request->session()->put(self::SESSION_KEY, $challenge->public_id);

        $user->notify(new LoginOtpNotification($code, $challenge->expires_at));

        return $challenge;
    }

    public function pendingChallenge(Request $request): ?LoginOtpChallenge
    {
        $publicId = $request->session()->get(self::SESSION_KEY);

        if (blank($publicId)) {
            return null;
        }

        $challenge = LoginOtpChallenge::with('user')
            ->where('public_id', $publicId)
            ->first();

        if (! $challenge || ! $challenge->isActive()) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return $challenge;
    }

    /**
     * @return array{0: User, 1: bool, 2: string|null}
     */
    public function verify(Request $request, string $code): array
    {
        $publicId = $request->session()->get(self::SESSION_KEY);

        if (blank($publicId)) {
            throw ValidationException::withMessages([
                'code' => 'Sesi verifikasi sudah kedaluwarsa. Silakan login ulang.',
            ]);
        }

        return DB::transaction(function () use ($publicId, $request, $code): array {
            $challenge = LoginOtpChallenge::with('user')
                ->where('public_id', $publicId)
                ->lockForUpdate()
                ->first();

            if (! $challenge || $challenge->consumed_at !== null) {
                $request->session()->forget(self::SESSION_KEY);

                throw ValidationException::withMessages([
                    'code' => 'Sesi verifikasi sudah kedaluwarsa. Silakan login ulang.',
                ]);
            }

            if ($challenge->expires_at->isPast()) {
                $challenge->forceFill(['consumed_at' => now()])->save();
                $request->session()->forget(self::SESSION_KEY);

                throw ValidationException::withMessages([
                    'code' => 'Kode OTP sudah kedaluwarsa. Silakan login ulang.',
                ]);
            }

            if ($challenge->attempts >= $this->maxAttempts()) {
                $challenge->forceFill(['consumed_at' => now()])->save();
                $request->session()->forget(self::SESSION_KEY);

                throw ValidationException::withMessages([
                    'code' => 'Terlalu banyak percobaan OTP. Silakan login ulang.',
                ]);
            }

            $challenge->forceFill(['attempts' => $challenge->attempts + 1])->save();

            if (! Hash::check($code, $challenge->code_hash)) {
                if ($challenge->attempts >= $this->maxAttempts()) {
                    $challenge->forceFill(['consumed_at' => now()])->save();
                    $request->session()->forget(self::SESSION_KEY);

                    throw ValidationException::withMessages([
                        'code' => 'Terlalu banyak percobaan OTP. Silakan login ulang.',
                    ]);
                }

                throw ValidationException::withMessages([
                    'code' => 'Kode OTP tidak valid.',
                ]);
            }

            $challenge->forceFill(['consumed_at' => now()])->save();
            $request->session()->forget(self::SESSION_KEY);

            return [$challenge->user, $challenge->remember, $challenge->redirect_path];
        });
    }

    public function resend(LoginOtpChallenge $challenge): void
    {
        if ($challenge->last_sent_at && $challenge->last_sent_at->gt(now()->subSeconds($this->resendCooldownSeconds()))) {
            throw ValidationException::withMessages([
                'code' => 'Tunggu sebentar sebelum meminta kode OTP baru.',
            ]);
        }

        $code = $this->generateCode();

        $challenge->forceFill([
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'last_sent_at' => now(),
            'expires_at' => now()->addMinutes($this->expiresMinutes()),
        ])->save();

        $challenge->user->notify(new LoginOtpNotification($code, $challenge->expires_at));
    }

    public function cancel(Request $request): void
    {
        $challenge = $this->pendingChallenge($request);

        if ($challenge) {
            $challenge->forceFill(['consumed_at' => now()])->save();
        }

        $request->session()->forget(self::SESSION_KEY);
    }

    protected function invalidatePendingChallenges(User $user): void
    {
        LoginOtpChallenge::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }

    protected function ensureProductionMailerIsConfigured(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $mailer = (string) config('mail.default');

        $fromAddress = (string) config('mail.from.address');
        $smtpHost = (string) config('mail.mailers.smtp.host');
        $fallbackMailers = (array) config('mail.mailers.'.$mailer.'.mailers', []);

        $usesNonProductionMailer = in_array($mailer, ['log', 'array'], true)
            || array_intersect($fallbackMailers, ['log', 'array']) !== [];
        $usesPlaceholderSender = blank($fromAddress)
            || str_ends_with($fromAddress, '.test')
            || str_contains($fromAddress, 'example.com');
        $usesPlaceholderSmtp = $mailer === 'smtp'
            && (blank($smtpHost) || str_contains($smtpHost, 'example.com'));

        if ($usesNonProductionMailer || $usesPlaceholderSender || $usesPlaceholderSmtp) {
            throw ValidationException::withMessages([
                'email' => 'Pengiriman OTP belum dikonfigurasi untuk production. Gunakan SMTP atau provider email transactional dengan alamat pengirim resmi.',
            ]);
        }
    }

    protected function generateCode(): string
    {
        $digits = max(4, min(8, (int) config('two_factor.login_otp.digits', 6)));
        $maximum = (10 ** $digits) - 1;

        return str_pad((string) random_int(0, $maximum), $digits, '0', STR_PAD_LEFT);
    }

    protected function expiresMinutes(): int
    {
        return max(1, (int) config('two_factor.login_otp.expires_minutes', 10));
    }

    protected function maxAttempts(): int
    {
        return max(1, (int) config('two_factor.login_otp.max_attempts', 5));
    }

    protected function resendCooldownSeconds(): int
    {
        return max(1, (int) config('two_factor.login_otp.resend_cooldown_seconds', 60));
    }
}
