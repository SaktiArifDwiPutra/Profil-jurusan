<?php 
require_once __DIR__ . '/../inc/session.php'; 
require_once __DIR__ . '/../inc/functions.php'; 
require_role(['admin']); 
include __DIR__ . '/../inc/header.php';  

// --- BAGIAN LOGIKA CRUD DAN TOAST ---
if($msg = flash_get('success')): ?> 
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">  
  <div id="liveToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">    
    <div class="d-flex">      
      <div class="toast-body">        
        <?=htmlspecialchars($msg)?>      
      </div>      
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>    
    </div>  
  </div> 
</div> 

<script> 
document.addEventListener('DOMContentLoaded', function(){  
  const toastEl = document.getElementById('liveToast');  
  if(toastEl){    
    const toast = new bootstrap.Toast(toastEl, {delay: 3000});    
    toast.show();  
  } 
}); 
</script> 
<?php endif;  

if ($_SERVER['REQUEST_METHOD']=='POST' && ($_POST['action'] ?? '')=='add') {     
    $nisn   = trim($_POST['nisn']);     
    $nama   = trim($_POST['nama']);     
    $jk     = $_POST['jenis_kelamin'];     
    $tgl    = $_POST['tanggal_lahir'] ?: null;     
    $alamat = trim($_POST['alamat']);     
    $nohp   = trim($_POST['no_hp']);     
    $id_kls = $_POST['id_kelas'] ?: null;  

    $stmt = $conn->prepare("INSERT INTO siswa (id_kelas, nisn, nama, jenis_kelamin, tanggal_lahir, alamat, no_hp) VALUES (?,?,?,?,?,?,?)");     
    $stmt->bind_param('issssss', $id_kls, $nisn, $nama, $jk, $tgl, $alamat, $nohp);     
    $stmt->execute();  

    flash_set('success','Siswa berhasil ditambahkan');     
    header('Location: /ProfilJurusan/admin/siswa.php'); exit; 
}  

if ($_SERVER['REQUEST_METHOD']=='POST' && ($_POST['action'] ?? '')=='edit') {     
    $id     = intval($_POST['id_siswa']);     
    $nisn   = trim($_POST['nisn']);     
    $nama   = trim($_POST['nama']);     
    $jk     = $_POST['jenis_kelamin'];     
    $tgl    = $_POST['tanggal_lahir'] ?: null;     
    $alamat = trim($_POST['alamat']);     
    $nohp   = trim($_POST['no_hp']);     
    $id_kls = $_POST['id_kelas'] ?: null;  

    $stmt = $conn->prepare("UPDATE siswa SET id_kelas=?, nisn=?, nama=?, jenis_kelamin=?, tanggal_lahir=?, alamat=?, no_hp=? WHERE id_siswa=?");     
    $stmt->bind_param('issssssi', $id_kls, $nisn, $nama, $jk, $tgl, $alamat, $nohp, $id);     
    $stmt->execute();  

    flash_set('success','Siswa berhasil diupdate');     
    header('Location: /ProfilJurusan/admin/siswa.php'); exit; 
}  

if (isset($_GET['hapus'])) {     
    $id = intval($_GET['hapus']);     
    $stmt = $conn->prepare("DELETE FROM siswa WHERE id_siswa=?");     
    $stmt->bind_param('i',$id);     
    $stmt->execute();     
    flash_set('success','Siswa berhasil dihapus');     
    header('Location: /ProfilJurusan/admin/siswa.php'); exit; 
}  

$kelas  = fetch_all("SELECT * FROM kelas ORDER BY tingkat, kelas"); 
$siswas = fetch_all("     
    SELECT s.*, k.tingkat, k.kelas      
    FROM siswa s      
    LEFT JOIN kelas k ON s.id_kelas=k.id_kelas      
    ORDER BY s.nama ASC 
"); 
?>  

<?php
// --- MULAI: ganti block atas (top controls, search, siswaContainer) dengan layout golden ---
$total_siswa = count($siswas);
$laki = 0; $perempuan = 0;
foreach($siswas as $ss){
  if(isset($ss['jenis_kelamin']) && $ss['jenis_kelamin'] === 'Laki-laki') $laki++;
  else $perempuan++;
}
$jumlah_kelas = count($kelas);

// ambil 5 siswa terakhir dari array (jika ingin berdasarkan waktu, ganti dengan query DB)
$recent = array_slice($siswas, max(0, $total_siswa - 5));
?>

<div class="layout-golden">

  <!-- KIRI: konten utama (pakai class left-col supaya CSS ke-apply) -->
  <div class="left-col">

    <div class="d-flex justify-content-between mb-3">
      <h4>Data Siswa</h4>
      <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#formAdd">Tambah Siswa</button>
    </div>

    <div id="formAdd" class="collapse mb-3 card p-3">
      <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="row g-2">
          <div class="col-md-3"><input name="nisn" class="form-control" placeholder="NISN" required></div>
          <div class="col-md-3"><input name="nama" class="form-control" placeholder="Nama" required></div>
          <div class="col-md-2">
            <select name="jenis_kelamin" class="form-select">
              <option value="Laki-laki">Laki-laki</option>
              <option value="Perempuan">Perempuan</option>
            </select>
          </div>
          <div class="col-md-2"><input type="date" name="tanggal_lahir"required class="form-control"></div>
          <div class="col-md-2">
            <select name="id_kelas"required class="form-select">
              <option value="">--Kelas--</option>
              <?php foreach($kelas as $k) echo "<option value='{$k['id_kelas']}'>{$k['tingkat']} - {$k['kelas']}</option>"; ?>
            </select>
          </div>
        </div>
        <div class="mt-2"><input name="no_hp" class="form-control" placeholder="No HP" required></div>
        <div class="mt-2"><textarea name="alamat" class="form-control" placeholder="Alamat"></textarea></div>
        <div class="mt-2"><button class="btn btn-primary">Simpan</button></div>
      </form>
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

  </div> <!-- /.left-col -->

  <!-- KANAN: sidebar yang memanfaatkan CSS golden -->
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

<!-- Modal Detail & Edit -->
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

        <div id="modal-edit-form" style="display: none;">          
          <form method="post" id="editForm">            
            <input type="hidden" name="action" value="edit">            
            <input type="hidden" name="id_siswa" id="edit-id-siswa">            
            <div class="row g-2">              
              <div class="col-md-4 mb-2"><input type="text" name="nisn" id="edit-nisn" class="form-control" placeholder="NISN" required></div>              
              <div class="col-md-4 mb-2"><input type="text" name="nama" id="edit-nama" class="form-control" placeholder="Nama" required></div>              
              <div class="col-md-4 mb-2">                
                <select name="jenis_kelamin" id="edit-jk" class="form-select">                  
                  <option value="Laki-laki">Laki-laki</option>                  
                  <option value="Perempuan">Perempuan</option>                
                </select>              
              </div>              
              <div class="col-md-4 mb-2"><input type="date" name="tanggal_lahir" id="edit-tgl" class="form-control"></div>              
              <div class="col-md-4 mb-2">                
                <select name="id_kelas" id="edit-id-kelas" class="form-select">                  
                  <option value="">--Kelas--</option>                  
                  <?php foreach($kelas as $k) echo "<option value='{$k['id_kelas']}'>{$k['tingkat']} - {$k['kelas']}</option>"; ?>                
                </select>              
              </div>              
              <div class="col-md-4 mb-2"><input type="text" name="no_hp" id="edit-nohp" class="form-control" placeholder="No HP"></div>            
            </div>            
            <div class="mt-2"><textarea name="alamat" id="edit-alamat" class="form-control" placeholder="Alamat"></textarea></div>          
          </form>        
        </div>      
      </div>      
      <div class="modal-footer" id="modal-footer-actions">
        <!-- kiri: tombol-tombol view (Tutup/Edit/Hapus) -->
        <div id="view-buttons" style="display:flex; padding-left: 50px; gap:8px; align-items:center;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="button" class="btn btn-primary" onclick="editRowInModal()">Edit</button>
          <a href="#" id="btn-hapus-siswa" class="btn btn-danger" onclick="return confirm('Yakin hapus?')">Hapus</a>
        </div>

        <!-- kanan (kosong) agar layout fleksibel -->
        <div style="flex:1"></div>

        <!-- edit buttons (tetap di kanan) -->
        <div id="edit-buttons" style="display: none; gap:8px;">
          <button type="submit" form="editForm" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" onclick="cancelEditInModal()">Batal</button>
        </div>

        <!-- NEW: export area full-width di bawah footer (diposisikan absolut relatif ke modal) -->
        <div class="modal-export-wrap">
          <a href="#" id="btn-export-siswa-pdf" style="display: block;" class="btn btn-sm btn-outline-danger btn-export-single" target="_blank">Export PDF</a>
        </div>
      </div>
  
    </div>  
  </div> 
</div> 

<script> 
 
// Fungsi untuk menampilkan detail siswa di modal (MODIFIED)
function showDetail(id) {
  const cardCol = document.querySelector(`.siswa-card-col[data-id="${id}"]`);
  if (!cardCol || !cardCol.dataset.siswa) return;

  const siswa = JSON.parse(cardCol.dataset.siswa);

  // tampilkan view, hide edit
  document.getElementById('modal-detail-view').style.display = 'block';
  document.getElementById('modal-edit-form').style.display = 'none';
  document.getElementById('view-buttons').style.display = 'block';
  document.getElementById('edit-buttons').style.display = 'none';
  document.getElementById('btn-export-siswa-pdf').style.display = 'block';

  const namaSiswa = siswa.nama || 'Data Tidak Ditemukan';
  document.getElementById('detailModalLabel').innerHTML = `Detail Siswa: <span id="modal-nama-view">${namaSiswa}</span>`;
  document.getElementById('detail-nisn').textContent = siswa.nisn || '-';
  document.getElementById('detail-jk').textContent = siswa.jk || '-';
  document.getElementById('detail-tgl').textContent = siswa.tgl || '-';
  document.getElementById('detail-kelas').textContent = siswa.kelas_label || 'Belum Ada Kelas';
  document.getElementById('detail-nohp').textContent = siswa.nohp || '-';
  document.getElementById('detail-alamat').textContent = siswa.alamat || '-';

  document.getElementById('btn-hapus-siswa').href = `?hapus=${id}`;

  document.getElementById('edit-id-siswa').value = siswa.id;
  document.getElementById('edit-nisn').value = siswa.nisn;
  document.getElementById('edit-nama').value = siswa.nama;
  document.getElementById('edit-tgl').value = siswa.tgl;
  document.getElementById('edit-nohp').value = siswa.nohp;
  document.getElementById('edit-alamat').value = siswa.alamat;

  document.getElementById('btn-export-siswa-pdf').href = '../inc/export.php?table=siswa&type=pdf&id_siswa=' + encodeURIComponent(siswa.id);

  const jkSelect = document.getElementById('edit-jk');
  Array.from(jkSelect.options).forEach(option => {
    option.selected = (option.value === siswa.jk);
  });

  const kelasSelect = document.getElementById('edit-id-kelas');
  Array.from(kelasSelect.options).forEach(option => {
    option.selected = (option.value == siswa.id_kelas);
  });

  // === tambahin kelas .vertical supaya modal jadi sempit/vertikal ===
  const modalEl = document.getElementById('detailModal');
  if (modalEl) modalEl.classList.add('vertical');
}

// Fungsi untuk switch ke mode edit di modal (MODIFIED)
function editRowInModal(){
  // hilangkan vertical supaya edit form punya ruang lebih
  const modalEl = document.getElementById('detailModal');
  if (modalEl) modalEl.classList.remove('vertical');

  document.getElementById('modal-detail-view').style.display = 'none';
  document.getElementById('modal-edit-form').style.display = 'block';
  document.getElementById('view-buttons').style.display = 'none';
  document.getElementById('edit-buttons').style.display = 'block';
  document.getElementById('btn-export-siswa-pdf').style.display = 'none';

  document.getElementById('detailModalLabel').textContent = 'Edit Siswa';
}

// Fungsi untuk batal edit dan kembali ke view (MODIFIED)
function cancelEditInModal(){
  document.getElementById('modal-detail-view').style.display = 'block';
  document.getElementById('modal-edit-form').style.display = 'none';
  document.getElementById('view-buttons').style.display = 'block';
  document.getElementById('edit-buttons').style.display = 'none';
  document.getElementById('btn-export-siswa-pdf').style.display = 'block';


  const namaSiswa = document.getElementById('edit-nama').value;
  document.getElementById('detailModalLabel').innerHTML = 'Detail Siswa: <span id="modal-nama-view">' + namaSiswa + '</span>';

  // kembalikan class vertical karena kita balik ke view
  const modalEl = document.getElementById('detailModal');
  if (modalEl) modalEl.classList.add('vertical');
}

// Optional: saat modal ditutup, kembalikan ke mode vertical (safety)
document.getElementById('detailModal')?.addEventListener('hidden.bs.modal', function () {
  const modalEl = document.getElementById('detailModal');
  if (modalEl) {
    // bersihkan state edit dan set vertical agar konsisten saat dibuka lagi
    modalEl.classList.add('vertical');
    document.getElementById('modal-detail-view').style.display = 'block';
    document.getElementById('modal-edit-form').style.display = 'none';
    document.getElementById('view-buttons').style.display = 'block';
    document.getElementById('edit-buttons').style.display = 'none';
  }
});
 


// Pencarian Card
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
addEventListener('DOMContentLoaded', function(){
  // jika layout udah dibungkus, skip
  if(document.querySelector('.layout-golden')) return;

  // buat wrapper grid
  const container = document.createElement('div');
  container.className = 'layout-golden';

  // left col: kita pindahkan elemen search + action + siswaContainer ke sini
  const left = document.createElement('div');
  left.className = 'left-col';

  // ambil elemen-elemen yang mau dipindah
  const searchRow = document.querySelector('.d-flex.justify-content-between.mb-2') || document.getElementById('searchRow');
  const siswaContainer = document.getElementById('siswaContainer');
  const topControls = document.querySelector('.d-flex.justify-content-between.mb-3');

  // insert left content in order: topControls, searchRow, siswaContainer
  if(topControls) left.appendChild(topControls);
  if(searchRow) left.appendChild(searchRow);
  if(siswaContainer) left.appendChild(siswaContainer);

  // right col: buat sidebar
  const right = document.createElement('aside');
  right.className = 'sidebar';

  // ---- buat stat cards ----
  const statTotal = document.createElement('div'); statTotal.className='stat-card';
  const statGender = document.createElement('div'); statGender.className='stat-card';
  const statKelas = document.createElement('div'); statKelas.className='stat-card';

  statTotal.innerHTML = '<h6>Total Siswa</h6><div class="stat-value" id="stat-total">0</div>';
  statGender.innerHTML = '<h6>Jenis Kelamin</h6><div id="stat-gender">Laki: 0 • Perempuan: 0</div>';
  statKelas.innerHTML = '<h6>Jumlah Kelas</h6><div id="stat-kelas">0</div>';

  // recent list
  const recentWrap = document.createElement('div'); recentWrap.className='stat-card';
  recentWrap.innerHTML = '<h6>Terakhir Ditambahkan</h6><div class="recent-list" id="recent-list"></div>';

  right.appendChild(statTotal);
  right.appendChild(statGender);
  right.appendChild(statKelas);
  right.appendChild(recentWrap);
  right.appendChild(qa);

  // masukkan grid ke DOM (letakkan sebelum footer / setelah main content)
  const mainParent = siswaContainer ? siswaContainer.parentNode : document.body;
  mainParent.insertBefore(container, siswaContainer); // tempat sementara

  container.appendChild(left);
  container.appendChild(right);

  // === hitung statistik dari DOM cards ===
  function buildStats(){
    const cols = Array.from(document.querySelectorAll('.siswa-card-col'));
    const total = cols.length;
    let laki = 0, perempuan = 0;
    const kelasSet = new Set();

    cols.forEach(col => {
      const s = col.dataset.siswa ? JSON.parse(col.dataset.siswa) : {};
      const jk = (s.jk || '').toLowerCase();
      if(jk.includes('laki')) laki++;
      else if(jk.includes('perempuan')) perempuan++;
      if(s.kelas_label) kelasSet.add(s.kelas_label);
    });

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-gender').textContent = `Laki: ${laki} • Perempuan: ${perempuan}`;
    document.getElementById('stat-kelas').textContent = kelasSet.size;

    // recent: ambil 5 terakhir dari array (asumsi order di DOM = urutan DB)
    const recentList = document.getElementById('recent-list');
    recentList.innerHTML = '';
    const recent = cols.slice(-5).reverse();
    recent.forEach(col => {
      const s = col.dataset.siswa ? JSON.parse(col.dataset.siswa) : {};
      const it = document.createElement('div');
      it.className = 'recent-item';
      it.innerHTML = `<div style="min-width:0;"><strong>${s.nama || '-'}</strong><div style="font-size:0.85rem;color:#666">${s.kelas_label || 'Belum Ada Kelas'}</div></div><div style="font-size:0.9rem;color:#333">${s.nohp||''}</div>`;
      recentList.appendChild(it);
    });
  }

  buildStats();

  // jika ada perubahan dinamika (mis. tambahin siswa tanpa reload), panggil ulang buildStats()
  // contoh: window.buildSiswaStats = buildStats; <-- bisa dipanggil dari lain
  window.buildSiswaStats = buildStats;
});
</script>  

<?php include __DIR__ . '/../inc/footer.php'; ?> 
