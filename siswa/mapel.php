<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['siswa']);
include __DIR__ . '/../inc/header.php';

$mapels = fetch_all("SELECT * FROM mapel ORDER BY nama_mapel ASC");

$ampu_rows = fetch_all("
  SELECT md.id_ampu, md.id_mapel, md.id_guru, g.nama AS guru_nama
  FROM mapel_diampu md
  JOIN guru g ON md.id_guru = g.id_guru
  ORDER BY g.nama
");

$ampu_map = [];
foreach($ampu_rows as $r){
  $mid = (int)$r['id_mapel'];
  if(!isset($ampu_map[$mid])) $ampu_map[$mid] = [];
  $ampu_map[$mid][] = [
    'id_guru' => (int)$r['id_guru'],
    'nama'  => $r['guru_nama']
  ];
}

$total_mapel = count($mapels);
$kategori_count = [];
foreach($mapels as $m){
  $kategori_count[$m['kategori']] = ($kategori_count[$m['kategori']] ?? 0) + 1;
}
$recent_mapels = array_slice($mapels, max(0, $total_mapel - 6));
?>

<div class="layout-golden">

 <div class="left-col">

  <div class="d-flex justify-content-between mb-3">
   <h4>Data Mata Pelajaran</h4>
  </div>

  <div class="d-flex justify-content-between mb-2 align-items-center">
   <div class="input-group w-50">
    <span class="input-group-text"><i class="fa fa-search"></i></span>
    <input type="text" id="searchInput" class="form-control" placeholder="Cari mapel...">
   </div>

   <div class="d-flex gap-2">
    <a href="../inc/export.php?table=mapel&type=csv" class="btn btn-outline-primary btn-sm" target="_blank">Export CSV</a>
    <a href="../inc/export.php?table=mapel&type=pdf" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
   </div>
  </div>

  <div id="mapelContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-5">
   <?php foreach($mapels as $m):
    $id = (int)$m['id_mapel'];
    $guru_list = $ampu_map[$id] ?? [];
    $guru_names = array_map(fn($x)=>$x['nama'], $guru_list);

    $data_json = json_encode([
     'id'=>$id,
     'nama'=>$m['nama_mapel'],
     'kategori'=>$m['kategori'],
     'gurus'=>$guru_names
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
   ?>
   <div class="col mapel-card-col" data-mapel="<?= htmlspecialchars($data_json, ENT_QUOTES) ?>" data-id="<?= $id ?>" data-nama="<?=htmlspecialchars($m['nama_mapel'], ENT_QUOTES)?>" data-kategori="<?=htmlspecialchars($m['kategori'], ENT_QUOTES)?>">
    <div class="card siswa-card shadow-sm h-100" data-bs-toggle="modal" data-bs-target="#detailModalMapel" onclick="showMapelDetail(<?= $id ?>)">
     <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
       <h5 class="card-title mb-0 text-start"><?=htmlspecialchars($m['nama_mapel'])?></h5>
       <span class="badge rounded-pill text-bg-<?= $m['kategori']==='Produktif' ? 'primary' : 'secondary' ?>"><?=htmlspecialchars($m['kategori'])?></span>
      </div>
      <div class="mt-2" style="font-size:.9rem;color:#555;">
       <?php if(!empty($guru_list)): ?>
        <?=htmlspecialchars(implode(', ', array_map(fn($g)=>$g['nama'], $guru_list)))?>
       <?php else: ?>
        <span class="text-muted">Belum ada guru pengampu</span>
       <?php endif; ?>
      </div>
    </div>
   </div>
  </div>
   <?php endforeach; ?>
  </div>
 </div>

 <aside class="sidebar">
  <div class="stat-card">
   <h6>Total Mapel</h6>
   <div class="stat-value"><?= $total_mapel ?></div>
  </div>

  <div class="stat-card">
   <h6>Kategori</h6>
   <div>
    <?php foreach($kategori_count as $k=>$cnt): ?>
     <div style="font-size:.95rem; color:#333; margin-bottom:4px;"><?=htmlspecialchars($k)?>: <strong><?= $cnt ?></strong></div>
    <?php endforeach; ?>
   </div>
  </div>

  <div class="stat-card">
   <h6>Terakhir Ditambahkan</h6>
   <div class="recent-list">
    <?php if(empty($recent_mapels)): ?>
     <div class="recent-item">Belum ada data</div>
    <?php else: foreach(array_reverse($recent_mapels) as $rg):
      $idrg = (int)$rg['id_mapel'];
      $gurus_for_recent = $ampu_map[$idrg] ?? [];
    ?>
     <div class="recent-item" style="cursor:pointer" onclick="document.querySelector('.mapel-card-col[data-id=&quot;<?= $idrg ?>&quot;]')?.scrollIntoView({behavior:'smooth'});">
      <div style="min-width:0;">
       <strong><?= htmlspecialchars($rg['nama_mapel']) ?></strong>
       <div style="font-size:0.85rem;color:#666"><?= htmlspecialchars($rg['kategori']) ?></div>
       <div style="font-size:0.82rem;color:#666"><?= !empty($gurus_for_recent) ? htmlspecialchars(implode(', ', array_map(fn($gg)=>$gg['nama'],$gurus_for_recent))) : 'Belum ada guru' ?></div>
      </div>
     </div>
    <?php endforeach; endif; ?>
   </div>
  </div>
 </aside>
</div>

<div class="modal fade" id="detailModalMapel" tabindex="-1" aria-labelledby="detailModalMapelLabel" aria-hidden="true">
 <div class="modal-dialog">
  <div class="modal-content">
   <div class="modal-header">
    <h5 class="modal-title" style="padding-left: 40px;" id="detailModalMapelLabel">Detail Mapel: <span id="modal-mapel-nama"></span></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
   </div>

   <div class="modal-body">
    <div id="modal-mapel-view">
     <p><strong>Nama Mapel:</strong> <span id="detail-nama"></span></p>
     <p><strong>Kategori:</strong> <span id="detail-kategori"></span></p>
     <p><strong>Guru Pengampu:</strong></p>
     <div style="padding-left: 40px;" id="detail-guru-list" class="wrap-text"></div>
    </div>
   </div>

   <div class="modal-footer" id="modal-footer-actions">
    <div id="view-buttons" style="display:flex; padding-left: 50px; gap:8px; align-items:center;">
     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
    </div>

    <div style="flex:1"></div>

    <div class="modal-export-wrap d-flex gap-2">
     <a href="#" id="btn-export-mapel-pdf" class="btn btn-sm btn-outline-danger btn-export-single" target="_blank">Export PDF</a>
    </div>
   </div>
  </div>
 </div>
</div>

<script>
function showMapelDetail(id){
 const col = document.querySelector(`.mapel-card-col[data-id="${id}"]`);
 if(!col) return;

 let m = null;
 try {
  m = JSON.parse(col.dataset.mapel);
 } catch(e){
  m = {
   id: col.dataset.id || id,
   nama: col.dataset.nama || '',
   kategori: col.dataset.kategori || '',
   gurus: []
  };
 }

 document.getElementById('modal-mapel-nama').textContent = m.nama || '-';
 document.getElementById('detail-nama').textContent = m.nama || '-';
 document.getElementById('detail-kategori').textContent = m.kategori || '-';

 const guruList = m.gurus || [];
 const guruContainer = document.getElementById('detail-guru-list');
 guruContainer.innerHTML = '';
 if(guruList.length){
  guruList.forEach(name => {
   const d = document.createElement('div');
   d.textContent = name;
   guruContainer.appendChild(d);
  });
 } else {
  guruContainer.innerHTML = '<div class="text-muted">Belum ada guru pengampu</div>';
 }

 const btnPdf = document.getElementById('btn-export-mapel-pdf');
 if(btnPdf) btnPdf.href = '../inc/export.php?table=mapel&type=pdf&id_mapel=' + encodeURIComponent(m.id);

 const modalRoot = document.getElementById('detailModalMapel');
 if(modalRoot) {
  modalRoot.classList.add('vertical');
  if(typeof bootstrap !== 'undefined'){
   const bsModal = bootstrap.Modal.getOrCreateInstance(modalRoot);
   bsModal.show();
  }
 }
}

document.getElementById('searchInput')?.addEventListener('input', function(){
 const q = (this.value || '').toLowerCase();
 document.querySelectorAll('.mapel-card-col').forEach(col=>{
  const nama = (col.dataset.nama || '').toLowerCase();
  const kategori = (col.dataset.kategori || '').toLowerCase();
  const mapelData = col.dataset.mapel ? JSON.parse(col.dataset.mapel) : null;
  const gurus = mapelData && (mapelData.gurus || []).join(' ').toLowerCase();
  const visible = !q || nama.includes(q) || kategori.includes(q) || (gurus && gurus.includes(q));
  col.style.display = visible ? '' : 'none';
 });
});

document.getElementById('detailModalMapel')?.addEventListener('show.bs.modal', function(){
 this.classList.add('vertical');
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>