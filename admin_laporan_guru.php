<?php
require_once 'session.php';
require_once 'database.php';
checkAdminAccess();

$database = new Database();
$db = $database->getConnection();

// Query untuk laporan kehadiran guru
$queryGuruAttendance = "
    SELECT g.nik, g.namaptk, 
           COUNT(CASE WHEN ag.keterangan = 'Hadir' THEN 1 END) as jumlah_hadir,
           COUNT(ag.id_absen) as total_absensi
    FROM guru g
    LEFT JOIN absen_guru ag ON g.nik = ag.nik
    GROUP BY g.nik, g.namaptk
    ORDER BY g.namaptk
";
$stmt = $db->prepare($queryGuruAttendance);
$stmt->execute();
$guruAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Guru - Sistem Presensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                <?php include 'admin_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <h3 class="mt-4">Laporan Kehadiran Guru</h3>
                
                <div class="table-responsive mt-4">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>NIK</th>
                                <th>Nama Guru</th>
                                <th>Jumlah Hadir</th>
                                <th>Total Absensi</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($guruAttendance as $guru): ?>
                                <tr>
                                    <td><?php echo $guru['nik']; ?></td>
                                    <td><?php echo $guru['namaptk']; ?></td>
                                    <td><?php echo $guru['jumlah_hadir']; ?></td>
                                    <td><?php echo $guru['total_absensi']; ?></td>
                                    <td>
                                        <?php 
                                        $percentage = ($guru['total_absensi'] > 0) 
                                            ? round(($guru['jumlah_hadir'] / $guru['total_absensi']) * 100, 2) 
                                            : 0;
                                        echo $percentage . '%';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>