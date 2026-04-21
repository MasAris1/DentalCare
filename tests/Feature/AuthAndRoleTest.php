<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesClinicData;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use CreatesClinicData;
    use RefreshDatabase;

    public function test_patient_registration_assigns_patient_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Putri Alisha',
            'email' => 'putri@example.com',
            'phone' => '08123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = auth()->user();

        $this->assertSame(UserRole::Patient, $user->role);
        $this->assertSame('DCL', $user->CompanyCode);
    }

    public function test_patient_cannot_open_admin_report_page(): void
    {
        $patient = $this->createPatient();

        $this->actingAs($patient)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }
}
