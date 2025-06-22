<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['nik'])) {
    echo json_encode(['error' => 'NIK parameter required']);
    exit;
}

$nik = $_GET['nik'];
$today = date('Y-m-d');

try {
    $query = "SELECT jam_datang, jam_pulang, keterangan FROM absen_guru WHERE nik = :nik AND tanggal = :today";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nik', $nik);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attendance) {
        echo json_encode([
            'status' => 'success',
            'data' => $attendance
        ]);
    } else {
        echo json_encode([
            'status' => 'not_found',
            'message' => 'Belum ada data absensi hari ini'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
// ...existing code...