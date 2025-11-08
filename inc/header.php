<?php
require_once __DIR__ . '/functions.php';
$u = current_user();
$role = $u['role'] ?? null;
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profil Jurusan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="/ProfilJurusan/assets/css/style.css?v=<?=time()?>">

</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">

    <img src="../assets/logo/logopplgH3.png" alt="logo" style="height:75px; vertical-align:middle; margin-right:8px;">
    
    <p class="navbar-brand fw-bold text-primary" style="margin-top: 10px;">ProfilJurusan</p>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto">

        <?php if(!$u): ?>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>" href="/ProfilJurusan/index.php"><i class="fa fa-home"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='siswa.php'?'active':'' ?>" href="/ProfilJurusan/guest/siswa.php">Siswa</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='guru.php'?'active':'' ?>" href="/ProfilJurusan/guest/guru.php">Guru</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='kelas.php'?'active':'' ?>" href="/ProfilJurusan/guest/kelas.php">Kelas</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='mapel.php'?'active':'' ?>" href="/ProfilJurusan/guest/mapel.php">Mapel</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='nilai.php'?'active':'' ?>" href="/ProfilJurusan/guest/nilai.php">Nilai</a></li>
        <li class="nav-item"><a class="btn btn-primary ms-2" href="/ProfilJurusan/auth/login.php">Login</a></li>


        <?php elseif($role === 'admin'): ?>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':'' ?>" href="/ProfilJurusan/admin/dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='siswa.php'?'active':'' ?>" href="/ProfilJurusan/admin/siswa.php">Siswa</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='guru.php'?'active':'' ?>" href="/ProfilJurusan/admin/guru.php">Guru</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='kelas.php'?'active':'' ?>" href="/ProfilJurusan/admin/kelas.php">Kelas</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='mapel.php'?'active':'' ?>" href="/ProfilJurusan/admin/mapel.php">Mapel</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='nilai.php'?'active':'' ?>" href="/ProfilJurusan/admin/nilai.php">Nilai</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='akun.php'?'active':'' ?>" href="/ProfilJurusan/admin/akun.php">Akun</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='absensi.php'?'active':'' ?>" href="/ProfilJurusan/admin/absensi.php">Absen</a></li>
        <li class="nav-item"><a class="btn btn-danger" href="/ProfilJurusan/auth/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>


        <?php elseif($role === 'guru'): ?>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':'' ?>" href="/ProfilJurusan/guru/dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='siswa.php'?'active':'' ?>" href="/ProfilJurusan/guru/siswa.php">Siswa</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='guru.php'?'active':'' ?>" href="/ProfilJurusan/guru/guru.php">Guru</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='kelas.php'?'active':'' ?>" href="/ProfilJurusan/guru/kelas.php">Kelas</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='mapel.php'?'active':'' ?>" href="/ProfilJurusan/guru/mapel.php">Mapel</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='nilai.php'?'active':'' ?>" href="/ProfilJurusan/guru/nilai.php">Nilai</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='absensi.php'?'active':'' ?>" href="/ProfilJurusan/guru/absensi.php">Absen</a></li>
        <li class="nav-item"><a class="btn btn-danger" href="/ProfilJurusan/auth/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>

        <?php elseif($role === 'siswa'): ?>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':'' ?>" href="/ProfilJurusan/siswa/dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='siswa.php'?'active':'' ?>" href="/ProfilJurusan/siswa/siswa.php">Siswa</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='guru.php'?'active':'' ?>" href="/ProfilJurusan/siswa/guru.php">Guru</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='kelas.php'?'active':'' ?>" href="/ProfilJurusan/siswa/kelas.php">Kelas</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='mapel.php'?'active':'' ?>" href="/ProfilJurusan/siswa/mapel.php">Mapel</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='nilai.php'?'active':'' ?>" href="/ProfilJurusan/siswa/nilai.php">Nilai</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='absensi.php'?'active':'' ?>" href="/ProfilJurusan/siswa/absensi.php">Absen</a></li>
        <li class="nav-item"><a class="btn btn-danger" href="/ProfilJurusan/auth/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>

        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<main class="container py-4">
