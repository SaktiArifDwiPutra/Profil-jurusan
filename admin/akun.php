<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['admin']);
include __DIR__ . '/../inc/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['action'] ?? '')=='add') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? '';
    $id_siswa = !empty($_POST['id_siswa']) ? intval($_POST['id_siswa']) : null;
    $id_guru  = !empty($_POST['id_guru']) ? intval($_POST['id_guru']) : null;

    if($username==='' || $password==='' || $role===''){
        flash_set('error','Semua field wajib diisi.');
        header('Location: /ProfilJurusan/admin/akun.php'); exit;
    }

    if($role==='siswa' && !$id_siswa){
        flash_set('error','Siswa wajib dipilih untuk role siswa.');
        header('Location: /ProfilJurusan/admin/akun.php'); exit;
    }
    if($role==='guru' && !$id_guru){
        flash_set('error','Guru wajib dipilih untuk role guru.');
        header('Location: /ProfilJurusan/admin/akun.php'); exit;
    }
    if($role==='admin'){ $id_siswa=$id_guru=null; }

    $hash = password_hash($password,PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO user (id_siswa,id_guru,username,password,role) VALUES (?,?,?,?,?)");
    $stmt->bind_param('iisss',$id_siswa,$id_guru,$username,$hash,$role);
    $stmt->execute();
    flash_set('success','Akun berhasil dibuat');
    header('Location: /ProfilJurusan/admin/akun.php'); exit;
}

if(isset($_GET['hapus'])){
    $id=intval($_GET['hapus']);
    $stmt=$conn->prepare("DELETE FROM user WHERE id_user=?");
    $stmt->bind_param('i',$id);
    $stmt->execute();
    flash_set('success','Akun dihapus');
    header('Location: /ProfilJurusan/admin/akun.php'); exit;
}

$users = fetch_all("
    SELECT u.*, s.nama AS nama_siswa, g.nama AS nama_guru 
    FROM user u 
    LEFT JOIN siswa s ON u.id_siswa=s.id_siswa
    LEFT JOIN guru g ON u.id_guru=g.id_guru
    ORDER BY u.username
");

$siswa = fetch_all("SELECT id_siswa,nama FROM siswa ORDER BY nama");
$guru  = fetch_all("SELECT id_guru,nama FROM guru ORDER BY nama");
?>

<div class="d-flex justify-content-between mb-3 align-items-center">
  <h4>Data Akun</h4>
  <div>
    <button class="btn btn-success me-2" data-bs-toggle="collapse" data-bs-target="#formAdd">Tambah Akun</button>
  </div>
</div>

<div id="formAdd" class="collapse card p-3 mb-3">
  <form method="post" class="row g-2">
    <input type="hidden" name="action" value="add">
    <div class="col-md-3">
      <input name="username" class="form-control" placeholder="Username" required>
    </div>
    <div class="col-md-3">
      <input name="password" type="password" class="form-control" placeholder="Password" required>
    </div>
    <div class="col-md-3">
      <select name="role" id="roleSelect" class="form-select" required>
        <option value="">-- Pilih Role --</option>
        <option value="guru">Guru</option>
        <option value="siswa">Siswa</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <div class="w-50 d-none" id="siswaSelect">
        <select name="id_siswa" class="form-select">
          <option value="">Pilih Siswa</option>
          <?php foreach($siswa as $s): ?>
            <option value="<?=$s['id_siswa']?>"><?=htmlspecialchars($s['nama'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="w-50 d-none" id="guruSelect">
        <select name="id_guru" class="form-select">
          <option value="">Pilih Guru</option>
          <?php foreach($guru as $g): ?>
            <option value="<?=$g['id_guru']?>"><?=htmlspecialchars($g['nama'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary flex-grow-1">Simpan</button>
    </div>
  </form>
</div>

<div class="d-flex justify-content-between mb-2 align-items-center">
  <div class="input-group w-50">
    <span class="input-group-text"><i class="fa fa-search"></i></span>
    <input type="text" id="searchInput" class="form-control" placeholder="Cari username, role, linked...">
  </div>
  <div>
    <a href="../inc/export.php?table=user&type=csv" class="btn btn-sm btn-outline-primary" target="_blank">Export CSV</a>
    <a href="../inc/export.php?table=user&type=pdf" class="btn btn-sm btn-outline-danger" target="_blank">Export PDF</a>
  </div>
</div>

<?php if($msg = flash_get('success')): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080">
  <div id="toastSuccess" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body"><?=htmlspecialchars($msg)?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var toastEl = document.getElementById('toastSuccess');
    if(toastEl){
        var toast = new bootstrap.Toast(toastEl, {delay: 3000});
        toast.show();
    }
});
</script>
<?php endif; ?>

<?php if($msg = flash_get('error')): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080">
  <div id="toastError" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body"><?=htmlspecialchars($msg)?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var toastEl = document.getElementById('toastError');
    if(toastEl){
        var toast = new bootstrap.Toast(toastEl, {delay: 3000});
        toast.show();
    }
});
</script>
<?php endif; ?>

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
    <?php foreach($users as $i=>$u): ?>
      <tr>
        <td><?=$i+1?></td>
        <td><?=htmlspecialchars($u['username'])?></td>
        <td><?=htmlspecialchars($u['role'])?></td>
        <td><?=htmlspecialchars($u['nama_siswa'] ?: $u['nama_guru'] ?: '-')?></td>
        <td>
          <a href="?hapus=<?=$u['id_user']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus akun?')">Hapus</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script>
document.getElementById('roleSelect').addEventListener('change', function(){
  const role = this.value;
  const siswaDiv = document.getElementById('siswaSelect');
  const guruDiv = document.getElementById('guruSelect');
  siswaDiv.classList.add('d-none'); guruDiv.classList.add('d-none');
  siswaDiv.querySelector('select').required=false;
  guruDiv.querySelector('select').required=false;
  if(role==='siswa'){ siswaDiv.classList.remove('d-none'); siswaDiv.querySelector('select').required=true; }
  if(role==='guru'){ guruDiv.classList.remove('d-none'); guruDiv.querySelector('select').required=true; }
});

document.getElementById('searchInput').addEventListener('input', function(){
  const filter=this.value.toLowerCase();
  document.querySelectorAll('#akunTable tbody tr').forEach(tr=>{
    tr.style.display = tr.innerText.toLowerCase().includes(filter)?'':'none';
  });
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>
