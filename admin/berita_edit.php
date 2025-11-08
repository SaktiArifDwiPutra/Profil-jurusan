<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['admin']);
include __DIR__ . '/../inc/header.php';

$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    flash_set('error','ID berita tidak valid.');
    header('Location: /ProfilJurusan/admin/dashboard.php');
    exit;
}

$berita = fetch_one("SELECT * FROM berita WHERE id_berita = ?", "i", [$id]);
if (!$berita) {
    flash_set('error','Berita tidak ditemukan.');
    header('Location: /ProfilJurusan/admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $isi   = trim($_POST['isi'] ?? '');

    if ($judul === '' || $isi === '') {
        flash_set('error','Judul & isi wajib diisi.');
        header('Location: /ProfilJurusan/admin/berita_edit.php?id='.$id);
        exit;
    }

    $gambarFilename = $berita['gambar'];
    if (!empty($_FILES['gambar']['name'])) {
        $f = $_FILES['gambar'];
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if ($f['error'] === 0 && in_array($f['type'],$allowed)) {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $newName = uniqid('berita_') . '.' . $ext;
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($f['tmp_name'],$dest)) {
                // hapus file lama kalau ada
                if (!empty($gambarFilename) && file_exists($uploadDir . $gambarFilename)) {
                    @unlink($uploadDir . $gambarFilename);
                }
                $gambarFilename = $newName;
            } else {
                flash_set('error','Upload gambar gagal.');
                header('Location: /ProfilJurusan/admin/berita_edit.php?id='.$id);
                exit;
            }
        } else {
            flash_set('error','Format gambar tidak diizinkan.');
            header('Location: /ProfilJurusan/admin/berita_edit.php?id='.$id);
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE berita SET judul=?, isi=?, gambar=? WHERE id_berita=?");
    $stmt->bind_param('sssi', $judul, $isi, $gambarFilename, $id);
    $ok = $stmt->execute();

    if ($ok) {
        flash_set('success','Berita berhasil diperbarui.');
        header('Location: /ProfilJurusan/admin/dashboard.php');
        exit;
    } else {
        flash_set('error','Gagal update: '.$stmt->error);
        header('Location: /ProfilJurusan/admin/berita_edit.php?id='.$id);
        exit;
    }
}
?>

<div class="card p-4 shadow-sm">
  <h4 class="fw-semibold mb-3">Edit Berita</h4>

  <?php if(!empty($_SESSION['flash']['error'])): ?>
    <div class="alert alert-danger">
      <?=htmlspecialchars($_SESSION['flash']['error']); unset($_SESSION['flash']['error']);?>
    </div>
  <?php elseif(!empty($_SESSION['flash']['success'])): ?>
    <div class="alert alert-success">
      <?=htmlspecialchars($_SESSION['flash']['success']); unset($_SESSION['flash']['success']);?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" id="formBeritaEdit">
    <div class="mb-3">
      <label class="form-label">Judul</label>
      <input type="text" name="judul" class="form-control" value="<?=htmlspecialchars($berita['judul'])?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Isi</label>
      <textarea name="isi" rows="8" class="form-control" required><?=htmlspecialchars($berita['isi'])?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Gambar Utama (opsional)</label>
      <input type="file" name="gambar" id="gambar" accept="image/*" class="form-control">
      <?php if(!empty($berita['gambar'])): ?>
        <div class="mt-3">
          <p class="small text-muted mb-1">Gambar saat ini:</p>
          <img src="/ProfilJurusan/uploads/<?=htmlspecialchars($berita['gambar'])?>" 
               alt="Gambar berita" 
               style="max-width:100%;border-radius:8px;max-height:220px;object-fit:cover;">
        </div>
      <?php endif; ?>
    </div>

    <div class="mt-3">
      <button class="btn btn-primary">Update</button>
      <a href="/ProfilJurusan/admin/dashboard.php" class="btn btn-danger ms-2">Batal</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../inc/footer.php'; ?>
