@extends('layouts.app')

@section('content')

<div class="card mb-4 shadow-sm border-0 overflow-hidden"
     style="background:url('{{ asset('assets/bg/Background.png') }}') no-repeat center;
            background-size:cover; min-height:350px;">
</div>
  <div class="row">
    <div class="col-md-8">

      <div class="card mb-4 p-4 shadow-sm">
        {{-- LOGIKA HEADER DINAMIS --}}
        @if(auth()->user()->role === 'admin')
            <h1 class="fw-bold mb-1">Dashboard Admin</h1>
            <p class="text-muted mb-0">Kelola berita, guru, siswa, kelas, dan mapel 👋</p>
        @else
            <h1 class="fw-bold mb-1">Halo, {{ auth()->user()->username }}!</h1>
            <p class="text-muted mb-0">Selamat datang di halaman dashboard {{ auth()->user()->role }} 👋</p>
        @endif
      </div>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-semibold">Berita Terbaru</h4>
        <a href="{{ route('admin.berita.create') }}" class="btn btn-success">+ Tambah Berita </a>
      </div>

      @if($berita->isEmpty())
        <div class="alert alert-info">Belum ada berita.</div>
      @else
        @foreach($berita as $b)
          <div class="card mb-4 shadow-sm overflow-hidden border {{ !$b->tanggal_post ? 'border-warning' : '' }}">
            <div class="row g-0">
              @if($b->gambar)
                <div class="col-md-5 d-none d-md-block" style="max-height:220px; overflow:hidden;">
                  <img src="{{ asset('uploads/' . $b->gambar) }}" 
                       class="img-fluid h-100 w-100" 
                       style="object-fit:cover;" alt="gambar berita">
                </div>
              @endif

              <div class="{{ $b->gambar ? 'col-md-7' : 'col-12' }}">
                <div class="p-3 d-flex flex-column h-100">
                  <div class="small text-muted mb-2">
                    @if($b->tanggal_post)
                      {{ \Carbon\Carbon::parse($b->tanggal_post)->format('d M Y') }}
                    @else
                      <span class="badge bg-warning text-dark">Menunggu Persetujuan</span>
                    @endif
                    &middot; {{ $b->user?->username ?? ($b->guru?->nama ?? 'Admin') }}
                  </div>

                  <h5 class="fw-bold mb-2">{{ $b->judul ?? 'Tanpa Judul' }}</h5>
                  <p class="flex-grow-1 mb-2">
                    {!! nl2br(e(strlen($b->isi) > 200 ? substr($b->isi, 0, 200) . '...' : $b->isi)) !!}
                  </p>

                  <div class="d-flex justify-content-between align-items-center mt-auto">
                    {{-- Tombol Baca Selengkapnya --}}
                    @if($b->tanggal_post)
                       <a href="{{ route('admin.berita.show', $b->id_berita) }}" class="btn btn-outline-dark btn-sm">Baca selengkapnya</a>
                    @else
                       {{-- Jika pending dan milik sendiri, boleh lihat preview --}}
                       @if($b->created_by == auth()->id() || auth()->user()->role == 'admin')
                           <a href="{{ route('admin.berita.show', $b->id_berita) }}" class="btn btn-outline-secondary btn-sm">Preview</a>
                       @else
                           <span class="text-muted small">Sedang ditinjau</span>
                       @endif
                    @endif

                    <div class="d-flex gap-2">
                      {{-- LOGIKA TOMBOL ACC (HANYA ADMIN) --}}
                      @if(auth()->user()->role === 'admin' && !$b->tanggal_post)
                        <form id="form-acc-{{ $b->id_berita }}" method="POST" action="{{ route('admin.berita.acc', $b->id_berita) }}" style="display:inline;">
                          @csrf
                          <button type="button" class="btn btn-success btn-sm" 
                                  onclick="confirmAction('form-acc-{{ $b->id_berita }}', 'Setujui berita ini agar tayang?', 'Ya, Setujui!', 'question')">
                              ACC
                          </button>
                        </form>

                        <form id="form-tolak-{{ $b->id_berita }}" method="POST" action="{{ route('admin.berita.destroy', $b->id_berita) }}" style="display:inline;">
                          @csrf
                          @method('DELETE')
                          <button type="button" class="btn btn-warning btn-sm" 
                                  onclick="confirmAction('form-tolak-{{ $b->id_berita }}', 'Tolak dan hapus berita ini?', 'Ya, Tolak!')">
                              Tolak
                          </button>
                        </form>
                      @endif

                      {{-- LOGIKA EDIT & HAPUS (ADMIN ATAU PEMILIK BERITA) --}}
                      @if(auth()->user()->role === 'admin' || $b->created_by === auth()->id())
                        {{-- Tombol Edit hanya muncul jika sudah ACC atau milik sendiri --}}
                        <a href="{{ route('admin.berita.edit', $b->id_berita) }}" class="btn btn-primary btn-sm">Edit</a>

                        <form id="form-delete-{{ $b->id_berita }}" method="POST" action="{{ route('admin.berita.destroy', $b->id_berita) }}" style="display:inline;">
                          @csrf
                          @method('DELETE')
                          <button type="button" class="btn btn-danger btn-sm" 
                                  onclick="confirmAction('form-delete-{{ $b->id_berita }}', 'Yakin ingin menghapus berita ini?')">
                              Hapus
                          </button>
                        </form>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      @endif

      <div class="card p-4 mb-4 shadow-sm">
        <h4>Tentang Jurusan</h4>
        <p>Jurusan PPLG adalah kompetensi keahlian di SMK yang fokus pada pengembangan perangkat lunak seperti aplikasi web, mobile, dan game. Siswa mempelajari pemrograman, basis data, dan desain antarmuka sesuai kebutuhan industri.
        Lulusan PPLG memiliki peluang karier luas di bidang teknologi informasi, seperti software developer, web developer, mobile app developer, dan game developer.</p>
        
        <ul class="mb-0">
          <li><strong>Visi:</strong><br>
            Menjadi jurusan unggulan dalam bidang pengembangan perangkat lunak dan gim yang menghasilkan lulusan kompeten, kreatif, dan siap bersaing di dunia industri serta dunia kerja.
          </li>
          <li><strong>Misi:</strong><br>
            <strong>1.</strong> Menyelenggarakan pendidikan dan pembelajaran pemrograman berbasis teknologi terkini. Membekali peserta didik dengan keterampilan pengembangan aplikasi web, mobile, dan gim.<br>
            <strong>2.</strong> Menanamkan sikap disiplin, tanggung jawab, dan etos kerja profesional.<br>
            <strong>3.</strong> Mendorong kreativitas, inovasi, dan kemampuan problem solving siswa.<br>
            <strong>4.</strong> Menjalin kerja sama dengan dunia industri untuk meningkatkan kualitas lulusan.
          </li>
        </ul>
      </div>
      <div class="card p-4 mb-4 shadow-sm">
        <h5>Mata Pelajaran</h5>
        <div class="d-flex flex-wrap gap-2">
          @foreach($mapel as $m)
            <span class="badge bg-light text-dark border">
              {{ $m->nama_mapel }} <small>/ {{ $m->kategori }}</small>
            </span>
          @endforeach
        </div>
        <a href="{{ route('admin.mapel.index') }}" class="btn btn-outline-primary mt-3">Lihat semua mapel</a>
      </div>

    </div>

    <div class="col-md-4">

      <div class="card p-4 mb-4 shadow-sm text-center">
        <h6 class="fw-semibold mb-3">Statistik</h6>
        <div class="row">
          <div class="col-6">
            <h3>{{ $siswa_count }}</h3>
            <small>Siswa</small>
          </div>
          <div class="col-6">
            <h3>{{ $guru_count }}</h3>
            <small>Guru</small>
          </div>
          <div class="col-6">
            <h3>{{ $kelas_count }}</h3>
            <small>Kelas</small>
          </div>
          <div class="col-6">
            <h3>{{ $mapel_count }}</h3>
            <small>Mapel</small>
          </div>
        </div>
      </div>

      <div class="card p-4 mb-4 shadow-sm">
        <h6>Beberapa Guru</h6>
        <ul class="list-group list-group-flush">
          @forelse($guru as $g)
            <li class="list-group-item">
              <strong>{{ $g->nama }}</strong><br>
              <small>{{ $g->nip }}</small>
            </li>
          @empty
            <li class="list-group-item">Belum ada data guru.</li>
          @endforelse
        </ul>
        <div class="mt-2">
          <a href="{{ route('admin.guru.index') }}" class="btn btn-outline-primary btn-sm">Lihat semua guru</a>
        </div>
      </div>

      <div class="card p-4 mb-4 shadow-sm">
        <h6>Beberapa Siswa</h6>
        <ul class="list-group list-group-flush">
          @foreach($siswa_preview as $s)
            <li class="list-group-item">
              {{ $s->nama }} <br>
              <small>NISN: {{ $s->nisn }}</small>
            </li>
          @endforeach
        </ul>
        <div class="mt-2">
          <a href="{{ route('admin.siswa.index') }}" class="btn btn-outline-primary btn-sm">Lihat semua siswa</a>
        </div>
      </div>

      <div class="card p-4 mb-4 shadow-sm">
        <h6>Daftar Kelas</h6>
        <ul class="list-group list-group-flush">
          @forelse($kelas as $k)
            <li class="list-group-item">
              <small>{{ $k->tingkat }}</small> /
              <small>{{ $k->kelas }}</small>
            </li>
          @empty
            <li class="list-group-item">Belum ada data kelas.</li>
          @endforelse
        </ul>
        <div class="mt-2">
          <a href="{{ route('admin.kelas.index') }}" class="btn btn-outline-primary btn-sm">Lihat semua kelas</a>
        </div>
      </div>

      <div class="card p-4 shadow-sm">
        <h6 class="fw-semibold mb-3">Kontak</h6>
        <small>Alamat: Sekolah / Jurusan</small><br>
        <small>Telepon: (021) xxxx xxxx</small><br>
        <small>Email: sekretariat@sekolah.sch.id</small>
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