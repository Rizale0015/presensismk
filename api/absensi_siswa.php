<?php
require_once '../session.php';
require_once '../database.php';
checkAdminAccess();

$database = new Database();
$db = $database->getConnection();

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : null;

// Base query with joins
$query = "SELECT a.*, s.nama AS nama_siswa, k.nama_kelas 
          FROM absen_siswa a 
          LEFT JOIN siswa s ON a.nis = s.nis 
          LEFT JOIN kelas k ON a.id_kelas = k.id_kelas
          WHERE 1=1 ";

// Parameters array
$params = [];

// Filter by kelas if provided
if ($id_kelas) {
    $query .= " AND a.id_kelas = :id_kelas ";
    $params[':id_kelas'] = $id_kelas;
}

// Search filter on student name or keterangan
if ($search) {
    $query .= " AND (s.nama LIKE :search OR a.keterangan LIKE :search) ";
    $params[':search'] = '%' . $search . '%';
}

// Order and limit
$query .= " ORDER BY a.id_tanggal DESC, a.jam_hadir DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);

// Bind parameters
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$absensiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM absen_siswa a 
               LEFT JOIN siswa s ON a.nis = s.nis 
               LEFT JOIN kelas k ON a.id_kelas = k.id_kelas
               WHERE 1=1 ";

if ($id_kelas) {
    $countQuery .= " AND a.id_kelas = :id_kelas ";
}
if ($search) {
    $countQuery .= " AND (s.nama LIKE :search OR a.keterangan LIKE :search) ";
}

$countStmt = $db->prepare($countQuery);

if ($id_kelas) {
    $countStmt->bindValue(':id_kelas', $id_kelas);
}
if ($search) {
    $countStmt->bindValue(':search', '%' . $search . '%');
}

$countStmt->execute();
$total = $countStmt->fetchColumn();

header('Content-Type: application/json');
echo json_encode([
    'data' => $absensiList,
    'total' => $total,
]);
?>
