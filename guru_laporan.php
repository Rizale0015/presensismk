<?php
require_once 'session.php';
require_once 'database.php';
checkGuruAccess();

$database = new Database();
$db = $database->getConnection();

// Get guru's NIK from session
$nik = $_SESSION['nik'];

// Get selected class from GET parameter
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : null;

// Query to get list of classes taught by the teacher
$queryKelas = "
    SELECT DISTINCT k.id_kelas, k.nama_kelas
    FROM jadwal_pelajaran j
    JOIN kelas k ON j.id_kelas = k.id_kelas
    WHERE j.nik = :nik
    ORDER BY k.nama_kelas
";
$stmtKelas = $db->prepare($queryKelas);
$stmtKelas->bindParam(':nik', $nik);
$stmtKelas->execute();
$kelasList = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);

// Add period and start date filter parameters and UI
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'bulanan';
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = $tanggal_awal;

if ($periode == 'mingguan') {
    $tanggal_akhir = date('Y-m-d', strtotime($tanggal_awal . ' +6 days'));
} elseif ($periode == 'bulanan') {
    $tanggal_akhir = date('Y-m-t', strtotime($tanggal_awal));
} elseif ($periode == 'tahunan') {
    $tanggal_akhir = date('Y-12-31', strtotime($tanggal_awal));
}

// Query untuk laporan absensi siswa, filtered by selected class and date range
$queryAbsensiSiswa = "
    SELECT s.nama AS nama_siswa, p.nama_pelajaran, 
           COUNT(CASE WHEN a.keterangan = 'Hadir' THEN 1 END) AS hadir,
           COUNT(CASE WHEN a.keterangan = 'Alpha' THEN 1 END) AS alpha,
           COUNT(CASE WHEN a.keterangan = 'Izin' THEN 1 END) AS izin
    FROM jadwal_pelajaran j
    JOIN kelas k ON j.id_kelas = k.id_kelas
    JOIN siswa s ON s.id_kelas = k.id_kelas
    JOIN pelajaran p ON j.id_pelajaran = p.id_pelajaran
    LEFT JOIN absen_siswa a ON a.nis = s.nis AND a.id_pelajaran = p.id_pelajaran AND a.id_tanggal BETWEEN :tanggal_awal AND :tanggal_akhir AND a.id_kelas = k.id_kelas
    WHERE j.nik = :nik
";
if ($id_kelas !== null) {
    $queryAbsensiSiswa .= " AND k.id_kelas = :id_kelas ";
}
$queryAbsensiSiswa .= "
    GROUP BY s.nis, p.id_pelajaran
    ORDER BY s.nama, p.nama_pelajaran
";

$stmt = $db->prepare($queryAbsensiSiswa);
$stmt->bindParam(':nik', $nik);
$stmt->bindParam(':tanggal_awal', $tanggal_awal);
$stmt->bindParam(':tanggal_akhir', $tanggal_akhir);
if ($id_kelas !== null) {
    $stmt->bindParam(':id_kelas', $id_kelas);
}
$stmt->execute();
$laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preserve selected filters in links and form
function buildQuery($params) {
    return http_build_query(array_merge($_GET, $params));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <style>
@media (max-width: 767.98px) {
  table.table thead {
    display: none !important;
  }
  table.table, table.table tbody, table.table tr, table.table td {
    display: block !important;
    width: 100% !important;
  }
  table.table tr {
    margin-bottom: 1rem;
    border: 1px solid #e3e3e3;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px #4f8cff11;
    padding: 0.7rem;
    background: #fff;
  }
  table.table td {
    border: none !important;
    border-bottom: 1px solid #f0f0f0 !important;
    font-size: 1rem;
    padding: 0.4rem 0.7rem !important;
    position: relative;
  }
  table.table td:before {
    content: attr(data-label);
    font-weight: bold;
    color: #4f8cff;
    min-width: 90px;
    display: inline-block;
    margin-right: 1rem;
  }
}
    </style>
</head>
<?php
$theme = 'light';
if (isset($_SESSION['settings']['theme'])) {
    $theme = $_SESSION['settings']['theme'];
}
?>
<body class="<?= $theme === 'dark' ? 'dark-mode' : 'light-mode' ?>">
<?php include 'guru_header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php include 'guru_sidebar.php'; ?>
        </div>

        <!-- Konten utama -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Laporan Absensi Siswa per Pelajaran</h5>
                    <form method="GET" class="d-flex align-items-center" style="gap: 0.5rem;">
                        <input type="hidden" name="id_kelas" value="<?= htmlspecialchars($id_kelas); ?>">
                        <label for="periode" class="mb-0">Periode:</label>
                        <select id="periode" name="periode" class="form-select form-select-sm">
                            <option value="mingguan" <?= ($periode == 'mingguan') ? 'selected' : ''; ?>>Mingguan</option>
                            <option value="bulanan" <?= ($periode == 'bulanan') ? 'selected' : ''; ?>>Bulanan</option>
                            <option value="tahunan" <?= ($periode == 'tahunan') ? 'selected' : ''; ?>>Tahunan</option>
                        </select>
                        <label for="tanggal_awal" class="mb-0">Tanggal Awal:</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" class="form-control form-control-sm" value="<?= htmlspecialchars($tanggal_awal); ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    </form>
                </div>
                <div class="card-body">
                    <!-- Class filter buttons -->
                    <div class="mb-3">
<?php foreach ($kelasList as $kelas): ?>
    <a href="guru_laporan.php?id_kelas=<?= $kelas['id_kelas']; ?>&periode=<?= htmlspecialchars($periode); ?>&tanggal_awal=<?= htmlspecialchars($tanggal_awal); ?>" 
       class="btn btn-outline-primary <?= ($id_kelas == $kelas['id_kelas']) ? 'active' : ''; ?>">
        <?= htmlspecialchars($kelas['nama_kelas']); ?>
    </a>
<?php endforeach; ?>
<a href="guru_laporan.php?periode=<?= htmlspecialchars($periode); ?>&tanggal_awal=<?= htmlspecialchars($tanggal_awal); ?>" class="btn btn-outline-secondary <?= ($id_kelas === null) ? 'active' : ''; ?>">Semua Kelas</a>
                    </div>

                    <a href="export_excel_laporan.php?date=<?= htmlspecialchars($date_filter); ?>" class="btn btn-success mb-3">Export ke Excel</a>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Siswa</th>
                                    <th>Pelajaran</th>
                                    <th>Hadir</th>
                                    <th>Alpha</th>
                                    <th>Izin</th>
                                </tr>
                            </thead>
                            <tbody>
<?php foreach ($laporan as $row): ?>
<tr>
    <td data-label="Nama Siswa"><?= htmlspecialchars($row['nama_siswa']); ?></td>
    <td data-label="Pelajaran"><?= htmlspecialchars($row['nama_pelajaran']); ?></td>
    <td data-label="Hadir"><?= $row['hadir']; ?></td>
    <td data-label="Alpha"><?= $row['alpha']; ?></td>
    <td data-label="Izin"><?= $row['izin']; ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($laporan)): ?>
<tr><td colspan="7" class="text-center">Tidak ada data.</td></tr>
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
</body>
</html>
