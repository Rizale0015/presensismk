<?php
require_once 'database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

try {
    // Count total guru
    $query = "SELECT COUNT(*) as total FROM guru";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_guru = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Count total siswa
    $query = "SELECT COUNT(*) as total FROM siswa";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_siswa = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Count total kelas
    $query = "SELECT COUNT(*) as total FROM kelas";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_kelas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Count today's attendance
    $today = date('Y-m-d');
    $query = "SELECT COUNT(*) as total FROM absen_guru WHERE tanggal = :tanggal";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':tanggal', $today);
    $stmt->execute();
    $absen_hari_ini = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'total_guru' => $total_guru,
        'total_siswa' => $total_siswa,
        'total_kelas' => $total_kelas,
        'absen_hari_ini' => $absen_hari_ini
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

