@extends('layouts.app')

@section('content')

<div class="layout-golden">
  <div class="left-col">
    <div class="card shadow-sm p-4 mb-4">
      <h4 class="fw-bold mb-1">Detail Nilai Siswa</h4>
      <p class="mb-3 text-muted">
        <strong>{{ $siswa->nama }}</strong> &middot; {{ $siswa->kelas->tingkat }} {{ $siswa->kelas->kelas }}
      </p>

      {{-- FILTER & SEARCH --}}
      <div class="row g-2 mb-4">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-search"></i></span>
                <input type="text" id="searchInputMapel" class="form-control" placeholder="Cari Mapel...">
            </div>
        </div>
        <div class="col-md-3">
            <select id="filterTahun" class="form-select">
                <option value="">Semua Tahun (Tingkat)</option>
                <option value="X">Tahun ke-1 (Kelas X)</option>
                <option value="XI">Tahun ke-2 (Kelas XI)</option>
                <option value="XII">Tahun ke-3 (Kelas XII)</option>
            </select>
        </div>
        <div class="col-md-5 text-end">
          <a href="{{ route('admin.nilai.index') }}" class="btn btn-secondary">← Kembali</a>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0" id="nilaiTable">
          <thead class="table-light">
            <tr>
              <th rowspan="2" class="text-center">No</th>
              <th rowspan="2">Mata Pelajaran</th>
              <th rowspan="2">Guru</th>
              <th colspan="2" class="text-center">Nilai Akhir</th>
              <th rowspan="2" class="text-center">Aksi</th>
            </tr>
            <tr>
                <th class="text-center text-muted small">Ganjil</th>
                <th class="text-center text-muted small">Genap</th>
            </tr>
          </thead>
          <tbody>
            @foreach($mapels as $i => $m)
              <?php
                  $id_ampu = $m->id_ampu;
                  $n_ganjil = $existing_nilai[$id_ampu]['Ganjil'] ?? null;
                  $n_genap  = $existing_nilai[$id_ampu]['Genap'] ?? null;
                  
                  $editable = in_array($id_ampu, $editable_ampu_ids);
                  
                  // Data JSON untuk Modal (dikirim via JS)
                  $jsonData = json_encode([
                      'id_ampu' => $id_ampu,
                      'mapel' => $m->mapel->nama_mapel,
                      'ganjil' => $n_ganjil,
                      'genap' => $n_genap
                  ]);
                  
                  // Filter Class (Tingkat dari Mapel/Kelas Mapel tsb)
                  $tingkat_mapel = $m->kelas->tingkat ?? ''; // X, XI, atau XII
              ?>

              <tr class="align-middle" data-mapel="{{ strtolower($m->mapel->nama_mapel) }}" data-tingkat="{{ $tingkat_mapel }}">
                <td class="text-center">{{ $i+1 }}</td>
                <td>
                    <span class="fw-bold text-primary">{{ $m->mapel->nama_mapel }}</span>
                    <div class="small text-muted">Kelas: {{ $tingkat_mapel }}</div>
                </td>
                <td class="small">{{ $m->guru->nama }}</td>
                
                {{-- Nilai Ganjil --}}
                <td class="text-center">
                    @if($n_ganjil)
                        <span class="badge {{ $n_ganjil->nilai_akhir >= 75 ? 'bg-success' : 'bg-warning' }}">
                            {{ $n_ganjil->nilai_akhir }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>

                {{-- Nilai Genap --}}
                <td class="text-center">
                    @if($n_genap)
                        <span class="badge {{ $n_genap->nilai_akhir >= 75 ? 'bg-success' : 'bg-warning' }}">
                            {{ $n_genap->nilai_akhir }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>

                <td class="text-center">
                  @if ($editable)
                    <button class="btn btn-sm btn-primary" onclick='openModalNilai({!! $jsonData !!})'>
                        <i class="fa fa-edit"></i> Kelola
                    </button>
                  @else
                    <span class="text-muted"><i class="fa fa-lock"></i></span>
                  @endif
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
      <h6>Info Siswa</h6>
      <p class="mb-1"><strong>NISN:</strong> {{ $siswa->nisn }}</p>
      <p class="mb-1"><strong>Kelas:</strong> {{ $siswa->kelas->tingkat }} {{ $siswa->kelas->kelas }}</p>
    </div>
    <div class="stat-card">
      <h6>Total Mapel</h6>
      <div class="stat-value">{{ $total_mapel }}</div>
    </div>
  </aside>
</div>

{{-- MODAL INPUT NILAI (Ganjil & Genap) --}}
<div class="modal fade" id="modalInputNilai" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Kelola Nilai: <span id="modalMapelName" class="fw-bold"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                
                {{-- Tabs --}}
                <ul class="nav nav-tabs nav-fill mb-3" id="semesterTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="ganjil-tab" data-bs-toggle="tab" data-bs-target="#tabGanjil" type="button">Semester Ganjil</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="genap-tab" data-bs-toggle="tab" data-bs-target="#tabGenap" type="button">Semester Genap</button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- CONTENT GANJIL --}}
                    <div class="tab-pane fade show active" id="tabGanjil">
                        <form method="POST" action="{{ route('admin.nilai.update') }}">
                            @csrf
                            <input type="hidden" name="id_siswa" value="{{ $siswa->id_siswa }}">
                            <input type="hidden" name="id_ampu" id="inputAmpuGanjil">
                            <input type="hidden" name="semester" value="Ganjil">

                            <div class="mb-2">
                                <label class="form-label small text-muted">Nilai Tugas</label>
                                <input type="number" step="0.01" name="nilai_tugas" id="tugasGanjil" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted">Nilai UTS</label>
                                <input type="number" step="0.01" name="nilai_uts" id="utsGanjil" class="form-control" required>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <div id="btnDeleteGanjilContainer"></div>
                                <button class="btn btn-success"><i class="fa fa-save"></i> Simpan Ganjil</button>
                            </div>
                        </form>
                    </div>

                    {{-- CONTENT GENAP --}}
                    <div class="tab-pane fade" id="tabGenap">
                        <form method="POST" action="{{ route('admin.nilai.update') }}">
                            @csrf
                            <input type="hidden" name="id_siswa" value="{{ $siswa->id_siswa }}">
                            <input type="hidden" name="id_ampu" id="inputAmpuGenap">
                            <input type="hidden" name="semester" value="Genap">

                            <div class="mb-2">
                                <label class="form-label small text-muted">Nilai Tugas</label>
                                <input type="number" step="0.01" name="nilai_tugas" id="tugasGenap" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted">Nilai UTS</label>
                                <input type="number" step="0.01" name="nilai_uts" id="utsGenap" class="form-control" required>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <div id="btnDeleteGenapContainer"></div>
                                <button class="btn btn-success"><i class="fa fa-save"></i> Simpan Genap</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Hidden Delete Form --}}
<form id="formDelete" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
// Filter Search Mapel
document.getElementById('searchInputMapel')?.addEventListener('input', filterRows);
document.getElementById('filterTahun')?.addEventListener('change', filterRows);

function filterRows() {
    const q = document.getElementById('searchInputMapel').value.trim().toLowerCase();
    const tahun = document.getElementById('filterTahun').value; // X, XI, XII

    document.querySelectorAll('#nilaiTable tbody tr').forEach(row => {
        const name = row.dataset.mapel || '';
        const tingkat = row.dataset.tingkat || ''; // X, XI, XII

        const matchName = name.includes(q);
        const matchTahun = tahun === '' || tingkat === tahun;

        row.style.display = (matchName && matchTahun) ? '' : 'none';
    });
}

// Open Modal & Populate Data
function openModalNilai(data) {
    document.getElementById('modalMapelName').innerText = data.mapel;
    
    // Set ID Ampu for both forms
    document.getElementById('inputAmpuGanjil').value = data.id_ampu;
    document.getElementById('inputAmpuGenap').value = data.id_ampu;

    // Populate Ganjil
    const g = data.ganjil;
    document.getElementById('tugasGanjil').value = g ? g.nilai_tugas : '';
    document.getElementById('utsGanjil').value = g ? g.nilai_uts : '';
    
    // Delete Button Ganjil
    const delGanjil = document.getElementById('btnDeleteGanjilContainer');
    if (g) {
        delGanjil.innerHTML = `<button type="button" class="btn btn-outline-danger" onclick="confirmDelete(${g.id_nilai}, 'Ganjil')">Hapus Nilai</button>`;
    } else {
        delGanjil.innerHTML = '';
    }

    // Populate Genap
    const gp = data.genap;
    document.getElementById('tugasGenap').value = gp ? gp.nilai_tugas : '';
    document.getElementById('utsGenap').value = gp ? gp.nilai_uts : '';

    // Delete Button Genap
    const delGenap = document.getElementById('btnDeleteGenapContainer');
    if (gp) {
        delGenap.innerHTML = `<button type="button" class="btn btn-outline-danger" onclick="confirmDelete(${gp.id_nilai}, 'Genap')">Hapus Nilai</button>`;
    } else {
        delGenap.innerHTML = '';
    }

    new bootstrap.Modal(document.getElementById('modalInputNilai')).show();
}

function confirmDelete(id, sem) {
    Swal.fire({
        title: 'Hapus Nilai?',
        text: `Hapus nilai semester ${sem}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            const f = document.getElementById('formDelete');
            f.action = `/admin/nilai/${id}`; // Sesuaikan route destroy kamu
            f.submit();
        }
    });
}

@if(session('success'))
    Swal.fire({icon: 'success', title: 'Berhasil', text: '{{ session('success') }}', showConfirmButton: false, timer: 1500});
@endif
</script>

@endsection