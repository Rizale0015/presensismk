<?php
require_once '../session.php';
require_once '../database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['type'], $input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$type = $input['type'];
$action = $input['action'];

try {
    if ($type === 'pelajaran') {
        if ($action === 'add' && !empty($input['name'])) {
            $stmt = $db->prepare("INSERT INTO pelajaran (nama_pelajaran) VALUES (:name)");
            $stmt->bindParam(':name', $input['name']);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;
        } elseif ($action === 'delete' && !empty($input['id'])) {
            $stmt = $db->prepare("DELETE FROM pelajaran WHERE id_pelajaran = :id");
            $stmt->bindParam(':id', $input['id']);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;
        }
    } elseif ($type === 'jam') {
        if ($action === 'add' && !empty($input['time'])) {
            // Expect time format "HH:MM - HH:MM"
            $times = explode('-', $input['time']);
            if (count($times) === 2) {
                $start = trim($times[0]);
                $end = trim($times[1]);
                $stmt = $db->prepare("INSERT INTO jam (jam_mulai, jam_selesai) VALUES (:start, :end)");
                $stmt->bindParam(':start', $start);
                $stmt->bindParam(':end', $end);
                $stmt->execute();
                echo json_encode(['success' => true]);
                exit;
            }
            echo json_encode(['success' => false, 'message' => 'Invalid time format']);
            exit;
        } elseif ($action === 'delete' && !empty($input['id'])) {
            $stmt = $db->prepare("DELETE FROM jam WHERE id_jam = :id");
            $stmt->bindParam(':id', $input['id']);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;
        }
    } elseif ($type === 'hari') {
        if ($action === 'add' && !empty($input['name'])) {
            $stmt = $db->prepare("INSERT INTO hari (nama_hari) VALUES (:name)");
            $stmt->bindParam(':name', $input['name']);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;
        } elseif ($action === 'delete' && !empty($input['id'])) {
            $stmt = $db->prepare("DELETE FROM hari WHERE id_hari = :id");
            $stmt->bindParam(':id', $input['id']);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Invalid action or missing parameters']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
