@extends('layouts.app')

@section('content')

<div class="layout-golden">
  <div class="left-col">
    <div class="d-flex justify-content-between mb-3">
      <h4>Data Mapel</h4>
      @if(auth()->user()->role === 'admin')
        <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#formAdd">Tambah Mapel</button>
      @endif
    </div>

    {{-- Tambah --}}
    <div id="formAdd" class="collapse mb-3 card p-3">
      <form id="formTambahMapel" method="POST" action="{{ route('admin.mapel.store') }}">
        @csrf
        <div class="row g-2 align-items-center">
          <div class="col-md-6">
            <input name="nama_mapel" class="form-control" placeholder="Nama Mapel" required>
          </div>
          <div class="col-md-4">
            <select name="kategori" class="form-select" required>
              <option value="">-- Pilih Kategori --</option>
              <option value="Produktif">Produktif</option>
              <option value="Umum">Umum</option>
            </select>
          </div>
          <div class="col-md-2 text-end">
            <button type="button" onclick="confirmAction('formTambahMapel', 'Simpan mapel baru?')" class="btn btn-primary">Simpan</button>
          </div>
        </div>
      </form>
    </div>

    {{-- Search Eksport --}}
    <div class="d-flex justify-content-between mb-2 align-items-center">
      <div class="input-group w-50">
        <span class="input-group-text"><i class="fa fa-search"></i></span>
        <input type="text" id="searchInput" class="form-control" placeholder="Cari mapel...">
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.mapel.export.csv') }}" class="btn btn-outline-primary btn-sm" target="_blank">Export CSV</a>
        <a href="{{ route('admin.mapel.export.pdf.all') }}" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
      </div>
    </div>

    {{-- Cards --}}
    <div id="mapelContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-5">
      @foreach($mapels as $m)
        <?php
          $guru_list = $m->mapelDiampu->map(fn($a) => $a->guru->nama ?? 'Guru Tidak Ditemukan')->all();
          $data = [
            'id' => $m->id_mapel,
            'nama' => $m->nama_mapel,
            'kategori' => $m->kategori,
            'gurus' => $guru_list
          ];
          $data_json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        ?>
        <div class="col mapel-card-col"
            data-mapel='{{ $data_json }}'
            data-id="{{ $m->id_mapel }}"
            data-nama="{{ strtolower($m->nama_mapel) }}"
            data-kategori="{{ strtolower($m->kategori) }}">
          <div class="card siswa-card shadow-sm h-100"
              data-bs-toggle="modal"
              data-bs-target="#detailModalMapel"
              onclick="showMapelDetail({{ $m->id_mapel }})">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 text-start">{{ $m->nama_mapel }}</h5>
                    <span class="badge rounded-pill text-bg-{{ $m->kategori === 'Produktif' ? 'primary' : 'secondary' }}">
                    {{ $m->kategori }}
                    </span>
                </div>
                
                <div class="mt-2" style="font-size:.9rem;color:#555;">
                    @if($guru_list)
                    {{ implode(', ', $guru_list) }}
                    @else
                    <span class="text-muted">Belum ada guru pengampu</span>
                    @endif
                </div>
              </div>
          </div>
        </div>
      @endforeach
    </div>

  </div>

  {{-- Sidebar --}}
  <aside class="sidebar">
    <div class="stat-card">
      <h6>Total Mapel</h6>
      <div class="stat-value">{{ $total_mapel }}</div>
    </div>
    <div class="stat-card">
      <h6>Kategori</h6>
      <div>
        @foreach($kategori_count as $k => $cnt)
          <div style="font-size:.95rem; color:#333; margin-bottom:4px;">{{ $k }}: <strong>{{ $cnt }}</strong></div>
        @endforeach
      </div>
    </div>
    <div class="stat-card">
      <h6>Terakhir Ditambahkan</h6>
      <div class="recent-list">
        @forelse(array_reverse($recent->all()) as $rg)
          <?php
            $gurus_for_recent = $rg->mapelDiampu->map(fn($a) => $a->guru->nama)->implode(', ') ?: 'Belum ada guru';
          ?>
          <div class="recent-item" style="cursor:pointer" onclick="document.querySelector('.mapel-card-col[data-id=&quot;{{ $rg->id_mapel }}&quot;]')?.scrollIntoView({behavior:'smooth'});">
            <div style="min-width:0;">
              <strong>{{ $rg->nama_mapel }}</strong>
              <div style="font-size:0.85rem;color:#666">{{ $rg->kategori }}</div>
              <div style="font-size:0.82rem;color:#666">{{ $gurus_for_recent }}</div>
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
<div class="modal fade" id="detailModalMapel" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="padding-left: 40px;" id="detailModalMapelLabel">
          Detail Mapel: <span id="modal-mapel-nama"></span>
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="modal-mapel-view">
          <p><strong>Nama Mapel:</strong> <span id="detail-nama"></span></p>
          <p><strong>Kategori:</strong> <span id="detail-kategori"></span></p>
          <p><strong>Guru Pengampu:</strong></p>
          <div style="padding-left: 40px;" id="detail-guru-list" class="wrap-text"></div>
        </div>

        <div id="modal-mapel-edit" style="display:none;">
          <form id="mapelEditForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="id_mapel" id="edit-id-mapel">
            <div class="row g-2">
              <div class="col-md-8 mb-2">
                <label class="form-label small text-muted">Nama Mapel</label>
                <input type="text" name="nama_mapel" id="edit-nama-mapel" class="form-control" required>
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label small text-muted">Kategori</label>
                <select name="kategori" id="edit-kategori" class="form-select" required>
                  <option value="Produktif">Produktif</option>
                  <option value="Umum">Umum</option>
                </select>
              </div>
            </div>
            </form>
        </div>
      </div>

      <div class="modal-footer d-flex flex-column align-items-center pt-0">
        <div id="view-buttons" class="w-100 d-flex justify-content-center gap-2 mb-3">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        @if(auth()->user()->role === 'admin')
          <button class="btn btn-primary" onclick="editMapelInModal()">Edit</button>
        @endif
          <form id="deleteForm" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
        @if(auth()->user()->role === 'admin')
            <button type="button" class="btn btn-danger" onclick="confirmAction('deleteForm', 'Yakin hapus mapel ini?', 'warning')">Hapus</button>
        @endif
          </form>
        </div>

        <div id="edit-buttons" class="w-100 d-flex justify-content-center gap-2 mb-3" style="display: none !important;">
          <button type="button" onclick="confirmAction('mapelEditForm', 'Simpan perubahan mapel?')" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" onclick="cancelEditMapel()">Batal</button>
        </div>

        <div id="export-wrap" class="mt-1">
          <a href="#" id="btn-export-mapel-pdf" class="btn btn-sm btn-outline-danger" target="_blank">Export PDF</a>
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
        width: '600px'
      });

      var formAdd = document.getElementById('formAdd');
      if(formAdd && !document.getElementById('edit-id-mapel').value) {
          new bootstrap.Collapse(formAdd, { toggle: true }).show();
      }
    });
  @endif

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

  function showMapelDetail(id) {
      const col = document.querySelector(`.mapel-card-col[data-id="${id}"]`);
      if (!col || !col.dataset.mapel) return;

      const m = JSON.parse(col.dataset.mapel);
      document.getElementById('modal-mapel-nama').textContent = m.nama || '-';
      document.getElementById('detail-nama').textContent = m.nama || '-';
      document.getElementById('detail-kategori').textContent = m.kategori || '-';

      const guruList = document.getElementById('detail-guru-list');
      guruList.innerHTML = '';
      if (m.gurus && m.gurus.length) {
          m.gurus.forEach(name => {
              const div = document.createElement('div');
              div.className = 'py-1 border-bottom small';
              div.textContent = name;
              guruList.appendChild(div);
          });
      } else {
          guruList.innerHTML = '<div class="text-muted small">Belum ada guru pengampu</div>';
      }

      document.getElementById('edit-id-mapel').value = m.id;
      document.getElementById('edit-nama-mapel').value = m.nama;
      document.getElementById('edit-kategori').value = m.kategori;

      // ===== PERBAIKAN URL EDIT & DELETE DI SINI =====
      let updateUrl = "{{ route('admin.mapel.update', ':id') }}";
      document.getElementById('mapelEditForm').action = updateUrl.replace(':id', id);

      let deleteUrl = "{{ route('admin.mapel.destroy', ':id') }}";
      document.getElementById('deleteForm').action = deleteUrl.replace(':id', id);

      // ===== PERBAIKAN URL EXPORT PDF =====
      let exportUrl = "{{ route('admin.mapel.export.pdf', ':id') }}";
      document.getElementById('btn-export-mapel-pdf').href = exportUrl.replace(':id', id);
      // ====================================

      switchToView();
  }

  function switchToView() {
      document.getElementById('modal-mapel-view').style.display = 'block';
      document.getElementById('modal-mapel-edit').style.display = 'none';
      document.getElementById('view-buttons').setAttribute('style', 'display: flex !important; justify-content: center;');
      document.getElementById('edit-buttons').setAttribute('style', 'display: none !important;');
      document.getElementById('export-wrap').style.display = 'block';
      document.getElementById('detailModalMapel').classList.add('vertical');
  }

  function editMapelInModal() {
      document.getElementById('modal-mapel-view').style.display = 'none';
      document.getElementById('modal-mapel-edit').style.display = 'block';
      document.getElementById('view-buttons').setAttribute('style', 'display: none !important;');
      document.getElementById('edit-buttons').setAttribute('style', 'display: flex !important; justify-content: center;');
      document.getElementById('export-wrap').style.display = 'none';
      document.getElementById('detailModalMapel').classList.remove('vertical');
  }

  function cancelEditMapel() {
      switchToView();
  }

  //Search
  document.getElementById('searchInput')?.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.mapel-card-col').forEach(c => {
          const nama = c.dataset.nama || '';
          const kategori = c.dataset.kategori || '';
          const mapelData = c.dataset.mapel ? JSON.parse(c.dataset.mapel) : null;
          const gurus = mapelData && (mapelData.gurus || []).join(' ').toLowerCase();    
          const match = nama.includes(q) || kategori.includes(q) || (gurus && gurus.includes(q));
          c.style.display = match ? '' : 'none';
      });
  });

  document.getElementById('detailModalMapel')?.addEventListener('hidden.bs.modal', function() {
      switchToView();
  });
</script>
@endsection