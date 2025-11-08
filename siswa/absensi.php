<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['siswa']);

$u = current_user();
$siswa = fetch_one("SELECT * FROM siswa WHERE id_siswa=?", "i", [$u['id_siswa']]);
if (!$siswa) { echo '<div class="alert alert-danger">Data siswa tidak ditemukan.</div>'; include __DIR__ . '/../inc/footer.php'; exit; }

$today = date('Y-m-d');
$cek = fetch_one("SELECT * FROM absensi WHERE id_siswa=? AND tanggal=?", "is", [$siswa['id_siswa'],$today]);

if ($_SERVER['REQUEST_METHOD']==='POST' && !$cek) {
  $status = $_POST['status'] ?? '';
  if ($status) {
    $stmt=$conn->prepare("INSERT INTO absensi (id_siswa,id_kelas,tanggal,status) VALUES (?,?,?,?)");
    $stmt->bind_param("iiss",$siswa['id_siswa'],$siswa['id_kelas'],$today,$status);
    $stmt->execute();
    flash_set('success','Absensi berhasil disimpan.');
    header("Location: absensi.php"); exit;
  }
}

$riwayat = fetch_all("SELECT * FROM absensi WHERE id_siswa=? ORDER BY tanggal DESC","i",[$siswa['id_siswa']]);

// Hitung statistik untuk sidebar
$total_absen = count($riwayat);
$stats = [
 'Hadir' => 0,
 'Izin' => 0,
 'Sakit' => 0,
 'Alpa' => 0,
];
foreach($riwayat as $r) {
  $stats[$r['status']] = ($stats[$r['status']] ?? 0) + 1;
}

include __DIR__ . '/../inc/header.php';
?>

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
  if(toastEl && typeof bootstrap !== 'undefined'){
    var toast = new bootstrap.Toast(toastEl, {delay: 3000});
    toast.show();
  }
});
</script>
<?php endif; ?>

<div class="layout-golden">

  <div class="left-col">
  <div class="card shadow-sm p-4 mb-4">
   <h4 class="fw-bold mb-1">Absensi Siswa</h4>
   <div class="mb-3">
    <p class="mb-0"><strong><?=htmlspecialchars($siswa['nama'])?></strong> (<?=htmlspecialchars($siswa['nisn'])?>)</p>
    <p class="text-muted mb-0">Tanggal Hari Ini: <?=date('d-m-Y')?></p>
   </div>

   <?php if($cek): ?>
    <div class="alert alert-success mt-2 mb-0">Kamu sudah absen hari ini sebagai <strong><?=$cek['status']?></strong>.</div>
   <?php else: ?>
    <div class="card p-3 mt-2">
     <form method="post" class="row g-3">
      <div class="col-md-6">
       <label for="statusKehadiran" class="form-label">Status Kehadiran</label>
       <select name="status" id="statusKehadiran" class="form-select" required>
        <option value="">-- Pilih --</option>
        <option value="Hadir">Hadir</option>
        <option value="Izin">Izin</option>
        <option value="Sakit">Sakit</option>
        <option value="Alpa">Alpa</option>
       </select>
      </div>
      <div class="col-md-6 d-flex align-items-end">
       <button class="btn btn-primary w-100">Kirim Absen</button>
      </div>
     </form>
    </div>
   <?php endif; ?>
  </div>

  <div class="card shadow-sm p-4">
   <h5>Riwayat Absensi</h5>
   <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
     <thead>
      <tr>
       <th>No</th>
       <th>Tanggal</th>
       <th>Status</th>
      </tr>
     </thead>
     <tbody>
      <?php foreach($riwayat as $i=>$r): ?>
       <tr>
        <td><?=$i+1?></td>
        <td><?=date('d-m-Y',strtotime($r['tanggal']))?></td>
        <td>
         <span class="badge rounded-pill text-bg-<?= $r['status'] == 'Hadir' ? 'success' : ($r['status'] == 'Izin' ? 'warning' : ($r['status'] == 'Sakit' ? 'info' : 'danger')) ?>">
          <?=$r['status']?>
         </span>
        </td>
       </tr>
      <?php endforeach; ?>
     </tbody>
    </table>
   </div>
  </div>
 </div>

  <aside class="sidebar">
  <div class="stat-card">
   <h6>Total Absensi Tercatat</h6>
   <div class="stat-value"><?= $total_absen ?> Hari</div>
  </div>

  <div class="stat-card">
   <h6>Statistik Kehadiran</h6>
   <div class="recent-list">
    <div class="recent-item">
     <div>Hadir:</div>
     <strong><?= $stats['Hadir'] ?? 0 ?></strong>
    </div>
    <div class="recent-item">
     <div>Sakit:</div>
     <strong><?= $stats['Sakit'] ?? 0 ?></strong>
    </div>
    <div class="recent-item">
     <div>Izin:</div>
     <strong><?= $stats['Izin'] ?? 0 ?></strong>
    </div>
    <div class="recent-item">
     <div>Alpa:</div>
     <strong><?= $stats['Alpa'] ?? 0 ?></strong>
    </div>
   </div>
  </div>

  <div class="stat-card">
   <h6>Absensi Terakhir</h6>
   <div class="recent-list">
    <?php if(empty($riwayat)): ?>
     <div class="recent-item">Belum ada absensi</div>
    <?php else:
      $last_5 = array_slice($riwayat, 0, 5);
      foreach($last_5 as $r):
        $status_class = $r['status'] == 'Hadir' ? 'text-success' : ($r['status'] == 'Izin' ? 'text-warning' : ($r['status'] == 'Sakit' ? 'text-info' : 'text-danger'));
    ?>
     <div class="recent-item">
      <div><?=date('d M',strtotime($r['tanggal']))?></div>
      <strong class="<?=$status_class?>"><?=$r['status']?></strong>
     </div>
    <?php endforeach; endif; ?>
   </div>
  </div>
 </aside>
</div>

<?php include __DIR__ . '/../inc/footer.php'; ?>