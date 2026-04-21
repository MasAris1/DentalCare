<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasAuditFields, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'phone',
        'google_id',
        'google_avatar',
        'role',
        'password',
        'CompanyCode',
        'Status',
        'IsDeleted',
        'CreatedBy',
        'CreatedDate',
        'LastUpdatedBy',
        'LastUpdatedDate',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'CreatedDate' => 'datetime',
            'LastUpdatedDate' => 'datetime',
        ];
    }

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class, 'user_id');
    }

    public function doctorSchedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class, 'doctor_id');
    }

    public function patientBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'patient_id');
    }

    public function loginOtpChallenges(): HasMany
    {
        return $this->hasMany(LoginOtpChallenge::class);
    }

    public function doctorBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'doctor_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isDoctor(): bool
    {
        return $this->role === UserRole::Doctor;
    }

    public function isPatient(): bool
    {
        return $this->role === UserRole::Patient;
    }

    public function homeRouteName(): string
    {
        return match (true) {
            $this->isAdmin() => 'admin.reports.index',
            $this->isDoctor() => 'doctor.dashboard',
            default => 'home',
        };
    }
}
