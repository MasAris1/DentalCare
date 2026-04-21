@php
    $label = $label ?? 'Lanjutkan dengan Google';
    $hint = $hint ?? null;
@endphp

<div class="d-grid gap-2">
    <a class="btn btn-outline-dark rounded-pill w-100 d-inline-flex align-items-center justify-content-center gap-2 py-2" href="{{ route('auth.google.redirect', request()->filled('redirect') ? ['redirect' => request('redirect')] : []) }}">
        <svg aria-hidden="true" focusable="false" height="18" viewBox="0 0 24 24" width="18">
            <path d="M21.8 12.23c0-.68-.06-1.33-.17-1.95H12v3.69h5.5a4.71 4.71 0 0 1-2.04 3.09v2.56h3.3c1.93-1.78 3.04-4.39 3.04-7.39Z" fill="#4285F4"/>
            <path d="M12 22c2.76 0 5.08-.91 6.77-2.47l-3.3-2.56c-.91.61-2.08.98-3.47.98-2.67 0-4.94-1.8-5.75-4.22H2.84v2.64A9.99 9.99 0 0 0 12 22Z" fill="#34A853"/>
            <path d="M6.25 13.73A5.98 5.98 0 0 1 5.93 12c0-.6.11-1.18.32-1.73V7.63H2.84a9.99 9.99 0 0 0 0 8.74l3.41-2.64Z" fill="#FBBC04"/>
            <path d="M12 6.05c1.5 0 2.84.52 3.89 1.53l2.91-2.91C17.08 3.07 14.76 2 12 2A9.99 9.99 0 0 0 2.84 7.63l3.41 2.64c.81-2.42 3.08-4.22 5.75-4.22Z" fill="#EA4335"/>
        </svg>
        <span>{{ $label }}</span>
    </a>

    @if ($hint)
        <p class="text-secondary small text-center mb-0">{{ $hint }}</p>
    @endif
</div>
