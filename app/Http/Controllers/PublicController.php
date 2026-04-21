<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function home(Request $request, BookingService $bookingService): View
    {
        $featuredServices = Service::query()->latest()->take(4)->get();
        $featuredDoctors = User::query()
            ->where('role', UserRole::Doctor->value)
            ->with(['doctorProfile', 'doctorSchedules'])
            ->take(4)
            ->get();
        $services = Service::query()->orderBy('name')->get();
        $selectedDoctor = null;
        $selectedService = null;
        $selectedDate = $request->string('booking_date')->toString();
        $availableSlots = [];

        if ($request->filled('doctor_id')) {
            $selectedDoctor = User::query()
                ->where('role', UserRole::Doctor->value)
                ->whereKey($request->integer('doctor_id'))
                ->with('doctorSchedules')
                ->first();
        }

        if ($request->filled('service_id')) {
            $selectedService = $services->firstWhere('id', $request->integer('service_id'));
        }

        if ($selectedDoctor && $selectedService && $selectedDate) {
            $availableSlots = $bookingService->availableSlots(
                $selectedDoctor,
                Carbon::parse($selectedDate),
                $selectedService
            );
        }

        return view('public.home', [
            'featuredServices' => $featuredServices,
            'featuredDoctors' => $featuredDoctors,
            'doctors' => User::query()
                ->where('role', UserRole::Doctor->value)
                ->with(['doctorProfile', 'doctorSchedules'])
                ->orderBy('name')
                ->get(),
            'services' => $services,
            'selectedDoctor' => $selectedDoctor,
            'selectedService' => $selectedService,
            'selectedDate' => $selectedDate,
            'availableSlots' => $availableSlots,
            'stats' => [
                'doctors' => User::query()->where('role', UserRole::Doctor->value)->count(),
                'services' => Service::query()->count(),
                'bookings' => Booking::query()->count(),
                'payments' => Payment::query()->where('payment_status', PaymentStatus::Paid->value)->count(),
            ],
        ]);
    }

    public function services(): View
    {
        return view('public.services', [
            'services' => Service::query()->latest()->paginate(9),
        ]);
    }

    public function doctors(): View
    {
        return view('public.doctors', [
            'doctors' => User::query()
                ->where('role', UserRole::Doctor->value)
                ->with(['doctorProfile', 'doctorSchedules'])
                ->paginate(9),
        ]);
    }
}
