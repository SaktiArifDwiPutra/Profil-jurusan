@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="alert alert-warning text-center shadow-sm p-5">
        <h4 class="alert-heading fw-bold"><i class="fa fa-exclamation-triangle me-2"></i> Akses Dibatasi</h4>
        <p class="mb-0 fs-5">Anda tidak terdaftar sebagai Wali Kelas.</p>
        <hr>
        <p class="mb-0">Fitur absensi kelas hanya tersedia bagi guru yang menjabat sebagai Wali Kelas.</p>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-dark mt-3">Kembali ke Dashboard</a>
    </div>
</div>
@endsection