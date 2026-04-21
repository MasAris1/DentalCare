<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Services\MidtransService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\CreatesClinicData;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use CreatesClinicData;
    use RefreshDatabase;

    public function test_booking_create_route_redirects_to_home_booking_section(): void
    {
        $response = $this->get(route('booking.create', [
            'doctor_id' => 2,
            'service_id' => 3,
            'booking_date' => '2026-04-20',
        ], absolute: false));

        $response->assertRedirect(route('home', [
            'doctor_id' => 2,
            'service_id' => 3,
            'booking_date' => '2026-04-20',
        ], absolute: false).'#booking-section');
    }

    public function test_patient_is_redirected_to_payment_page_after_creating_a_booking(): void
    {
        $patient = $this->createPatient();
        $doctor = $this->createDoctor();
        $service = $this->createService();
        $date = Carbon::tomorrow();

        $this->createSchedule($doctor, [
            'day_of_week' => $date->dayOfWeekIso,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'quota' => 4,
        ]);

        $midtrans = Mockery::mock(MidtransService::class);
        $midtrans->shouldReceive('createTransaction')
            ->once()
            ->andReturn([
                'token' => 'snap-token-test',
                'redirect_url' => 'https://payments.example.test/checkout/DC-TEST',
                'response' => ['mocked' => true],
            ]);

        $this->app->instance(MidtransService::class, $midtrans);

        $response = $this->actingAs($patient)->post(route('booking.store'), [
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'booking_date' => $date->toDateString(),
            'booking_time' => '09:00',
            'notes' => 'Tes booking',
        ]);

        $response->assertRedirect('https://payments.example.test/checkout/DC-TEST');

        $this->assertDatabaseHas('bookings', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'queue_number' => 1,
            'booking_status' => BookingStatus::PendingPayment->value,
        ]);

        $this->assertDatabaseHas('payments', [
            'payment_status' => PaymentStatus::Pending->value,
            'amount' => $service->price,
        ]);
    }

    public function test_booking_redirects_to_history_when_payment_page_is_not_available(): void
    {
        $patient = $this->createPatient();
        $doctor = $this->createDoctor();
        $service = $this->createService();
        $date = Carbon::tomorrow();

        $this->createSchedule($doctor, [
            'day_of_week' => $date->dayOfWeekIso,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'quota' => 4,
        ]);

        $midtrans = Mockery::mock(MidtransService::class);
        $midtrans->shouldReceive('createTransaction')
            ->once()
            ->andReturn([
                'token' => 'snap-token-test',
                'redirect_url' => null,
                'response' => ['mocked' => true],
            ]);

        $this->app->instance(MidtransService::class, $midtrans);

        $response = $this->actingAs($patient)->post(route('booking.store'), [
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'booking_date' => $date->toDateString(),
            'booking_time' => '09:00',
        ]);

        $response->assertRedirect(route('history.index'));
        $response->assertSessionHas('success', 'Reservasi berhasil dibuat. Tautan pembayaran belum tersedia, silakan lanjutkan pembayaran dari riwayat reservasi.');
    }

    public function test_booking_is_rejected_when_daily_quota_is_full(): void
    {
        $patient = $this->createPatient();
        $doctor = $this->createDoctor();
        $service = $this->createService();
        $date = Carbon::tomorrow();

        $this->createSchedule($doctor, [
            'day_of_week' => $date->dayOfWeekIso,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'quota' => 1,
        ]);

        $existingPatient = $this->createPatient(['email' => 'another@example.com']);
        $this->createBooking($existingPatient, $doctor, $service, [
            'booking_date' => $date->toDateString(),
            'booking_time' => '09:00',
            'booking_status' => BookingStatus::Confirmed,
        ]);

        $response = $this->actingAs($patient)
            ->from(route('home', [
                'doctor_id' => $doctor->id,
                'service_id' => $service->id,
                'booking_date' => $date->toDateString(),
            ], absolute: false))
            ->post(route('booking.store'), [
                'doctor_id' => $doctor->id,
                'service_id' => $service->id,
                'booking_date' => $date->toDateString(),
                'booking_time' => '09:30',
            ]);

        $response->assertSessionHasErrors('booking_time');
    }

    public function test_booking_is_rejected_when_service_duration_overlaps_existing_slot(): void
    {
        $patient = $this->createPatient();
        $doctor = $this->createDoctor();
        $longService = $this->createService([
            'name' => 'Cabut Gigi',
            'slug' => 'cabut-gigi-test',
            'duration_minutes' => 60,
        ]);
        $shortService = $this->createService([
            'name' => 'Kontrol',
            'slug' => 'kontrol-test',
            'duration_minutes' => 30,
            'price' => 150000,
        ]);
        $date = Carbon::tomorrow();

        $this->createSchedule($doctor, [
            'day_of_week' => $date->dayOfWeekIso,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'quota' => 4,
            'slot_minutes' => 30,
        ]);

        $existingPatient = $this->createPatient(['email' => 'existing@example.com']);
        $this->createBooking($existingPatient, $doctor, $longService, [
            'booking_date' => $date->toDateString(),
            'booking_time' => '09:00',
            'booking_status' => BookingStatus::Confirmed,
        ]);

        $response = $this->actingAs($patient)
            ->from(route('home', [
                'doctor_id' => $doctor->id,
                'service_id' => $shortService->id,
                'booking_date' => $date->toDateString(),
            ], absolute: false))
            ->post(route('booking.store'), [
                'doctor_id' => $doctor->id,
                'service_id' => $shortService->id,
                'booking_date' => $date->toDateString(),
                'booking_time' => '09:30',
            ]);

        $response->assertSessionHasErrors('booking_time');
    }
}
