<?php
require_once 'session.php';
require_once 'database.php';
date_default_timezone_set('Asia/Jakarta');

checkGuruAccess();

$database = new Database();
$db = $database->getConnection();

$nik = $_SESSION['nik'];
$today = date('Y-m-d');
$current_time = date('H:i:s');
$current_day = date('l');

// Translate day to Indonesian
$days = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa', 
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];
$hari = $days[$current_day];

// Handle form submission
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'absen_datang':
                // Check if already absent today
                $check_query = "SELECT * FROM absen_guru WHERE nik = :nik AND tanggal = :tanggal";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(':nik', $nik);
                $check_stmt->bindParam(':tanggal', $today);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $error = "Anda sudah melakukan absensi datang hari ini!";
                } else {
                // Fix: Do not insert id_absen, let DB auto-increment it
                $query = "INSERT INTO absen_guru (nik, jam_datang, tanggal, hari, keterangan) 
                         VALUES (:nik, :jam_datang, :tanggal, :hari, :keterangan)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nik', $nik);
                $stmt->bindParam(':jam_datang', $current_time);
                $stmt->bindParam(':tanggal', $today);
                $stmt->bindParam(':hari', $hari);
                $keterangan = $_POST['keterangan'] ?? 'Hadir';
                $stmt->bindParam(':keterangan', $keterangan);
                
                if ($stmt->execute()) {
                    $success = "Absensi datang berhasil dicatat!";
                } else {
                    $error = "Gagal mencatat absensi datang!";
                }
                }
                break;
        }
    }
}

// Get today's attendance
$query = "SELECT * FROM absen_guru WHERE nik = :nik AND tanggal = :tanggal";
$stmt = $db->prepare($query);
$stmt->bindParam(':nik', $nik);
$stmt->bindParam(':tanggal', $today);
$stmt->execute();
$today_attendance = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent attendance history
$query = "SELECT * FROM absen_guru WHERE nik = :nik ORDER BY tanggal DESC LIMIT 7";
$stmt = $db->prepare($query);
$stmt->bindParam(':nik', $nik);
$stmt->execute();
$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Guru</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Panggil Bootstrap dan Font Awesome di sini -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'guru_header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include 'guru_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h5>Absensi Hari Ini - <?php echo date('d F Y'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Absensi Datang</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($today_attendance && $today_attendance['jam_datang']): ?>
                                            <div class="alert alert-success">
                                                <strong>Sudah Absen Datang</strong><br>
                                                Jam: <?php echo $today_attendance['jam_datang']; ?><br>
                                                Keterangan: <?php echo $today_attendance['keterangan']; ?>
                                            </div>
                                        <?php else: ?>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="absen_datang">
                                                <div class="mb-3">
                                                    <label class="form-label">Keterangan</label>
                                                    <select class="form-control" name="keterangan" required>
                                                        <option value="Hadir">Hadir</option>
                                                        <option value="Terlambat">Terlambat</option>
                                                        <option value="Sakit">Sakit</option>
                                                        <option value="Izin">Izin</option>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100">
                                                    Absen Datang (<?php echo date('H:i:s'); ?>)
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                    <div class="card-body">
                        <!-- Removed jam pulang feature as per user request -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Riwayat Absensi (7 Hari Terakhir)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Hari</th>
                                        <th>Jam Datang</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_attendance)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Belum ada data absensi</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_attendance as $attendance): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($attendance['tanggal'])); ?></td>
                                            <td><?php echo $attendance['hari']; ?></td>
                                            <td><?php echo $attendance['jam_datang'] ?? '-'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($attendance['keterangan']) {
                                                        'Hadir' => 'success',
                                                        'Terlambat' => 'warning',
                                                        'Sakit' => 'danger',
                                                        'Izin' => 'info',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo $attendance['keterangan']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh page every 30 seconds to update time
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>