@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Login Accurate Online</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form method="POST" action="{{ route('accurate.login.submit') }}">
        @csrf
        <div class="mb-3">
            <label for="code" class="form-label">Kode Autentikasi</label>
            <input type="text" class="form-control" id="code" name="code" required>
        </div>
        <button type="submit" class="btn btn-primary">Get Token</button>
    </form>
    <form method="POST" action="{{ route('accurate.syncAll') }}" class="mt-4">
        @csrf
        <button type="submit" class="btn btn-success">Sync Semua Data Tenant ke Accurate Online</button>
    </form>
</div>
@endsection