<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['guru']);
include __DIR__ . '/../inc/header.php';

$gurus = fetch_all("SELECT id_guru, nama FROM guru ORDER BY nama");
$kelas_rows = fetch_all("SELECT k.*, g.nama AS wali_nama FROM kelas k LEFT JOIN guru g ON k.id_wali = g.id_guru ORDER BY k.tingkat, k.kelas");

$total_kelas = count($kelas_rows);
$tingkat_set = [];
$wali_set = [];
foreach ($kelas_rows as $kr) {
  $tingkat_set[$kr['tingkat']] = true;
  if (!empty($kr['id_wali'])) $wali_set[$kr['id_wali']] = $kr['wali_nama'] ?? '';
}
$recent = array_slice($kelas_rows, max(0, $total_kelas - 5));
?>

<div class="layout-golden">

 <div class="left-col">

  <div class="d-flex justify-content-between align-items-center mb-3">
   <h4 class="mb-0">Data Kelas</h4>
  </div>

  <div class="d-flex justify-content-between mb-2 align-items-center">
   <div class="input-group w-50">
    <span class="input-group-text"><i class="fa fa-search"></i></span>
    <input type="text" id="searchInput" class="form-control" placeholder="Cari kelas (Tingkat, Nama, Wali)...">
   </div>

   <div class="d-flex gap-2">
    <a href="../inc/export.php?table=kelas&type=csv" class="btn btn-outline-primary btn-sm" target="_blank">Export CSV</a>
    <a href="../inc/export.php?table=kelas&type=pdf" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
   </div>
  </div>

  <div id="kelasContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
   <?php foreach($kelas_rows as $k):
    $id = (int)$k['id_kelas'];
    $data = [
     'id' => $id,
     'tingkat' => $k['tingkat'],
     'kelas' => $k['kelas'],
     'wali_id' => $k['id_wali'] ?? null,
     'wali_nama' => $k['wali_nama'] ?? ''
    ];
    $data_json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
   ?>
   <div class="col kelas-card-col"
    data-kelas="<?= htmlspecialchars($data_json, ENT_QUOTES) ?>"
    data-id="<?= $id ?>"
    data-tingkat="<?=htmlspecialchars($k['tingkat'] ?? '', ENT_QUOTES)?>"
    data-nama="<?=htmlspecialchars($k['kelas'] ?? '', ENT_QUOTES)?>"
    data-wali="<?=htmlspecialchars($k['wali_nama'] ?? '-', ENT_QUOTES)?>">
    <div class="card siswa-card shadow-sm h-100" data-bs-toggle="modal" data-bs-target="#detailModalKelas" onclick="showKelasDetail(<?= $id ?>)">
     <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
       <h5 class="card-title mb-0"><?=htmlspecialchars($k['tingkat'].' - '.$k['kelas'])?></h5>
       <span class="badge rounded-pill text-bg-secondary"><?=htmlspecialchars($k['tingkat'])?></span>
      </div>
      <p class="card-text text-muted mb-0"><small><?=htmlspecialchars($k['kelas'])?></small></p>
      <div class="mt-2" style="font-size:.9rem;color:#555;">
       <div><strong>Wali:</strong> <?=htmlspecialchars($k['wali_nama'] ?? '-')?></div>
      </div>
     </div>
    </div>
   </div>
   <?php endforeach; ?>
  </div>
 </div>

 <aside class="sidebar">
  <div class="stat-card">
   <h6>Total Kelas</h6>
   <div class="stat-value"><?= $total_kelas ?></div>
  </div>

  <div class="stat-card">
   <h6>Jumlah Tingkat</h6>
   <div><?= count($tingkat_set) ?></div>
  </div>

  <div class="stat-card">
   <h6>Jumlah Wali</h6>
   <div><?= count($wali_set) ?></div>
  </div>

  <div class="stat-card">
   <h6>Terakhir Ditambahkan</h6>
   <div class="recent-list">
    <?php if(empty($recent)): ?>
     <div class="recent-item">Belum ada data</div>
    <?php else: foreach(array_reverse($recent) as $r): ?>
     <div class="recent-item" style="cursor:pointer" onclick="document.querySelector('.kelas-card-col[data-id=&quot;<?= (int)$r['id_kelas'] ?>&quot;]')?.scrollIntoView({behavior:'smooth'});">
      <div style="min-width:0;">
       <strong><?= htmlspecialchars($r['tingkat'].' - '.$r['kelas']) ?></strong>
       <div style="font-size:0.85rem;color:#666"><?= htmlspecialchars($r['wali_nama'] ?? '-') ?></div>
      </div>
     </div>
    <?php endforeach; endif; ?>
   </div>
  </div>
 </aside>
</div>

<div class="modal fade" id="detailModalKelas" tabindex="-1" aria-labelledby="detailModalKelasLabel" aria-hidden="true">
 <div class="modal-dialog modal-lg">
  <div class="modal-content">
   <div class="modal-header">
    <h5 class="modal-title" style="padding-left: 40px;" id="detailModalKelasLabel">Detail Kelas: <span id="modal-kelas-nama"></span></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
   </div>
   <div class="modal-body">
    <div id="modal-kelas-view">
     <p><strong>Tingkat:</strong> <span id="detail-tingkat"></span></p>
     <p><strong>Nama Kelas:</strong> <span id="detail-kelas"></span></p>
     <p><strong>Wali Kelas:</strong> <span id="detail-wali"></span></p>
    </div>
   </div>

   <div class="modal-footer" id="modal-footer-actions">
    <div id="view-buttons" style="display:flex; padding-left: 120px; gap:8px; align-items:center;">
     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
    </div>

    <div style="flex:1"></div>

    <div class="modal-export-wrap d-flex gap-2">
     <a href="#" id="btn-export-kelas-pdf" class="btn btn-sm btn-outline-danger btn-export-single" target="_blank">Export PDF</a>
    </div>
   </div>
  </div>
 </div>
</div>

<script>
function showKelasDetail(id) {
 const col = document.querySelector(`.kelas-card-col[data-id="${id}"]`);
 if(!col) return;

 let k = null;
 if(col.dataset.kelas){
  try {
   k = JSON.parse(col.dataset.kelas);
  } catch(e){
   k = {
    id: id,
    tingkat: col.dataset.tingkat || '',
    kelas: col.dataset.nama || '',
    wali_nama: col.dataset.wali || ''
   };
  }
 } else {
  k = { id: id, tingkat: col.dataset.tingkat || '', kelas: col.dataset.nama || '', wali_nama: col.dataset.wali || '' };
 }

 document.getElementById('modal-kelas-view').style.display = 'block';
 document.getElementById('view-buttons').style.display = 'flex';
 document.getElementById('btn-export-kelas-pdf').style.display = 'block';

 document.getElementById('modal-kelas-nama').textContent = (k.tingkat || '') + ' ' + (k.kelas || '');
 document.getElementById('detail-tingkat').textContent = k.tingkat || '-';
 document.getElementById('detail-kelas').textContent = k.kelas || '-';
 document.getElementById('detail-wali').textContent = k.wali_nama || '-';

 const btnPdf = document.getElementById('btn-export-kelas-pdf');
 if(btnPdf) btnPdf.href = '../inc/export.php?table=kelas&type=pdf&id_kelas=' + encodeURIComponent(k.id);

 const modalEl = document.getElementById('detailModalKelas');
 if(modalEl) modalEl.classList.add('vertical');
}

document.getElementById('searchInput')?.addEventListener('input', function(){
 const q = (this.value || '').toLowerCase();
 document.querySelectorAll('.kelas-card-col').forEach(col=>{
  const tingkat = (col.dataset.tingkat || '').toLowerCase();
  const nama = (col.dataset.nama || '').toLowerCase();
  const wali = (col.dataset.wali || '').toLowerCase();
  const visible = !q || tingkat.includes(q) || nama.includes(q) || wali.includes(q);
  col.style.display = visible ? '' : 'none';
 });
});

document.getElementById('detailModalKelas')?.addEventListener('show.bs.modal', function(){
 this.classList.add('vertical');
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>