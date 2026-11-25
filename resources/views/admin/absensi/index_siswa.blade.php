@extends('layouts.app')

@section('content')

<div class="layout-golden">
  <div class="left-col">
    <div class="card shadow-sm p-4 mb-4">
      <h4 class="fw-bold mb-1">Absensi Siswa</h4>
      <div class="mb-3">
        <p class="mb-0"><strong>{{ $siswa->nama }}</strong> ({{ $siswa->nisn }})</p>
        <p class="text-muted mb-0">Tanggal Hari Ini: {{ date('d-m-Y') }}</p>
      </div>

      @if(session('error'))
        <div class="alert alert-warning">{{ session('error') }}</div>
      @endif

      @if($cek_absen)
        <div class="alert alert-success mt-2 mb-0">
            Kamu sudah absen hari ini sebagai <strong>{{ $cek_absen->status }}</strong>.
        </div>
      @else
        <div class="card p-3 mt-2 border-primary">
          <h6 class="mb-3 text-primary">Input Kehadiran Hari Ini</h6>
          <form method="POST" action="{{ route('admin.absensi.update') }}" class="row g-3">
            @csrf
            {{-- Siswa tidak perlu kirim ID Siswa/Tanggal (otomatis di controller) --}}
            
            <div class="col-md-6">
              <label for="statusKehadiran" class="form-label">Status Kehadiran</label>
              <select name="status" id="statusKehadiran" class="form-select" required>
                <option value="">Pilih</option>
                <option value="Hadir">Hadir</option>
                <option value="Izin">Izin</option>
                <option value="Sakit">Sakit</option>
              </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <button class="btn btn-primary w-100">Kirim Absen</button>
            </div>
          </form>
        </div>
      @endif
    </div>

    <div class="card shadow-sm p-4">
      <h5 class="mb-3">Riwayat Absensi</h5>
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($riwayat as $i => $r)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d-m-Y') }}</td>
                <td>
                  @php
                    $badges = [
                        'hadir' => 'success', 'izin' => 'warning', 'sakit' => 'info', 'alpha' => 'danger'
                    ];
                    $bg = $badges[$r->status] ?? 'secondary';
                  @endphp
                  <span class="badge rounded-pill text-bg-{{ $bg }}">
                    {{ $r->status }}
                  </span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <aside class="sidebar">
    <div class="stat-card">
      <h6>Total Absensi</h6>
      <div class="stat-value">{{ $total_absen }} Hari</div>
    </div>

    <div class="stat-card">
      <h6>Statistik</h6>
      <div class="recent-list">
        <div class="recent-item"><div>Hadir:</div><strong>{{ $stats['Hadir'] }}</strong></div>
        <div class="recent-item"><div>Sakit:</div><strong>{{ $stats['Sakit'] }}</strong></div>
        <div class="recent-item"><div>Izin:</div><strong>{{ $stats['Izin'] }}</strong></div>
        <div class="recent-item"><div>Alpha:</div><strong>{{ $stats['Alpha'] }}</strong></div>
      </div>
    </div>
  </aside>
</div>

<script>
    @if(session('success'))
        Swal.fire({icon: 'success', title: 'Berhasil', text: '{{ session('success') }}', showConfirmButton: false, timer: 2000});
    @endif
</script>
@endsection