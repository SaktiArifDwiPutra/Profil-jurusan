<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['admin']);
include __DIR__ . '/../inc/header.php';

$id_siswa = (int)($_GET['id_siswa'] ?? 0);
if(!$id_siswa){
  flash_set('error','Data tidak valid');
  header('Location: nilai.php'); exit;
}

$s = fetch_one("SELECT s.*, k.tingkat, k.kelas 
                FROM siswa s 
                JOIN kelas k ON s.id_kelas=k.id_kelas 
                WHERE id_siswa=?","i",[$id_siswa]);
if(!$s){
  echo '<div class="alert alert-warning">Siswa tidak ditemukan.</div>'; 
  include __DIR__.'/../inc/footer.php'; exit;
}

$mapel = fetch_all("
    SELECT m.id_mapel, m.nama_mapel, md.id_ampu, g.nama AS nama_guru
    FROM mapel_diampu md
    JOIN mapel m ON md.id_mapel = m.id_mapel
    LEFT JOIN guru g ON md.id_guru = g.id_guru
    WHERE md.id_kelas = ?
    ORDER BY m.nama_mapel
","i",[$s['id_kelas']]);

// statistik sidebar: jumlah mapel, semester terakhir (per mapel kita ambil di loop nanti)
$total_mapel = count($mapel);
$recent_mapels = array_slice($mapel, max(0, $total_mapel - 5));
?>

<div class="layout-golden">

  <!-- LEFT: judul + export sejajar, konten utama (tabel atau grid) -->
  <div class="left-col">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Nilai - <?= htmlspecialchars($s['nama']) ?> <small class="text-muted" style="font-size:.9rem;">(<?= htmlspecialchars(($s['tingkat'] ?? '').' '.($s['kelas'] ?? '')) ?>)</small></h4>

      <div class="d-flex gap-2">
        <a href="../inc/export.php?table=nilai&type=csv&id_siswa=<?= $id_siswa ?>" class="btn btn-outline-primary btn-sm" target="_blank">Export CSV</a>
        <a href="../inc/export.php?table=nilai&type=pdf&id_siswa=<?= $id_siswa ?>" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
      </div>
    </div>

    <!-- optional small info card -->
    <div class="mb-3">
      <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div style="font-size:.95rem;"><strong><?= htmlspecialchars($s['nama']) ?></strong></div>
            <div class="text-muted" style="font-size:.87rem;"><?= htmlspecialchars(($s['tingkat'] ?? '').' - '.($s['kelas'] ?? '')) ?></div>
            <div class="text-muted" style="font-size:.85rem;"><?= htmlspecialchars($s['nisn'] ?? '-') ?> • <?= htmlspecialchars($s['no_hp'] ?? '') ?></div>
          </div>
          <div class="text-end" style="min-width:160px;">
            <a href="nilai.php" class="btn btn-secondary btn-sm">← Kembali</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Konten nilai: gunakan card + responsive table di dalam agar konsisten -->
    <div class="card mb-4">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th style="width:60px">No</th>
                <th>Mapel</th>
                <th style="width:180px">Guru</th>
                <th style="width:110px">Semester</th>
                <th style="width:100px">Tugas</th>
                <th style="width:100px">UTS</th>
                <th style="width:100px">Akhir</th>
              </tr>
            </thead>
            <tbody>
            <?php if(empty($mapel)): ?>
              <tr><td colspan="8" class="text-center py-3">Belum ada mapel untuk kelas ini.</td></tr>
            <?php else: foreach($mapel as $i=>$m): 
              // ambil nilai terbaru untuk mapel ini (tetap seperti logika awal)
              $n = fetch_one("SELECT * FROM nilai WHERE id_siswa=? AND id_ampu=? ORDER BY semester DESC LIMIT 1",
                             "ii",[$id_siswa,$m['id_ampu']]);
            ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($m['nama_mapel']) ?></td>
                <td><?= htmlspecialchars($m['nama_guru'] ?? '-') ?></td>
                <td><?= htmlspecialchars($n['semester'] ?? '-') ?></td>
                <td><?= htmlspecialchars($n['nilai_tugas'] ?? '-') ?></td>
                <td><?= htmlspecialchars($n['nilai_uts'] ?? '-') ?></td>
                <td><?= htmlspecialchars($n['nilai_akhir'] ?? '-') ?></td>
                <td>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div> <!-- /.left-col -->

  <!-- RIGHT: sidebar (ringkasan siswa + statistik nilai/mapel) -->
  <aside class="sidebar">
    <div class="stat-card">
      <h6>Ringkasan Siswa</h6>
      <div style="padding-top:.25rem;">
        <div style="font-size:1rem; font-weight:600;"><?= htmlspecialchars($s['nama']) ?></div>
        <div style="font-size:.85rem;color:#666;"><?= htmlspecialchars($s['nisn'] ?? '-') ?></div>
        <div style="font-size:.85rem;color:#666;"><?= htmlspecialchars(($s['tingkat'] ?? '').' - '.($s['kelas'] ?? '')) ?></div>
        <div style="margin-top:.4rem; font-size:.95rem;"><strong>Mapel:</strong> <?= $total_mapel ?></div>
      </div>
    </div>

    <div class="stat-card">
      <h6>Mapel</h6>
      <div>
        <?php if(empty($mapel)): ?>
          <div class="recent-item">Belum ada mapel</div>
        <?php else: foreach($mapel as $mp): ?>
          <div style="font-size:.95rem; color:#333; margin-bottom:6px;"><?= htmlspecialchars($mp['nama_mapel']) ?> <div style="font-size:.8rem;color:#666"><?= htmlspecialchars($mp['nama_guru'] ?? '-') ?></div></div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <div class="stat-card">
      <h6>Terakhir Ditambahkan</h6>
      <div class="recent-list">
        <?php if(empty($recent_mapels)): ?>
          <div class="recent-item">Belum ada data</div>
        <?php else: foreach(array_reverse($recent_mapels) as $rm): ?>
          <div class="recent-item" style="cursor:pointer" onclick="document.querySelector('table tbody tr:nth-child(<?= (int)array_search($rm, $mapel) + 1 ?>)')?.scrollIntoView({behavior:'smooth'});">
            <div style="min-width:0;">
              <strong><?= htmlspecialchars($rm['nama_mapel']) ?></strong>
              <div style="font-size:0.85rem;color:#666"><?= htmlspecialchars($rm['nama_guru'] ?? '-') ?></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </aside>

</div> <!-- /.layout-golden -->

<?php include __DIR__ . '/../inc/footer.php'; ?>
