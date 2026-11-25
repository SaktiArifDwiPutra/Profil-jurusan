@extends('layouts.app')

@section('content')

<div class="layout-golden">
  
  <div class="left-col">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Data Kelas</h4>
      
      {{-- HANYA ADMIN: Tombol Tambah --}}
      @if(auth()->user()->role === 'admin')
        <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#formAddKelas">Tambah</button>
      @endif
    </div>

    {{-- HANYA ADMIN: Form Tambah --}}
    @if(auth()->user()->role === 'admin')
        <div id="formAddKelas" class="collapse card p-3 mb-3"> 
        <form id="formTambahKelas" method="POST" action="{{ route('admin.kelas.store') }}">
            @csrf
            <div class="row g-2">
            <div class="col-md-3">
                <select name="tingkat" class="form-select" required>
                <option value="">Pilih Tingkat</option>
                <option value="X">X</option>
                <option value="XI">XI</option>
                <option value="XII">XII</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="kelas" class="form-control" placeholder="Nama Kelas" required>
            </div>
            <div class="col-md-4">
                <select name="id_wali" class="form-select">
                <option value="">Pilih Wali Kelas</option>
                @foreach($gurus as $g)
                    <option value="{{ $g->id_guru }}">{{ $g->nama }}</option>
                @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" onclick="confirmAction('formTambahKelas', 'Simpan data kelas baru?')" class="btn btn-primary w-100">Simpan</button>
            </div>
            </div>
        </form>
        </div>
    @endif

    {{-- Search Eksport --}}
    <div class="d-flex justify-content-between mb-2 align-items-center">
      <div class="input-group w-50">
        <span class="input-group-text"><i class="fa fa-search"></i></span>
        <input type="text" id="searchInput" class="form-control" placeholder="Cari kelas (Tingkat, Nama, Wali)...">
      </div>

      <div class="d-flex gap-2">
        <a href="{{ route('admin.kelas.export.csv') }}" class="btn btn-outline-primary btn-sm">Export CSV</a>
        <a href="{{ route('admin.kelas.export.pdf.all') }}" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
      </div>
    </div>

    {{-- Cards (READ ONLY untuk Semua) --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
      @foreach($kelas as $k)
        <?php
          $data = [
            'id' => $k->id_kelas,
            'tingkat' => $k->tingkat,
            'kelas' => $k->kelas,
            'wali_id' => $k->id_wali,
            'wali_nama' => $k->wali?->nama ?? ''
          ];
          $data_json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        ?>
        <div class="col kelas-card-col"
             data-kelas='{{ $data_json }}'
             data-id="{{ $k->id_kelas }}"
             data-tingkat="{{ strtolower($k->tingkat) }}"
             data-nama="{{ strtolower($k->kelas) }}"
             data-wali="{{ strtolower($k->wali?->nama ?? '-') }}">

          <div class="card kelas-card h-100"
               data-bs-toggle="modal"
               data-bs-target="#detailModalKelas"
               onclick="showDetail({{ $k->id_kelas }})">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ $k->tingkat }} - {{ $k->kelas }}</h5>
                <span class="badge rounded-pill text-bg-secondary">{{ $k->tingkat }}</span>
              </div>
              <p class="card-text text-muted mb-0"><small>{{ $k->kelas }}</small></p>
              <div class="mt-2"><strong>Wali:</strong> {{ $k->wali?->nama ?? '-' }}</div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  {{-- Sidebar --}}
  <aside class="sidebar">
    <div class="stat-card">
      <h6>Total Kelas</h6>
      <div class="stat-value">{{ $total_kelas }}</div>
    </div>
    <div class="stat-card">
      <h6>Jumlah Tingkat</h6>
      <div>{{ $tingkat_count }}</div>
    </div>
    <div class="stat-card">
      <h6>Jumlah Wali</h6>
      <div>{{ $wali_count }}</div>
    </div>
    <div class="stat-card">
      <h6>Terakhir Ditambahkan</h6>
      <div class="recent-list">
        @forelse(array_reverse($recent->all()) as $r)
          <div class="recent-item" style="cursor:pointer"
               onclick="document.querySelector('.kelas-card-col[data-id=&quot;{{ $r->id_kelas }}&quot;]')?.scrollIntoView({behavior:'smooth'})">
            <div>
              <strong>{{ $r->tingkat }} - {{ $r->kelas }}</strong>
              <div class="small text-muted">{{ $r->wali?->nama ?? '-' }}</div>
            </div>
          </div>
        @empty
          <div class="recent-item">Belum ada data</div>
        @endforelse
      </div>
    </div>
  </aside>
</div>

{{-- Modal --}}
<div class="modal fade" id="detailModalKelas" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="padding-left: 40px;" id="detailModalLabel">
          Detail Kelas: <span id="modalNama"></span>
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="modal-kelas-view">
          <p><strong>Tingkat:</strong> <span id="modalTingkat"></span></p>
          <p><strong>Nama Kelas:</strong> <span id="modalKelas"></span></p>
          <p><strong>Wali Kelas:</strong> <span id="modalWali"></span></p>
        </div>

        {{-- HANYA ADMIN: Form Edit --}}
        @if(auth()->user()->role === 'admin')
            <div id="modal-kelas-edit" style="display:none;">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="id_kelas" id="editId">
                <div class="row g-2">
                <div class="col-md-4 mb-2">
                    <label class="form-label small text-muted">Tingkat</label>
                    <select name="tingkat" id="editTingkat" class="form-select" required>
                    <option value="X">X</option>
                    <option value="XI">XI</option>
                    <option value="XII">XII</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label small text-muted">Nama Kelas</label>
                    <input type="text" name="kelas" id="editKelas" class="form-control" placeholder="Nama Kelas" required>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label small text-muted">Wali Kelas</label>
                    <select name="id_wali" id="editWali" class="form-select">
                    <option value="">-- Pilih Wali Kelas --</option>
                    @foreach($gurus as $g)
                        <option value="{{ $g->id_guru }}">{{ $g->nama }}</option>
                    @endforeach
                    </select>
                </div>
                </div>
            </form>
            </div>
        @endif
      </div>

      <div class="modal-footer d-flex flex-column align-items-center pt-0">
        <div id="viewButtons" class="gap-2">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          
          {{-- HANYA ADMIN: Tombol Edit & Hapus --}}
          @if(auth()->user()->role === 'admin')
            <button class="btn btn-primary" onclick="switchToEdit()">Edit</button>
            <form id="deleteForm" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-danger" onclick="confirmAction('deleteForm', 'Yakin hapus kelas ini?', 'warning')">Hapus</button>
            </form>
          @endif
        </div>

        {{-- HANYA ADMIN: Tombol Simpan --}}
        @if(auth()->user()->role === 'admin')
            <div id="editButtons" style="display:none;" class="gap-2">
            <button type="button" onclick="confirmAction('editForm', 'Simpan perubahan kelas?')" class="btn btn-primary">Simpan</button>
            <button class="btn btn-secondary" onclick="switchToView()">Batal</button>
            </div>
        @endif

        <div class="mt-2">
          <a href="#" id="btnExportPdf" class="btn btn-sm btn-outline-danger" target="_blank">Export PDF</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function confirmAction(formId, message, iconType = 'question') {
      Swal.fire({
          title: 'Konfirmasi',
          text: message,
          icon: iconType,
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Ya, Lanjutkan!',
          cancelButtonText: 'Batal'
      }).then((result) => {
          if (result.isConfirmed) {
              document.getElementById(formId).submit();
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

  @if ($errors->any())
    document.addEventListener('DOMContentLoaded', function() {
      let pesanError = '<ul style="text-align: left; margin-left: 1rem;">';
      @foreach ($errors->all() as $error)
        pesanError += '<li style="margin-bottom: 5px;">{{ $error }}</li>';
      @endforeach
      pesanError += '</ul>';

      Swal.fire({
        icon: 'error',
        title: 'Gagal Menyimpan Data!',
        html: pesanError,
        confirmButtonText: 'OK, Saya Perbaiki',
        confirmButtonColor: '#d33',
      });
      
      var formAdd = document.getElementById('formAddKelas');
      // Cek apakah elemen ada sebelum di-toggle (Admin Only check)
      if(formAdd && !document.getElementById('editId').value) {
          new bootstrap.Collapse(formAdd, { toggle: true }).show();
      }
    });
  @endif

  function showDetail(id) {
    const col = document.querySelector(`.kelas-card-col[data-id="${id}"]`);
    if (!col || !col.dataset.kelas) return;

    const k = JSON.parse(col.dataset.kelas);

    document.getElementById('modalNama').innerText = k.tingkat + ' ' + k.kelas;
    document.getElementById('modalTingkat').innerText = k.tingkat;
    document.getElementById('modalKelas').innerText = k.kelas;
    document.getElementById('modalWali').innerText = k.wali_nama || '-';
    
    // === LOGIKA EDIT FORM (PERBAIKAN URL DI SINI) ===
    const editForm = document.getElementById('editForm');
    if(editForm) {
        // ===== PERBAIKAN URL EDIT & DELETE =====
        let updateUrl = "{{ route('admin.kelas.update', ':id') }}";
        editForm.action = updateUrl.replace(':id', id);

        let deleteUrl = "{{ route('admin.kelas.destroy', ':id') }}";
        document.getElementById('deleteForm').action = deleteUrl.replace(':id', id);
        // =======================================

        document.getElementById('editId').value = k.id;
        document.getElementById('editTingkat').value = k.tingkat;
        document.getElementById('editKelas').value = k.kelas;
        document.getElementById('editWali').value = k.wali_id || '';
        
        // Reset UI ke view mode
        document.getElementById('modal-kelas-view').style.display = 'block';
        document.getElementById('modal-kelas-edit').style.display = 'none';
        document.getElementById('viewButtons').style.display = 'flex'; 
        document.getElementById('editButtons').style.display = 'none'; 
        document.getElementById('btnExportPdf').style.display = 'inline-block';
    }

    // ===== PERBAIKAN URL EXPORT PDF =====
    let exportUrl = "{{ route('admin.kelas.export.pdf', ':id') }}";
    document.getElementById('btnExportPdf').href = exportUrl.replace(':id', id);
    // ====================================

    document.getElementById('detailModalKelas').classList.add('vertical');
  }

  function switchToEdit() {
    // Safety check
    if(!document.getElementById('modal-kelas-edit')) return;

    document.getElementById('modal-kelas-view').style.display = 'none';
    document.getElementById('modal-kelas-edit').style.display = 'block';
    document.getElementById('viewButtons').style.display = 'none';
    document.getElementById('editButtons').style.display = 'flex';
    document.getElementById('btnExportPdf').style.display = 'none';
    document.getElementById('detailModalKelas').classList.remove('vertical');
  }

  function switchToView() {
    document.getElementById('modal-kelas-view').style.display = 'block';
    
    // Cek elemen
    if(document.getElementById('modal-kelas-edit')) {
        document.getElementById('modal-kelas-edit').style.display = 'none';
        document.getElementById('editButtons').style.display = 'none';
    }

    document.getElementById('viewButtons').style.display = 'flex';
    document.getElementById('btnExportPdf').style.display = 'inline-block';
    document.getElementById('detailModalKelas').classList.add('vertical');
  }

  // Search
  document.getElementById('searchInput')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.kelas-card-col').forEach(c => {
      c.style.display = (
        c.dataset.tingkat.includes(q) ||
        c.dataset.nama.includes(q) ||
        c.dataset.wali.includes(q)
      ) ? '' : 'none';
    });
  });

  document.getElementById('detailModalKelas')?.addEventListener('hidden.bs.modal', function () {
    switchToView();
  });
</script>
@endsection