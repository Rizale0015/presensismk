<?php
require_once 'session.php';
require_once 'database.php';
date_default_timezone_set('Asia/Jakarta');

checkGuruAccess();

$database = new Database();
$db = $database->getConnection();
$today = date('Y-m-d');
$nik = $_SESSION['nik'];

$query_absen_today = "SELECT jam_datang FROM absen_guru WHERE nik = :nik AND tanggal = :tanggal LIMIT 1";
$stmt_absen_today = $db->prepare($query_absen_today);
$stmt_absen_today->bindParam(':nik', $nik);
$stmt_absen_today->bindParam(':tanggal', $today);
$stmt_absen_today->execute();
$absen_today = $stmt_absen_today->fetch(PDO::FETCH_ASSOC);

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
    <meta charset="UTF-8">
    <title>Dashboard Guru</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Panggil Bootstrap dan Font Awesome di sini -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .dashboard-card {
        border: none;
        border-radius: 1.2rem;
        box-shadow: 0 4px 24px rgba(79,140,255,0.10);
        transition: transform 0.15s, box-shadow 0.15s;
        cursor: pointer;
    }
    .dashboard-card:hover {
        transform: translateY(-4px) scale(1.03);
        box-shadow: 0 8px 32px rgba(79,140,255,0.18);
        opacity: 0.97;
    }
    .dashboard-card .card-body i {
        font-size: 2.2rem;
        margin-bottom: 0.5rem;
        opacity: 0.85;
    }
    .dashboard-card .card-title {
        font-weight: bold;
        letter-spacing: 0.5px;
    }
    </style>
</head>
<body>
    <?php include 'guru_header.php'; ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include 'guru_sidebar.php'; ?>
            </div>

            <div class="col-md-9">
                <div class="card mb-4">
                    <div class="card-header">Dashboard Guru</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <div class="card dashboard-card bg-primary text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-check mb-2"></i>
                                        <h5 class="card-title">Absensi Hari Ini</h5>
                                        <p class="mb-1"><?= date('d F Y'); ?></p>
                                        <a href="guru_absen.php" class="btn btn-light">Isi Absensi</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card dashboard-card bg-success text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-check mb-2"></i>
                                        <h5 class="card-title">Absensi Siswa</h5>
                                        <p class="mb-1">Kelola absensi siswa</p>
                                        <a href="guru_absen_siswa.php" class="btn btn-light">Kelola</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5>Status Absensi Hari Ini</h5>
                            <p><strong>Jam Datang:</strong>
                                <?= $absen_today['jam_datang'] ?? '<span class="text-danger">Belum absen</span>'; ?>
                            </p>
                        </div>

                        <div class="mt-4">
                            <h5>Jadwal Mengajar</h5>
                            <?php if ($jadwalList): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
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
                                                    <td><?= $jadwal['nama_pelajaran']; ?></td>
                                                    <td><?= $jadwal['nama_kelas']; ?></td>
                                                    <td><?= $jadwal['jam_mulai']; ?></td>
                                                    <td><?= $jadwal['jam_selesai']; ?></td>
                                                    <td><?= $jadwal['nama_hari']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>Belum ada jadwal mengajar.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
