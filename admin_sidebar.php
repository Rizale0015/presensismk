<?php
// Fetch kelas list for submenu
require_once 'database.php';
$database = new Database();
$db = $database->getConnection();

$queryKelas = "SELECT k.*, g.namaptk AS nama_wali FROM kelas k LEFT JOIN guru g ON k.nik_wali = g.nik ORDER BY k.id_kelas";
$stmtKelas = $db->prepare($queryKelas);
$stmtKelas->execute();
$kelasList = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);

// Fetch siswa grouped by kelas
$querySiswa = "SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id_kelas ORDER BY s.nama";
$stmtSiswa = $db->prepare($querySiswa);
$stmtSiswa->execute();
$siswaList = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

// Group siswa by id_kelas
$siswaByKelas = [];
foreach ($siswaList as $siswa) {
    $siswaByKelas[$siswa['id_kelas']][] = $siswa;
}

// Determine active page for highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+Knujsl5/5hb5g5+5/5hb5g5+5/5hb5g5+5/5hb5g5+5/5hb5g5" crossorigin="anonymous">

    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <title>Admin Dashboard</title>
</head>
<body>
    <!-- Tombol toggle sidebar untuk mobile -->
    <button class="btn btn-primary d-md-none mb-3 shadow-sm rounded-pill" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarAdmin" aria-controls="sidebarAdmin">
        <i class="fas fa-bars"></i> Menu
    </button>

    <!-- Sidebar offcanvas untuk mobile -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarAdmin" aria-labelledby="sidebarAdminLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="sidebarAdminLabel">Menu Admin</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <ul class="nav flex-column p-2 gap-1">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active-sidebar' : '' ?>" href="admin_dashboard.php">
                        <i class="fas fa-home me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'admin_kelas.php' ? 'active-sidebar' : '' ?>" href="admin_kelas.php">
                        <i class="fas fa-chalkboard me-2"></i> Data Kelas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'admin_guru.php' ? 'active-sidebar' : '' ?>" href="admin_guru.php">
                        <i class="fas fa-user-tie me-2"></i> Data Guru
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'admin_siswa.php' ? 'active-sidebar' : '' ?>" href="admin_siswa.php">
                        <i class="fas fa-users me-2"></i> Data Siswa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'admin_absensi.php' ? 'active-sidebar' : '' ?>" href="admin_absensi.php">
                        <i class="fas fa-calendar-check me-2"></i> Data Absensi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'admin_jadwal.php' ? 'active-sidebar' : '' ?>" href="admin_jadwal.php">
                        <i class="fas fa-calendar-alt me-2"></i> Jadwal Pelajaran
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'admin_laporan.php' ? 'active-sidebar' : '' ?>" href="admin_laporan.php">
                        <i class="fas fa-file-alt me-2"></i> Laporan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 <?= basename($_SERVER['PHP_SELF']) == 'admin_profil.php' ? 'active-sidebar' : '' ?>" href="admin_profil.php">
                        <i class="fas fa-user-cog me-2"></i> Profil
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link d-flex align-items-center rounded-pill px-3 py-2 text-danger bg-white fw-bold" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <nav id="sidebarMenu" class="d-md-block sidebar collapse" style="min-height: 100vh;">
        <div class="position-sticky pt-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'admin_dashboard.php') ? 'active' : ''; ?>" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'admin_guru.php') ? 'active' : ''; ?>" href="admin_guru.php">
                        <i class="fas fa-chalkboard-teacher me-2"></i> Data Guru
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'admin_siswa.php') ? 'active' : ''; ?>" href="admin_siswa.php">
                        <i class="fas fa-user-graduate me-2"></i> Data Siswa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'admin_kelas.php') ? 'active' : ''; ?>" href="admin_kelas.php">
                        <i class="fas fa-door-open me-2"></i> Data Kelas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'admin_absensi.php') ? 'active' : ''; ?>" href="admin_absensi.php">
                        <i class="fas fa-calendar-check me-2"></i> Data Absensi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'admin_jadwal.php') ? 'active' : ''; ?>" href="admin_jadwal.php">
                        <i class="fas fa-calendar-alt me-2"></i> Jadwal Pelajaran
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="laporanDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-alt me-2"></i> Laporan
                    </a>
                    <ul class="dropdown-menu shadow animated--grow-in" aria-labelledby="laporanDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="admin_laporan.php">
                                <i class="fas fa-user-graduate me-2 text-primary"></i> Laporan Siswa
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="admin_laporan_guru.php">
                                <i class="fas fa-chalkboard-teacher me-2 text-success"></i> Laporan Guru
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Optional JavaScript; choose one of the two! -->
    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G8cG8cG8cG8cG8cG8cG8cG8cG8cG8cG8cG8cG8" crossorigin="anonymous"></script>

    <!-- Option 2: Separate Popper and Bootstrap JS -->
    <!--
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz4fnFO9gybB+2z5z5z5z5z5z5z5z5z5z5z5z5z5z5z5z5z5" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G8cG8cG8cG8cG8cG8cG8cG8cG8cG8cG8cG8cG8" crossorigin="anonymous"></script>
    -->

    <!-- Tambahkan CSS custom di bawah ini atau di assets/css/custom.css -->
    <style>
    .sidebar-admin {
        background: linear-gradient(135deg, #4f8cff 0%, #6fd6ff 100%) !important;
    }
    .active-sidebar {
        background: #fff !important;
        color: #4f8cff !important;
        font-weight: bold;
        box-shadow: 0 2px 8px #4f8cff22;
    }
    .nav-link {
        transition: background 0.2s, color 0.2s;
    }
    .nav-link:hover:not(.active-sidebar) {
        background: #ffffff33;
        color: #fff !important;
    }
    </style>
</body>
</html>
