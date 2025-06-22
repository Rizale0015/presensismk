<?php
require_once 'session.php';
require_once 'database.php';
checkGuruAccess();

$database = new Database();
$db = $database->getConnection();

$nik = $_SESSION['nik'] ?? null;
$nip = $_SESSION['nip'] ?? '';

$photoPath = null;
$nama_guru = '';
if ($nik) {
    $stmt = $db->prepare("SELECT namaptk, foto FROM guru WHERE nik = :nik");
    $stmt->bindParam(':nik', $nik);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $nama_guru = $result['namaptk'] ?? '';
        if (!empty($result['foto']) && file_exists($result['foto'])) {
            $photoPath = $result['foto'];
        }
    }
}

$defaultPhoto = "assets/img/logoAA.png";
$photoToShow = $photoPath ?? $defaultPhoto;
?>

<!-- Tambahkan di file guru_sidebar.php atau di <head> -->
<style>
.guru-sidebar {
    background: linear-gradient(135deg, #6fd6ff 0%, #4f8cff 100%);
    min-height: 100vh;
    border-radius: 1.5rem;
    box-shadow: 0 4px 24px rgba(79,140,255,0.10);
    padding: 2rem 1rem 1rem 1rem;
    color: #fff;
}
.guru-sidebar .sidebar-header {
    text-align: center;
    margin-bottom: 2rem;
}
.guru-sidebar .sidebar-header img {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px #4f8cff33;
    margin-bottom: 0.5rem;
}
.guru-sidebar .sidebar-header h5 {
    margin-bottom: 0.2rem;
    font-weight: bold;
    color: #fff;
}
.guru-sidebar .sidebar-header small {
    color: #e0f7fa;
}
.guru-sidebar .nav-link {
    color: #fff;
    font-weight: 500;
    border-radius: 2rem;
    margin-bottom: 0.5rem;
    transition: background 0.18s, color 0.18s;
    padding: 0.7rem 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.7rem;
}
.guru-sidebar .nav-link.active,
.guru-sidebar .nav-link:hover {
    background: #fff;
    color: #4f8cff !important;
    font-weight: bold;
    box-shadow: 0 2px 8px #4f8cff22;
}
.guru-sidebar .nav-link i {
    font-size: 1.2rem;
}
.guru-sidebar .logout-link {
    color: #ff5252 !important;
    background: #fff;
    font-weight: bold;
    margin-top: 2rem;
}
.guru-sidebar .logout-link:hover {
    background: #ff5252;
    color: #fff !important;
}
</style>

<!-- Sidebar Guru for larger screens -->
<div class="guru-sidebar d-none d-md-block">
    <div class="sidebar-header">
        <img src="<?= htmlspecialchars($photoToShow) ?>" alt="Guru">
        <h5 class="mb-0"><?= htmlspecialchars($nama_guru) ?></h5>
        <small class="text-muted"><?= htmlspecialchars($nip) ?></small>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item mb-2">
            <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_dashboard.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_dashboard.php">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_absen.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_absen.php">
                <i class="fas fa-calendar-check me-2"></i> Absensi Saya
            </a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_absen_siswa.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_absen_siswa.php">
                <i class="fas fa-user-check me-2"></i> Absensi Siswa
            </a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_jadwal.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_jadwal.php">
                <i class="fas fa-calendar-alt me-2"></i> Jadwal Mengajar
            </a>
        </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_laporan.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_laporan.php">
                        <i class="fas fa-file-alt me-2"></i> Laporan Siswa
                    </a>
                </li>
        <li class="nav-item mb-2">
            <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_pengaturan.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_pengaturan.php">
                <i class="fas fa-cog me-2"></i> Pengaturan
            </a>
        </li>
        <li class="nav-item mt-4">
            <a class="nav-link d-flex align-items-center logout-link" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </li>
    </ul>
</div>

<!-- Sidebar Guru for mobile screens -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarGuru" aria-labelledby="sidebarGuruLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarGuruLabel">Menu Guru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="bg-white shadow-sm rounded p-3 h-100" style="min-height: 100vh;">
            <div class="mb-4 text-center">
                <img src="<?= htmlspecialchars($photoToShow) ?>" alt="Guru" width="64" height="64" class="rounded-circle mb-2">
                <h5 class="mb-0"><?= htmlspecialchars($nama_guru) ?></h5>
                <small class="text-muted"><?= htmlspecialchars($nip) ?></small>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_dashboard.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_dashboard.php">
                        <i class="fas fa-home me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_absen.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_absen.php">
                        <i class="fas fa-calendar-check me-2"></i> Absensi Saya
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_absen_siswa.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_absen_siswa.php">
                        <i class="fas fa-user-check me-2"></i> Absensi Siswa
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_jadwal.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_jadwal.php">
                        <i class="fas fa-calendar-alt me-2"></i> Jadwal Mengajar
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_laporan.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_laporan.php">
                        <i class="fas fa-file-alt me-2"></i> Laporan Siswa
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'guru_pengaturan.php' ? 'active fw-bold text-primary' : '' ?>" href="guru_pengaturan.php">
                        <i class="fas fa-cog me-2"></i> Pengaturan
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link d-flex align-items-center logout-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Toggle Button for Mobile -->
<button class="btn btn-primary d-md-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarGuru" aria-controls="sidebarGuru">
    <i class="fas fa-bars"></i> Menu
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Pastikan Font Awesome sudah di-include di <head> -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
