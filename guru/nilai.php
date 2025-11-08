<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['guru']);
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

 <div class="left-col">

 <h4 class="fw-bold mb-3">Daftar Nilai Siswa</h4>
 <p class="text-muted">Pilih siswa untuk melihat atau mengelola nilai.</p>

 <div class="d-flex mb-4 align-items-center">
 <div class="input-group w-75">
  <span class="input-group-text"><i class="fa fa-search"></i></span>
  <input type="text" id="searchInputNilai" class="form-control" placeholder="Cari siswa (NISN, Nama, Kelas)...">
 </div>
 </div>

  <div id="siswaContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-5">
 <?php if(empty($siswa)): ?>
  <div class="col-12">
  <div class="alert alert-info shadow-sm">Tidak ada data siswa yang ditemukan dalam sistem.</div>
  </div>
 <?php else: foreach($siswa as $s): 
  $id = (int)$s['id_siswa'];
  $kelas_label = ($s['tingkat'] ?? '') ? ($s['tingkat'].' - '.$s['kelas']) : 'Belum Ada Kelas';
 ?>
  <div class="col siswa-card-col" data-id="<?= $id ?>" data-nisn="<?=strtolower(htmlspecialchars($s['nisn'] ?? '', ENT_QUOTES))?>" data-nama="<?=strtolower(htmlspecialchars($s['nama'] ?? '', ENT_QUOTES))?>" data-kelas="<?=strtolower(htmlspecialchars($kelas_label, ENT_QUOTES))?>">
  <div class="card siswa-card h-100 p-3 shadow-sm">
    <div class="d-flex flex-column h-100">

          <div class="mb-3">
      <h6 class="mb-0 fw-bold text-primary text-truncate" style="font-size: 1.1rem;"><?=htmlspecialchars($s['nama'])?></h6>
      
            <span class="badge rounded-pill text-bg-info mb-1" style="font-size: 0.8em;"><?=htmlspecialchars($kelas_label)?></span>
      
      <p class="small text-muted mb-0">NISN: <?=htmlspecialchars($s['nisn'])?></p>
     </div>

          <a href="nilai_detail.php?id_siswa=<?=$id?>" class="btn btn-sm btn-outline-primary mt-auto">
     Lihat Nilai <i class="fa fa-arrow-right ms-1"></i>
     </a>
    </div>
  </div>
  </div>
 <?php endforeach; endif; ?>
 </div>

</div>  
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
 <div class="recent-list">
  <?php foreach($kelas_count as $label => $cnt): ?>
  <div class="recent-item">
   <div><?= htmlspecialchars($label) ?>:</div>
   <strong><?= $cnt ?></strong>
  </div>
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

</div> 

<script>
// filter grid cards (search)
document.getElementById('searchInputNilai')?.addEventListener('input', function(){
const q = (this.value || '').toLowerCase().trim();
document.querySelectorAll('.siswa-card-col').forEach(col=>{
 // Menggunakan data attribute yang sudah di-lowercase dari PHP
 const nisn = col.dataset.nisn || '';
 const nama = col.dataset.nama || '';
 const kelas = col.dataset.kelas || '';
 const hay = nisn + ' ' + nama + ' ' + kelas;
 col.style.display = (!q || hay.includes(q)) ? 'block' : 'none';
});
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>