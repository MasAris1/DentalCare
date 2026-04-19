<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        config()->set('services.google.client_id', '');
        config()->set('services.google.client_secret', '');
        config()->set('services.google.redirect', '');

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk dengan Google belum aktif di environment ini.', escape: false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.login', absolute: false));

        $response = $this->post(route('two-factor.verify'), [
            'code' => $this->otpCodeFor($user),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_doctors_are_redirected_to_their_dashboard_after_login(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => UserRole::Doctor,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.login', absolute: false));

        $response = $this->post(route('two-factor.verify'), [
            'code' => $this->otpCodeFor($user),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('doctor.dashboard', absolute: false));
    }

    public function test_patient_login_keeps_booking_state_on_the_home_page(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $target = route('home', [
            'doctor_id' => 5,
            'service_id' => 2,
            'booking_date' => '2026-04-20',
        ], absolute: false).'#booking-section';

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'redirect' => $target,
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.login', absolute: false));

        $response = $this->post(route('two-factor.verify'), [
            'code' => $this->otpCodeFor($user),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect($target);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        Notification::assertNothingSent();
    }

    public function test_users_cannot_finish_login_with_invalid_otp(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $wrongCode = $this->otpCodeFor($user) === '000000' ? '111111' : '000000';

        $this->post(route('two-factor.verify'), [
            'code' => $wrongCode,
        ])->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_two_factor_screen_requires_a_pending_challenge(): void
    {
        $this->get(route('two-factor.login'))
            ->assertRedirect(route('login'));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
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
