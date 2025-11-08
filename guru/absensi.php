<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['guru']);
include __DIR__ . '/../inc/header.php';

$u = current_user();
$id_guru = (int)$u['id_guru'];
$kelas = fetch_one("SELECT * FROM kelas WHERE id_wali=?","i",[$id_guru]);
if(!$kelas){ 
    echo '<div class="alert alert-warning">Anda bukan wali kelas.</div>'; 
    include __DIR__.'/../inc/footer.php'; 
    exit; 
}

$siswa = fetch_all("SELECT * FROM siswa WHERE id_kelas=? ORDER BY nama","i",[$kelas['id_kelas']]);

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='edit') {
    $id_siswa = (int)$_POST['id_siswa'];
    $status = $_POST['status'] ?? '-';
    $cek = fetch_one("SELECT id_absensi FROM absensi WHERE id_siswa=? AND tanggal=?","is",[$id_siswa,$tanggal]);
    if($cek){
        $stmt=$conn->prepare("UPDATE absensi SET status=? WHERE id_absensi=?");
        $stmt->bind_param("si",$status,$cek['id_absensi']);
        $stmt->execute();
    } else {
        $stmt=$conn->prepare("INSERT INTO absensi (id_siswa,id_kelas,tanggal,status) VALUES (?,?,?,?)");
        $stmt->bind_param("iiss",$id_siswa,$kelas['id_kelas'],$tanggal,$status);
        $stmt->execute();
    }
    flash_set('success','Absensi diperbarui.');
    header("Location: absensi.php?tanggal=$tanggal"); exit;
}

$data_absen = [];
foreach($siswa as $s){
  $a = fetch_one("SELECT * FROM absensi WHERE id_siswa=? AND tanggal=?","is",[$s['id_siswa'],$tanggal]);
  $data_absen[$s['id_siswa']] = $a['status'] ?? '-';
}
?>

<div class="card p-4 shadow-sm">
  <h4>Absensi Kelas <?=$kelas['tingkat'].' '.$kelas['kelas']?></h4>
  <form method="get" class="mb-3 d-flex align-items-center gap-2">
    <label for="tanggal" class="form-label m-0">Pilih Tanggal:</label>
    <input type="date" id="tanggal" name="tanggal" value="<?=$tanggal?>" class="form-control w-auto">
  </form>

  <p class="text-muted">Tanggal: <?=date('d-m-Y',strtotime($tanggal))?></p>

  <table class="table table-striped">
    <thead>
      <tr>
        <th style="width:60px">No</th>
        <th>Nama</th>
        <th style="width:150px">Status</th>
        <th style="width:160px">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($siswa as $i=>$s): 
        $id = (int)$s['id_siswa'];
        $statusNow = $data_absen[$id];
      ?>
        <tr id="row-<?=$id?>">
          <td class="view"><?=$i+1?></td>
          <td class="view"><?=htmlspecialchars($s['nama'])?></td>
          <td class="view"><?=$statusNow?></td>
          <td class="view">
            <button class="btn btn-sm btn-primary" onclick="editRow(<?=$id?>)">Edit</button>
          </td>

          <td colspan="4" class="edit d-none">
            <form method="post" class="d-flex gap-2 align-items-center">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id_siswa" value="<?=$id?>">
              <select name="status" class="form-select form-select-sm">
                <?php foreach(['-','Hadir','Izin','Sakit','Alpa'] as $st): ?>
                  <option value="<?=$st?>" <?=$statusNow==$st?'selected':''?>><?=$st?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-primary">Simpan</button>
              <button type="button" class="btn btn-sm btn-danger" onclick="cancelEdit(<?=$id?>)">Batal</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
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

document.getElementById('tanggal').addEventListener('change', function(){
  window.location = 'absensi.php?tanggal=' + this.value;
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>
