@extends('layouts.app')

@section('content')

<div class="layout-golden">
  <div class="left-col">
    <div class="card shadow-sm p-4 mb-4">
       <h4 class="fw-bold mb-1">Nilai Saya</h4>
       <p class="mb-3 text-muted">Data nilai untuk <strong>{{ $siswa->nama }}</strong> (Kelas {{ $siswa->kelas->tingkat }} {{ $siswa->kelas->kelas }})</p>

       <div class="d-flex justify-content-end gap-2 mb-3">
       </div>

       <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead>
           <tr>
            <th>No</th>
            <th>Mapel</th>
            <th>Guru</th>
            <th>Semester</th>
            <th>Tugas</th>
            <th>UTS</th>
            <th>Akhir</th>
           </tr>
          </thead>
          <tbody>
           @forelse($nilai as $index => $n)
            <tr>
             <td>{{ $index + 1 }}</td>
             <td>
                {{ $n->ampu->mapel->nama_mapel }} <br>
                <span class="badge text-bg-secondary">{{ $n->ampu->mapel->kategori }}</span>
             </td>
             <td>{{ $n->ampu->guru->nama }}</td>
             <td>{{ $n->semester }}</td>
             <td>{{ $n->nilai_tugas }}</td>
             <td>{{ $n->nilai_uts }}</td>
             <td class="fw-bold">{{ $n->nilai_akhir }}</td>
            </tr>
           @empty
            <tr><td colspan="7" class="text-center">Belum ada nilai.</td></tr>
           @endforelse
          </tbody>
        </table>
       </div>
    </div>
  </div>

  <aside class="sidebar">
    <div class="stat-card">
       <h6>Rata-rata Nilai Akhir (Global)</h6>
       <div class="stat-value text-primary">{{ $rata_rata_global }}</div>
    </div>

    <div class="stat-card">
       <h6>Jumlah Mapel Tercatat</h6>
       <div class="stat-value">{{ $total_mapel }}</div>
    </div>

    <div class="stat-card">
       <h6>Rata-rata Nilai per Semester</h6>
       <div class="recent-list">
        @forelse($nilai_per_semester as $sem => $data)
           @php $avg = $data['count'] > 0 ? round($data['sum'] / $data['count'], 2) : 0; @endphp
           <div class="recent-item">
             <div>Semester {{ $sem }} ({{ $data['count'] }} mapel):</div>
             <strong class="text-primary">{{ $avg }}</strong>
           </div>
        @empty
           <div class="recent-item">Belum ada data nilai</div>
        @endforelse
       </div>
    </div>
  </aside>
</div>
@endsection