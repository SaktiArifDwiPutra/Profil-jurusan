<?php
session_start();
require_once __DIR__ . '/inc/functions.php';

if (isset($_SESSION['user']['role'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: admin/dashboard.php"); exit;
    } elseif ($_SESSION['user']['role'] === 'guru') {
        header("Location: guru/dashboard.php"); exit;
    } elseif ($_SESSION['user']['role'] === 'siswa') {
        header("Location: siswa/dashboard.php"); exit;
    }
}

include __DIR__ . '/inc/header2.php';

$siswa_count = fetch_one("SELECT COUNT(*) as c FROM siswa")['c'] ?? 0;
$guru_count  = fetch_one("SELECT COUNT(*) as c FROM guru")['c'] ?? 0;
$kelas_count = fetch_one("SELECT COUNT(*) as c FROM kelas")['c'] ?? 0;
$mapel_count = fetch_one("SELECT COUNT(*) as c FROM mapel")['c'] ?? 0;

$berita = fetch_all("
    SELECT b.*, g.nama as nama_guru, u.username as nama_user, u.role as role_user
    FROM berita b
    LEFT JOIN guru g ON b.id_guru = g.id_guru
    LEFT JOIN user u ON b.id_user = u.id_user
    ORDER BY b.tanggal_post DESC
    LIMIT 5
");

$guru   = fetch_all("SELECT id_guru, nama, nip FROM guru ORDER BY nama LIMIT 6");
$kelas  = fetch_all("SELECT id_kelas, tingkat, kelas FROM kelas ORDER BY tingkat LIMIT 8");
$mapel  = fetch_all("SELECT id_mapel, nama_mapel, kategori FROM mapel ORDER BY nama_mapel LIMIT 8");
$siswa_preview = fetch_all("SELECT id_siswa, nama, nisn FROM siswa ORDER BY nama LIMIT 6");
?>

<div class="card mb-4 shadow-sm border-0 overflow-hidden" 
     style="background: url('/ProfilJurusan/assets/bg/background.png') no-repeat center center; 
            background-size: cover; min-height: 350px;">
</div>

<div class="row">
  <div class="col-md-8">
    <div class="card mb-4 p-4 shadow-sm">
      <h1 class="mb-1 fw-bold">Dashboard</h1>
      <p class="mb-0 text-muted">Selamat datang di web Profil Jurusan RPL 👋</p>
      <p class="mb-0 text-muted">Silahkan login untuk membuka berbagai fitur</p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="fw-semibold">Berita Terbaru</h4>
    </div>

    <?php if (count($berita) === 0): ?>
      <div class="alert alert-info">Belum ada berita.</div>
    <?php else: ?>
      <?php foreach ($berita as $b): ?>
        <div class="card mb-4 shadow-sm overflow-hidden">
          <div class="row g-0">
            <?php if (!empty($b['gambar'])): ?>
              <div class="col-md-5 d-none d-md-block" style="max-height:220px; overflow:hidden;">
                <img src="/ProfilJurusan/uploads/<?=htmlspecialchars($b['gambar'])?>" 
                     class="img-fluid h-100 w-100" 
                     style="object-fit:cover;" alt="gambar berita">
              </div>
            <?php endif; ?>

            <div class="<?= !empty($b['gambar']) ? 'col-md-7' : 'col-12' ?>">
              <div class="p-3 d-flex flex-column h-100">
                <div class="small text-muted mb-2">
                  <?=htmlspecialchars($b['tanggal_post'] ?? '-')?> 
                  &middot; 
                  <?php 
                    if (!empty($b['nama_user'])) {
                        echo htmlspecialchars(ucfirst($b['role_user']))." - ".htmlspecialchars($b['nama_user']);
                    } elseif (!empty($b['nama_guru'])) {
                        echo htmlspecialchars("Guru - ".$b['nama_guru']);
                    } else {
                        echo "Admin";
                    }
                  ?>
                </div>

                <h5 class="fw-bold mb-2"><?=htmlspecialchars($b['judul'] ?? 'Tanpa Judul')?></h5>
                <p class="flex-grow-1 mb-2">
                  <?= nl2br(htmlspecialchars(strlen($b['isi']) > 200 ? substr($b['isi'],0,200).'...' : $b['isi'])) ?>
                </p>

                <div class="d-flex justify-content-between align-items-center mt-auto">
                  <a href="/ProfilJurusan/guest/berita_detail.php?id=<?=$b['id_berita']?>" class="btn btn-outline-dark">Baca selengkapnya</a>
                </div>

              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <div class="card p-4 mb-4 shadow-sm">
      <h4 class="fw-semibold">Tentang Jurusan</h4>
      <p>Deskripsi singkat jurusan bisa ditaruh di sini. Jelaskan visi, misi, kompetensi, dan keunggulan jurusan secara ringkas.</p>
      <ul class="mb-0">
        <li>Visi: Menjadi jurusan unggul di bidang ...</li>
        <li>Misi: Memberikan pembelajaran praktik terbaik, menumbuhkan soft-skill siswa, dsb.</li>
      </ul>
    </div>

    <div class="card p-4 mb-4 shadow-sm">
      <h5 class="fw-semibold">Mata Pelajaran</h5>
      <div class="d-flex flex-wrap gap-2 mb-2">
        <?php foreach($mapel as $m): ?>
          <span class="badge bg-light text-dark border">
            <?=htmlspecialchars($m['nama_mapel'])?> 
            <small class="text-muted">/ <?=htmlspecialchars($m['kategori'])?></small>
          </span>
        <?php endforeach; ?>
      </div>
      <a href="/ProfilJurusan/guest/mapel.php" class="btn btn-outline-primary">Lihat semua mapel</a>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card mb-4 p-4 shadow-sm">
      <h6 class="fw-semibold mb-3">Statistik</h6>
      <div class="row text-center">
        <div class="col-6 border-end">
          <div class="h3 mb-0"><?=$siswa_count?></div>
          <small class="text-muted">Siswa</small>
        </div>
        <div class="col-6">
          <div class="h3 mb-0"><?=$guru_count?></div>
          <small class="text-muted">Guru</small>
        </div>
      </div>
      <div class="row text-center mt-3">
        <div class="col-6 border-end">
          <div class="h4 mb-0"><?=$kelas_count?></div>
          <small class="text-muted">Kelas</small>
        </div>
        <div class="col-6">
          <div class="h4 mb-0"><?=$mapel_count?></div>
          <small class="text-muted">Mapel</small>
        </div>
      </div>
    </div>

    <div class="card mb-4 p-4 shadow-sm">
      <h6 class="fw-semibold mb-3">Beberapa Guru</h6>
      <ul class="list-group list-group-flush">
        <?php if(count($guru)===0): ?>
          <li class="list-group-item">Belum ada data guru.</li>
        <?php else: ?>
          <?php foreach($guru as $g): ?>
            <li class="list-group-item">
              <strong><?=htmlspecialchars($g['nama'])?></strong><br>
              <small class="text-muted"><?=htmlspecialchars($g['nip'] ?? '-')?></small>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
      <div class="mt-2"><a href="/ProfilJurusan/guest/guru.php" class="btn btn-outline-primary btn-sm">Lihat semua guru</a></div>
    </div>

    <div class="card mb-4 p-4 shadow-sm">
      <h6 class="fw-semibold mb-3">Daftar Kelas</h6>
      <?php if(count($kelas)===0): ?>
        <div class="small text-muted">Belum ada kelas.</div>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach($kelas as $k): ?>
            <div class="list-group-item"><?=htmlspecialchars($k['tingkat'].' / '.$k['kelas'])?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="mt-2"><a href="/ProfilJurusan/guest/kelas.php" class="btn btn-outline-primary btn-sm">Lihat semua kelas</a></div>
    </div>

    <div class="card mb-4 p-4 shadow-sm">
      <h6 class="fw-semibold mb-3">Beberapa Siswa</h6>
      <ul class="list-group list-group-flush">
        <?php if(count($siswa_preview)===0): ?>
          <li class="list-group-item">Belum ada data siswa.</li>
        <?php else: ?>
          <?php foreach($siswa_preview as $s): ?>
            <li class="list-group-item">
              <strong><?=htmlspecialchars($s['nama'])?></strong><br>
              <small class="text-muted">NISN: <?=htmlspecialchars($s['nisn'] ?? '-')?></small>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
      <div class="mt-2">
        <a href="/ProfilJurusan/guest/siswa.php" class="btn btn-outline-primary">Lihat semua siswa</a>
      </div>
    </div>

    <div class="card p-4 shadow-sm">
      <h6 class="fw-semibold mb-3">Kontak</h6>
      <p class="small text-muted mb-1">Alamat: Sekolah / Jurusan</p>
      <p class="small text-muted mb-1">Telepon: (021) xxxx xxxx</p>
      <p class="small text-muted mb-0">Email: sekretariat@sekolah.sch.id</p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>
