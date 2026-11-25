@extends('layouts.app')
@section('content')


<div class="card mb-4 shadow-sm border-0 overflow-hidden"
     style="background:url('{{ asset('assets/bg/Background.png') }}') no-repeat center center;
            background-size:cover; min-height:350px;">
</div>

<div class="row">
  <div class="col-md-8">

    <div class="card mb-4 p-4 shadow-sm">
      <h1 class="mb-1 fw-bold">Dashboard</h1>
      <p class="mb-0 text-muted">Selamat datang di web Profil Jurusan RPL 👋</p>
      <p class="mb-0 text-muted">Silahkan login untuk membuka berbagai fitur</p>
    </div>

    <h4 class="fw-semibold mb-3">Berita Terbaru</h4>

    @forelse($berita as $b)
    <div class="card mb-4 shadow-sm overflow-hidden">
      <div class="row g-0">

        @if($b->gambar)
        <div class="col-md-5 d-none d-md-block" style="max-height:220px; overflow:hidden;">
          <img src="{{ asset('uploads/'.$b->gambar) }}"
               class="img-fluid h-100 w-100" style="object-fit:cover;">
        </div>
        @endif

        <div class="{{ $b->gambar ? 'col-md-7' : 'col-12' }}">
          <div class="p-3 d-flex flex-column h-100">

            <div class="small text-muted mb-2">
              {{ $b->tanggal_post ?? '-' }} ·
              @if($b->user)
                {{ ucfirst($b->user->role) }} - {{ $b->user->username }}
              @elseif($b->guru)
                Guru - {{ $b->guru->nama }}
              @else
                Admin
              @endif
            </div>

            <h5 class="fw-bold">{{ $b->judul }}</h5>

            <p class="flex-grow-1">
              {{ \Illuminate\Support\Str::limit($b->isi,200) }}
            </p>

            <a href="{{ route('berita.detail',$b->id_berita) }}" class="btn btn-outline-dark mt-auto">
              Baca selengkapnya
            </a>

          </div>
        </div>
      </div>
    </div>
    @empty
      <div class="alert alert-info">Belum ada berita.</div>
    @endforelse


    <div class="card p-4 mb-4 shadow-sm">
      <h4>Tentang Jurusan</h4>
      <p>Jurusan PPLG adalah kompetensi keahlian di SMK yang fokus pada pengembangan perangkat lunak seperti aplikasi web, mobile, dan game. Siswa mempelajari pemrograman, basis data, dan desain antarmuka sesuai kebutuhan industri.
      Lulusan PPLG memiliki peluang karier luas di bidang teknologi informasi, seperti software developer, web developer, mobile app developer, dan game developer.</p>
      <ul class="mb-0">
        <li>Visi: 
          Menjadi jurusan unggulan dalam bidang pengembangan perangkat lunak dan gim yang menghasilkan lulusan kompeten, kreatif, dan siap bersaing di dunia industri serta dunia kerja.
        </li>
        <li>Misi: <br>
          <strong>1.</strong>Menyelenggarakan pendidikan dan pembelajaran pemrograman berbasis teknologi terkini.Membekali peserta didik dengan keterampilan pengembangan aplikasi web, mobile, dan gim.<br>
          <strong>2.</strong>Menanamkan sikap disiplin, tanggung jawab, dan etos kerja profesional.<br>
          <strong>3.</strong>Mendorong kreativitas, inovasi, dan kemampuan problem solving siswa.<br>
          <strong>4.</strong>Menjalin kerja sama dengan dunia industri untuk meningkatkan kualitas lulusan.
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
      <a href={{ route('login', ['id'=>1]) }} class="btn btn-outline-primary">Login terlebih dahulu</a>
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
      <div class="mt-2"><a href={{ route('login', ['id'=>1]) }} class="btn btn-outline-primary btn-sm">Login terlebih dahulu</a></div>

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
      <div class="mt-2"><a href={{ route('login', ['id'=>1]) }} class="btn btn-outline-primary btn-sm">Login terlebih dahulu</a></div>

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
      <div class="mt-2"><a href={{ route('login', ['id'=>1]) }} class="btn btn-outline-primary btn-sm">Login terlebih dahulu</a></div>

      </div>

      <div class="card p-4 shadow-sm">
        <h6 class="fw-semibold mb-3">Kontak</h6>
        <small>Alamat: Sekolah / Jurusan</small>
        <small>Telepon: (021) xxxx xxxx</small>
        <small>Email: sekretariat@sekolah.sch.id</small>
      </div>
    </div>

  </div>
</div>

@endsection
