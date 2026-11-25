@extends('layouts.app')

@section('content')

<div class="layout-golden">
  <div class="left-col">
    <div class="d-flex justify-content-between mb-3">
      <h4>Data Siswa</h4>
      
      {{-- HANYA ADMIN YANG BISA LIHAT TOMBOL TAMBAH --}}
      @if(auth()->user()->role === 'admin')
        <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#formAdd">Tambah Siswa</button>
      @endif
    </div>

    {{-- HANYA ADMIN YANG BISA LIHAT FORM TAMBAH --}}
    @if(auth()->user()->role === 'admin')
        <div id="formAdd" class="collapse mb-3 card p-3">
        <form id="formTambahSiswa" method="POST" action="{{ route('admin.siswa.store') }}">
            @csrf
            <div class="row g-2">
            <div class="col-md-3">
                <input name="nisn" class="form-control" placeholder="NISN (9-10 Digit)" required
                    minlength="9" maxlength="10" inputmode="numeric"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            <div class="col-md-3"><input name="nama" class="form-control" placeholder="Nama" required></div>
            <div class="col-md-2">
                <select name="jenis_kelamin" class="form-select" required>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
                </select>
            </div>
            <div class="col-md-2"><input type="date" name="tanggal_lahir" class="form-control" required></div>
            <div class="col-md-2">
                <select name="id_kelas" class="form-select" required>
                <option value="">Kelas</option>
                @foreach($kelas as $k)
                    <option value="{{ $k->id_kelas }}">{{ $k->tingkat }} - {{ $k->kelas }}</option>
                @endforeach
                </select>
            </div>
            </div>
            <div class="mt-2">
                <input name="no_hp" class="form-control" placeholder="No HP" required
                    inputmode="numeric"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            <div class="mt-2"><textarea name="alamat" class="form-control" placeholder="Alamat" rows="2"></textarea></div>
            <div class="mt-2">
                <button type="button" onclick="confirmAction('formTambahSiswa', 'Simpan data siswa baru?')" class="btn btn-primary">Simpan</button>
            </div>
        </form>
        </div>
    @endif

    {{-- Search Eksport --}}
    <div class="d-flex justify-content-between mb-2 align-items-center">
      <div class="input-group w-50">
        <span class="input-group-text"><i class="fa fa-search"></i></span>
        <input type="text" id="searchInput" class="form-control" placeholder="Cari siswa...">
      </div>
      <div class="d-flex gap-2">
        {{-- Export CSV & PDF Biarkan saja agar Guru/Siswa bisa download data --}}
        <a href="{{ route('admin.siswa.export.csv') }}" class="btn btn-outline-primary btn-sm" target="_blank">Export CSV</a>
        <a href="{{ route('admin.siswa.export.pdf.all') }}" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
      </div>
    </div>

    {{-- Cards (Biarkan sama, karena read-only) --}}
    <div id="siswaContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-5">
      @foreach($siswas as $s)
        <?php
          $data = [
            'id' => $s->id_siswa,
            'nisn' => $s->nisn,
            'nama' => $s->nama,
            'jk' => $s->jenis_kelamin,
            'tgl' => $s->tanggal_lahir,
            'alamat' => $s->alamat,
            'nohp' => $s->no_hp,
            'id_kelas' => $s->id_kelas,
            'kelas_label' => $s->kelas ? $s->kelas->tingkat . ' - ' . $s->kelas->kelas : 'Belum Ada Kelas'
          ];
          $data_json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        ?>
        <div class="col siswa-card-col"
             data-siswa='{{ $data_json }}'
             data-id="{{ $s->id_siswa }}"
             data-nama="{{ strtolower($s->nama) }}"
             data-kelas="{{ strtolower($s->kelas ? $s->kelas->tingkat . ' - ' . $s->kelas->kelas : '') }}">

             <div class="card siswa-card shadow-sm h-100"
                data-nisn="{{ $s->nisn }}" 
                data-bs-toggle="modal"
                data-bs-target="#detailModal"
                onclick="showDetail({{ $s->id_siswa }})">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ $s->nama }}</h5>
                <span class="badge rounded-pill text-bg-{{ $s->jenis_kelamin == 'Laki-laki' ? 'primary' : 'danger' }}">
                  {{ $s->jenis_kelamin }}
                </span>
              </div>
              <p class="card-text text-muted mb-0">
                <small>{{ $s->kelas ? $s->kelas->tingkat . ' - ' . $s->kelas->kelas : 'Belum Ada Kelas' }}</small>
              </p>
            </div>
          </div>
        </div>
      @endforeach
    </div>

  </div>

  {{-- Sidebar (Biarkan sama) --}}
  <aside class="sidebar">
    <div class="stat-card">
      <h6>Total Siswa</h6>
      <div class="stat-value">{{ $total_siswa }}</div>
    </div>
    <div class="stat-card">
      <h6>Jenis Kelamin</h6>
      <div>Laki: {{ $laki }} • Perempuan: {{ $perempuan }}</div>
    </div>
    <div class="stat-card">
      <h6>Jumlah Kelas</h6>
      <div>{{ $jumlah_kelas }}</div>
    </div>
    <div class="stat-card">
      <h6>Terakhir Ditambahkan</h6>
      <div class="recent-list">
        @forelse(array_reverse($recent->all()) as $r)
          <div class="recent-item" style="cursor:pointer" onclick="showDetail({{ $r->id_siswa }})">
            <div style="min-width:0;">
              <strong>{{ $r->nama }}</strong>
              <div style="font-size:0.85rem;color:#666">
                {{ $r->kelas ? $r->kelas->tingkat . ' - ' . $r->kelas->kelas : 'Belum Ada Kelas' }}
              </div>
            </div>
            <div style="font-size:0.9rem;color:#333">{{ $r->no_hp }}</div>
          </div>
        @empty
          <div class="recent-item">Belum ada data</div>
        @endforelse
      </div>
    </div>
  </aside>

</div>

{{-- Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="padding-left: 40px;" id="detailModalLabel">
          Detail Siswa: <span id="modal-nama-view"></span>
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="modal-detail-view">
          <p><strong>NISN:</strong> <span id="detail-nisn"></span></p>
          <p><strong>Jenis Kelamin:</strong> <span id="detail-jk"></span></p>
          <p><strong>Tanggal Lahir:</strong> <span id="detail-tgl"></span></p>
          <p><strong>Kelas:</strong> <span id="detail-kelas"></span></p>
          <p><strong>No HP:</strong> <span id="detail-nohp"></span></p>
          <p><strong>Alamat:</strong> <span id="detail-alamat" class="wrap-text"></span></p>
        </div>

        {{-- Form Edit (HANYA MUNCUL DI DOM UNTUK ADMIN) --}}
        @if(auth()->user()->role === 'admin')
            <div id="modal-edit-form" style="display:none;">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="id_siswa" id="edit-id-siswa">
                <div class="row g-2">
                <div class="col-md-4 mb-2">
                    <input type="text" name="nisn" id="edit-nisn" class="form-control" placeholder="NISN" required
                            minlength="9" maxlength="10" inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                <div class="col-md-4 mb-2"><input type="text" name="nama" id="edit-nama" class="form-control" placeholder="Nama" required></div>
                <div class="col-md-4 mb-2">
                    <select name="jenis_kelamin" id="edit-jk" class="form-select" required>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2"><input type="date" name="tanggal_lahir" id="edit-tgl" class="form-control"></div>
                <div class="col-md-4 mb-2">
                    <select name="id_kelas" id="edit-id-kelas" class="form-select" required>
                    <option value="">--Kelas--</option>
                    @foreach($kelas as $k)
                        <option value="{{ $k->id_kelas }}">{{ $k->tingkat }} - {{ $k->kelas }}</option>
                    @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <input type="text" name="no_hp" id="edit-nohp" class="form-control" placeholder="No HP" required
                            inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                </div>
                <div class="mt-2"><textarea name="alamat" id="edit-alamat" class="form-control" placeholder="Alamat" rows="2"></textarea></div>
            </form>
            </div>
        @endif
      </div>
      
      <div class="modal-footer d-flex flex-column align-items-center pt-0">
        <div id="view-buttons" class="d-flex gap-2">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          
          {{-- TOMBOL EDIT DAN HAPUS HANYA UNTUK ADMIN --}}
          @if(auth()->user()->role === 'admin')
            <button class="btn btn-primary" onclick="editRowInModal()">Edit</button>
            
            <form id="deleteForm" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-danger" onclick="confirmAction('deleteForm', 'Yakin hapus siswa ini? Data tidak bisa dikembalikan.', 'warning')">Hapus</button>
            </form>
          @endif
        </div>

        {{-- TOMBOL SIMPAN EDIT HANYA UNTUK ADMIN --}}
        @if(auth()->user()->role === 'admin')
            <div id="edit-buttons" style="display:none;" class="gap-2">
            <button type="button" onclick="confirmAction('editForm', 'Simpan perubahan data siswa?')" class="btn btn-primary">Simpan</button>
            <button class="btn btn-secondary" onclick="cancelEditInModal()">Batal</button>
            </div>
        @endif

        <div class="mt-2">
          <a href="#" id="btn-export-siswa-pdf" class="btn btn-sm btn-outline-danger" target="_blank">Export PDF</a></div>
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

  function showDetail(id) {
    const col = document.querySelector(`.siswa-card-col[data-id="${id}"]`);
    if (!col || !col.dataset.siswa) return;

    const s = JSON.parse(col.dataset.siswa);
    document.getElementById('modal-detail-view').style.display = 'block';
    
    // Cek dulu apakah elemen edit form ada (karena user Guru/Siswa tidak punya elemen ini)
    if(document.getElementById('modal-edit-form')) {
        document.getElementById('modal-edit-form').style.display = 'none';
        document.getElementById('edit-buttons').style.display = 'none';
        
        // ===== PERBAIKAN URL EDIT & DELETE DI SINI =====
        // Kita gunakan route helper dari Laravel untuk membuat template URL yang benar
        const form = document.getElementById('editForm');
        let updateUrl = "{{ route('admin.siswa.update', ':id') }}"; // Template URL dari Laravel
        form.action = updateUrl.replace(':id', id); // Ganti :id dengan ID siswa asli

        // Fix untuk form Delete juga
        let deleteUrl = "{{ route('admin.siswa.destroy', ':id') }}";
        document.getElementById('deleteForm').action = deleteUrl.replace(':id', id);
        // ===============================================

        document.getElementById('edit-id-siswa').value = s.id;
        document.getElementById('edit-nisn').value = s.nisn;
        document.getElementById('edit-nama').value = s.nama;
        document.getElementById('edit-tgl').value = s.tgl;
        document.getElementById('edit-nohp').value = s.nohp;
        document.getElementById('edit-alamat').value = s.alamat;
        document.getElementById('edit-jk').value = s.jk;
        document.getElementById('edit-id-kelas').value = s.id_kelas || '';
    }

    document.getElementById('view-buttons').style.display = 'flex';
    document.getElementById('btn-export-siswa-pdf').style.display = 'inline-block';
    document.getElementById('modal-nama-view').textContent = s.nama;
    document.getElementById('detail-nisn').textContent = s.nisn || '-';
    document.getElementById('detail-jk').textContent = s.jk || '-';
    document.getElementById('detail-tgl').textContent = s.tgl || '-';
    document.getElementById('detail-kelas').textContent = s.kelas_label || 'Belum Ada Kelas';
    document.getElementById('detail-nohp').textContent = s.nohp || '-';
    document.getElementById('detail-alamat').textContent = s.alamat || '-';

    // Fix URL Export PDF Per Siswa
    let exportUrl = "{{ route('admin.siswa.export.pdf', ':id') }}";
    document.getElementById('btn-export-siswa-pdf').href = exportUrl.replace(':id', id);
    
    document.getElementById('detailModal').classList.add('vertical');
  }

  // Fungsi Edit (Hanya akan dipanggil oleh Admin)
  function editRowInModal() {
    if(!document.getElementById('modal-edit-form')) return; // Safety check

    document.getElementById('modal-detail-view').style.display = 'none';
    document.getElementById('modal-edit-form').style.display = 'block';
    document.getElementById('view-buttons').setAttribute('style', 'display: none !important');
    document.getElementById('edit-buttons').setAttribute('style', 'display: flex !important');
    document.getElementById('btn-export-siswa-pdf').style.display = 'none';
    document.getElementById('detailModal').classList.remove('vertical');
  }

  function cancelEditInModal() {
    document.getElementById('modal-detail-view').style.display = 'block';
    if(document.getElementById('modal-edit-form')) {
        document.getElementById('modal-edit-form').style.display = 'none';
        document.getElementById('edit-buttons').setAttribute('style', 'display: none !important');
    }
    document.getElementById('view-buttons').setAttribute('style', 'display: flex !important');
    document.getElementById('btn-export-siswa-pdf').style.display = 'inline-block';
    document.getElementById('detailModal').classList.add('vertical');
  }

  // Search
  document.getElementById('searchInput')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.siswa-card-col').forEach(c => {
      const matchNisn = c.dataset.nisn ? c.dataset.nisn.includes(q) : false;
      const matchNama = c.dataset.nama ? c.dataset.nama.includes(q) : false;
      const matchKelas = c.dataset.kelas ? c.dataset.kelas.includes(q) : false;
      c.style.display = (matchNisn || matchNama || matchKelas) ? '' : 'none';
    });
  });

  document.getElementById('detailModal')?.addEventListener('hidden.bs.modal', function () {
    cancelEditInModal();
  });

  // Alert Error
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
      // Cek apakah elemen formAdd ada sebelum di-toggle
      if(formAdd && !document.getElementById('edit-id-siswa').value) {
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
</script>

@endsection