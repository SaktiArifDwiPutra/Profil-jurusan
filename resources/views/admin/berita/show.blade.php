@extends('layouts.app')

@section('content')

<div class="row">
    <div class="col-md-8">
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
                <a href="{{ route('dashboard') }}" class="btn btn-danger">← Kembali</a>
            </div>
        </article>

        <section id="comments" class="card p-4 shadow-sm">
            <h5 class="fw-semibold mb-3">Komentar ({{ $comments->count() }})</h5>

            @if($comments->isEmpty())
                <div class="text-muted mb-2">Belum ada komentar. Jadilah yang pertama!</div>
            @endif

            @foreach($comments as $c)
                <?php $author = $c->user?->username ?: 'admin'; ?>
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
                            <div class="ms-auto">
                                @if(auth()->user()->role === 'admin' || $c->id_user === auth()->id())
                                    <form id="form-del-komentar-{{ $c->id_komentar }}" method="POST" action="{{ route('admin.komentar.delete', $c->id_komentar) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmAction('form-del-komentar-{{ $c->id_komentar }}', 'Hapus komentar ini?')">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <div>{!! nl2br(e($c->komentar)) !!}</div>
                    </div>
                </div>
            @endforeach

            <form method="POST" action="{{ route('admin.berita.comment.store', $berita->id_berita) }}" class="mt-3">
                @csrf
                <label class="form-label">Komentar sebagai <strong>{{ auth()->user()->username }}</strong></label>
                <textarea name="komentar" rows="3" class="form-control mb-2" required></textarea>
                <button class="btn btn-primary">Kirim Komentar</button>
            </form>
        </section>
    </div>

    <div class="col-md-4">
        <div class="card p-4 shadow-sm mb-4">
            <h6 class="fw-semibold mb-3">Berita Lainnya</h6>
            <ul class="list-unstyled mb-0">
                @foreach($otherBerita as $o)
                    <li class="mb-2">
                        <a href="{{ route('admin.berita.show', $o->id_berita) }}" class="link-primary fw-medium">
                            {{ $o->judul }}
                        </a>
                        <div class="small text-muted">{{ $o->tanggal_post }}</div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

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

    @if(session('error'))
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: "{{ session('error') }}",
            });
        });
    @endif

    function confirmAction(formId, message, confirmText = 'Ya, Lanjutkan!', icon = 'warning') {
        Swal.fire({
            title: 'Konfirmasi',
            text: message,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }
</script>

@endsection