<?php
require_once '../database.php';
header('Content-Type: application/json');

date_default_timezone_set('Asia/Jakarta');


if (!isset($_GET['nik'])) {
    echo json_encode(['error' => 'NIK tidak disediakan']);
    exit;
}

$nik = $_GET['nik'];
$tanggal = date('Y-m-d');

$database = new Database();
$db = $database->getConnection();

$query = "SELECT jam_datang, jam_pulang FROM absen_guru WHERE nik = :nik AND tanggal = :tanggal LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':nik', $nik);
$stmt->bindParam(':tanggal', $tanggal);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($data);
} else {
    echo json_encode(['jam_datang' => null, 'jam_pulang' => null]);
}
