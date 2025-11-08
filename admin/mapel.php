<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['admin']);
include __DIR__ . '/../inc/header.php';

// --- BACKEND (tetap seperti semula) ---
if($_SERVER['REQUEST_METHOD']=='POST' && ($_POST['action'] ?? '')=='add'){
    $nama = trim($_POST['nama_mapel'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    if($nama === '' || $kategori === ''){
        flash_set('error','Nama mapel & kategori wajib diisi');
        header('Location: /ProfilJurusan/admin/mapel.php');
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO mapel (nama_mapel, kategori) VALUES (?,?)");
    $stmt->bind_param('ss',$nama,$kategori);
    $stmt->execute();
    flash_set('success','Mapel ditambahkan');
    header('Location: /ProfilJurusan/admin/mapel.php');
    exit;
}

if($_SERVER['REQUEST_METHOD']=='POST' && ($_POST['action'] ?? '')=='edit'){
    $id = intval($_POST['id_mapel'] ?? 0);
    $nama = trim($_POST['nama_mapel'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    if($id <= 0 || $nama === '' || $kategori === ''){
        flash_set('error','Data edit tidak valid');
        header('Location: /ProfilJurusan/admin/mapel.php'); exit;
    }
    $stmt = $conn->prepare("UPDATE mapel SET nama_mapel = ?, kategori = ? WHERE id_mapel = ?");
    $stmt->bind_param('ssi', $nama, $kategori, $id);
    $stmt->execute();
    flash_set('success','Mapel diupdate');
    header('Location: /ProfilJurusan/admin/mapel.php');
    exit;
}

if(isset($_GET['hapus'])){
    $id = intval($_GET['hapus']);
    if($id > 0){
        $stmt = $conn->prepare("DELETE FROM mapel WHERE id_mapel = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash_set('success','Mapel dihapus');
    }
    header('Location: /ProfilJurusan/admin/mapel.php');
    exit;
}

// --- DATA FETCH (tetap seperti semula) ---
// ambil semua mapel
$mapels = fetch_all("SELECT * FROM mapel ORDER BY nama_mapel ASC");

// ambil relasi mapel_diampu -> guru agar bisa menampilkan daftar guru per mapel
$ampu_rows = fetch_all("
    SELECT md.id_ampu, md.id_mapel, md.id_guru, g.nama AS guru_nama
    FROM mapel_diampu md
    JOIN guru g ON md.id_guru = g.id_guru
    ORDER BY g.nama
");

// susun map dari id_mapel => [ {id_guru, guru_nama}, ... ]
$ampu_map = [];
foreach($ampu_rows as $r){
    $mid = (int)$r['id_mapel'];
    if(!isset($ampu_map[$mid])) $ampu_map[$mid] = [];
    $ampu_map[$mid][] = [
        'id_guru' => (int)$r['id_guru'],
        'nama'    => $r['guru_nama']
    ];
}

// Statistik untuk sidebar
$total_mapel = count($mapels);
$kategori_count = [];
foreach($mapels as $m){
    $kategori_count[$m['kategori']] = ($kategori_count[$m['kategori']] ?? 0) + 1;
}
$recent_mapels = array_slice($mapels, max(0, $total_mapel - 6)); // 6 terakhir
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
// warning kalau bootstrap JS nggak ada
if(typeof bootstrap === 'undefined') console.warn('Bootstrap JS tidak ditemukan. Pastikan bootstrap.bundle.min.js dimuat (biasanya di footer).');
document.addEventListener('DOMContentLoaded', function(){
    var toastEl = document.getElementById('toastSuccess');
    if(toastEl && typeof bootstrap !== 'undefined'){
        var toast = new bootstrap.Toast(toastEl, {delay: 3000});
        toast.show();
    }
});
</script>
<?php endif; ?>

<div class="layout-golden">

  <!-- KIRI: konten utama (pakai class left-col supaya CSS ke-apply) -->
  <div class="left-col">

    <div class="d-flex justify-content-between mb-3">
      <h4>Data mapel</h4>
      <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#formAdd">Tambah mapel</button>
    </div>

    <!-- FORM ADD (perbaikan: gunakan field yang sesuai dengan backend: nama_mapel & kategori) -->
    <div id="formAdd" class="collapse mb-3 card p-3">
      <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="row g-2 align-items-center">
          <div class="col-md-6">
            <input name="nama_mapel" class="form-control" placeholder="Nama Mapel" required>
          </div>
          <div class="col-md-4">
            <select name="kategori" class="form-select" required>
              <option value="">-- Pilih Kategori --</option>
              <option value="Produktif">Produktif</option>
              <option value="Umum">Umum</option>
            </select>
          </div>
          <div class="col-md-2 text-end">
            <button class="btn btn-primary">Simpan</button>
          </div>
        </div>
      </form>
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
        // ambil daftar guru untuk mapel ini (jika ada)
        $guru_list = $ampu_map[$id] ?? [];
        // buat array nama guru saja untuk JSON
        $guru_names = array_map(fn($x)=>$x['nama'], $guru_list);

        $data_json = json_encode([
          'id'=>$id,
          'nama'=>$m['nama_mapel'],
          'kategori'=>$m['kategori'],
          'gurus'=>$guru_names
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
      ?>
      <div class="col mapel-card-col" data-mapel="<?= htmlspecialchars($data_json, ENT_QUOTES) ?>" data-id="<?= $id ?>" data-nama="<?=htmlspecialchars($m['nama_mapel'], ENT_QUOTES)?>" data-kategori="<?=htmlspecialchars($m['kategori'], ENT_QUOTES)?>">
        <div class="card siswa-card shadow-sm h-100" onclick="showMapelDetail(<?= $id ?>)">
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

        <div id="modal-mapel-edit" style="display:none;">
          <form method="post" id="mapelEditForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_mapel" id="edit-id-mapel">
            <div class="row g-2">
              <div class="col-md-8 mb-2">
                <input type="text" name="nama_mapel" id="edit-nama-mapel" class="form-control" placeholder="Nama Mapel" required>
              </div>
              <div class="col-md-4 mb-2">
                <select name="kategori" id="edit-kategori" class="form-select" required>
                  <option value="Produktif">Produktif</option>
                  <option value="Umum">Umum</option>
                </select>
              </div>
            </div>
            <div class="mt-2 small text-muted">Untuk mengubah guru pengampu, edit pada halaman Guru (mapel diampu).</div>
          </form>
        </div>
      </div>

      <div class="modal-footer" id="modal-footer-actions">
        <div id="view-buttons" style="display:flex; padding-left: 50px; gap:8px; align-items:center;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="button" class="btn btn-primary" onclick="editMapelInModal()">Edit</button>
          <a href="#" id="btn-hapus-mapel" class="btn btn-danger" onclick="return confirm('Hapus mapel ini?')">Hapus</a>
        </div>

        <div style="flex:1"></div>

        <div id="edit-buttons" style="display:none; gap:8px;">
          <button type="submit" form="mapelEditForm" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" onclick="cancelEditMapel()">Batal</button>
        </div>

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
    // dataset.mapel sudah di-escaped via htmlspecialchars, dataset memberikan string yang valid
    m = JSON.parse(col.dataset.mapel);
  } catch(e){
    console.error('JSON parse error for data-mapel:', e, col.dataset.mapel);
    // fallback: gunakan data-* lain yang tersedia
    m = {
      id: col.dataset.id || id,
      nama: col.dataset.nama || '',
      kategori: col.dataset.kategori || '',
      gurus: []
    };
  }

  // set view/edit UI
  document.getElementById('modal-mapel-view').style.display = 'block';
  document.getElementById('modal-mapel-edit').style.display = 'none';
  document.getElementById('view-buttons').style.display = 'flex';
  document.getElementById('edit-buttons').style.display = 'none';
  document.getElementById('btn-export-mapel-pdf').style.display = 'block';

  document.getElementById('modal-mapel-nama').textContent = m.nama || '-';
  document.getElementById('detail-nama').textContent = m.nama || '-';
  document.getElementById('detail-kategori').textContent = m.kategori || '-';

  // set guru list (array of names)
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

  // set action links (cek dulu elemen ada)
  const btnHapus = document.getElementById('btn-hapus-mapel');
  if(btnHapus) btnHapus.href = '?hapus=' + encodeURIComponent(m.id);

  const btnPdf = document.getElementById('btn-export-mapel-pdf');
  if(btnPdf) btnPdf.href = '../inc/export.php?table=mapel&type=pdf&id_mapel=' + encodeURIComponent(m.id);

  // prepare edit form values
  document.getElementById('edit-id-mapel').value = m.id || '';
  document.getElementById('edit-nama-mapel').value = m.nama || '';
  const sel = document.getElementById('edit-kategori');
  if(sel) Array.from(sel.options).forEach(o => o.selected = (o.value === (m.kategori || '')));

  // --- IMPORTANT: add 'vertical' BEFORE showing modal to avoid flicker ---
  const modalRoot = document.getElementById('detailModalMapel');
  if(modalRoot) {
    modalRoot.classList.add('vertical'); // narrow style applied
    // programmatically show modal after class added
    if(typeof bootstrap !== 'undefined'){
      const bsModal = bootstrap.Modal.getOrCreateInstance(modalRoot);
      bsModal.show();
    } else {
      console.warn('Bootstrap Modal API tidak ditemukan. Pastikan bootstrap JS sudah dimuat.');
    }
  }
}

function editMapelInModal(){
  const modalEl = document.getElementById('detailModalMapel');
  if(modalEl) modalEl.classList.remove('vertical');

  document.getElementById('modal-mapel-view').style.display = 'none';
  document.getElementById('modal-mapel-edit').style.display = 'block';
  document.getElementById('view-buttons').style.display = 'none';
  document.getElementById('edit-buttons').style.display = 'flex';
  document.getElementById('btn-export-mapel-pdf').style.display = 'none';

}

function cancelEditMapel(){
  document.getElementById('modal-mapel-view').style.display = 'block';
  document.getElementById('modal-mapel-edit').style.display = 'none';
  document.getElementById('view-buttons').style.display = 'flex';
  document.getElementById('edit-buttons').style.display = 'none';
  document.getElementById('btn-export-mapel-pdf').style.display = 'block';
  const modalEl = document.getElementById('detailModalMapel');
  if(modalEl) modalEl.classList.add('vertical');
}

// search cards (includes searching guru names)
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

// ensure modal reset on hide: remove vertical? keep consistent state
document.getElementById('detailModalMapel')?.addEventListener('hidden.bs.modal', function(){
  // reset view/edit states
  cancelEditMapel();
  // remove vertical so next open starts from clean state (we add again before show)
  this.classList.remove('vertical');
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>