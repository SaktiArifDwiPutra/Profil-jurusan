<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['admin']);

// --- BACKEND: (tetap seperti sebelumnya) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $tingkat = $conn->real_escape_string($_POST['tingkat'] ?? '');
        $kelas   = $conn->real_escape_string($_POST['kelas'] ?? '');
        $id_wali = isset($_POST['id_wali']) && $_POST['id_wali'] !== '' ? (int)$_POST['id_wali'] : null;

        $id_wali_sql = ($id_wali === null) ? 'NULL' : $id_wali;
        $sql = "INSERT INTO kelas (tingkat, kelas, id_wali) VALUES ('{$tingkat}','{$kelas}', {$id_wali_sql})";
        $conn->query($sql);

        flash_set('success', 'Kelas berhasil ditambahkan');
        header('Location: kelas.php');
        exit;
    }

    if ($action === 'edit') {
        $id      = (int)($_POST['id_kelas'] ?? 0);
        $tingkat = $conn->real_escape_string($_POST['tingkat'] ?? '');
        $kelas   = $conn->real_escape_string($_POST['kelas'] ?? '');
        $id_wali = isset($_POST['id_wali']) && $_POST['id_wali'] !== '' ? (int)$_POST['id_wali'] : null;

        $id_wali_sql = ($id_wali === null) ? 'NULL' : $id_wali;
        $sql = "UPDATE kelas SET tingkat='{$tingkat}', kelas='{$kelas}', id_wali={$id_wali_sql} WHERE id_kelas={$id}";
        $conn->query($sql);

        flash_set('success', 'Kelas berhasil diupdate');
        header('Location: kelas.php');
        exit;
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM kelas WHERE id_kelas={$id}");
    flash_set('success', 'Kelas berhasil dihapus');
    header('Location: kelas.php');
    exit;
}

// --- FETCH DATA ---
$gurus = fetch_all("SELECT id_guru, nama FROM guru ORDER BY nama");
$kelas_rows = fetch_all("SELECT k.*, g.nama AS wali_nama FROM kelas k LEFT JOIN guru g ON k.id_wali = g.id_guru ORDER BY k.tingkat, k.kelas");

// stats for sidebar
$total_kelas = count($kelas_rows);
$tingkat_set = [];
$wali_set = [];
foreach ($kelas_rows as $kr) {
    $tingkat_set[$kr['tingkat']] = true;
    if (!empty($kr['id_wali'])) $wali_set[$kr['id_wali']] = $kr['wali_nama'] ?? '';
}
$recent = array_slice($kelas_rows, max(0, $total_kelas - 5));

// include header (HTML head, CSS, etc)
include __DIR__ . '/../inc/header.php';
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
    if(toastEl && typeof bootstrap !== 'undefined'){
        var toast = new bootstrap.Toast(toastEl, {delay: 3000});
        toast.show();
    }
});
</script>
<?php endif; ?>

<div class="layout-golden">

  <!-- KIRI: konten utama -->
  <div class="left-col">

    <!-- Judul + tombol Tambah (SEJAJAR) -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Data Kelas</h4>
      <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#formAddKelas">Tambah</button>
    </div>

    <!-- Form Tambah (collapse) -->
    <div id="formAddKelas" class="collapse card p-3 mb-3">
      <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="row g-2">
          <div class="col-md-3">
            <select name="tingkat" class="form-select" required>
              <option value="">Pilih Tingkat</option>
              <option value="X">X</option>
              <option value="XI">XI</option>
              <option value="XII">XII</option>
            </select>
          </div>
          <div class="col-md-3">
            <input name="kelas" class="form-control" placeholder="Nama Kelas" required>
          </div>
          <div class="col-md-4">
            <select name="id_wali" class="form-select"required>
              <option value="">-- Pilih Wali Kelas --</option>
              <?php foreach($gurus as $g): ?>
                <option value="<?=$g['id_guru']?>"><?=htmlspecialchars($g['nama'])?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-primary w-100">Simpan</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Search + Export (SEJAJAR: search kiri, export kanan) -->
    <div class="d-flex justify-content-between mb-2 align-items-center">
      <div class="input-group w-50">
        <span class="input-group-text"><i class="fa fa-search"></i></span>
        <!-- gunakan id yang sama seperti halaman siswa agar konsisten -->
        <input type="text" id="searchInput" class="form-control" placeholder="Cari kelas (Tingkat, Nama, Wali)...">
      </div>

      <!-- export buttons sejajar kanan (sama seperti halaman siswa) -->
      <div class="d-flex gap-2">
        <a href="../inc/export.php?table=kelas&type=csv" class="btn btn-outline-primary btn-sm" target="_blank">Export CSV</a>
        <a href="../inc/export.php?table=kelas&type=pdf" class="btn btn-outline-danger btn-sm" target="_blank">Export PDF</a>
      </div>
    </div>

    <!-- KARTU KELAS -->
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

  <!-- SIDEBAR (kanan) -->
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
<!-- END GOLDEN LAYOUT -->

<!-- MODAL DETAIL & EDIT for KELAS -->
<div class="modal fade" id="detailModalKelas" tabindex="-1" aria-labelledby="detailModalKelasLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="padding-left: 40px;" id="detailModalKelasLabel">Detail Kelas: <span id="modal-kelas-nama"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- view -->
        <div id="modal-kelas-view">
          <p><strong>Tingkat:</strong> <span id="detail-tingkat"></span></p>
          <p><strong>Nama Kelas:</strong> <span id="detail-kelas"></span></p>
          <p><strong>Wali Kelas:</strong> <span id="detail-wali"></span></p>
        </div>

        <!-- edit -->
        <div id="modal-kelas-edit" style="display:none;">
          <form method="post" id="kelasEditForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_kelas" id="edit-id-kelas">
            <div class="row g-2">
              <div class="col-md-3 mb-2">
                <select name="tingkat" id="edit-tingkat" class="form-select" required>
                  <option value="X">X</option>
                  <option value="XI">XI</option>
                  <option value="XII">XII</option>
                </select>
              </div>
              <div class="col-md-4 mb-2">
                <input type="text" name="kelas" id="edit-kelas" class="form-control" placeholder="Nama Kelas" required>
              </div>
              <div class="col-md-5 mb-2">
                <select name="id_wali" id="edit-id-wali" class="form-select">
                  <option value="">-- Pilih Wali Kelas --</option>
                  <?php foreach($gurus as $g): ?>
                    <option value="<?=$g['id_guru']?>"><?=htmlspecialchars($g['nama'])?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="modal-footer" id="modal-footer-actions">
        <div id="view-buttons" style="display:flex; padding-left: 50px; gap:8px; align-items:center;">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="button" class="btn btn-primary" onclick="editKelasInModal()">Edit</button>
          <a href="#" id="btn-hapus-kelas" class="btn btn-danger" onclick="return confirm('Hapus kelas ini?')">Hapus</a>
        </div>

        <div style="flex:1"></div>

        <div id="edit-buttons" style="display:none; gap:8px;">
          <button type="submit" form="kelasEditForm" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" onclick="cancelEditKelas()">Batal</button>
        </div>

        <div class="modal-export-wrap d-flex gap-2">
          <a href="#" id="btn-export-kelas-pdf" class="btn btn-sm btn-outline-danger btn-export-single" target="_blank">Export PDF</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JS: kartu -> modal, search, edit/cancel -->
<script>
function showKelasDetail(id) {
  const col = document.querySelector(`.kelas-card-col[data-id="${id}"]`);
  if(!col) return;

  let k = null;
  if(col.dataset.kelas){
    try {
      k = JSON.parse(col.dataset.kelas);
    } catch(e){
      console.error('JSON parse error data-kelas', e, col.dataset.kelas);
      // fallback to data- attributes
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

  // show view, hide edit
  document.getElementById('modal-kelas-view').style.display = 'block';
  document.getElementById('modal-kelas-edit').style.display = 'none';
  document.getElementById('view-buttons').style.display = 'flex';
  document.getElementById('edit-buttons').style.display = 'none';
  document.getElementById('btn-export-kelas-pdf').style.display = 'block';

  // fill values
  document.getElementById('modal-kelas-nama').textContent = (k.tingkat || '') + ' ' + (k.kelas || '');
  document.getElementById('detail-tingkat').textContent = k.tingkat || '-';
  document.getElementById('detail-kelas').textContent = k.kelas || '-';
  document.getElementById('detail-wali').textContent = k.wali_nama || '-';

  const btnHapus = document.getElementById('btn-hapus-kelas');
  if(btnHapus) btnHapus.href = '?hapus=' + encodeURIComponent(id);

  // export links (modal)
  const btnPdf = document.getElementById('btn-export-kelas-pdf');
  if(btnPdf) btnPdf.href = '../inc/export.php?table=kelas&type=pdf&id_kelas=' + encodeURIComponent(k.id);

  const btnCsv = document.getElementById('btn-export-kelas-csv');
  if(btnCsv) btnCsv.href = '../inc/export.php?table=kelas&type=csv&id_kelas=' + encodeURIComponent(k.id);

  // prepare edit form fields
  const editId = document.getElementById('edit-id-kelas');
  if(editId) editId.value = k.id || '';
  const selTingkat = document.getElementById('edit-tingkat');
  if(selTingkat) selTingkat.value = k.tingkat || '';
  const editKelas = document.getElementById('edit-kelas');
  if(editKelas) editKelas.value = k.kelas || '';
  const selWali = document.getElementById('edit-id-wali');
  if(selWali){
    Array.from(selWali.options).forEach(o => o.selected = (o.value == (k.wali_id ?? '')));
  }

  // add vertical class to modal root (for narrow look)
  const modalEl = document.getElementById('detailModalKelas');
  if(modalEl) modalEl.classList.add('vertical');
}

function editKelasInModal(){
  const modalEl = document.getElementById('detailModalKelas');
  if(modalEl) modalEl.classList.remove('vertical');

  document.getElementById('modal-kelas-view').style.display = 'none';
  document.getElementById('modal-kelas-edit').style.display = 'block';
  document.getElementById('view-buttons').style.display = 'none';
  document.getElementById('edit-buttons').style.display = 'flex';
  document.getElementById('btn-export-kelas-pdf').style.display = 'none';

}

function cancelEditKelas(){
  document.getElementById('modal-kelas-view').style.display = 'block';
  document.getElementById('modal-kelas-edit').style.display = 'none';
  document.getElementById('view-buttons').style.display = 'flex';
  document.getElementById('edit-buttons').style.display = 'none';
  document.getElementById('btn-export-kelas-pdf').style.display = 'block';
  const modalEl = document.getElementById('detailModalKelas');
  if(modalEl) modalEl.classList.add('vertical');
}

// search cards (pakai id searchInput supaya persis seperti halaman siswa)
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

// reset modal state on hide
document.getElementById('detailModalKelas')?.addEventListener('hidden.bs.modal', function(){
  // reset view/edit states
  cancelEditKelas();
  // remove vertical so next open starts clean (we add again before show)
  this.classList.remove('vertical');
});
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>
