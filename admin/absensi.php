<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['admin']);
include __DIR__ . '/../inc/header.php';

$kelas_list = fetch_all("SELECT * FROM kelas ORDER BY tingkat,kelas");
$f_tingkat = $_GET['tingkat'] ?? '';
$f_kelas  = $_GET['kelas'] ?? '';
$tanggal  = $_GET['tanggal'] ?? date('Y-m-d');

$sql = "SELECT s.id_siswa,s.nama,s.id_kelas,k.tingkat,k.kelas 
    FROM siswa s JOIN kelas k ON s.id_kelas=k.id_kelas WHERE 1=1";
$params=[]; $types="";
if($f_tingkat){ $sql.=" AND k.tingkat=?"; $params[]=$f_tingkat; $types.="s"; }
if($f_kelas){ $sql.=" AND k.kelas=?"; $params[]=$f_kelas; $types.="s"; }
$sql.=" ORDER BY s.nama";
$siswa = $types?fetch_all($sql,$types,$params):fetch_all($sql);

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='edit') {
  $id_siswa = (int)$_POST['id_siswa'];
  $id_kelas = (int)$_POST['id_kelas'];
  $status = $_POST['status'] ?? '-';
  $cek = fetch_one("SELECT id_absensi FROM absensi WHERE id_siswa=? AND tanggal=?","is",[$id_siswa,$tanggal]);
  if($cek){
    $stmt=$conn->prepare("UPDATE absensi SET status=? WHERE id_absensi=?");
    $stmt->bind_param("si",$status,$cek['id_absensi']);
    $stmt->execute();
  } else {
    $stmt=$conn->prepare("INSERT INTO absensi (id_siswa,id_kelas,tanggal,status) VALUES (?,?,?,?)");
    $stmt->bind_param("iiss",$id_siswa,$id_kelas,$tanggal,$status);
    $stmt->execute();
  }
  flash_set('success','Absensi diperbarui.');
  header("Location: absensi.php?tingkat=".urlencode($f_tingkat)."&kelas=".urlencode($f_kelas)."&tanggal=".urlencode($tanggal)); exit;
}

$data_absen=[];
foreach($siswa as $s){
 $a=fetch_one("SELECT * FROM absensi WHERE id_siswa=? AND tanggal=?","is",[$s['id_siswa'],$tanggal]);
 $data_absen[$s['id_siswa']]=$a['status']??'-';
}

// sidebar stats
$total_siswa = count($siswa);
$unique_tingkat = array_unique(array_column($kelas_list,'tingkat'));
$jumlah_tingkat = count($unique_tingkat);
?>

<style>
/* Golden layout (keuntungan: non-intrusive, responsive) */
.layout-golden {
 display: grid;
 grid-template-columns: 1fr 320px;
 gap: 20px;
 align-items: start;
 margin-top: 1rem;
}
@media (max-width: 992px) {
 .layout-golden { grid-template-columns: 1fr; }
 .sidebar { order: 2; }
 .left-col { order: 1; }
}
.left-col { min-width: 0; }
.sidebar {
 position: sticky;
 top: 1rem;
 align-self: start;
}
.stat-card {
 background:#fff;
 border:1px solid rgba(0,0,0,0.06);
 padding:12px;
 margin-bottom:12px;
 border-radius:8px;
 box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.stat-card h6 { margin:0 0 8px 0; font-size:.92rem; }
.stat-value { font-size:1.4rem; font-weight:700; color:#1f3d7a; }
.recent-list { display:flex; flex-direction:column; gap:8px; }
.recent-item { padding:6px; border-radius:6px; }
.recent-item:hover { background: rgba(0,0,0,0.03); cursor:pointer; }
.table-responsive { overflow-x:auto; }

/* Small polish to keep inline edit form comfortable */
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
      Tanggal: <strong><?= htmlspecialchars($tanggal) ?></strong>
      <?php if($f_tingkat): ?> • Tingkat: <strong><?=htmlspecialchars($f_tingkat)?></strong><?php endif; ?>
      <?php if($f_kelas): ?> • Kelas: <strong><?=htmlspecialchars($f_kelas)?></strong><?php endif; ?>
     </div>
    </div>


   </div>

      <form method="get" class="row g-3 mb-3">
    <div class="col-md-3">
     <label class="form-label">Tingkat</label>
     <select name="tingkat" class="form-select" onchange="this.form.submit()">
      <option value="">-- Semua --</option>
      <?php foreach(array_unique(array_column($kelas_list,'tingkat')) as $t): ?>
       <option value="<?=htmlspecialchars($t)?>" <?=$f_tingkat==$t?'selected':''?>><?=htmlspecialchars($t)?></option>
      <?php endforeach; ?>
     </select>
    </div>
    <div class="col-md-3">
     <label class="form-label">Kelas</label>
     <select name="kelas" class="form-select" onchange="this.form.submit()">
      <option value="">-- Semua --</option>
      <?php foreach($kelas_list as $k): ?>
       <option value="<?=htmlspecialchars($k['kelas'])?>" <?=$f_kelas==$k['kelas']?'selected':''?>><?=htmlspecialchars($k['kelas'])?></option>
      <?php endforeach; ?>
     </select>
    </div>
    <div class="col-md-3">
     <label class="form-label">Tanggal</label>
     <input type="date" name="tanggal" value="<?=htmlspecialchars($tanggal)?>" class="form-control" onchange="this.form.submit()">
    </div>
    <div class="col-md-3 d-flex align-items-end">
     <a href="absensi.php" class="btn btn-secondary w-100">Reset</a>
    </div>
   </form>

   <?php 
        // Mengambil pesan flash untuk ditampilkan (error, success, warning, info)
        $flash_message = flash_get('success') ?? flash_get('error') ?? flash_get('warning') ?? flash_get('info') ?? '';
        if ($flash_message): 
          // Menentukan tipe alert
          $alert_type = 'info';
          if (isset($_SESSION['flash']['success'])) $alert_type = 'success';
          else if (isset($_SESSION['flash']['error'])) $alert_type = 'danger';
          else if (isset($_SESSION['flash']['warning'])) $alert_type = 'warning';
      ?>
      <div class="position-fixed top-0 end-0 p-3" style="z-index:1080">
    <div id="toastFlash" class="toast align-items-center text-bg-<?= $alert_type ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
     <div class="d-flex">
      <div class="toast-body"><?=htmlspecialchars($flash_message)?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
     </div>
    </div>
   </div>
   <?php endif; ?>

   <div class="table-responsive">
    <table class="table table-striped" id="absensiTable">
     <thead><tr><th style="width:60px">No</th><th>Nama</th><th>Kelas</th><th>Status</th><th style="width:140px">Aksi</th></tr></thead>
     <tbody>
      <?php if(empty($siswa)): ?>
       <tr><td colspan="5" class="text-center py-3">Tidak ada siswa.</td></tr>
      <?php else: foreach($siswa as $i=>$s): ?>
       <tr id="row-<?=$s['id_siswa']?>">
        <td class="view"><?=$i+1?></td>
        <td class="view"><?=htmlspecialchars($s['nama'])?></td>
        <td class="view"><?=htmlspecialchars($s['tingkat'].' '.$s['kelas'])?></td>
        <td class="view"><?=htmlspecialchars($data_absen[$s['id_siswa']])?></td>
        <td class="view">
         <button class="btn btn-sm btn-primary" onclick="editRow(<?=$s['id_siswa']?>)">Edit</button>
        </td>

                <td class="edit d-none" colspan="5">
         <form method="post" class="d-flex gap-2 align-items-center">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id_siswa" value="<?=$s['id_siswa']?>">
          <input type="hidden" name="id_kelas" value="<?=$s['id_kelas']?>">
          <select name="status" class="form-select form-select-sm">
           <?php foreach(['-','Hadir','Izin','Sakit','Alpa'] as $st): ?>
            <option value="<?=htmlspecialchars($st)?>" <?=$data_absen[$s['id_siswa']]==$st?'selected':''?>><?=htmlspecialchars($st)?></option>
           <?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-primary">Simpan</button>
          <button type="button" class="btn btn-sm btn-danger" onclick="cancelEdit(<?=$s['id_siswa']?>)">Batal</button>
         </form>
        </td>
       </tr>
      <?php endforeach; endif; ?>
     </tbody>
    </table>
   </div>
  </div>  </div>   <aside class="sidebar">
  <div class="stat-card">
   <h6>Total Siswa</h6>
   <div class="stat-value"><?= $total_siswa ?></div>
  </div>

  <div class="stat-card">
   <h6>Filter</h6>
   <div style="font-size:.95rem; color:#333;">
    Tingkat: <strong><?= $f_tingkat ?: 'Semua' ?></strong><br>
    Kelas: <strong><?= $f_kelas ?: 'Semua' ?></strong><br>
    Tanggal: <strong><?= htmlspecialchars($tanggal) ?></strong>
   </div>
  </div>

  <div class="stat-card">
   <h6>Terakhir Ditambahkan</h6>
   <div class="recent-list">
    <?php if(empty($siswa)): ?>
     <div class="recent-item">Belum ada data</div>
    <?php else: ?>
     <?php foreach(array_slice(array_reverse($siswa),0,6) as $rs): ?>
      <div class="recent-item" onclick="document.getElementById('row-<?= (int)$rs['id_siswa'] ?>')?.scrollIntoView({behavior:'smooth'})">
       <strong><?= htmlspecialchars($rs['nama']) ?></strong>
       <div style="font-size:.85rem;color:#666"><?= htmlspecialchars($rs['tingkat'].' '.$rs['kelas']) ?></div>
      </div>
     <?php endforeach; ?>
    <?php endif; ?>
   </div>
  </div>

 </aside>
</div> <script>
document.addEventListener('DOMContentLoaded', function(){
 // Mengubah toastSuccess menjadi toastFlash agar lebih generik
 const toastEl = document.getElementById('toastFlash');
 if(toastEl && typeof bootstrap !== 'undefined'){
  // Pastikan elemen Toast ada sebelum mencoba menginisialisasi
  const t = new bootstrap.Toast(toastEl, {delay: 3000});
  t.show();
 }
});

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

document.getElementById('searchInputAbsensi')?.addEventListener('keyup', function(){
 const filter = this.value.toLowerCase();
 document.querySelectorAll('#absensiTable tbody tr').forEach(tr => {
  // skip hidden edit rows by checking id presence and text of parent (simpler: use innerText)
  const text = tr.innerText.toLowerCase();
  tr.style.display = text.includes(filter) ? '' : 'none';
 });
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>