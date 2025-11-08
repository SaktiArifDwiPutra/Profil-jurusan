<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
include __DIR__ . '/../inc/header.php';

$nilai = fetch_all("SELECT * FROM v_nilai_lengkap ORDER BY nama_siswa, nama_mapel");
?>
<h4>Daftar Nilai</h4>
<table class="table table-striped">
  <thead><tr><th>No</th><th>Nama Siswa</th><th>Mapel</th><th>Guru</th><th>Semester</th><th>Nilai Akhir</th></tr></thead>
  <tbody>
    <?php foreach($nilai as $i=>$n): ?>
      <tr>
        <td><?=$i+1?></td>
        <td><?=htmlspecialchars($n['nama_siswa'])?></td>
        <td><?=htmlspecialchars($n['nama_mapel'].' ('.$n['kategori'].')')?></td>
        <td><?=htmlspecialchars($n['nama_guru'])?></td>
        <td><?=htmlspecialchars($n['semester'])?></td>
        <td><?=htmlspecialchars($n['nilai_akhir'])?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/../inc/footer.php'; ?>
