<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['admin']);
include __DIR__ . '/../inc/header.php';

// --- QUERY TIDAK DIUBAH (sesuai permintaan) ---
$sql = "SELECT s.id_siswa, s.nisn, s.nama, k.tingkat, k.kelas 
        FROM siswa s
        JOIN kelas k ON s.id_kelas = k.id_kelas
        ORDER BY k.tingkat, k.kelas, s.nama";

$siswa = fetch_all($sql);

// --- STAT FOR SIDEBAR (mirip halaman lain) ---
$total_siswa = count($siswa);
$kelas_count = [];
foreach($siswa as $s){
    $label = trim(($s['tingkat'] ?? '') . ' - ' . ($s['kelas'] ?? ''));
    if($label === '-') $label = 'Belum Ada Kelas';
    $kelas_count[$label] = ($kelas_count[$label] ?? 0) + 1;
}
$recent = array_slice($siswa, max(0, $total_siswa - 6)); // 6 terakhir
?>

<div class="layout-golden">

  <!-- LEFT: judul + add/export + search + grid cards -->
  <div class="left-col">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Daftar Nilai Siswa</h4>
    </div>

    <div class="d-flex justify-content-between mb-2 align-items-center">
      <div class="input-group w-50">
        <span class="input-group-text"><i class="fa fa-search"></i></span>
        <input type="text" id="searchInputNilai" class="form-control" placeholder="Cari siswa (NISN, Nama, Kelas)...">
      </div>

      <!-- optional area (kosong agar layout rapi, export ada di header) -->
      <div></div>
    </div>

    <!-- GRID: ubah tabel menjadi grid card (sama gaya seperti halaman siswa) -->
    <div id="siswaContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-5">
      <?php if(empty($siswa)): ?>
        <div class="col">
          <div class="card p-3 text-center text-muted">Tidak ada data siswa</div>
        </div>
      <?php else: foreach($siswa as $s): 
        $id = (int)$s['id_siswa'];
        $kelas_label = ($s['tingkat'] ?? '') ? ($s['tingkat'].' - '.$s['kelas']) : 'Belum Ada Kelas';
        $data_json = json_encode([
          'id' => $id,
          'nisn' => $s['nisn'] ?? '',
          'nama' => $s['nama'] ?? '',
          'kelas' => $kelas_label
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
      ?>
        <div class="col siswa-card-col" data-siswa='<?= htmlspecialchars($data_json, ENT_QUOTES) ?>' data-id="<?= $id ?>" data-nisn="<?=htmlspecialchars($s['nisn'] ?? '', ENT_QUOTES)?>" data-nama="<?=htmlspecialchars($s['nama'] ?? '', ENT_QUOTES)?>" data-kelas="<?=htmlspecialchars($kelas_label, ENT_QUOTES)?>">
          <div class="card siswa-card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0" style="font-size:1rem;"><?=htmlspecialchars($s['nama'])?></h5>
                <span class="badge rounded-pill text-bg-secondary" style="font-size:.75rem;"><?=htmlspecialchars($kelas_label)?></span>
              </div>

              <div class="mb-2 text-muted" style="font-size:.9rem;">
                <div><strong>NISN:</strong> <?=htmlspecialchars($s['nisn'] ?? '-')?></div>
              </div>

              <div style="margin-top:auto; display:flex; gap:.5rem;">
                <a href="/ProfilJurusan/admin/nilai_detail.php?id_siswa=<?=$id?>" class="btn btn-sm btn-primary flex-grow-1">Lihat Nilai</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

  </div> <!-- /.left-col -->

  <!-- RIGHT: sidebar statistik (sama seperti halaman lain) -->
  <aside class="sidebar">
    <div class="stat-card">
      <h6>Total Siswa</h6>
      <div class="stat-value"><?= $total_siswa ?></div>
    </div>

    <div class="stat-card">
      <h6>Jumlah Kelas</h6>
      <div><?= count($kelas_count) ?></div>
    </div>

    <div class="stat-card">
      <h6>Distribusi Kelas</h6>
      <div>
        <?php foreach($kelas_count as $label => $cnt): ?>
          <div style="font-size:.95rem; color:#333; margin-bottom:4px;"><?= htmlspecialchars($label) ?>: <strong><?= $cnt ?></strong></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="stat-card">
      <h6>Terakhir Ditambahkan</h6>
      <div class="recent-list">
        <?php if(empty($recent)): ?>
          <div class="recent-item">Belum ada data</div>
        <?php else: foreach(array_reverse($recent) as $r): 
          $nameEsc = htmlspecialchars($r['nama'], ENT_QUOTES);
          $kelasEsc = htmlspecialchars((($r['tingkat'] ?? '') . ' - ' . ($r['kelas'] ?? '')), ENT_QUOTES);
        ?>
          <div class="recent-item" style="cursor:pointer" onclick="document.querySelector('.siswa-card-col[data-id=&quot;<?= (int)$r['id_siswa'] ?>&quot;]')?.scrollIntoView({behavior:'smooth'});">
            <div style="min-width:0;">
              <strong><?= $nameEsc ?></strong>
              <div style="font-size:0.85rem;color:#666"><?= $kelasEsc ?></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </aside>

</div> <!-- /.layout-golden -->

<script>
// filter grid cards (search)
document.getElementById('searchInputNilai')?.addEventListener('input', function(){
  const q = (this.value || '').toLowerCase().trim();
  document.querySelectorAll('.siswa-card-col').forEach(col=>{
    const nisn = (col.dataset.nisn || '').toLowerCase();
    const nama = (col.dataset.nama || '').toLowerCase();
    const kelas = (col.dataset.kelas || '').toLowerCase();
    const hay = nisn + ' ' + nama + ' ' + kelas;
    col.style.display = (!q || hay.includes(q)) ? '' : 'none';
  });
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>
