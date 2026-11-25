@extends('layouts.app')

@section('content')

<div class="row">
    <div class="col-md-8">
        {{-- ARTIKEL BERITA --}}
        <article class="card p-4 mb-4 shadow-sm">
            <header class="mb-3">
                <h2 class="fw-bold">{{ $berita->judul }}</h2>
                <div class="small text-muted">
                    {{ $berita->tanggal_post }}
                    &middot;
                    {{ $berita->user?->username ?? ($berita->guru?->nama ?? 'Admin') }}
                </div>
            </header>

            @if($berita->gambar)
                <img src="{{ asset('uploads/' . $berita->gambar) }}" 
                     alt="gambar berita" 
                     class="img-fluid mb-3 rounded" style="max-height:400px; object-fit:cover;">
            @endif

            <div class="mb-3">{!! nl2br(e($berita->isi)) !!}</div>

            <div class="d-flex justify-content-between">
                {{-- Tombol Kembali langsung ke Halaman Depan (Home) --}}
                <a href="{{ url('/') }}" class="btn btn-danger">← Kembali</a>
            </div>
        </article>

        {{-- BAGIAN KOMENTAR --}}
        <section id="comments" class="card p-4 shadow-sm">
            <h5 class="fw-semibold mb-3">Komentar ({{ $comments->count() }})</h5>

            @if($comments->isEmpty())
                <div class="text-muted mb-2">Belum ada komentar.</div>
            @endif

            {{-- LOOPING KOMENTAR (READ ONLY) --}}
            @foreach($comments as $c)
                <?php $author = $c->user?->username ?: 'Pengguna'; ?>
                <div class="d-flex mb-3">
                    <div class="me-3">
                        <div style="width:40px;height:40px;border-radius:50%;background:#dee7fb;display:flex;align-items:center;justify-content:center;font-weight:600;color:#0b5ed7;">
                            {{ strtoupper(substr($author, 0, 1)) }}
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <strong>{{ $author }}</strong>
                            <div class="small text-muted ms-2">{{ $c->tanggal_post }}</div>
                            {{-- TIDAK ADA TOMBOL HAPUS DISINI --}}
                        </div>
                        <div>{!! nl2br(e($c->komentar)) !!}</div>
                    </div>
                </div>
            @endforeach

            {{-- FORMULIR TERKUNCI (LOGIN PROMPT) --}}
            <div class="mt-4 pt-3 border-top">
                <div class="bg-light p-3 rounded text-center border">
                    <i class="fa fa-lock mb-2 text-muted" style="font-size: 1.5rem;"></i>
                    <h6 class="text-muted fw-semibold">Ingin bergabung dalam diskusi?</h6>
                    <p class="small text-muted mb-3">Silahkan login terlebih dahulu untuk menulis komentar.</p>
                    
                    {{-- Dummy textarea yang terkunci --}}
                    <textarea class="form-control mb-3" rows="2" disabled 
                        style="cursor: not-allowed; background-color: #e9ecef;">Login terlebih dahulu untuk berkomentar...</textarea>
                    
                    <a href="{{ route('login') }}" class="btn btn-primary px-4">
                        <i class="fa fa-sign-in-alt me-1"></i> Login Sekarang
                    </a>
                </div>
            </div>

        </section>
    </div>

    {{-- SIDEBAR BERITA LAIN --}}
    <div class="col-md-4">
        <div class="card p-4 shadow-sm mb-4">
            <h6 class="fw-semibold mb-3">Berita Lainnya</h6>
            <ul class="list-unstyled mb-0">
                @foreach($otherBerita as $o)
                    <li class="mb-2">
                        {{-- Arahkan ke route detail yang sama --}}
                        <a href="{{ route('berita.detail', $o->id_berita) }}" class="link-primary fw-medium">
                            {{ $o->judul }}
                        </a>
                        <div class="small text-muted">{{ $o->tanggal_post }}</div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

{{-- SCRIPT SEDERHANA (Hapus confirmAction karena tidak ada tombol delete) --}}
<script>
    @if(session('success'))
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
                timer: 3000,
                showConfirmButton: false
            });
        });
    @endif
</script>

@endsection