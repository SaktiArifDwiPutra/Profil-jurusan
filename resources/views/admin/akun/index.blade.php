@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3 align-items-center">
  <h4>Data Akun</h4>
  <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#formAdd">Tambah Akun</button>
</div>
 
<div id="formAdd" class="collapse card p-3 mb-3"> 
  <form id="formTambahAkun" method="POST" action="{{ route('admin.akun.store') }}" class="row g-2">
    @csrf
    <div class="col-md-3"> 
      <input name="username" class="form-control" placeholder="Username" required
             oninput="this.value = this.value.replace(/\s/g, '')">
    </div>
    <div class="col-md-3">
      <input name="password" type="password" class="form-control" placeholder="Password" required>
    </div>
    <div class="col-md-3">
      <select name="role" id="roleSelect" class="form-select" required>
        <option value="">Pilih Role</option>
        <option value="guru">Guru</option>
        <option value="siswa">Siswa</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <div class="w-50 d-none" id="siswaSelect">
        <select name="id_siswa" class="form-select">
          <option value="">Pilih Siswa</option>
          @foreach($siswas as $s)
            <option value="{{ $s->id_siswa }}">{{ $s->nama }}</option>
          @endforeach
        </select>
      </div>
      <div class="w-50 d-none" id="guruSelect">
        <select name="id_guru" class="form-select">
          <option value="">Pilih Guru</option>
          @foreach($gurus as $g)
            <option value="{{ $g->id_guru }}">{{ $g->nama }}</option>
          @endforeach
        </select>
      </div> 
      <button type="button" onclick="confirmAction('formTambahAkun', 'Buat akun baru?')" class="btn btn-primary flex-grow-1">Simpan</button>
    </div>
  </form>
</div>

<div class="d-flex justify-content-between mb-2 align-items-center">
  <div class="input-group w-50">
    <span class="input-group-text"><i class="fa fa-search"></i></span>
    <input type="text" id="searchInput" class="form-control" placeholder="Cari username, role, linked...">
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('admin.akun.export.csv') }}" class="btn btn-sm btn-outline-primary" target="_blank">Export CSV</a>
    <a href="{{ route('admin.akun.export.pdf.all') }}" class="btn btn-sm btn-outline-danger" target="_blank">Export PDF</a>
  </div>
</div>

<table class="table table-striped" id="akunTable">
  <thead>
    <tr>
      <th style="width:60px">No</th>
      <th>Username</th>
      <th style="width:120px">Role</th>
      <th>Linked</th>
      <th style="width:120px">Aksi</th>
    </tr>
  </thead>
  <tbody>
    @foreach($users as $i => $u)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $u->username }}</td>
        <td>
            <span class="badge rounded-pill text-bg-{{ $u->role == 'admin' ? 'danger' : ($u->role == 'guru' ? 'primary' : 'success') }}">
                {{ ucfirst($u->role) }}
            </span>
        </td>
        <td>{{ $u->siswa?->nama ?? $u->guru?->nama ?? '-' }}</td>
        <td>
          <form id="deleteForm-{{ $u->id_user }}" method="POST" action="{{ route('admin.akun.destroy', $u->id_user) }}" style="display:inline;">
            @csrf
            @method('DELETE') 
            <button type="button" class="btn btn-sm btn-danger" onclick="confirmAction('deleteForm-{{ $u->id_user }}', 'Yakin hapus akun ini?', 'warning')">Hapus</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>

<script>
  document.getElementById('roleSelect').addEventListener('change', function(){
    const role = this.value;
    const siswaDiv = document.getElementById('siswaSelect');
    const guruDiv = document.getElementById('guruSelect');
    
    siswaDiv.classList.add('d-none');
    guruDiv.classList.add('d-none');
    
    const siswaSelect = siswaDiv.querySelector('select');
    const guruSelect = guruDiv.querySelector('select');
    siswaSelect.required = false;
    guruSelect.required = false;
    
    if(role === 'siswa'){
      siswaDiv.classList.remove('d-none');
      siswaSelect.required = true;
    }
    if(role === 'guru'){
      guruDiv.classList.remove('d-none');
      guruSelect.required = true;
    }
  });

  // search
  document.getElementById('searchInput').addEventListener('input', function(){
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#akunTable tbody tr').forEach(tr => {
      tr.style.display = tr.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
  });

  // alert
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
      if(formAdd) {
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
</script>

@endsection