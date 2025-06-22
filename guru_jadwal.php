<?php
require_once 'session.php';
require_once 'database.php';
checkGuruAccess();

$database = new Database();
$db = $database->getConnection();

$nik = $_SESSION['nik'];
$queryJadwal = "SELECT jp.id, p.nama_pelajaran, k.nama_kelas, j.jam_mulai, j.jam_selesai, h.nama_hari
               FROM jadwal_pelajaran jp
               LEFT JOIN pelajaran p ON jp.id_pelajaran = p.id_pelajaran
               LEFT JOIN kelas k ON jp.id_kelas = k.id_kelas
               LEFT JOIN jam j ON jp.id_jam = j.id_jam
               LEFT JOIN hari h ON jp.id_hari = h.id_hari
               WHERE jp.nik = :nik
               ORDER BY h.id_hari, j.jam_mulai";
$stmtJadwal = $db->prepare($queryJadwal);
$stmtJadwal->bindParam(':nik', $nik);
$stmtJadwal->execute();
$jadwalList = $stmtJadwal->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Jadwal Mengajar - Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/guru.css" />
</head>
<body>
<?php include 'guru_header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include 'guru_sidebar.php'; ?>
        </div>
        <div class="col-md-9 main-content">
            <h5>Jadwal Mengajar</h5>
            <?php if (count($jadwalList) > 0): ?>
                <div class="table-responsive"></div>
                <div class="col-md-9 main-content"></div>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Pelajaran</th>
                                <th>Kelas</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Hari</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jadwalList as $jadwal): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($jadwal['nama_pelajaran']); ?></td>
                                <td><?php echo htmlspecialchars($jadwal['nama_kelas']); ?></td>
                                <td><?php echo htmlspecialchars($jadwal['jam_mulai']); ?></td>
                                <td><?php echo htmlspecialchars($jadwal['jam_selesai']); ?></td>
                                <td><?php echo htmlspecialchars($jadwal['nama_hari']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>Tidak ada jadwal mengajar.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
