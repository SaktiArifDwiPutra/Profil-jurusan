<?php 
require_once __DIR__ . '/../inc/session.php'; 
require_once __DIR__ . '/../inc/functions.php'; 
require_role(['siswa']); 
include __DIR__ . '/../inc/header.php'; 

$kelas = fetch_all("SELECT * FROM kelas ORDER BY tingkat, kelas"); 
$siswas = fetch_all("   
  SELECT s.*, k.tingkat, k.kelas   
  FROM siswa s   
  LEFT JOIN kelas k ON s.id_kelas=k.id_kelas   
  ORDER BY s.nama ASC 
"); 
?> 

<?php
$total_siswa = count($siswas);
$laki = 0; $perempuan = 0;
foreach($siswas as $ss){
 if(isset($ss['jenis_kelamin']) && $ss['jenis_kelamin'] === 'Laki-laki') $laki++;
 else $perempuan++;
}
$jumlah_kelas = count($kelas);

$recent = array_slice($siswas, max(0, $total_siswa - 5));
?>

<div class="layout-golden">

 <div class="left-col">

  <div class="d-flex justify-content-between mb-3">
   <h4 class="mb-0">Data Siswa</h4>
  </div>

  <div class="d-flex justify-content-between mb-2 align-items-center">
   <div class="input-group w-50">
    <span class="input-group-text"><i class="fa fa-search"></i></span>
    <input type="text" id="searchInput" class="form-control" placeholder="Cari siswa...">
   </div>

   <div class="d-flex gap-2">
    <a href="../inc/export.php?table=siswa&type=csv" class="btn btn-outline-primary btn-sm" target="_blank">Export CSV</a>
    <a href="../inc/export.php?table=siswa&type=pdf" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
   </div>
  </div>

  <div id="siswaContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-5">
   <?php foreach($siswas as $i=>$s): $id=$s['id_siswa'];
    $data_siswa_json = json_encode([
     'id' => $id,
     'nisn' => htmlspecialchars($s['nisn']),
     'nama' => htmlspecialchars($s['nama']),
     'jk' => htmlspecialchars($s['jenis_kelamin']),
     'tgl' => $s['tanggal_lahir'] ?: '',
     'alamat' => htmlspecialchars($s['alamat']),
     'nohp' => htmlspecialchars($s['no_hp']),
     'id_kelas' => $s['id_kelas'],
     'kelas_label' => $s['tingkat'] ? htmlspecialchars($s['tingkat'].' - '.$s['kelas']) : 'Belum Ada Kelas'
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
   ?>
    <div class="col siswa-card-col"
     data-siswa='<?=$data_siswa_json?>'
     data-id="<?=$id?>"
     data-nama="<?=htmlspecialchars($s['nama'])?>"
     data-kelas="<?=htmlspecialchars($s['tingkat'].' - '.$s['kelas'])?>">
     <div class="card siswa-card shadow-sm h-100" data-bs-toggle="modal" data-bs-target="#detailModal" onclick="showDetail(<?=$id?>)">
      <div class="card-body">
       <div class="d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><?=htmlspecialchars($s['nama'])?></h5>
        <span class="badge rounded-pill text-bg-<?=($s['jenis_kelamin']=='Laki-laki' ? 'primary' : 'danger')?>"><?=($s['jenis_kelamin']=='Laki-laki' ? 'Laki-laki' : 'Perempuan')?></span>
       </div>
       <p class="card-text text-muted mb-0"><small><?=$s['tingkat'] ? htmlspecialchars($s['tingkat'].' - '.$s['kelas']) : 'Belum Ada Kelas'?></small></p>
      </div>
     </div>
    </div>
   <?php endforeach; ?>
  </div>

 </div> 

 <aside class="sidebar">
  <div class="stat-card">
   <h6>Total Siswa</h6>
   <div class="stat-value"><?= $total_siswa ?></div>
  </div>

  <div class="stat-card">
   <h6>Jenis Kelamin</h6>
   <div>Laki: <?= $laki ?> • Perempuan: <?= $perempuan ?></div>
   </div>

  <div class="stat-card">
   <h6>Jumlah Kelas</h6>
   <div><?= $jumlah_kelas ?></div>
  </div>

  <div class="stat-card">
   <h6>Terakhir Ditambahkan</h6>
   <div class="recent-list">
    <?php if(empty($recent)): ?>
     <div class="recent-item">Belum ada data</div>
    <?php else: foreach(array_reverse($recent) as $r): ?>
     <div class="recent-item" style="cursor:pointer" onclick="showDetail(<?= (int)$r['id_siswa'] ?>)">
      <div style="min-width:0;">
       <strong><?= htmlspecialchars($r['nama']) ?></strong>
       <div style="font-size:0.85rem;color:#666"><?= $r['tingkat'] ? htmlspecialchars($r['tingkat'].' - '.$r['kelas']) : 'Belum Ada Kelas' ?></div>
      </div>
      <div style="font-size:0.9rem;color:#333"><?= htmlspecialchars($r['no_hp'] ?? '') ?></div>
     </div>
    <?php endforeach; endif; ?>
   </div>
  </div>
 </aside>

</div> 

<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true"> 
 <div class="modal-dialog modal-lg">  
  <div class="modal-content">   
   <div class="modal-header">    
    <h5 class="modal-title" style="padding-left: 40px;" id="detailModalLabel">Detail Siswa: <span id="modal-nama-view"></span></h5>    
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>   
   </div>   
   <div class="modal-body">    
    <div id="modal-detail-view">     
     <p><strong>NISN:</strong> <span id="detail-nisn"></span></p>     
     <p><strong>Jenis Kelamin:</strong> <span id="detail-jk"></span></p>     
     <p><strong>Tanggal Lahir:</strong> <span id="detail-tgl"></span></p>     
     <p><strong>Kelas:</strong> <span id="detail-kelas"></span></p>     
     <p><strong>No HP:</strong> <span id="detail-nohp"></span></p>     
     <p><strong>Alamat:</strong> <span id="detail-alamat" class="wrap-text"></span></p>    
    </div> 
   </div>   
   <div class="modal-footer" id="modal-footer-actions">
    <div id="view-buttons" style="display:flex; padding-left: 50px; gap:8px; align-items:center;">
     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
    </div>

    <div style="flex:1"></div>

    <div id="edit-buttons" style="display: none; gap:8px;"></div>

    <div class="modal-export-wrap">
     <a href="#" id="btn-export-siswa-pdf" class="btn btn-sm btn-outline-danger btn-export-single" target="_blank">Export PDF</a>
    </div>
   </div>
 
  </div> 
 </div> 
</div> 

<script> 
function showDetail(id) {
 const cardCol = document.querySelector(`.siswa-card-col[data-id="${id}"]`);
 if (!cardCol || !cardCol.dataset.siswa) return;

 const siswa = JSON.parse(cardCol.dataset.siswa);

 document.getElementById('modal-detail-view').style.display = 'block';
 document.getElementById('view-buttons').style.display = 'block';
 document.getElementById('btn-export-siswa-pdf').style.display = 'block';

 const namaSiswa = siswa.nama || 'Data Tidak Ditemukan';
 document.getElementById('detailModalLabel').innerHTML = `Detail Siswa: <span id="modal-nama-view">${namaSiswa}</span>`;
 document.getElementById('detail-nisn').textContent = siswa.nisn || '-';
 document.getElementById('detail-jk').textContent = siswa.jk || '-';
 document.getElementById('detail-tgl').textContent = siswa.tgl || '-';
 document.getElementById('detail-kelas').textContent = siswa.kelas_label || 'Belum Ada Kelas';
 document.getElementById('detail-nohp').textContent = siswa.nohp || '-';
 document.getElementById('detail-alamat').textContent = siswa.alamat || '-';

 document.getElementById('btn-export-siswa-pdf').href = '../inc/export.php?table=siswa&type=pdf&id_siswa=' + encodeURIComponent(siswa.id);

 const modalEl = document.getElementById('detailModal');
 if (modalEl) modalEl.classList.add('vertical');
}

const searchInput = document.getElementById('searchInput'); 
searchInput.addEventListener('keyup', function() { 
 const keyword = this.value.toLowerCase(); 

 document.querySelectorAll('#siswaContainer .siswa-card-col').forEach(col => {  
  const nama = col.dataset.nama.toLowerCase();  
  const kelas = col.dataset.kelas.toLowerCase();  
  const siswaData = JSON.parse(col.dataset.siswa);  
  const nisn = siswaData.nisn ? siswaData.nisn.toLowerCase() : '';  

  if(nisn.includes(keyword) || nama.includes(keyword) || kelas.includes(keyword)){   
   col.style.display = 'block';  
  } else {   
   col.style.display = 'none';  
  } 
 }); 
}); 

document.getElementById('detailModal')?.addEventListener('show.bs.modal', function () {
 this.classList.add('vertical');
});
</script> 

<?php include __DIR__ . '/../inc/footer.php'; ?>