@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', 'Buat Reservasi | '.config('clinic.name'))
@section('page_kicker', 'Area Pasien')
@section('page_title', 'Buat Reservasi')

@section('content')
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="text-uppercase small text-secondary mb-2">Langkah 1</div>
                    <h1 class="h4 fw-bold mb-3">Pilih dokter, layanan, dan tanggal kunjungan</h1>
                    <p class="text-secondary">Sistem akan menampilkan slot yang tersedia berdasarkan jadwal praktik, durasi layanan, dan kuota harian dokter.</p>

                    <form method="GET" action="{{ route('booking.create') }}" class="d-grid gap-3">
                        <div>
                            <label class="form-label">Dokter</label>
                            <select class="form-select" name="doctor_id" required>
                                <option value="">Pilih dokter</option>
                                @foreach ($doctors as $doctor)
                                    <option value="{{ $doctor->id }}" @selected(request('doctor_id') == $doctor->id)>
                                        {{ $doctor->name }} - {{ $doctor->doctorProfile?->specialization }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Layanan</label>
                            <select class="form-select" name="service_id" required>
                                <option value="">Pilih layanan</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}" @selected(request('service_id') == $service->id)>
                                        {{ $service->name }} - {{ $service->duration_minutes }} menit
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Tanggal kunjungan</label>
                            <input class="form-control" type="date" name="booking_date" min="{{ now()->toDateString() }}" value="{{ $selectedDate }}" required>
                        </div>

                        <button class="btn btn-primary rounded-pill" type="submit">Cek slot tersedia</button>
                    </form>

                    @if ($selectedDoctor)
                        <div class="selected-card mt-4">
                            <div class="fw-semibold mb-1">{{ $selectedDoctor->name }}</div>
                            <div class="small text-secondary mb-3">{{ $selectedDoctor->doctorProfile?->specialization }}</div>
                            @if ($selectedService)
                                <div class="small text-secondary mb-3">
                                    Layanan dipilih: <span class="fw-semibold text-dark">{{ $selectedService->name }}</span>
                                    • {{ $selectedService->duration_minutes }} menit
                                </div>
                            @endif
                            <div class="small text-secondary d-grid gap-1">
                                @foreach ($selectedDoctor->doctorSchedules as $schedule)
                                    <span>{{ $schedule->dayLabel() }} • {{ $schedule->formattedTimeRange() }} • Kuota {{ $schedule->quota }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="text-uppercase small text-secondary mb-2">Langkah 2</div>
                    <h2 class="h4 fw-bold mb-3">Pilih slot waktu dan simpan reservasi</h2>

                    @guest
                        <div class="alert alert-warning border-0">
                            Anda perlu masuk sebagai pasien untuk menyelesaikan reservasi.
                            <div class="mt-3 d-flex gap-2">
                                <a class="btn btn-dark rounded-pill px-4" href="{{ route('login', ['redirect' => url()->full()]) }}">Masuk</a>
                                <a class="btn btn-outline-dark rounded-pill px-4" href="{{ route('register', ['redirect' => url()->full()]) }}">Daftar</a>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('booking.store') }}" class="d-grid gap-3">
                            @csrf
                            <input type="hidden" name="doctor_id" value="{{ request('doctor_id') }}">
                            <input type="hidden" name="service_id" value="{{ request('service_id') }}">
                            <input type="hidden" name="booking_date" value="{{ $selectedDate }}">

                            @if ($selectedService)
                                <div class="alert alert-light border mb-0">
                                    <div class="fw-semibold">{{ $selectedService->name }}</div>
                                    <div class="small text-secondary">
                                        Durasi {{ $selectedService->duration_minutes }} menit • Rp {{ number_format($selectedService->price, 0, ',', '.') }}
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="form-label">Slot waktu</label>
                                @if ($selectedDoctor && $selectedService && $selectedDate)
                                    <div class="slot-grid">
                                        @forelse ($availableSlots as $slot)
                                            <label class="slot-option">
                                                <input type="radio" name="booking_time" value="{{ $slot }}" @checked(old('booking_time') === $slot) required>
                                                <span>{{ $slot }}</span>
                                            </label>
                                        @empty
                                            <div class="alert alert-light border">Belum ada slot tersedia pada tanggal ini. Coba dokter atau tanggal lain.</div>
                                        @endforelse
                                    </div>
                                @else
                                    <div class="alert alert-light border">Pilih dokter, layanan, dan tanggal terlebih dahulu untuk melihat slot tersedia.</div>
                                @endif
                            </div>

                            <div>
                                <label class="form-label">Catatan untuk dokter</label>
                                <textarea class="form-control" name="notes" rows="4" placeholder="Contoh: gigi sensitif, ingin konsultasi scaling, dll.">{{ old('notes') }}</textarea>
                            </div>

                            <button class="btn btn-primary rounded-pill" type="submit" @disabled(! $selectedDoctor || ! $selectedService || empty($availableSlots))>
                                Simpan reservasi dan lanjutkan pembayaran
                            </button>
                        </form>
                    @endguest
                </div>
            </div>
        </div>
    </div>
@endsection
