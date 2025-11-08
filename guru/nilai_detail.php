<?php
// nilai_detail.php (full) - update existing nilai if exists, otherwise insert
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['guru']);

// === fallback execute_query jika fungsi itu belum ada di inc/functions.php ===
if (!function_exists('execute_query')) {

    function get_mysqli_conn() {
        foreach (['mysqli', 'mysqli_conn', 'conn', 'db', 'link', 'koneksi', 'mysql', 'db_conn'] as $name) {
            if (isset($GLOBALS[$name]) && $GLOBALS[$name] instanceof mysqli) {
                return $GLOBALS[$name];
            }
        }
        if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
            $m = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($m->connect_errno) throw new Exception('Gagal konek DB: ' . $m->connect_error);
            return $m;
        }
        // fallback lokal (sesuaikan jika perlu)
        $m = new mysqli('localhost', 'root', '', '');
        if ($m->connect_errno) throw new Exception('Gagal konek DB (default): ' . $m->connect_error);
        return $m;
    }

    function execute_query($sql, $types = '', $params = []) {
        $mysqli = get_mysqli_conn();
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: ({$mysqli->errno}) {$mysqli->error} -- SQL: {$sql}");
            return false;
        }

        if (!empty($params)) {
            if ($types === '' || $types === null) {
                $types = '';
                foreach ($params as $p) {
                    if (is_int($p)) $types .= 'i';
                    elseif (is_double($p) || is_float($p)) $types .= 'd';
                    else $types .= 's';
                }
            }
            $bind_names = [];
            $bind_names[] = $types;
            for ($i = 0; $i < count($params); $i++) {
                $bind_name = 'bind' . $i;
                $$bind_name = $params[$i];
                $bind_names[] = &$$bind_name;
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_names);
        }

        $exec = $stmt->execute();
        if ($exec === false) {
            error_log("Execute failed: ({$stmt->errno}) {$stmt->error} -- SQL: {$sql}");
            $stmt->close();
            return false;
        }

        $meta = $stmt->result_metadata();
        if ($meta) {
            $res = $stmt->get_result();
            if ($res === false) { $stmt->close(); return []; }
            $rows = $res->fetch_all(MYSQLI_ASSOC);
            $res->free();
            $stmt->close();
            return $rows;
        }

        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected >= 0;
    }
}
// === end fallback ===

// ambil user & param awal
$guru = current_user();
$id_guru = (int)($guru['id_guru'] ?? 0);
$id_siswa = (int)($_GET['id_siswa'] ?? 0);

// Validasi awal
if (!$id_guru || !$id_siswa) {
    flash_set('error', 'Data tidak valid');
    header('Location: nilai.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| HANDLER: Update (POST) dan Delete (GET action=delete)
| Semua redirect dilakukan sebelum include header agar header() aman
|--------------------------------------------------------------------------
*/

// --- HANDLE UPDATE (POST): update last nilai if exists, otherwise insert ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'update')) {
    $id_ampu = (int)($_GET['id_ampu'] ?? 0);
    $semester = trim($_POST['semester'] ?? '');
    $nilai_tugas = $_POST['nilai_tugas'] ?? null;
    $nilai_uts = $_POST['nilai_uts'] ?? null;

    if (!$id_ampu || $semester === '' || $nilai_tugas === null || $nilai_uts === null) {
        flash_set('error', 'Data tidak lengkap untuk menyimpan nilai.');
        header("Location: nilai_detail.php?id_siswa={$id_siswa}");
        exit;
    }

    $nilai_tugas = (float)$nilai_tugas;
    $nilai_uts = (float)$nilai_uts;
    $nilai_akhir = round($nilai_tugas * 0.4 + $nilai_uts * 0.6, 2);

    // Cek apakah sudah ada nilai untuk siswa+ampu (ambil id_nilai terakhir)
    $row = fetch_one("SELECT id_nilai FROM nilai WHERE id_siswa=? AND id_ampu=? ORDER BY id_nilai DESC LIMIT 1", "ii", [$id_siswa, $id_ampu]);

    if ($row && isset($row['id_nilai']) && (int)$row['id_nilai'] > 0) {
        // UPDATE existing record
        $id_nilai_terakhir = (int)$row['id_nilai'];
        $ok = execute_query(
            "UPDATE nilai SET semester = ?, nilai_tugas = ?, nilai_uts = ?, nilai_akhir = ? WHERE id_nilai = ?",
            "", // biarkan execute_query infer types
            [$semester, $nilai_tugas, $nilai_uts, $nilai_akhir, $id_nilai_terakhir]
        );
    } else {
        // INSERT new record (riwayat)
        $ok = execute_query(
            "INSERT INTO nilai (id_siswa, id_ampu, semester, nilai_tugas, nilai_uts, nilai_akhir) VALUES (?,?,?,?,?,?)",
            "",
            [$id_siswa, $id_ampu, $semester, $nilai_tugas, $nilai_uts, $nilai_akhir]
        );
    }

    if ($ok) flash_set('success', 'Nilai berhasil disimpan.');
    else flash_set('error', 'Gagal menyimpan nilai.');

    header("Location: nilai_detail.php?id_siswa={$id_siswa}");
    exit;
}

// --- HANDLE DELETE (GET) ---
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id_nilai = (int)($_GET['id_nilai'] ?? 0);
    if (!$id_nilai) {
        flash_set('error', 'Parameter id_nilai tidak valid.');
        header("Location: nilai_detail.php?id_siswa={$id_siswa}");
        exit;
    }

    // Pastikan nilai milik siswa
    $check = fetch_one("SELECT * FROM nilai WHERE id_nilai=? AND id_siswa=?", "ii", [$id_nilai, $id_siswa]);
    if (!$check) {
        flash_set('error', 'Data nilai tidak ditemukan atau bukan milik siswa ini.');
        header("Location: nilai_detail.php?id_siswa={$id_siswa}");
        exit;
    }

    $del = execute_query("DELETE FROM nilai WHERE id_nilai=?", "", [$id_nilai]);
    if ($del) flash_set('success', 'Nilai berhasil dihapus.');
    else flash_set('error', 'Gagal menghapus nilai.');

    header("Location: nilai_detail.php?id_siswa={$id_siswa}");
    exit;
}

// setelah handler, include header & render halaman
include __DIR__ . '/../inc/header.php';

// Ambil data siswa & mapel
$s = fetch_one("SELECT s.*, k.tingkat, k.kelas FROM siswa s JOIN kelas k ON s.id_kelas=k.id_kelas WHERE id_siswa=?", "i", [$id_siswa]);
if (!$s) {
    echo '<div class="alert alert-warning">Siswa tidak ditemukan.</div>';
    include __DIR__ . '/../inc/footer.php';
    exit;
}

$mapel = fetch_all("
 SELECT m.id_mapel, m.nama_mapel, md.id_ampu, md.id_guru
 FROM mapel_diampu md
 JOIN mapel m ON md.id_mapel = m.id_mapel
 WHERE md.id_kelas = ?
 ORDER BY m.nama_mapel
", "i", [$s['id_kelas']]);

$existing_nilai_all = fetch_all("SELECT * FROM nilai WHERE id_siswa=? ORDER BY semester DESC, id_nilai DESC", "i", [$id_siswa]);
$nilai_data = [];
$nilai_history = [];
foreach ($existing_nilai_all as $n) {
    $id_ampu = $n['id_ampu'];
    if (!isset($nilai_data[$id_ampu]) || $n['semester'] > $nilai_data[$id_ampu]['semester']) {
        $nilai_data[$id_ampu] = $n;
    }
    $nilai_history[$id_ampu][] = $n;
}

$total_mapel_diampu = count($mapel);
$total_bisa_edit = 0;
foreach ($mapel as $m) {
    if ($m['id_guru'] == $id_guru) $total_bisa_edit++;
}

$mapel_terakhir_dinilai = null;
if (!empty($existing_nilai_all)) {
    $mapel_terakhir_dinilai = $existing_nilai_all[0];
    foreach ($mapel as $m) {
        if ($m['id_ampu'] == $mapel_terakhir_dinilai['id_ampu']) {
            $mapel_terakhir_dinilai['nama_mapel'] = $m['nama_mapel'];
            break;
        }
    }
}
?>

<div class="layout-golden">
  <div class="left-col">
    <div class="card shadow-sm p-4 mb-4">
      <h4 class="fw-bold mb-1">Detail Nilai Siswa</h4>
      <p class="mb-3 text-muted">Data nilai untuk <strong><?=htmlspecialchars($s['nama'])?></strong> (Kelas <?=htmlspecialchars($s['tingkat'].' - '.$s['kelas'])?>)</p>

      <div class="d-flex justify-content-between mb-4 align-items-center">
        <div class="input-group w-50">
          <span class="input-group-text"><i class="fa fa-search"></i></span>
          <input type="text" id="searchInputMapel" class="form-control" placeholder="Cari Mapel...">
        </div>

        <div class="d-flex gap-2">
          <a href="nilai.php" class="btn btn-sm btn-secondary"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
          <a href="../inc/export.php?table=nilai&type=csv&id_siswa=<?=urlencode($id_siswa)?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fa fa-file-csv me-1"></i> Export CSV</a>
          <a href="../inc/export.php?table=nilai&type=pdf&id_siswa=<?=urlencode($id_siswa)?>" class="btn btn-sm btn-outline-danger" target="_blank"><i class="fa fa-file-pdf me-1"></i> Export PDF</a>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0" id="nilaiTable">
          <thead>
            <tr>
              <th>No</th><th>Mapel</th><th>Semester</th><th>Tugas</th><th>UTS</th><th>Akhir</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($mapel as $i=>$m):
              $id_ampu = (int)($m['id_ampu'] ?? 0);
              $n = $nilai_data[$id_ampu] ?? [];
              $editable = ($m['id_guru'] == $id_guru);
              $nilai_akhir = isset($n['nilai_akhir']) ? (float)$n['nilai_akhir'] : 0;
              $bg_class = $nilai_akhir > 85 ? 'table-success' : ($nilai_akhir >= 70 ? 'table-warning' : '');
            ?>
              <tr class="<?=$bg_class?>" data-mapel="<?=strtolower(htmlspecialchars($m['nama_mapel']))?>">
                <td><?=$i+1?></td>
                <td class="mapel-name"><?=htmlspecialchars($m['nama_mapel'])?></td>
                <td><?=htmlspecialchars($n['semester'] ?? '-')?></td>
                <td><?=htmlspecialchars($n['nilai_tugas'] ?? '-')?></td>
                <td><?=htmlspecialchars($n['nilai_uts'] ?? '-')?></td>
                <td class="fw-bold"><?=htmlspecialchars($n['nilai_akhir'] ?? '-')?></td>
                <td>
                  <?php if ($editable): ?>
                    <button class="btn btn-sm btn-info me-1" data-bs-toggle="collapse" data-bs-target="#form-<?=$id_ampu?>">Input/Edit</button>

                    <?php
                      $can_delete = isset($n['id_nilai']) && (int)$n['id_nilai'] > 0;
                      $data_id_nilai = $can_delete ? (int)$n['id_nilai'] : '';
                    ?>
                    <?php if ($can_delete): ?>
                      <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id-nilai="<?=htmlspecialchars($data_id_nilai)?>" data-mapel-name="<?=htmlspecialchars($m['nama_mapel'])?>">Hapus</button>
                    <?php else: ?>
                      <button class="btn btn-sm btn-danger disabled" disabled>Hapus</button>
                    <?php endif; ?>

                  <?php else: ?>
                    <span class="text-muted"><i class="fa fa-lock"></i></span>
                  <?php endif; ?>
                </td>
              </tr>

              <?php if ($editable): ?>
                <tr class="collapse" id="form-<?=$id_ampu?>">
                  <td colspan="7" class="bg-light">
                    <!-- ACTION di-set ke nilai_detail.php + query string id_ampu -->
                    <form method="post" action="./nilai_detail.php?id_siswa=<?=urlencode($id_siswa)?>&id_ampu=<?=$id_ampu?>" class="row g-2 align-items-center p-2">
                      <input type="hidden" name="action" value="update">
                      <div class="col-md-2">
                        <label class="form-label visually-hidden">Semester</label>
                        <select name="semester" class="form-select form-select-sm" required>
                          <option value="Ganjil" <?=(($n['semester']??'')==='Ganjil')?'selected':''?>>Ganjil</option>
                          <option value="Genap" <?=(($n['semester']??'')==='Genap')?'selected':''?>>Genap</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <input type="number" step="0.01" min="0" max="100" name="nilai_tugas" class="form-control form-control-sm" placeholder="Nilai Tugas" value="<?=htmlspecialchars($n['nilai_tugas'] ?? '')?>" required>
                      </div>
                      <div class="col-md-2">
                        <input type="number" step="0.01" min="0" max="100" name="nilai_uts" class="form-control form-control-sm" placeholder="Nilai UTS" value="<?=htmlspecialchars($n['nilai_uts'] ?? '')?>" required>
                      </div>
                      <div class="col-md-2">
                        <input type="text" class="form-control form-control-sm" value="Avg: <?=htmlspecialchars($n['nilai_akhir'] ?? '-')?>" placeholder="Akhir" readonly>
                      </div>
                      <div class="col-md-2">
                        <button class="btn btn-success btn-sm w-100"><i class="fa fa-save me-1"></i> Simpan</button>
                      </div>
                      <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" data-bs-toggle="collapse" data-bs-target="#form-<?=$id_ampu?>"><i class="fa fa-times me-1"></i> Batal</button>
                      </div>
                    </form>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <aside class="sidebar">
    <div class="stat-card">
      <h6>Info Siswa</h6>
      <p class="mb-1"><strong>NISN:</strong> <?=htmlspecialchars($s['nisn'])?></p>
      <p class="mb-1"><strong>Kelas:</strong> <?=htmlspecialchars($s['tingkat'].' - '.$s['kelas'])?></p>
    </div>

    <div class="stat-card">
      <h6>Ringkasan Mapel</h6>
      <div class="recent-list">
        <div class="recent-item"><div>Mapel di Kelas:</div><strong><?=$total_mapel_diampu?></strong></div>
        <div class="recent-item"><div>Mapel yang Anda Ampu:</div><strong class="text-info"><?=$total_bisa_edit?></strong></div>
      </div>
    </div>

    <?php if ($mapel_terakhir_dinilai): ?>
      <div class="stat-card">
        <h6>Nilai Terakhir Dicatat</h6>
        <div class="recent-list">
          <div class="recent-item"><div>Mapel:</div><strong><?=htmlspecialchars($mapel_terakhir_dinilai['nama_mapel'] ?? 'N/A')?></strong></div>
          <div class="recent-item"><div>Semester:</div><strong><?=htmlspecialchars($mapel_terakhir_dinilai['semester'] ?? 'N/A')?></strong></div>
          <div class="recent-item"><div>Nilai Akhir:</div><strong class="text-primary"><?=htmlspecialchars($mapel_terakhir_dinilai['nilai_akhir'] ?? '-')?></strong></div>
        </div>
      </div>
    <?php endif; ?>
  </aside>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
 <div class="modal-dialog">
  <div class="modal-content">
   <div class="modal-header bg-danger text-white">
    <h5 class="modal-title"><i class="fa fa-trash-alt me-2"></i> Konfirmasi Hapus Nilai</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
   </div>
   <div class="modal-body">
    <p>Anda yakin ingin menghapus nilai <strong>terakhir</strong> untuk mata pelajaran <strong><span id="modal-mapel-name"></span></strong>?</p>
    <p class="text-danger small">Tindakan ini tidak dapat dibatalkan.</p>
   </div>
   <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <a id="btn-delete-confirm" href="#" class="btn btn-danger">Ya, Hapus Nilai</a>
   </div>
  </div>
 </div>
</div>

<script>
// filter
document.getElementById('searchInputMapel')?.addEventListener('input', function(){
  const q = this.value.trim().toLowerCase();
  const rows = Array.from(document.querySelectorAll('#nilaiTable tbody tr'));
  for (let r of rows) {
    if (r.id && r.id.startsWith('form-')) continue;
    const name = r.querySelector('.mapel-name')?.textContent.toLowerCase() || '';
    const show = name.includes(q);
    r.style.display = show ? '' : 'none';
    const next = r.nextElementSibling;
    if (next && next.id && next.id.startsWith('form-')) next.style.display = show ? '' : 'none';
  }
});

// modal delete: set href ke nilai_detail.php?action=delete...
const delModal = document.getElementById('deleteModal');
if (delModal) {
  delModal.addEventListener('show.bs.modal', function(event){
    const btn = event.relatedTarget;
    if (!btn) return;
    const idNilai = btn.getAttribute('data-id-nilai') || '';
    const mapelName = btn.getAttribute('data-mapel-name') || '';
    const modalMapel = document.getElementById('modal-mapel-name');
    const delBtn = document.getElementById('btn-delete-confirm');
    if (modalMapel) modalMapel.textContent = mapelName;
    if (delBtn) {
      if (!idNilai) {
        delBtn.classList.add('disabled');
        delBtn.setAttribute('href', '#');
      } else {
        delBtn.classList.remove('disabled');
        delBtn.setAttribute('href', './nilai_detail.php?action=delete&id_nilai=' + encodeURIComponent(idNilai) + '&id_siswa=<?=urlencode($id_siswa)?>');
      }
    }
  });
}
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>
