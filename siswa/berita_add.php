<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['siswa']);
include __DIR__ . '/../inc/header.php';
$uploadDir = __DIR__ . '/../uploads/';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
$u = current_user();
$creator_user_id = $u['id_user'] ?? ($u['id'] ?? null);
$creator_username = $u['username'] ?? '';
$cur_siswa_id = $u['id_siswa'] ?? null;
$siswa = fetch_one("SELECT nama FROM siswa WHERE id_siswa = ?", "i", [$cur_siswa_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $isi   = trim($_POST['isi'] ?? '');

    if ($judul === '' || $isi === '') {
        flash_set('error','Judul dan isi wajib diisi.');
        header('Location: /ProfilJurusan/siswa/berita_add.php'); exit;
    }

    $gambarFilename = null;
    if (!empty($_FILES['gambar']['name'])) {
        $f = $_FILES['gambar'];
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if ($f['error'] === 0 && in_array($f['type'], $allowed)) {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $gambarFilename = uniqid('berita_') . '.' . $ext;
            $dest = $uploadDir . $gambarFilename;
            if (!move_uploaded_file($f['tmp_name'], $dest)) {
                flash_set('error','Upload gambar gagal.');
                header('Location: /ProfilJurusan/siswa/berita_add.php'); exit;
            }
        } else {
            flash_set('error','Format gambar tidak diizinkan (jpg/png/webp/gif).');
            header('Location: /ProfilJurusan/siswa/berita_add.php'); exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO berita (id_guru, judul, isi, gambar, tanggal_post) 
                        VALUES (?, ?, ?, ?, NULL)");

    $stmt->bind_param('isss', $creator_user_id, $judul, $isi, $gambarFilename);
    $ok = $stmt->execute();
    if ($ok) {
        flash_set('success','Berita berhasil ditambahkan.');
        header('Location: /ProfilJurusan/siswa/dashboard.php'); exit;
    } else {
        flash_set('error','Gagal menyimpan berita: '.$stmt->error);
        header('Location: /ProfilJurusan/siswa/berita_add.php'); exit;
    }
}
?>

<div class="card p-3">
  <h4>Tambah Berita</h4>

  <?php if(!empty($_SESSION['flash']['error'])): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($_SESSION['flash']['error']); unset($_SESSION['flash']['error']);?></div>
  <?php endif; ?>
  <?php if(!empty($_SESSION['flash']['success'])): ?>
    <div class="alert alert-success"><?=htmlspecialchars($_SESSION['flash']['success']); unset($_SESSION['flash']['success']);?></div>
  <?php endif; ?>

  <div class="mb-2 small text-muted">Posting sebagai: <strong><?=htmlspecialchars($siswa['nama'] ?? $creator_username)?> (Siswa)</strong></div>

  <form method="post" enctype="multipart/form-data" id="formBerita" novalidate>
    <div class="mb-3">
      <label class="form-label">Judul</label>
      <input type="text" name="judul" id="judul" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Isi</label>
      <textarea name="isi" id="isi" rows="8" class="form-control" required></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Gambar Utama (opsional)</label>
      <input type="file" name="gambar" id="gambar" accept="image/*" class="form-control">
      <div id="previewWrap" class="mt-2 d-none">
        <label class="form-label small">Preview:</label>
        <div><img id="previewImg" src="" alt="preview" style="max-width:100%; border-radius:8px; max-height:220px; object-fit:cover;"></div>
        <button type="button" id="removePreview" class="btn btn-sm btn-outline-danger mt-2">Hapus gambar</button>
      </div>
    </div>

    <div class="mt-2">
      <button class="btn btn-primary">Simpan Berita</button>
      <a href="/ProfilJurusan/siswa/dashboard.php" class="btn btn-danger ms-2">Batal</a>
    </div>
  </form>
</div>

<script>
const gambarInput = document.getElementById('gambar');
const previewWrap = document.getElementById('previewWrap');
const previewImg = document.getElementById('previewImg');
const removePreview = document.getElementById('removePreview');

if (gambarInput) {
  gambarInput.addEventListener('change', e => {
    const f = e.target.files[0];
    if (!f) { previewWrap.classList.add('d-none'); return; }
    const url = URL.createObjectURL(f);
    previewImg.src = url;
    previewWrap.classList.remove('d-none');
  });
}
if (removePreview) {
  removePreview.addEventListener('click', () => {
    gambarInput.value = '';
    previewImg.src = '';
    previewWrap.classList.add('d-none');
  });
}
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?>
