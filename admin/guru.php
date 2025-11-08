<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['admin']);
include __DIR__ . '/../inc/header.php';

// --- BACKEND LOGIC (TIDAK DIUBAH) ---
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    if($action === 'add'){
        $nip = $_POST['nip'] ?? '';
        $nama = $_POST['nama'] ?? '';
        $jk = $_POST['jenis_kelamin'] ?? '';
        $email = $_POST['email'] ?? '';
        $no_hp = $_POST['no_hp'] ?? '';

        $conn->query("INSERT INTO guru (nip,nama,jenis_kelamin,email,no_hp) VALUES 
                     ('".$conn->real_escape_string($nip)."',
                      '".$conn->real_escape_string($nama)."',
                      '".$conn->real_escape_string($jk)."',
                      '".$conn->real_escape_string($email)."',
                      '".$conn->real_escape_string($no_hp)."')");
        $id_guru = $conn->insert_id;

        $ampu_mapel = $_POST['ampu_mapel'] ?? [];
        $ampu_kelas = $_POST['ampu_kelas'] ?? [];
        foreach($ampu_mapel as $i => $mp){
            $kls = $ampu_kelas[$i] ?? '';
            if($mp && $kls){
                $conn->query("INSERT INTO mapel_diampu (id_guru,id_mapel,id_kelas) VALUES ($id_guru,$mp,$kls)");
            }
        }

        flash_set('success','Guru berhasil ditambahkan');
        header('Location: guru.php'); exit;

    } elseif($action === 'edit'){
        $id_guru = (int)($_POST['id_guru'] ?? 0);
        $nip = $_POST['nip'] ?? '';
        $nama = $_POST['nama'] ?? '';
        $jk = $_POST['jenis_kelamin'] ?? '';
        $email = $_POST['email'] ?? '';
        $no_hp = $_POST['no_hp'] ?? '';

        $conn->query("UPDATE guru SET 
            nip='".$conn->real_escape_string($nip)."',
            nama='".$conn->real_escape_string($nama)."',
            jenis_kelamin='".$conn->real_escape_string($jk)."',
            email='".$conn->real_escape_string($email)."',
            no_hp='".$conn->real_escape_string($no_hp)."'
            WHERE id_guru=$id_guru");

        $ampu_id    = $_POST['ampu_id'] ?? [];
        $ampu_mapel = $_POST['ampu_mapel'] ?? [];
        $ampu_kelas = $_POST['ampu_kelas'] ?? [];

        foreach($ampu_id as $i => $aid){
            $aid = (int)$aid;
            $mp = $ampu_mapel[$i] ?? '';
            $kl = $ampu_kelas[$i] ?? '';
            if($mp && $kl){
                if($aid){
                    $conn->query("UPDATE mapel_diampu SET id_mapel=$mp, id_kelas=$kl WHERE id_ampu=$aid");
                } else {
                    $conn->query("INSERT INTO mapel_diampu (id_guru,id_mapel,id_kelas) VALUES ($id_guru,$mp,$kl)");
                }
            } elseif($aid && (!$mp || !$kl)){
                $conn->query("DELETE FROM mapel_diampu WHERE id_ampu=$aid");
            }
        }

        flash_set('success','Data guru berhasil diperbarui');
        header('Location: guru.php'); exit;
    }
}

if(isset($_GET['hapus'])){
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM mapel_diampu WHERE id_guru=$id");
    $conn->query("DELETE FROM guru WHERE id_guru=$id");
    flash_set('success','Guru berhasil dihapus');
    header('Location: guru.php'); exit;
}

// --- FETCH DATA ---
$mapels = fetch_all("SELECT * FROM mapel ORDER BY nama_mapel");
$kelas  = fetch_all("SELECT * FROM kelas ORDER BY tingkat, kelas");
$gurus  = fetch_all("SELECT * FROM guru ORDER BY nama ASC");

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

<?php if($msg = flash_get('success')): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1080">
  <div id="toastSuccess" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body"><?=htmlspecialchars($msg)?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var toastEl = document.getElementById('toastSuccess');
    if(toastEl){
        var toast = new bootstrap.Toast(toastEl, {delay: 3000});
        toast.show();
    }
});
</script>
<?php endif;?>

<?php
// --- STATISTICS FOR SIDEBAR ---
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

<!-- GOLDEN LAYOUT -->
<div class="layout-golden">
  <!-- LEFT: title, add form, search+export, cards -->
  <div class="left-col">

    <!-- Judul + tombol Tambah (SEJAJAR) -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Data Guru</h4>
      <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#formAddGuru">Tambah Guru</button>
    </div>

    <!-- Form Tambah (collapse) -->
    <div id="formAddGuru" class="collapse mb-3 card p-3">
      <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="row g-2 mb-2">
          <div class="col-md-2"><input name="nip" class="form-control" placeholder="NIP"></div>
          <div class="col-md-3"><input name="nama" class="form-control" placeholder="Nama" required></div>
          <div class="col-md-2">
            <select name="jenis_kelamin" class="form-select">
              <option value="Laki-laki">Laki-laki</option>
              <option value="Perempuan">Perempuan</option>
            </select>
          </div>
          <div class="col-md-3"><input name="email" class="form-control" placeholder="Email"required></div>
          <div class="col-md-2"><input name="no_hp" class="form-control" placeholder="No HP"required></div>
        </div>

        <label class="form-label">Mapel Diampu (opsional)</label>
        <div id="ampu-list-new" class="mb-2"></div>
        <div class="mb-3">
          <button type="button" class="btn btn-sm btn-success" onclick="addAmpuRow('new')">Tambah Mapel</button>
        </div>

        <button class="btn btn-primary">Simpan</button>
      </form>
    </div>

    <!-- Search + Export (SEJAJAR: search kiri, export kanan) -->
    <div class="d-flex justify-content-between mb-2 align-items-center">
      <div class="input-group w-50">
        <span class="input-group-text"><i class="fa fa-search"></i></span>
        <!-- tetap pakai id searchInputGuru agar JS pencarian bekerja -->
        <input type="text" id="searchInputGuru" class="form-control" placeholder="Cari guru (Nama, NIP, Mapel)...">
      </div>

      <!-- export buttons sejajar kanan (sama seperti halaman siswa) -->
      <div class="d-flex gap-2">
        <a href="../inc/export.php?table=guru&type=csv" class="btn btn-outline-primary btn-sm" target="_blank">Export CSV</a>
        <a href="../inc/export.php?table=guru&type=pdf" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
      </div>
    </div>

    <!-- cards -->
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

  </div> <!-- /.left-col -->

  <!-- RIGHT: sidebar -->
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
<!-- END GOLDEN LAYOUT -->

<!-- MODAL DETAIL & EDIT for GURU (mirip siswa) -->
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

        <div id="modal-guru-edit" style="display:none;">
          <form method="post" id="guruEditForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_guru" id="edit-id-guru">
            <div class="row g-2">
              <div class="col-md-3 mb-2"><input type="text" name="nip" id="edit-nip" class="form-control" placeholder="NIP"></div>
              <div class="col-md-4 mb-2"><input type="text" name="nama" id="edit-nama" class="form-control" placeholder="Nama" required></div>
              <div class="col-md-2 mb-2">
                <select name="jenis_kelamin" id="edit-jk" class="form-select">
                  <option value="Laki-laki">Laki-laki</option>
                  <option value="Perempuan">Perempuan</option>
                </select>
              </div>
              <div class="col-md-3 mb-2"><input type="email" name="email" id="edit-email" class="form-control" placeholder="Email"></div>
              <div class="col-md-4 mb-2"><input type="text" name="no_hp" id="edit-nohp" class="form-control" placeholder="No HP"></div>
            </div>

            <label class="form-label mt-2">Mapel Diampu</label>
            <div id="edit-ampu-list" class="mt-2"></div>
            <div class="mt-2 mb-1">
              <button type="button" class="btn btn-sm btn-success" onclick="addAmpuRow('edit')">Tambah Mapel</button>
            </div>
          </form>
        </div>

      </div>

      <div class="modal-footer" id="modal-footer-actions">
        <div id="view-buttons" style="display:flex; padding-left: 50px; gap:8px; align-items:center;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="button" class="btn btn-primary" id="btn-edit-guru" onclick="editGuruInModal()">Edit</button>
          <a href="#" id="btn-hapus-guru" class="btn btn-danger" onclick="return confirm('Hapus guru ini?')">Hapus</a>
        </div>

        <div style="flex:1"></div>

        <div id="edit-buttons" style="display:none; gap:8px;">
          <button type="submit" form="guruEditForm" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" onclick="cancelEditGuru()">Batal</button>
        </div>

        <div class="modal-export-wrap">
          <a href="#" id="btn-export-guru-pdf" class="btn btn-sm btn-outline-danger btn-export-single" target="_blank">Export PDF</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JS: mirror fungsi siswa + mapel helpers -->
<script>
const _MAPELS = <?=json_encode(array_map(fn($m)=>['id'=>(int)$m['id_mapel'],'name'=>$m['nama_mapel']], $mapels))?>;
const _KELAS  = <?=json_encode(array_map(fn($k)=>['id'=>(int)$k['id_kelas'],'label'=>$k['tingkat'].' '.$k['kelas']], $kelas))?>;

// add ampu row for 'new' (add form) or 'edit' modal (id 'edit')
function addAmpuRow(context){
  const container = document.getElementById(context === 'new' ? 'ampu-list-new' : (context === 'edit' ? 'edit-ampu-list' : 'ampu-list-'+context));
  if(!container) return;
  const row = document.createElement('div');
  row.className = 'row g-2 ampu-row mb-2';
  let mapelHtml = '<option value="">--Pilih Mapel--</option>';
  for(const m of _MAPELS) mapelHtml += `<option value="${m.id}">${m.name}</option>`;
  let kelasHtml = '<option value="">--Pilih Kelas--</option>';
  for(const k of _KELAS) kelasHtml += `<option value="${k.id}">${k.label}</option>`;
  row.innerHTML = `
    <input type="hidden" name="ampu_id[]" value="">
    <div class="col-md-6"><select name="ampu_mapel[]" class="form-select">${mapelHtml}</select></div>
    <div class="col-md-4"><select name="ampu_kelas[]" class="form-select">${kelasHtml}</select></div>
    <div class="col-md-2"><button type="button" class="btn btn-sm btn-danger w-100" onclick="removeAmpuRow(this)">Hapus</button></div>
  `;
  container.appendChild(row);
}

function removeAmpuRow(btn){
  const row = btn.closest('.ampu-row');
  if(row) row.remove();
}

// show detail in modal (view)
function showGuruDetail(id){
  const col = document.querySelector(`.guru-card-col[data-id="${id}"]`);
  if(!col || !col.dataset.guru) return;
  const g = JSON.parse(col.dataset.guru);

  // show view, hide edit
  document.getElementById('modal-guru-view').style.display = 'block';
  document.getElementById('modal-guru-edit').style.display = 'none';
  document.getElementById('view-buttons').style.display = 'flex';
  document.getElementById('edit-buttons').style.display = 'none';
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

  // set delete href
  document.getElementById('btn-hapus-guru').href = '?hapus=' + encodeURIComponent(id);

  // set export links for this guru
  const expPdf = document.getElementById('btn-export-guru-pdf');
  const expCsv = document.getElementById('btn-export-guru-csv');
  if(expPdf) expPdf.href = '../inc/export.php?table=guru&type=pdf&id_guru=' + encodeURIComponent(g.id);
  if(expCsv) expCsv.href = '../inc/export.php?table=guru&type=csv&id_guru=' + encodeURIComponent(g.id);

  // prepare edit form values
  document.getElementById('edit-id-guru').value = g.id;
  document.getElementById('edit-nip').value = g.nip || '';
  document.getElementById('edit-nama').value = g.nama || '';
  document.getElementById('edit-email').value = g.email || '';
  document.getElementById('edit-nohp').value = g.nohp || '';
  // set JK select
  const sel = document.getElementById('edit-jk');
  Array.from(sel.options).forEach(o => o.selected = (o.value === (g.jk || '')));

  // populate edit ampu list
  const editAmpu = document.getElementById('edit-ampu-list');
  editAmpu.innerHTML = '';
  if(g.ampu && g.ampu.length){
    g.ampu.forEach(a=>{
      const row = document.createElement('div');
      row.className = 'row g-2 ampu-row mb-2';
      let mapelHtml = '<option value="">--Pilih Mapel--</option>';
      for(const m of _MAPELS) mapelHtml += `<option value="${m.id}" ${m.id==a.id_mapel?'selected':''}>${m.name}</option>`;
      let kelasHtml = '<option value="">--Pilih Kelas--</option>';
      for(const k of _KELAS) kelasHtml += `<option value="${k.id}" ${k.id==a.id_kelas?'selected':''}>${k.label}</option>`;
      row.innerHTML = `
        <input type="hidden" name="ampu_id[]" value="${a.id_ampu}">
        <div class="col-md-6"><select name="ampu_mapel[]" class="form-select">${mapelHtml}</select></div>
        <div class="col-md-4"><select name="ampu_kelas[]" class="form-select">${kelasHtml}</select></div>
        <div class="col-md-2"><button type="button" class="btn btn-sm btn-danger w-100" onclick="removeAmpuRow(this)">Hapus</button></div>
      `;
      editAmpu.appendChild(row);
    });
  }

  // add vertical class for modal (narrow) like siswa
  const modalEl = document.getElementById('detailModalGuru');
  if(modalEl) modalEl.classList.add('vertical');
}

function editGuruInModal(){
  const modalEl = document.getElementById('detailModalGuru');
  if(modalEl) modalEl.classList.remove('vertical');

  document.getElementById('modal-guru-view').style.display = 'none';
  document.getElementById('modal-guru-edit').style.display = 'block';
  document.getElementById('view-buttons').style.display = 'none';
  document.getElementById('edit-buttons').style.display = 'flex';
  document.getElementById('btn-export-guru-pdf').style.display = 'none';
}

function cancelEditGuru(){
  document.getElementById('modal-guru-view').style.display = 'block';
  document.getElementById('modal-guru-edit').style.display = 'none';
  document.getElementById('view-buttons').style.display = 'flex';
  document.getElementById('edit-buttons').style.display = 'none';
  document.getElementById('btn-export-guru-pdf').style.display = 'block';
  const modalEl = document.getElementById('detailModalGuru');
  if(modalEl) modalEl.classList.add('vertical');
}

// search cards
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

// ensure modal resets on hide
document.getElementById('detailModalGuru')?.addEventListener('hidden.bs.modal', function(){
  cancelEditGuru();
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>
