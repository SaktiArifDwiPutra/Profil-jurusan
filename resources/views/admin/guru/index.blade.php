@extends('layouts.app')

@section('content')

<div class="layout-golden">
  <div class="left-col">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Data Guru</h4>
      
      {{-- HANYA ADMIN: Tombol Tambah --}}
      @if(auth()->user()->role === 'admin')
        <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#formAddGuru">Tambah Guru</button>
      @endif
    </div>

    {{-- HANYA ADMIN: Form Tambah --}}
    @if(auth()->user()->role === 'admin')
        <div id="formAddGuru" class="collapse mb-3 card p-3">
        <form id="formTambahGuru" method="POST" action="{{ route('admin.guru.store') }}">
            @csrf
            <div class="row g-2 mb-2">
            <div class="col-md-3">
                <input name="nip" class="form-control" placeholder="NIP (18 Digit)" 
                        minlength="18" maxlength="18" inputmode="numeric"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            <div class="col-md-3"><input name="nama" class="form-control" placeholder="Nama" required></div>
            <div class="col-md-2">
                <select name="jenis_kelamin" class="form-select" required>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="email" name="email" class="form-control" placeholder="Email (contoh@gmail.com)" required>
            </div>
            <div class="col-md-2">
                <input name="no_hp" class="form-control" placeholder="No HP" required
                        inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            </div>

            <label class="form-label">Mapel Diampu (opsional)</label>
            <div id="ampu-list-new" class="mb-2"></div>
            <div class="mb-3">
            <button type="button" class="btn btn-sm btn-success" onclick="addAmpuRow('new')">Tambah Mapel</button>
            </div>
            <button type="button" onclick="confirmAction('formTambahGuru', 'Simpan data guru baru?')" class="btn btn-primary">Simpan</button>
        </form>
        </div>
    @endif

    {{-- Search Eksport --}}
    <div class="d-flex justify-content-between mb-2 align-items-center">
      <div class="input-group w-50">
        <span class="input-group-text"><i class="fa fa-search"></i></span>
        <input type="text" id="searchInputGuru" class="form-control" placeholder="Cari guru (Nama, NIP, Mapel)...">
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.guru.export.csv') }}" class="btn btn-outline-primary btn-sm" target="_blank">Export CSV</a>
        <a href="{{ route('admin.guru.export.pdf.all') }}" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
      </div>
    </div>

    {{-- Cards (READ ONLY untuk semua) --}}
    <div id="guruContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
      @foreach($gurus as $g)
        <?php
          $ampu_for_g = $g->mapelDiampu->map(function($a) {
            return [
              'id_ampu' => $a->id_ampu,
              'id_mapel' => $a->id_mapel,
              'nama_mapel' => $a->mapel->nama_mapel,
              'id_kelas' => $a->id_kelas,
              'tingkat' => $a->kelas->tingkat,
              'kelas' => $a->kelas->kelas,
            ];
          })->all();

          $data = [
            'id' => $g->id_guru,
            'nip' => $g->nip,
            'nama' => $g->nama,
            'jk' => $g->jenis_kelamin,
            'email' => $g->email,
            'nohp' => $g->no_hp,
            'ampu' => $ampu_for_g
          ];
          $data_json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        ?>
        <div class="col guru-card-col"
             data-guru='{{ $data_json }}'
             data-id="{{ $g->id_guru }}"
             data-nama="{{ strtolower($g->nama) }}"
             data-mapel="{{ strtolower(implode(', ', array_column($ampu_for_g, 'nama_mapel'))) }}">
          <div class="card siswa-card shadow-sm h-100"
               data-bs-toggle="modal"
               data-bs-target="#detailModalGuru"
               onclick="showGuruDetail({{ $g->id_guru }})">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ $g->nama }}</h5>
                <span class="badge rounded-pill text-bg-{{ $g->jenis_kelamin == 'Laki-laki' ? 'primary' : 'danger' }}">
                  {{ $g->jenis_kelamin }}
                </span>
              </div>
              <p class="card-text text-muted mb-0"><small>{{ $g->nip ?: '-' }}</small></p>
              <div class="mt-2" style="font-size:.9rem;color:#555;">
                @if($ampu_for_g)
                  @foreach($ampu_for_g as $m)
                    <div>{{ $m['nama_mapel'] }} • {{ $m['tingkat'] }} {{ $m['kelas'] }}</div>
                  @endforeach
                @else
                  <div class="text-muted">Belum ada mapel</div>
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
      <h6>Total Guru</h6>
      <div class="stat-value">{{ $total_guru }}</div>
    </div>
    <div class="stat-card">
      <h6>Jenis Kelamin</h6>
      <div>Laki: {{ $laki }} • Perempuan: {{ $perempuan }}</div>
    </div>
    <div class="stat-card">
      <h6>Jumlah Mapel Unik</h6>
      <div>{{ count($mapel_set) }}</div>
    </div>
    <div class="stat-card">
      <h6>Jumlah Kelas Terlibat</h6>
      <div>{{ count($kelas_set) }}</div>
    </div>
    <div class="stat-card">
      <h6>Terakhir Ditambahkan</h6>
      <div class="recent-list">
        @forelse(array_reverse($recent->all()) as $rg)
          <div class="recent-item" style="cursor:pointer" onclick="document.querySelector('.guru-card-col[data-id=&quot;{{ $rg->id_guru }}&quot;]')?.scrollIntoView({behavior:'smooth'});">
            <div style="min-width:0;">
              <strong>{{ $rg->nama }}</strong>
              <div style="font-size:0.85rem;color:#666">{{ $rg->nip ?? '' }}</div>
            </div>
            <div style="font-size:0.9rem;color:#333">{{ $rg->no_hp }}</div>
          </div>
        @empty
          <div class="recent-item">Belum ada data</div>
        @endforelse
      </div>
    </div>
  </aside>

</div>

{{-- modal --}}
<div class="modal fade" id="detailModalGuru" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="padding-left: 40px;" id="detailModalGuruLabel">
          Detail Guru: <span id="modal-guru-nama"></span>
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="modal-guru-view">
          <p><strong>NIP:</strong> <span id="detail-nip"></span></p>
          <p><strong>Email:</strong> <span id="detail-email"></span></p>
          <p><strong>Jenis Kelamin:</strong> <span id="detail-jk"></span></p>
          <p><strong>No HP:</strong> <span id="detail-nohp"></span></p>
          <hr>
          <p class="mb-2"><strong>Mapel Diampu:</strong></p>
          <div style="padding-left: 20px;" id="detail-ampu-list" class="bg-light p-2 rounded"></div>
        </div>

        {{-- HANYA ADMIN: Form Edit --}}
        @if(auth()->user()->role === 'admin')
            <div id="modal-guru-edit" style="display:none;">
            <form id="guruEditForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="id_guru" id="edit-id-guru">
                
                <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label small text-muted">NIP</label>
                    <input type="text" name="nip" id="edit-nip" class="form-control" placeholder="NIP (18 Digit)"
                            minlength="18" maxlength="18" inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-muted">Nama Lengkap</label>
                    <input type="text" name="nama" id="edit-nama" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="edit-jk" class="form-select" required>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Email</label>
                    <input type="email" name="email" id="edit-email" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">No HP</label>
                    <input type="text" name="no_hp" id="edit-nohp" class="form-control" required
                            inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold mb-0">Mapel Diampu</label>
                <button type="button" class="btn btn-sm btn-success" onclick="addAmpuRow('edit')">
                    <i class="fa fa-plus"></i> Tambah Mapel
                </button>
                </div>
                <div id="edit-ampu-list"></div>
            </form>
            </div>
        @endif
      </div>

      <div class="modal-footer d-flex flex-column align-items-center pt-0">
        <div id="view-buttons" class="d-flex gap-2">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          
          {{-- HANYA ADMIN: Tombol Edit & Hapus --}}
          @if(auth()->user()->role === 'admin')
            <button class="btn btn-primary" onclick="editGuruInModal()">Edit</button>
            <form id="deleteForm" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-danger" onclick="confirmAction('deleteForm', 'Yakin hapus guru ini? Semua mapel yang diampu juga akan terhapus.', 'warning')">Hapus</button>
            </form>
          @endif
        </div>

        {{-- HANYA ADMIN: Tombol Simpan Edit --}}
        @if(auth()->user()->role === 'admin')
            <div id="edit-buttons" style="display:none;" class="gap-2">
            <button type="button" onclick="confirmAction('guruEditForm', 'Simpan perubahan data guru?')" class="btn btn-primary">Simpan</button>
            <button class="btn btn-secondary" onclick="cancelEditGuru()">Batal</button>
            </div>
        @endif

        <div class="mt-2">
          <a href="#" id="btn-export-guru-pdf" class="btn btn-sm btn-outline-danger" target="_blank">Export PDF</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const _MAPELS = @json($mapels->map(fn($m) => ['id' => $m->id_mapel, 'name' => $m->nama_mapel]));
  const _KELAS  = @json($kelas->map(fn($k) => ['id' => $k->id_kelas, 'label' => $k->tingkat . ' ' . $k->kelas]));

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
  
  //alert error
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

      var formAdd = document.getElementById('formAddGuru');
      // Cek apakah elemen ada sebelum akses value
      if(formAdd && !document.getElementById('edit-id-guru').value) {
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
        showConfirmButton: false
      });
    });
  @endif

  function addAmpuRow(context, existingData = null) {
      const container = context === 'new' ? document.getElementById('ampu-list-new') : document.getElementById('edit-ampu-list');
      if (!container) return; 

      const row = document.createElement('div');
      row.className = 'row g-2 ampu-row mb-2';

      let mapelOptions = '<option value="">-- Mapel --</option>';
      _MAPELS.forEach(m => {
          const selected = existingData && existingData.id_mapel == m.id ? 'selected' : '';
          mapelOptions += `<option value="${m.id}" ${selected}>${m.name}</option>`;
      });

      let kelasOptions = '<option value="">-- Kelas --</option>';
      _KELAS.forEach(k => {
          const selected = existingData && existingData.id_kelas == k.id ? 'selected' : '';
          kelasOptions += `<option value="${k.id}" ${selected}>${k.label}</option>`;
      });

      row.innerHTML = `
          <div class="col-md-5">
              <select name="ampu_mapel[]" class="form-select form-select-sm" required>${mapelOptions}</select>
          </div>
          <div class="col-md-5">
              <select name="ampu_kelas[]" class="form-select form-select-sm" required>${kelasOptions}</select>
          </div>
          <div class="col-md-2">
              <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.parentElement.parentElement.remove()">
                  <i class="fa fa-times"></i>
              </button>
          </div>
      `;
      container.appendChild(row);
  }

  function showGuruDetail(id) {
      const col = document.querySelector(`.guru-card-col[data-id="${id}"]`);
      if (!col || !col.dataset.guru) return;

      const g = JSON.parse(col.dataset.guru);

      document.getElementById('modal-guru-nama').textContent = g.nama || '-';
      document.getElementById('detail-nip').textContent = g.nip || '-';
      document.getElementById('detail-email').textContent = g.email || '-';
      document.getElementById('detail-jk').textContent = g.jk || '-';
      document.getElementById('detail-nohp').textContent = g.nohp || '-';

      const ampuList = document.getElementById('detail-ampu-list');
      ampuList.innerHTML = '';
      if (g.ampu && g.ampu.length) {
          g.ampu.forEach(a => {
              const div = document.createElement('div');
              div.className = 'small py-1 border-bottom';
              div.textContent = `${a.nama_mapel} • Kelas ${a.tingkat} ${a.kelas}`;
              ampuList.appendChild(div);
          });
      } else {
          ampuList.innerHTML = '<div class="text-muted small">Belum ada mapel diampu</div>';
      }

      // === LOGIKA EDIT FORM (PERBAIKAN URL DI SINI) ===
      const editForm = document.getElementById('guruEditForm');
      if (editForm) {
          document.getElementById('edit-id-guru').value = g.id;
          document.getElementById('edit-nip').value = g.nip || '';
          document.getElementById('edit-nama').value = g.nama || '';
          document.getElementById('edit-email').value = g.email || '';
          document.getElementById('edit-nohp').value = g.nohp || '';
          document.getElementById('edit-jk').value = g.jk;

          // ===== PERBAIKAN URL EDIT & DELETE BIAR GA 404 =====
          let updateUrl = "{{ route('admin.guru.update', ':id') }}";
          editForm.action = updateUrl.replace(':id', id);

          let deleteUrl = "{{ route('admin.guru.destroy', ':id') }}";
          document.getElementById('deleteForm').action = deleteUrl.replace(':id', id);
          // ===================================================

          const editAmpuContainer = document.getElementById('edit-ampu-list');
          editAmpuContainer.innerHTML = '';
          if (g.ampu && g.ampu.length) {
              g.ampu.forEach(a => {
                  addAmpuRow('edit', a);
              });
          }
          
          document.getElementById('modal-guru-view').style.display = 'block';
          document.getElementById('modal-guru-edit').style.display = 'none';
          document.getElementById('view-buttons').setAttribute('style', 'display: flex !important');
          document.getElementById('edit-buttons').setAttribute('style', 'display: none !important');
          document.getElementById('btn-export-guru-pdf').parentElement.style.display = 'block';
      }

      // ===== PERBAIKAN URL EXPORT PDF =====
      let exportUrl = "{{ route('admin.guru.export.pdf', ':id') }}";
      document.getElementById('btn-export-guru-pdf').href = exportUrl.replace(':id', id);
      // ====================================

      document.getElementById('detailModalGuru').classList.add('vertical');
  }

  function editGuruInModal() {
      if(!document.getElementById('modal-guru-edit')) return;

      document.getElementById('modal-guru-view').style.display = 'none';
      document.getElementById('modal-guru-edit').style.display = 'block';
      document.getElementById('view-buttons').setAttribute('style', 'display: none !important');
      document.getElementById('edit-buttons').setAttribute('style', 'display: flex !important');
      document.getElementById('btn-export-guru-pdf').parentElement.style.display = 'none';
      document.getElementById('detailModalGuru').classList.remove('vertical');
  }

  function switchToView() {
      document.getElementById('modal-guru-view').style.display = 'block';
      
      if(document.getElementById('modal-guru-edit')) {
          document.getElementById('modal-guru-edit').style.display = 'none';
          document.getElementById('edit-buttons').setAttribute('style', 'display: none !important');
      }
      
      document.getElementById('view-buttons').setAttribute('style', 'display: flex !important');
      document.getElementById('btn-export-guru-pdf').parentElement.style.display = 'block';
      document.getElementById('detailModalGuru').classList.add('vertical');
  }

  function cancelEditGuru() {
      switchToView();
  }

  // Search
  document.getElementById('searchInputGuru')?.addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.guru-card-col').forEach(c => {
          const nama = c.dataset.nama || '';
          const mapel = c.dataset.mapel || '';
          const nip = c.querySelector('.card-text small')?.textContent?.toLowerCase() || '';
          c.style.display = (nama.includes(q) || mapel.includes(q) || nip.includes(q)) ? '' : 'none';
      });
  });

  document.getElementById('detailModalGuru')?.addEventListener('hidden.bs.modal', switchToView);
</script>
@endsection