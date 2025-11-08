<?php
require_once __DIR__ . '/../koneksi.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function current_user(){
    return $_SESSION['user'] ?? null;
}
function is_logged_in(){ return current_user() !== null; }

function require_role($roles = []){
    if(!is_logged_in()){
        header('Location: /ProfilJurusan/auth/login.php'); exit;
    }
    $u = current_user();
    if(!in_array($u['role'], (array)$roles)){
        http_response_code(403);
        echo "Access denied."; exit;
    }
}

function require_login(){
    if(!is_logged_in()){
        header('Location: /ProfilJurusan/auth/login.php'); exit;
    }
}

function fetch_all($sql, $types = '', $params = []){
    global $conn;
    $stmt = $conn->prepare($sql);
    if($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
function fetch_one($sql, $types = '', $params = []){
    $rows = fetch_all($sql, $types, $params);
    return $rows[0] ?? null;
}

function flash_set($key, $msg){
    $_SESSION['flash'][$key] = $msg;
}
function flash_get($key){
    $v = $_SESSION['flash'][$key] ?? null;
    if(isset($_SESSION['flash'][$key])) unset($_SESSION['flash'][$key]);
    return $v;
}

function current_user_ampu_mapel() {
    global $conn;
    $user = current_user();
    
    if ($user['role'] !== 'guru' || empty($user['id_guru'])) {
        return [];
    }
    
    $id_guru = $user['id_guru'];
    $query = "SELECT m.id_mapel, m.nama_mapel, k.id_kelas, k.tingkat, k.kelas
              FROM mapel_diampu md 
              JOIN mapel m ON md.id_mapel = m.id_mapel 
              JOIN kelas k ON md.id_kelas = k.id_kelas
              WHERE md.id_guru = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id_guru);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}
