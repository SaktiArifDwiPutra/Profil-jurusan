<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
include __DIR__ . '/../inc/header.php';

$mapels = fetch_all("SELECT * FROM mapel ORDER BY nama_mapel");
$kelas = fetch_all("SELECT * FROM kelas ORDER BY tingkat, kelas");
$gurus = fetch_all("SELECT * FROM guru ORDER BY nama ASC");

$ampu_rows = fetch_all("
 SELECT md.id_ampu, md.id_guru, md.id_mapel, m.nama_mapel,
    k.id_kelas, k.tingkat, k.kelas
 FROM mapel_diampu md
 JOIN mapel m ON md.id_mapel = m.id_mapel
 JOIN kelas k ON md.id_kelas = k.id_kelas
 ORDER BY md.id_guru, m.nama_mapel, k.tingkat, k.kelas
");

$ampu_map = [];
foreach ($ampu_rows as $r) {
  $gid = (int)$r['id_guru'];
  if (!isset($ampu_map[$gid])) $ampu_map[$gid] = [];
  $ampu_map[$gid][] = $r;
}
?>

<?php
$total_guru = count($gurus);
$laki = 0; $perempuan = 0;
$mapel_set = [];
$kelas_set = [];
foreach($gurus as $g){
  if(($g['jenis_kelamin'] ?? '') === 'Laki-laki') $laki++;
  else $perempuan++;
  $gid = (int)$g['id_guru'];
  if(!empty($ampu_map[$gid])){
    foreach($ampu_map[$gid] as $a){
      $mapel_set[$a['id_mapel']] = $a['nama_mapel'];
      $kelas_set[$a['id_kelas']] = ($a['tingkat'].' '.$a['kelas']);
    }
  }
}
$recent_gurus = array_slice($gurus, max(0, $total_guru - 5));
?>

<div class="layout-golden">
 <div class="left-col">

  <div class="d-flex justify-content-between align-items-center mb-3">
   <h4 class="mb-0">Data Guru</h4>
  </div>

  <div class="d-flex justify-content-between mb-2 align-items-center">
   <div class="input-group w-50">
    <span class="input-group-text"><i class="fa fa-search"></i></span>
    <input type="text" id="searchInputGuru" class="form-control" placeholder="Cari guru (Nama, NIP, Mapel)...">
   </div>

   <div class="d-flex gap-2">
    <a href="../inc/export.php?table=guru&type=csv" class="btn btn-outline-primary btn-sm" target="_blank">Export CSV</a>
    <a href="../inc/export.php?table=guru&type=pdf" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
   </div>
  </div>

  <div id="guruContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
   <?php foreach($gurus as $g):
    $id = (int)$g['id_guru'];
    $ampu_for_g = $ampu_map[$id] ?? [];
    $data_json = json_encode([
     'id'=>$id,
     'nip'=>htmlspecialchars($g['nip']),
     'nama'=>htmlspecialchars($g['nama']),
     'jk'=>htmlspecialchars($g['jenis_kelamin']),
     'email'=>htmlspecialchars($g['email']),
     'nohp'=>htmlspecialchars($g['no_hp']),
     'ampu'=>$ampu_for_g
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
   ?>
   <div class="col guru-card-col" data-guru='<?=$data_json?>' data-id="<?=$id?>" data-nama="<?=htmlspecialchars($g['nama'])?>" data-mapel="<?=htmlspecialchars(implode(', ', array_map(fn($m)=>$m['nama_mapel'],$ampu_for_g)))?>">
    <div class="card siswa-card shadow-sm h-100" data-bs-toggle="modal" data-bs-target="#detailModalGuru" onclick="showGuruDetail(<?=$id?>)">
     <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
       <h5 class="card-title mb-0"><?=htmlspecialchars($g['nama'])?></h5>
       <span class="badge rounded-pill text-bg-<?=($g['jenis_kelamin']=='Laki-laki' ? 'primary' : 'danger')?>"><?=($g['jenis_kelamin']=='Laki-laki' ? 'Laki-laki' : 'Perempuan')?></span>
      </div>
      <p class="card-text text-muted mb-0"><small><?=htmlspecialchars($g['nip'] ?: '-')?></small></p>
      <div class="mt-2" style="font-size:.9rem;color:#555;">
       <?php if($ampu_for_g): foreach($ampu_for_g as $m): ?>
        <div><?=htmlspecialchars($m['nama_mapel'].' • '.$m['tingkat'].' '.$m['kelas'])?></div>
       <?php endforeach; else: ?>
        <div class="text-muted">Belum ada mapel</div>
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
   <h6>Total Guru</h6>
   <div class="stat-value"><?= $total_guru ?></div>
  </div>

  <div class="stat-card">
   <h6>Jenis Kelamin</h6>
   <div>Laki: <?= $laki ?> • Perempuan: <?= $perempuan ?></div>
  </div>

  <div class="stat-card">
   <h6>Jumlah Mapel Unik</h6>
   <div><?= count($mapel_set) ?></div>
  </div>

  <div class="stat-card">
   <h6>Jumlah Kelas Terlibat</h6>
   <div><?= count($kelas_set) ?></div>
  </div>

  <div class="stat-card">
   <h6>Terakhir Ditambahkan</h6>
   <div class="recent-list">
    <?php if(empty($recent_gurus)): ?>
     <div class="recent-item">Belum ada data</div>
    <?php else: foreach(array_reverse($recent_gurus) as $rg): ?>
     <div class="recent-item" style="cursor:pointer" onclick="document.querySelector('.guru-card-col[data-id=&quot;<?= (int)$rg['id_guru'] ?>&quot;]')?.scrollIntoView({behavior:'smooth'});">
      <div style="min-width:0;">
       <strong><?= htmlspecialchars($rg['nama']) ?></strong>
       <div style="font-size:0.85rem;color:#666"><?= htmlspecialchars($rg['nip'] ?? '') ?></div>
      </div>
      <div style="font-size:0.9rem;color:#333"><?= htmlspecialchars($rg['no_hp'] ?? '') ?></div>
     </div>
    <?php endforeach; endif; ?>
   </div>
  </div>
 </aside>
</div>

<div class="modal fade" id="detailModalGuru" tabindex="-1" aria-labelledby="detailModalGuruLabel" aria-hidden="true">
 <div class="modal-dialog modal-lg">
  <div class="modal-content">
   <div class="modal-header">
    <h5 class="modal-title" style="padding-left: 40px;" id="detailModalGuruLabel">Detail Guru: <span id="modal-guru-nama"></span></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
   <div class="modal-body">
    <div id="modal-guru-view">
     <p><strong>NIP:</strong> <span id="detail-nip"></span></p>
     <p><strong>Email:</strong> <span id="detail-email"></span></p>
     <p><strong>Jenis Kelamin:</strong> <span id="detail-jk"></span></p>
     <p><strong>No HP:</strong> <span id="detail-nohp"></span></p>
     <p><strong>Mapel Diampu:</strong></p>
     <div style="padding-left: 40px;" id="detail-ampu-list"></div>
    </div>

   </div>

   <div class="modal-footer" id="modal-footer-actions">
    <div id="view-buttons" style="display:flex; padding-left: 120px; gap:8px; align-items:center;">
     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
    </div>

    <div style="flex:1"></div>

    <div id="edit-buttons" style="display:none; gap:8px;"></div>

    <div class="modal-export-wrap">
     <a href="#" id="btn-export-guru-pdf" class="btn btn-sm btn-outline-danger btn-export-single" target="_blank">Export PDF</a>
    </div>
   </div>
  </div>
 </div>
</div>

<script>
const _MAPELS = <?=json_encode(array_map(fn($m)=>['id'=>(int)$m['id_mapel'],'name'=>$m['nama_mapel']], $mapels))?>;
const _KELAS = <?=json_encode(array_map(fn($k)=>['id'=>(int)$k['id_kelas'],'label'=>$k['tingkat'].' '.$k['kelas']], $kelas))?>;

function showGuruDetail(id){
 const col = document.querySelector(`.guru-card-col[data-id="${id}"]`);
 if(!col || !col.dataset.guru) return;
 const g = JSON.parse(col.dataset.guru);

 document.getElementById('modal-guru-view').style.display = 'block';
 document.getElementById('view-buttons').style.display = 'flex';
 document.getElementById('btn-export-guru-pdf').style.display = 'block';
 
 document.getElementById('modal-guru-nama').textContent = g.nama || '-';
 document.getElementById('detail-nip').textContent = g.nip || '-';
 document.getElementById('detail-email').textContent = g.email || '-';
 document.getElementById('detail-jk').textContent = g.jk || '-';
 document.getElementById('detail-nohp').textContent = g.nohp || '-';

 const ampuList = document.getElementById('detail-ampu-list');
 ampuList.innerHTML = '';
 if(g.ampu && g.ampu.length){
  g.ampu.forEach(a=>{
   const div = document.createElement('div');
   div.textContent = (a.nama_mapel ?? a.nama_mapel) + ' • ' + ( (a.tingkat??'') + ' ' + (a.kelas??'') );
   ampuList.appendChild(div);
  });
 } else {
  ampuList.innerHTML = '<div class="text-muted">Belum ada mapel</div>';
 }

 const expPdf = document.getElementById('btn-export-guru-pdf');
 if(expPdf) expPdf.href = '../inc/export.php?table=guru&type=pdf&id_guru=' + encodeURIComponent(g.id);

 const modalEl = document.getElementById('detailModalGuru');
 if(modalEl) modalEl.classList.add('vertical');
}

document.getElementById('searchInputGuru')?.addEventListener('input', function(){
 const q = (this.value || '').toLowerCase();
 document.querySelectorAll('.guru-card-col').forEach(col=>{
  const nama = (col.dataset.nama || '').toLowerCase();
  const mapel = (col.dataset.mapel || '').toLowerCase();
  const nip = (col.querySelector('.card-text small')?.textContent || '').toLowerCase();
  const visible = !q || nama.includes(q) || mapel.includes(q) || nip.includes(q);
  col.style.display = visible ? '' : 'none';
 });
});

document.getElementById('detailModalGuru')?.addEventListener('show.bs.modal', function(){
 this.classList.add('vertical');
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>