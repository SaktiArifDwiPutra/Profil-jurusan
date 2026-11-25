@extends('layouts.app')

@section('content')

<div class="layout-golden">
  <div class="left-col">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Daftar Nilai Siswa</h4>
    </div>

    <div class="d-flex justify-content-between mb-2 align-items-center">
      <div class="input-group w-50">
        <span class="input-group-text"><i class="fa fa-search"></i></span>
        <input type="text" id="searchInputNilai" class="form-control" placeholder="Cari siswa (NISN, Nama, Kelas)...">
      </div>
      <div></div>
    </div>

    <div id="siswaContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-5">
      @if($siswas->isEmpty())
        <div class="col">
          <div class="card p-3 text-center text-muted">Tidak ada data siswa</div>
        </div>
      @else
        @foreach($siswas as $s)
          <?php
            $kelas_label = $s->tingkat ? $s->tingkat . ' - ' . $s->kelas : 'Belum Ada Kelas';
            $data = [
              'id' => $s->id_siswa,
              'nisn' => $s->nisn,
              'nama' => $s->nama,
              'kelas' => $kelas_label
            ];
            $data_json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
          ?>
          <div class="col siswa-card-col"
               data-siswa='{{ $data_json }}'
               data-id="{{ $s->id_siswa }}"
               data-nisn="{{ strtolower($s->nisn) }}"
               data-nama="{{ strtolower($s->nama) }}"
               data-kelas="{{ strtolower($kelas_label) }}">
            <div class="card siswa-card shadow-sm h-100">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h5 class="card-title mb-0" style="font-size:1rem;">{{ $s->nama }}</h5>
                  <span class="badge rounded-pill text-bg-secondary" style="font-size:.75rem;">{{ $kelas_label }}</span>
                </div>
                <div class="mb-2 text-muted" style="font-size:.9rem;">
                  <div><strong>NISN:</strong> {{ $s->nisn ?? '-' }}</div>
                </div>
                <div style="margin-top:auto; display:flex; gap:.5rem;">
                  <a href="{{ route('admin.nilai.show', $s->id_siswa) }}" class="btn btn-sm btn-primary flex-grow-1">Lihat Nilai</a>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      @endif
    </div>
  </div>

  {{-- Sidebar --}}
  <aside class="sidebar">
    <div class="stat-card">
      <h6>Total Siswa</h6>
      <div class="stat-value">{{ $total_siswa }}</div>
    </div>
    <div class="stat-card">
      <h6>Jumlah Kelas</h6>
      <div>{{ count($kelas_count) }}</div>
    </div>
    <div class="stat-card">
      <h6>Distribusi Kelas</h6>
      <div>
        @foreach($kelas_count as $label => $cnt)
          <div style="font-size:.95rem; color:#333; margin-bottom:4px;">{{ $label }}: <strong>{{ $cnt }}</strong></div>
        @endforeach
      </div>
    </div>
    <div class="stat-card">
      <h6>Terakhir Ditambahkan</h6>
      <div class="recent-list">
        @forelse(array_reverse($recent->all()) as $r)
          <div class="recent-item" style="cursor:pointer" onclick="document.querySelector('.siswa-card-col[data-id=&quot;{{ $r->id_siswa }}&quot;]')?.scrollIntoView({behavior:'smooth'});">
            <div style="min-width:0;">
              <strong>{{ $r->nama }}</strong>
              <div style="font-size:0.85rem;color:#666">{{ $r->tingkat ? $r->tingkat . ' - ' . $r->kelas : 'Belum Ada Kelas' }}</div>
            </div>
          </div>
        @empty
          <div class="recent-item">Belum ada data</div>
        @endforelse
      </div>
    </div>
  </aside>

</div>

<script>
  document.getElementById('searchInputNilai')?.addEventListener('input', function(){
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.siswa-card-col').forEach(col=>{
      const nisn = (col.dataset.nisn || '');
      const nama = (col.dataset.nama || '');
      const kelas = (col.dataset.kelas || '');
      const hay = nisn + ' ' + nama + ' ' + kelas;
      col.style.display = (!q || hay.includes(q)) ? '' : 'none';
    });
  });
</script>
@endsection