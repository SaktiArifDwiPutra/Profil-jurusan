@extends('layouts.app')

@section('content')

<div class="layout-golden">
  <div class="left-col">
    <div class="card p-4 shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Absensi Kelas {{ $kelas_wali->tingkat }} {{ $kelas_wali->kelas }}</h4>
          <p class="text-muted small mb-0 mt-1">Kelola kehadiran siswa perwalian Anda.</p>
        </div>
      </div>

      {{-- Filter Tanggal --}}
      <form method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-auto">
            <label class="form-label mb-0">Pilih Tanggal:</label>
        </div>
        <div class="col-auto">
            <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" onchange="this.form.submit()">
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.absensi.index') }}" class="btn btn-secondary">Hari Ini</a>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-striped align-middle" id="absensiTable">
          <thead>
            <tr>
              <th style="width:50px">No</th>
              <th>Nama Siswa</th>
              <th style="width:100px">Status</th>
              <th style="width:120px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @if($siswa->isEmpty())
              <tr><td colspan="4" class="text-center py-3">Tidak ada siswa di kelas ini.</td></tr>
            @else
              @foreach($siswa as $i => $s)
                <tr id="row-{{ $s->id_siswa }}">
                  <td class="view">{{ $i + 1 }}</td>
                  <td class="view fw-semibold">{{ $s->nama }}</td>
                  <td class="view">
                    @php
                        $st = $data_absen[$s->id_siswa];
                        $bg = match($st) { 'hadir'=>'success', 'izin'=>'warning', 'sakit'=>'info', 'alpha'=>'danger', default=>'secondary' };
                    @endphp
                    <span class="badge text-bg-{{ $bg }}">{{ $st }}</span>
                  </td>
                  
                  <td class="view">
                    <button class="btn btn-sm btn-primary" onclick="editRow({{ $s->id_siswa }})">Edit</button>
                  </td>

                  {{-- Form Edit Inline (Sama seperti Admin) --}}
                  <td class="edit d-none" colspan="4">
                    <form method="POST" action="{{ route('admin.absensi.update') }}" class="d-flex gap-2 align-items-center w-100">
                      @csrf
                      <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                      <input type="hidden" name="id_siswa" value="{{ $s->id_siswa }}">
                      <input type="hidden" name="id_kelas" value="{{ $s->id_kelas }}">
                      
                      <div class="fw-bold" style="min-width:120px">{{ $s->nama }}</div>

                      <select name="status" class="form-select form-select-sm">
                        @foreach(['Hadir', 'Izin', 'Sakit', 'Alpha'] as $st)
                          <option value="{{ $st }}" {{ $data_absen[$s->id_siswa] == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                      </select>
                      <button type="button" class="btn btn-sm btn-success" onclick="confirmUpdate(this)">Simpan</button>
                      <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEdit({{ $s->id_siswa }})">Batal</button>
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
        <h6>Info Kelas</h6>
        <div class="stat-value">{{ $kelas_wali->tingkat }} {{ $kelas_wali->kelas }}</div>
    </div>
    <div class="stat-card">
        <h6>Total Siswa</h6>
        <div>{{ $siswa->count() }}</div>
    </div>
    <div class="stat-card">
        <h6>Tanggal</h6>
        <div>{{ \Carbon\Carbon::parse($tanggal)->format('d M Y') }}</div>
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

    function confirmUpdate(buttonElement) {
        // Langsung submit saja agar cepat (UX Guru Absen massal biasanya butuh cepat)
        // Atau pakai Swal jika ingin konsisten
        Swal.fire({
            title: 'Simpan?',
            text: 'Update status kehadiran?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                buttonElement.closest('form').submit();
            }
        });
    }

    @if(session('success'))
      Swal.fire({icon: 'success', title: 'Berhasil', text: '{{ session('success') }}', showConfirmButton: false, timer: 1500});
    @endif
</script>
@endsection