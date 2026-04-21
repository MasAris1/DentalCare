@if (session('success'))
    <div class="alert alert-success border-0 shadow-sm" role="alert">
        {{ session('success') }}
    </div>
@endif

@if (session('status'))
    <div class="alert alert-info border-0 shadow-sm" role="alert">
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger border-0 shadow-sm" role="alert">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm" role="alert">
        <div class="fw-semibold mb-1">Periksa kembali input Anda.</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
