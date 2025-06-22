<?php
require_once 'session.php';
checkAdminAccess();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Presensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sidebar.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include 'admin_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h5>Dashboard</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h4 id="total-guru">0</h4>
                                        <p>Total Guru</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h4 id="total-siswa">0</h4>
                                        <p>Total Siswa</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h4 id="total-kelas">0</h4>
                                        <p>Total Kelas</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h4 id="absen-hari-ini">0</h4>
                                        <p>Absen Hari Ini</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load dashboard statistics
        fetch('dashboard_stats.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('total-guru').textContent = data.total_guru;
                document.getElementById('total-siswa').textContent = data.total_siswa;
                document.getElementById('total-kelas').textContent = data.total_kelas;
                document.getElementById('absen-hari-ini').textContent = data.absen_hari_ini;
            });
    </script>
</body>
</html>