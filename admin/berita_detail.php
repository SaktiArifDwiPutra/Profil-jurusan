<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header('Location: /ProfilJurusan/admin/berita.php'); exit; }

$berita = fetch_one("
    SELECT b.*, g.nama AS nama_guru, u.username AS nama_user
    FROM berita b
    LEFT JOIN guru g ON b.id_guru = g.id_guru
    LEFT JOIN user u ON b.created_by = u.id_user
    WHERE b.id_berita = ?", "i", [$id]);

if (!$berita) {
    include __DIR__ . '/../inc/header.php';
    echo '<div class="alert alert-warning">Berita tidak ditemukan.</div>';
    include __DIR__ . '/../inc/footer.php';
    exit;
}

if (isset($_GET['hapus_komen'])) {
    if (!is_logged_in()) {
        flash_set('error','Silakan login dulu.');
        header('Location: /ProfilJurusan/auth/login.php'); exit;
    }
    $komen_id = intval($_GET['hapus_komen']);
    $k = fetch_one("SELECT id_user FROM komentar_berita WHERE id_komentar=?", "i", [$komen_id]);
    $u = current_user();
    $userId = $u['id_user'] ?? ($u['id'] ?? null);
    if ($k && ($k['id_user'] == $userId || ($u['role'] ?? '') === 'admin')) {
        $stmt = $conn->prepare("DELETE FROM komentar_berita WHERE id_komentar=?");
        $stmt->bind_param('i', $komen_id);
        $stmt->execute();
        flash_set('success','Komentar dihapus.');
    } else {
        flash_set('error','Tidak berwenang hapus komentar.');
    }
    header('Location: /ProfilJurusan/admin/berita_detail.php?id='.$id.'#comments'); exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='komen') {
    if (!is_logged_in()) {
        flash_set('error','Silakan login untuk komentar.');
        header('Location: /ProfilJurusan/auth/login.php'); exit;
    }
    $u = current_user();
    $userId = $u['id_user'] ?? ($u['id'] ?? null);
    $text = trim($_POST['komentar'] ?? '');
    if ($text==='') {
        flash_set('error','Komentar tidak boleh kosong.');
    } else {
        $stmt = $conn->prepare("INSERT INTO komentar_berita (id_berita,id_user,komentar) VALUES (?,?,?)");
        $stmt->bind_param('iis',$id,$userId,$text);
        $stmt->execute();
        flash_set('success','Komentar terkirim.');
    }
    header('Location: /ProfilJurusan/admin/berita_detail.php?id='.$id.'#comments'); exit;
}

$comments = fetch_all("
    SELECT k.*, u.username 
    FROM komentar_berita k 
    LEFT JOIN user u ON k.id_user=u.id_user 
    WHERE k.id_berita=? ORDER BY k.tanggal_post DESC", "i", [$id]);

include __DIR__ . '/../inc/header.php';
?>

<div class="row">
  <div class="col-md-8">
    <article class="card p-4 mb-4 shadow-sm">
      <header class="mb-3">
        <h2 class="fw-bold"><?=htmlspecialchars($berita['judul'])?></h2>
        <div class="small text-muted">
          <?=htmlspecialchars($berita['tanggal_post'] ?? '-')?>
          &middot;
          <?=htmlspecialchars($berita['nama_user'] ?: ($berita['nama_guru'] ?: 'Admin'))?>
        </div>
      </header>

      <?php if (!empty($berita['gambar'])): ?>
        <img src="/ProfilJurusan/uploads/<?=htmlspecialchars($berita['gambar'])?>" 
             alt="gambar berita" 
             class="img-fluid mb-3 rounded" style="max-height:400px; object-fit:cover;">
      <?php endif; ?>

      <div class="mb-3"><?=nl2br(htmlspecialchars($berita['isi']))?></div>

      <div class="d-flex justify-content-between">
        <a href="/ProfilJurusan/admin/dashboard.php" class="btn btn-danger">← Kembali</a>
      </div>
    </article>

    <section id="comments" class="card p-4 shadow-sm">
      <h5 class="fw-semibold mb-3">Komentar (<?=count($comments)?>)</h5>

      <?php if (count($comments)===0): ?>
        <div class="text-muted mb-2">Belum ada komentar. Jadilah yang pertama!</div>
      <?php endif; ?>

      <?php foreach($comments as $c): 
        $author = htmlspecialchars($c['username'] ?: 'admin');
        $initial = strtoupper(mb_substr($author,0,1));
      ?>
        <div class="d-flex mb-3">
          <div class="me-3">
            <div style="width:40px;height:40px;border-radius:50%;background:#dee7fb;display:flex;align-items:center;justify-content:center;font-weight:600;color:#0b5ed7;">
              <?=$initial?>
            </div>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex align-items-center mb-1">
              <strong><?=$author?></strong>
              <div class="small text-muted ms-2"><?=htmlspecialchars($c['tanggal_post'])?></div>
              <div class="ms-auto">
                <?php
                  $u=current_user();
                  $userId=$u['id_user']??($u['id']??null);
                  if(is_logged_in() && ($userId==$c['id_user'] || ($u['role']??'')==='admin')): ?>
                    <a href="/ProfilJurusan/admin/berita_detail.php?id=<?=$id?>&hapus_komen=<?=$c['id_komentar']?>" 
                       class="btn btn-sm btn-outline-danger" 
                       onclick="return confirm('Hapus komentar ini?')">Hapus</a>
                <?php endif; ?>
              </div>
            </div>
            <div><?=nl2br(htmlspecialchars($c['komentar']))?></div>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (is_logged_in()):
        $u=current_user();
        $username=htmlspecialchars($u['username']??'');
      ?>
        <form method="post" class="mt-3">
          <input type="hidden" name="action" value="komen">
          <label class="form-label">Komentar sebagai <strong><?=$username?></strong></label>
          <textarea name="komentar" rows="3" class="form-control mb-2" required></textarea>
          <button class="btn btn-primary">Kirim Komentar</button>
        </form>
      <?php else: ?>
        <div class="mt-3 small text-muted">
          Silakan <a href="/ProfilJurusan/auth/login.php" class="link-primary">login</a> untuk mengomentari.
        </div>
      <?php endif; ?>
    </section>
  </div>

  <!-- kanannya -->
  <div class="col-md-4">
    <div class="card p-4 shadow-sm mb-4">
      <h6 class="fw-semibold mb-3">Berita Lainnya</h6>
      <ul class="list-unstyled mb-0">
        <?php
        $others=fetch_all("SELECT id_berita,judul,tanggal_post FROM berita WHERE id_berita<>? ORDER BY tanggal_post DESC LIMIT 6","i",[$id]);
        foreach($others as $o): ?>
          <li class="mb-2">
            <a href="/ProfilJurusan/admin/berita_detail.php?id=<?=$o['id_berita']?>" class="link-primary fw-medium">
              <?=htmlspecialchars($o['judul'])?>
            </a>
            <div class="small text-muted"><?=htmlspecialchars($o['tanggal_post'])?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../inc/footer.php'; ?>
