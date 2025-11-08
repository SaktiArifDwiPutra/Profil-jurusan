<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['guru']);
include __DIR__ . '/../inc/header.php';

$u = current_user();
$userId      = $u['id_user'] ?? ($u['id'] ?? null);
$username    = $u['username'] ?? ($u['nama'] ?? 'Guru');
$cur_guru_id = $u['id_guru'] ?? null;
$cur_siswa_id = $u['id_siswa'] ?? null;
$guru_preview  = fetch_all("SELECT id_guru, nama, nip FROM guru ORDER BY nama LIMIT 6");
$kelas_preview = fetch_all("SELECT id_kelas, tingkat, kelas FROM kelas ORDER BY tingkat, kelas LIMIT 8");
$mapel_preview = fetch_all("SELECT id_mapel, nama_mapel, kategori FROM mapel ORDER BY nama_mapel LIMIT 12");
$siswa_preview = fetch_all("SELECT id_siswa, nama, nisn FROM siswa ORDER BY nama LIMIT 6");
$siswa_count = fetch_one("SELECT COUNT(*) as c FROM siswa")['c'] ?? 0;
$guru_count  = fetch_one("SELECT COUNT(*) as c FROM guru")['c'] ?? 0;
$kelas_count = fetch_one("SELECT COUNT(*) as c FROM kelas")['c'] ?? 0;
$mapel_count = fetch_one("SELECT COUNT(*) as c FROM mapel")['c'] ?? 0;

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $row = fetch_one("SELECT id_berita, gambar, created_by, id_guru FROM berita WHERE id_berita = ?", "i", [$id]);
    if (!$row) {
        flash_set('error','Berita tidak ditemukan.');
    } else {
        $isOwner = false;
        if (!empty($row['created_by']) && $userId && intval($row['created_by']) === intval($userId)) $isOwner = true;
        if (!empty($row['id_guru']) && $cur_guru_id && intval($row['id_guru']) === intval($cur_guru_id)) $isOwner = true;

        if ($isOwner) {
            if (!empty($row['gambar'])) {
                $uploadDir = __DIR__ . '/../../uploads/';
                $f = $uploadDir . $row['gambar'];
                if (file_exists($f)) @unlink($f);
            }
            $stmt = $conn->prepare("DELETE FROM berita WHERE id_berita = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            flash_set('success','Berita berhasil dihapus.');
        } else {
            flash_set('error','Anda tidak berwenang menghapus berita ini.');
        }
    }
    header('Location: /ProfilJurusan/guru/dashboard.php');
    exit;
}
$uid = intval($userId ?: 0);
$gid = intval($cur_guru_id ?: 0);

$berita = fetch_all("
    SELECT b.*, g.nama AS nama_guru, u.username AS nama_user
    FROM berita b
    LEFT JOIN guru g ON b.id_guru = g.id_guru
    LEFT JOIN user u ON b.created_by = u.id_user
    WHERE b.tanggal_post IS NOT NULL
       OR b.created_by = ?
       OR b.id_guru = ?
    ORDER BY (b.tanggal_post IS NULL) DESC, COALESCE(b.tanggal_post, b.id_berita) DESC
    LIMIT 50
", "ii", [$uid, $gid]);

function berita_is_owner(array $b, array $u) {
    $userId = $u['id_user'] ?? ($u['id'] ?? null);
    $guruId = $u['id_guru'] ?? null;
    if (!empty($b['created_by']) && $userId && intval($b['created_by']) === intval($userId)) return true;
    if (!empty($b['id_guru']) && $guruId && intval($b['id_guru']) === intval($guruId)) return true;
    return false;
}
?>

<div class="card mb-4 shadow-sm border-0 overflow-hidden" 
     style="background: url('/ProfilJurusan/assets/bg/background.png') no-repeat center center; 
            background-size: cover; min-height: 350px;">
</div>

<div class="row">
  <div class="col-md-8">
    <div class="card mb-4 p-4 shadow-sm">
      <h1 class="mb-1 fw-bold">Dashboard Guru</h1>
      <p class="mb-0 text-muted">Halo, <?=htmlspecialchars($username)?> 👋</p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="fw-semibold">Berita Terbaru</h4>
      <a href="/ProfilJurusan/guru/berita_add.php" class="btn btn-success">+ Tambah Berita</a>
    </div>

    <?php if (empty($berita)): ?>
      <div class="alert alert-info">Belum ada berita.</div>
    <?php else: foreach($berita as $b):
        $owner = berita_is_owner($b, $u);
        $author = $b['nama_user'] ?: ($b['nama_guru'] ?: 'Admin');
        if ($owner) {
            if (!empty($b['id_guru']) && $cur_guru_id && intval($b['id_guru']) === intval($cur_guru_id)) {
                $author = $b['nama_guru'] ?: $username;
            } else {
                $author = $b['nama_user'] ?: $username;
            }
        }
    ?>
      <div class="card mb-4 shadow-sm overflow-hidden <?= empty($b['tanggal_post']) ? 'border border-warning' : '' ?>">
        <div class="row g-0">
          <?php if (!empty($b['gambar'])): ?>
            <div class="col-md-5 d-none d-md-block" style="max-height:220px;overflow:hidden">
              <img src="/ProfilJurusan/uploads/<?=htmlspecialchars($b['gambar'])?>" class="img-fluid h-100 w-100" style="object-fit:cover" alt="gambar berita">
            </div>
          <?php endif; ?>

          <div class="<?= !empty($b['gambar']) ? 'col-md-7' : 'col-12' ?>">
            <div class="p-3 d-flex flex-column h-100">
              <div class="small text-muted mb-2">
                <?php if (!empty($b['tanggal_post'])): ?>
                  <?=htmlspecialchars($b['tanggal_post'])?>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Pending</span>
                <?php endif; ?>
                · <?=htmlspecialchars($author)?>
              </div>

              <h5 class="fw-bold mb-2"><?=htmlspecialchars($b['judul'] ?? 'Tanpa Judul')?></h5>
              <p class="flex-grow-1 mb-2"><?=nl2br(htmlspecialchars(strlen($b['isi'])>200?substr($b['isi'],0,200).'...':$b['isi']))?></p>

              <div class="d-flex justify-content-between align-items-center mt-auto">
                <?php if (!empty($b['tanggal_post'])): ?>
                  <a href="/ProfilJurusan/guru/berita_detail.php?id=<?= (int)$b['id_berita'] ?>" class="btn btn-outline-dark">Baca selengkapnya</a>
                <?php else: ?>
                  <span class="small text-muted">Menunggu persetujuan admin...</span>
                <?php endif; ?>

                <?php if ($owner): ?>
                  <div class="d-flex gap-2">
                    <a href="/ProfilJurusan/guru/berita_edit.php?id=<?= (int)$b['id_berita'] ?>" class="btn btn-primary">Edit</a>
                    <a href="/ProfilJurusan/guru/dashboard.php?hapus=<?= (int)$b['id_berita'] ?>" class="btn btn-danger" onclick="return confirm('Hapus berita ini?')">Hapus</a>
                  </div>
                <?php endif; ?>
              </div>

            </div>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>

    <div class="card p-4 mb-4 shadow-sm">
      <h4 class="fw-semibold">Tentang Jurusan</h4>
      <p class="mb-2"><strong>Jurusan Rekayasa Perangkat Lunak (RPL)</strong> 
        <br>Jurusan Rekayasa Perangkat Lunak (RPL) adalah bidang keahlian yang berfokus pada pengembangan perangkat lunak, mulai dari analisis kebutuhan, perancangan, implementasi, pengujian, hingga pemeliharaan sistem</p>
      <ul class="mb-0">
        <li><strong>Visi:</strong> Menjadi jurusan unggul di bidang Rekayasa Perangkat Lunak yang menghasilkan lulusan berkompeten dan berkarakter.</li>
        <li><strong>Misi:</strong>
          <ul class="mb-0">
            <li>Menyelenggarakan pembelajaran berbasis praktik dan proyek.</li>
            <li>Mengembangkan keterampilan teknis dan soft-skill siswa.</li>
            <li>Membangun kerjasama dengan industri lokal.</li>
          </ul>
        </li>
      </ul>
    </div>

    <div class="card p-4 mb-4 shadow-sm">
      <h5 class="fw-semibold">Mata Pelajaran</h5>
      <div class="d-flex flex-wrap gap-2 mb-2">
        <?php foreach($mapel_preview as $m): ?>
          <span class="badge bg-light text-dark border"><?=htmlspecialchars($m['nama_mapel'])?> <small class="text-muted">/ <?=htmlspecialchars($m['kategori'])?></small></span>
        <?php endforeach; ?>
      </div>
      <a href="/ProfilJurusan/guru/mapel.php" class="btn btn-outline-primary">Lihat semua mapel</a>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card mb-4 p-4 shadow-sm">
      <h6 class="fw-semibold mb-3">Statistik</h6>
      <div class="row text-center">
        <div class="col-6 border-end"><div class="h3 mb-0"><?=$siswa_count?></div><small class="text-muted">Siswa</small></div>
        <div class="col-6"><div class="h3 mb-0"><?=$guru_count?></div><small class="text-muted">Guru</small></div>
      </div>
      <div class="row text-center mt-3">
        <div class="col-6 border-end"><div class="h4 mb-0"><?=$kelas_count?></div><small class="text-muted">Kelas</small></div>
        <div class="col-6"><div class="h4 mb-0"><?=$mapel_count?></div><small class="text-muted">Mapel</small></div>
      </div>
    </div>

    <div class="card mb-4 p-4 shadow-sm">
      <h6 class="fw-semibold mb-3">Beberapa Guru</h6>
      <ul class="list-group list-group-flush">
        <?php foreach($guru_preview as $g): ?>
          <li class="list-group-item"><strong><?=htmlspecialchars($g['nama'])?></strong><br><small class="text-muted"><?=htmlspecialchars($g['nip'] ?? '-')?></small></li>
        <?php endforeach; ?>
      </ul>
      <div class="mt-2"><a href="/ProfilJurusan/guru/guru.php" class="btn btn-outline-primary">Lihat semua guru</a></div>
    </div>

    <div class="card mb-4 p-4 shadow-sm">
      <h6 class="fw-semibold mb-3">Daftar Kelas</h6>
      <div class="list-group list-group-flush">
        <?php foreach($kelas_preview as $k): ?>
          <div class="list-group-item"><?=htmlspecialchars($k['tingkat'].' - '.$k['kelas'])?></div>
        <?php endforeach; ?>
      </div>
      <div class="mt-2"><a href="/ProfilJurusan/guru/kelas.php" class="btn btn-outline-primary">Lihat semua kelas</a></div>
    </div>

    <div class="card mb-4 p-4 shadow-sm">
      <h6 class="fw-semibold mb-3">Beberapa Siswa</h6>
      <ul class="list-group list-group-flush">
        <?php foreach($siswa_preview as $s): ?>
          <li class="list-group-item"><strong><?=htmlspecialchars($s['nama'])?></strong><br><small class="text-muted">NISN: <?=htmlspecialchars($s['nisn'] ?? '-')?></small></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="card p-4 shadow-sm">
      <h6 class="fw-semibold mb-3">Kontak sekolah</h6>
      <p class="small text-muted mb-1">Alamat: JL. Raya Padalarang No. 451</p>
      <p class="small text-muted mb-1">Instagram: @smkn4padalarang</p>
      <p class="small text-muted mb-0">Email: smkn4.padalarang@gmail.com</p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../inc/footer.php'; ?>
