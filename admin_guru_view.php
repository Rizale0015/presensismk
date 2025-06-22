<?php
require_once 'session.php';
require_once 'database.php';
checkAdminAccess();

if (!isset($_GET['nik'])) {
    echo json_encode(['error' => 'NIK tidak ditemukan']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT g.*, v.password, v.password_plain FROM guru g LEFT JOIN verifikasi_absen v ON g.nik = v.nik WHERE g.nik = :nik");
$stmt->bindParam(':nik', $_GET['nik']);
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
    // Jika password di-hash, tampilkan tetap (plaintext tidak bisa didapat dari hash)
    // Jika ingin plaintext, Anda harus simpan password asli (tidak direkomendasikan untuk produksi)
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Data tidak ditemukan']);
}
?>
