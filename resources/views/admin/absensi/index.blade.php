@extends('layouts.app')

@section('content')

<style>
.layout-golden { display: grid; grid-template-columns: 1fr 320px; gap: 20px; align-items: start; margin-top: 1rem; }
@media (max-width: 992px) { .layout-golden { grid-template-columns: 1fr; } .sidebar { order: 2; } .left-col { order: 1; } }
.left-col { min-width: 0; }
.sidebar { position: sticky; top: 1rem; align-self: start; }
.stat-card { background:#fff; border:1px solid rgba(0,0,0,0.06); padding:12px; margin-bottom:12px; border-radius:8px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
.stat-card h6 { margin:0 0 8px 0; font-size:.92rem; }
.stat-value { font-size:1.4rem; font-weight:700; color:#1f3d7a; }
.recent-list { display:flex; flex-direction:column; gap:8px; }
.recent-item { padding:6px; border-radius:6px; }
.recent-item:hover { background: rgba(0,0,0,0.03); cursor:pointer; }
.table-responsive { overflow-x:auto; }
td.edit form select.form-select { width: 160px; }
@media (max-width:576px){
 td.edit form { flex-direction:column; align-items:stretch; gap:.5rem; }
 td.edit form select.form-select { width:100%; }
}
</style>

<div class="layout-golden">

  <div class="left-col">
    <div class="card p-4 shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Absensi - Admin</h4>
          <div class="text-muted" style="font-size:.9rem; margin-top:3px;">
            Tanggal: <strong>{{ $tanggal }}</strong>
            @if($f_tingkat) • Tingkat: <strong>{{ $f_tingkat }}</strong>@endif
            @if($f_kelas) • Kelas: <strong>{{ $f_kelas }}</strong>@endif
          </div>
        </div>
      </div>

      <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
          <label class="form-label">Tingkat</label>
          <select name="tingkat" class="form-select" onchange="this.form.submit()">
            <option value="">Semua</option>
            @foreach($unique_tingkat as $t)
              <option value="{{ $t }}" {{ $f_tingkat == $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Kelas</label>
          <select name="kelas" class="form-select" onchange="this.form.submit()">
            <option value="">Semua</option>
            @foreach($kelas_list as $k)
              <option value="{{ $k->kelas }}" {{ $f_kelas == $k->kelas ? 'selected' : '' }}>{{ $k->kelas }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tanggal</label>
          <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" onchange="this.form.submit()">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <a href="{{ route('admin.absensi.index') }}" class="btn btn-secondary w-100">Reset</a>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-striped" id="absensiTable">
          <thead>
            <tr>
              <th style="width:60px">No</th>
              <th>Nama</th>
              <th>Kelas</th>
              <th>Status</th>
              <th style="width:140px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @if($siswa->isEmpty())
              <tr><td colspan="5" class="text-center py-3">Tidak ada siswa.</td></tr>
            @else
              @foreach($siswa as $i => $s)
                <tr id="row-{{ $s->id_siswa }}">
                  <td class="view">{{ $i + 1 }}</td>
                  <td class="view">{{ $s->nama }}</td>
                  <td class="view">{{ $s->tingkat }} {{ $s->kelas }}</td>
                  <td class="view">{{ $data_absen[$s->id_siswa] }}</td>
                  
                  <td class="view">
                    <button class="btn btn-sm btn-primary" onclick="editRow({{ $s->id_siswa }})">Edit</button>
                  </td>

                  <td class="edit d-none" colspan="5">
                    <form method="POST" action="{{ route('admin.absensi.update') }}" class="d-flex gap-2 align-items-center">
                      @csrf
                      <input type="hidden" name="tingkat" value="{{ $f_tingkat }}">
                      <input type="hidden" name="kelas" value="{{ $f_kelas }}">
                      <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                      <input type="hidden" name="id_siswa" value="{{ $s->id_siswa }}">
                      <input type="hidden" name="id_kelas" value="{{ $s->id_kelas }}">
                      
                      <select name="status" class="form-select form-select-sm">
                        @foreach(['Hadir', 'Izin', 'Sakit', 'Alpha'] as $st)
                          <option value="{{ $st }}" {{ $data_absen[$s->id_siswa] == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                      </select>
                      <button type="button" class="btn btn-sm btn-primary" onclick="confirmUpdate(this)">Simpan</button>
                      <button type="button" class="btn btn-sm btn-danger" onclick="cancelEdit({{ $s->id_siswa }})">Batal</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <aside class="sidebar">
    <div class="stat-card">
      <h6>Total Siswa</h6>
      <div class="stat-value">{{ $total_siswa }}</div>
    </div>

    <div class="stat-card">
      <h6>Filter</h6>
      <div style="font-size:.95rem; color:#333;">
        Tingkat: <strong>{{ $f_tingkat ?: 'Semua' }}</strong><br>
        Kelas: <strong>{{ $f_kelas ?: 'Semua' }}</strong><br>
        Tanggal: <strong>{{ $tanggal }}</strong>
      </div>
    </div>

    <div class="stat-card">
      <h6>Terakhir Ditambahkan</h6>
      <div class="recent-list">
        @if($siswa->isEmpty())
          <div class="recent-item">Belum ada data</div>
        @else
          @foreach($siswa->reverse()->take(6) as $rs)
            <div class="recent-item" onclick="document.getElementById('row-{{ $rs->id_siswa }}')?.scrollIntoView({behavior:'smooth'})">
              <strong>{{ $rs->nama }}</strong>
              <div style="font-size:.85rem;color:#666">{{ $rs->tingkat }} {{ $rs->kelas }}</div>
            </div>
          @endforeach
        @endif
      </div>
    </div>
  </aside>
</div>

<script>
    function editRow(id){
        const row = document.getElementById('row-'+id);
        if(!row) return;
        row.querySelectorAll('.view').forEach(e => e.classList.add('d-none'));
        row.querySelectorAll('.edit').forEach(e => e.classList.remove('d-none'));
    }

    function cancelEdit(id){
        const row = document.getElementById('row-'+id);
        if(!row) return;
        row.querySelectorAll('.view').forEach(e => e.classList.remove('d-none'));
        row.querySelectorAll('.edit').forEach(e => e.classList.add('d-none'));
    }

    //alert
    function confirmUpdate(buttonElement) {
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Simpan perubahan status kehadiran siswa ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                buttonElement.closest('form').submit();
            }
        });
    }

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