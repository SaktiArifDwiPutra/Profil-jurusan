<?php
session_start();
require_once __DIR__ . '/../inc/functions.php'; 

// redirect login
if (!empty($_SESSION['user']['role'])) {
    $role = $_SESSION['user']['role'];
    if ($role === 'admin') header("Location: /ProfilJurusan/admin/dashboard.php");
    elseif ($role === 'guru') header("Location: /ProfilJurusan/guru/dashboard.php");
    elseif ($role === 'siswa') header("Location: /ProfilJurusan/siswa/dashboard.php");
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id_user, username, password, role, id_guru, id_siswa FROM user WHERE username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $sess = [
                    'id_user'  => (int)$user['id_user'],
                    'username' => $user['username'],
                    'role'     => $user['role'],
                    'id_guru'  => !empty($user['id_guru']) ? (int)$user['id_guru'] : null,
                    'id_siswa' => !empty($user['id_siswa']) ? (int)$user['id_siswa'] : null,
                ];

                $display_name = $user['username'];
                if (!empty($sess['id_guru'])) {
                    $g = fetch_one("SELECT nama FROM guru WHERE id_guru = ? LIMIT 1", "i", [$sess['id_guru']]);
                    if ($g && !empty($g['nama'])) $display_name = $g['nama'];
                } elseif (!empty($sess['id_siswa'])) {
                    $s = fetch_one("SELECT nama FROM siswa WHERE id_siswa = ? LIMIT 1", "i", [$sess['id_siswa']]);
                    if ($s && !empty($s['nama'])) $display_name = $s['nama'];
                } else {
                    if ($sess['role'] === 'admin') $display_name = 'Admin';
                }

                $sess['display_name'] = $display_name;
                $_SESSION['user'] = $sess;

                if ($sess['role'] === 'admin') {
                    header("Location: /ProfilJurusan/admin/dashboard.php"); exit;
                } elseif ($sess['role'] === 'guru') {
                    header("Location: /ProfilJurusan/guru/dashboard.php"); exit;
                } else {
                    header("Location: /ProfilJurusan/siswa/dashboard.php"); exit;
                }
            } else {
                $error = 'Username atau password salah.';
            }
        } else {
            $error = 'Kesalahan server (prepare).';
        }
    }
}

include __DIR__ . '/../inc/head.php'; 
?>

<style>
  body {
    background: url('/ProfilJurusan/assets/bg/gelap.jpg') no-repeat center center fixed;
    background-size: cover;
  }
  .card {
    background-color: rgba(255,255,255,0.85); 
  }
</style>

<div class="d-flex justify-content-center align-items-center vh-100">
  <div class="card p-4 shadow" style="max-width: 420px; width:100%">
    <h4 class="mb-3 text-center">Login</h4>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($username) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>

      <div class="d-grid">
        <button type="submit" class="btn btn-primary">Login</button>
      </div>
    </form>

    <div class="text-center mt-3">
      <small>Masuk sebagai <a href="/ProfilJurusan/index.php">tamu</a></small>
    </div>
  </div>
</div>

