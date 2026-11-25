@extends('layouts.app')

@section('content')

<div class="container">
    <div class="card p-3">
        <h4>Tambah Berita</h4>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="mb-2 small text-muted">Posting sebagai: <strong>{{ auth()->user()->username }} ({{ auth()->user()->role }})</strong></div>

        <form method="POST" action="{{ route('admin.berita.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label">Judul</label>
                <input type="text" name="judul" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Isi</label>
                <textarea name="isi" rows="8" class="form-control" required></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Gambar Utama (opsional)</label>
                <input type="file" name="gambar" accept="image/*" class="form-control">
            </div>

            <div class="mt-2">
                <button class="btn btn-primary">Simpan Berita</button>
                <a href="{{ route('dashboard') }}" class="btn btn-danger ms-2">Batal</a>
            </div>
        </form>
    </div>
</div>

@endsection