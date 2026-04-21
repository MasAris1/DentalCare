<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost:8080/auth/google/callback');
    }

    public function test_users_can_be_redirected_to_google(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_google_redirect_requires_configuration(): void
    {
        config()->set('services.google.client_id', '');
        config()->set('services.google.client_secret', '');
        config()->set('services.google.redirect', '');

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Konfigurasi login Google belum lengkap.');
    }

    public function test_google_redirect_requires_the_same_host_as_the_configured_callback(): void
    {
        $response = $this->get('http://127.0.0.1/auth/google/redirect');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Buka aplikasi melalui http://localhost:8080/auth/google/callback agar login Google dapat diproses.');
    }

    public function test_google_callback_requires_configuration(): void
    {
        config()->set('services.google.client_id', '');
        config()->set('services.google.client_secret', '');
        config()->set('services.google.redirect', '');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Konfigurasi login Google belum lengkap.');
    }

    public function test_new_users_can_register_and_login_with_google(): void
    {
        Notification::fake();

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($this->fakeGoogleUser(
                id: 'google-123',
                name: 'Putri Alisha',
                email: 'putri@example.com',
            ));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $user = User::query()->firstWhere('email', 'putri@example.com');

        $this->assertNotNull($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame(UserRole::Patient, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertGuest();
        $response->assertRedirect(route('two-factor.login', absolute: false));

        $response = $this->post(route('two-factor.verify'), [
            'code' => $this->otpCodeFor($user),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_google_callback_handles_oauth_cancellation(): void
    {
        Socialite::shouldReceive('driver')->never();

        $response = $this->get(route('auth.google.callback', [
            'error' => 'access_denied',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Login Google dibatalkan atau gagal di sisi Google.');
        $this->assertGuest();
    }

    public function test_google_callback_handles_expired_oauth_state(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andThrow(new InvalidStateException());

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Sesi login Google sudah kedaluwarsa. Silakan coba lagi.');
        $this->assertGuest();
    }

    public function test_existing_users_are_linked_to_google_by_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'putri@example.com',
            'google_id' => null,
            'google_avatar' => null,
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($this->fakeGoogleUser(
                id: 'google-456',
                name: 'Putri Alisha',
                email: 'putri@example.com',
                avatar: 'https://example.com/avatar.png',
            ));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $user->refresh();

        $this->assertSame('google-456', $user->google_id);
        $this->assertSame('https://example.com/avatar.png', $user->google_avatar);
        $this->assertGuest();
        $response->assertRedirect(route('two-factor.login', absolute: false));

        $response = $this->post(route('two-factor.verify'), [
            'code' => $this->otpCodeFor($user),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_google_login_is_rejected_for_internal_accounts(): void
    {
        $user = User::factory()->create([
            'email' => 'dokter@example.com',
            'role' => UserRole::Doctor,
            'google_id' => null,
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($this->fakeGoogleUser(
                id: 'google-doctor',
                name: 'Dr. Salsa',
                email: 'dokter@example.com',
            ));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $user->refresh();

        $this->assertNull($user->google_id);
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Login Google hanya tersedia untuk akun pasien.');
        $this->assertGuest();
    }

    public function test_google_login_requires_verified_email(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($this->fakeGoogleUser(
                id: 'google-unverified',
                name: 'Putri Alisha',
                email: 'putri@example.com',
                verifiedEmail: false,
            ));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Email Google harus sudah terverifikasi.');
        $this->assertDatabaseMissing('users', [
            'email' => 'putri@example.com',
        ]);
        $this->assertGuest();
    }

    private function fakeGoogleUser(
        string $id,
        string $name,
        string $email,
        ?string $avatar = 'https://example.com/google-avatar.png',
        ?bool $verifiedEmail = true,
    ): SocialiteUser {
        $user = new SocialiteUser();

        $attributes = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
            'verified_email' => $verifiedEmail,
        ];

        $user->setRaw($attributes);
        $user->map($attributes);

        return $user;
    }

    private function otpCodeFor(User $user): string
    {
        $code = null;

        Notification::assertSentTo($user, LoginOtpNotification::class, function (LoginOtpNotification $notification) use (&$code) {
            $code = $notification->code;

            return preg_match('/^\d{6}$/', $code) === 1;
        });

        $this->assertNotNull($code);

        return $code;
    }
}
