@extends('layouts.app')

@section('content')

<div class="container">
    <div class="card p-4 shadow-sm">
        <h4 class="fw-semibold mb-3">Edit Berita</h4>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.berita.update', $berita->id_berita) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Judul</label>
                <input type="text" name="judul" class="form-control" value="{{ $berita->judul }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Isi</label>
                <textarea name="isi" rows="8" class="form-control" required>{{ $berita->isi }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Gambar Utama (opsional)</label>
                <input type="file" name="gambar" accept="image/*" class="form-control">
                @if($berita->gambar)
                    <div class="mt-3">
                        <p class="small text-muted mb-1">Gambar saat ini:</p>
                        <img src="{{ asset('uploads/' . $berita->gambar) }}" 
                             alt="Gambar berita" 
                             style="max-width:100%;border-radius:8px;max-height:220px;object-fit:cover;">
                    </div>
                @endif
            </div>

            <div class="mt-3">
                <button class="btn btn-primary">Update</button>
                <a href="{{ route('dashboard') }}" class="btn btn-danger ms-2">Batal</a>
            </div>
        </form>
    </div>
</div>

@endsection