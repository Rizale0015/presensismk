<?php
require_once 'session.php';
require_once 'database.php';
checkAdminAccess();

$database = new Database();
$db = $database->getConnection();

// Summary per kelas
$queryAttendanceSummary = "
    SELECT k.nama_kelas, COUNT(a.id_absen) AS total_absensi
    FROM kelas k
    LEFT JOIN siswa s ON k.id_kelas = s.id_kelas
    LEFT JOIN absen_siswa a ON s.nis = a.nis
    GROUP BY k.id_kelas, k.nama_kelas
    ORDER BY k.nama_kelas
";
$stmt = $db->prepare($queryAttendanceSummary);
$stmt->execute();
$attendanceSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kinerja siswa
$queryStudentPerformance = "
    SELECT s.nis, s.nama, k.nama_kelas, 
    COUNT(a.id_absen) AS hadir,
    (SELECT COUNT(*) FROM absen_siswa WHERE nis = s.nis) AS total_absensi
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN absen_siswa a ON s.nis = a.nis AND a.keterangan = 'Hadir'
    GROUP BY s.nis, s.nama, k.nama_kelas
    ORDER BY k.nama_kelas, s.nama
";
$stmt = $db->prepare($queryStudentPerformance);
$stmt->execute();
$studentPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Per tanggal per kelas
$queryClassAttendance = "
    SELECT k.nama_kelas, a.id_tanggal AS tanggal, COUNT(a.id_absen) AS jumlah_absen
    FROM absen_siswa a
    LEFT JOIN siswa s ON a.nis = s.nis
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    GROUP BY k.id_kelas, a.id_tanggal
    ORDER BY k.nama_kelas, a.id_tanggal DESC
";
$stmt = $db->prepare($queryClassAttendance);
$stmt->execute();
$classAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Default: bulanan
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'bulanan';
$tanggal_awal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-01');
$tanggal_akhir = $tanggal_awal;

if ($periode == 'mingguan') {
    $tanggal_akhir = date('Y-m-d', strtotime($tanggal_awal . ' +6 days'));
} elseif ($periode == 'bulanan') {
    $tanggal_akhir = date('Y-m-t', strtotime($tanggal_awal));
} elseif ($periode == 'tahunan') {
    $tanggal_akhir = date('Y-12-31', strtotime($tanggal_awal));
}

// Query rekap absensi per kelas sesuai periode
$queryRekap = "
    SELECT k.nama_kelas, 
           COUNT(CASE WHEN a.keterangan = 'Hadir' THEN 1 END) AS hadir,
           COUNT(CASE WHEN a.keterangan = 'Izin' THEN 1 END) AS izin,
           COUNT(CASE WHEN a.keterangan = 'Alpha' THEN 1 END) AS alpha,
           COUNT(CASE WHEN a.keterangan = 'Hadir' THEN 1 END) AS total_hadir,
           COUNT(CASE WHEN a.keterangan = 'Alpha' THEN 1 END) AS total_alpha
    FROM kelas k
    LEFT JOIN siswa s ON k.id_kelas = s.id_kelas
    LEFT JOIN absen_siswa a ON s.nis = a.nis AND a.id_tanggal BETWEEN :awal AND :akhir
    GROUP BY k.nama_kelas
    ORDER BY k.nama_kelas
";
$stmt = $db->prepare($queryRekap);
$stmt->bindParam(':awal', $tanggal_awal);
$stmt->bindParam(':akhir', $tanggal_akhir);
$stmt->execute();
$rekapPeriode = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Per siswa per kelas (dengan breakdown hadir/izin/sakit/terlambat)
$queryPerClassStudents = "
    SELECT 
        k.nama_kelas, 
        s.nis, 
        s.nama,
        SUM(CASE WHEN a.keterangan = 'Hadir' AND a.id_tanggal BETWEEN :awal AND :akhir THEN 1 ELSE 0 END) AS hadir,
        SUM(CASE WHEN a.keterangan = 'Izin' AND a.id_tanggal BETWEEN :awal AND :akhir THEN 1 ELSE 0 END) AS izin,
        SUM(CASE WHEN a.keterangan = 'Alpha' AND a.id_tanggal BETWEEN :awal AND :akhir THEN 1 ELSE 0 END) AS alpha
    FROM kelas k
    JOIN siswa s ON k.id_kelas = s.id_kelas
    LEFT JOIN absen_siswa a ON s.nis = a.nis
    GROUP BY k.nama_kelas, s.nis, s.nama
    ORDER BY k.nama_kelas, s.nama
";
$stmt = $db->prepare($queryPerClassStudents);
$stmt->bindParam(':awal', $tanggal_awal);
$stmt->bindParam(':akhir', $tanggal_akhir);
$stmt->execute();
$perClassData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mengelompokkan berdasarkan kelas
$classGroupedData = [];
foreach ($perClassData as $row) {
    $classGroupedData[$row['nama_kelas']][] = $row;
}

// --- Tambahkan kode ini untuk mengurutkan kelas terpilih ke atas ---
if (isset($_GET['id_kelas']) && isset($classGroupedData[$_GET['id_kelas']])) {
    $selectedClass = $_GET['id_kelas'];
    // Ambil data kelas terpilih
    $selectedData = [$selectedClass => $classGroupedData[$selectedClass]];
    // Hapus dari array utama
    unset($classGroupedData[$selectedClass]);
    // Gabungkan: kelas terpilih di depan, sisanya di belakang
    $classGroupedData = $selectedData + $classGroupedData;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Laporan - Sistem Presensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <?php include 'admin_sidebar.php'; ?>
            </div>
    
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4">
                <!-- Your existing content here -->
            </div>
        </div>
    </div>    <link rel="stylesheet" href="assets/css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'admin_sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-4">
            <div class="container">
                <h3 class="mt-4">Laporan Presensi</h3>

                <h5>Total Absensi per Kelas</h5>
                <table class="table table-striped">
                    <thead><tr><th>Kelas</th><th>Total Absensi</th></tr></thead>
                    <tbody>
                        <?php foreach ($attendanceSummary as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                <td><?= $row['total_absensi'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Removed Kinerja Siswa table as per user request -->


                <form method="get" class="row g-2 align-items-end mb-4">
                    <div class="col-auto">
                        <label for="periode" class="form-label mb-0">Rekap Periode</label>
                        <select name="periode" id="periode" class="form-select">
                            <option value="mingguan" <?= (isset($_GET['periode']) && $_GET['periode']=='mingguan')?'selected':''; ?>>Mingguan</option>
                            <option value="bulanan" <?= (isset($_GET['periode']) && $_GET['periode']=='bulanan')?'selected':''; ?>>Bulanan</option>
                            <option value="tahunan" <?= (isset($_GET['periode']) && $_GET['periode']=='tahunan')?'selected':''; ?>>Tahunan</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label for="tanggal" class="form-label mb-0">Tanggal (awal)</label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d'); ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                </form>

                <h5>Rekap Absensi <?= ucfirst($periode) ?> (<?= date('d M Y', strtotime($tanggal_awal)); ?> - <?= date('d M Y', strtotime($tanggal_akhir)); ?>)</h5>
                <table class="table table-bordered table-striped mb-4">
                    <thead>
                        <tr>
                            <th>Kelas</th>
                            <th>Hadir</th>
                            <th>Izin</th>
                <th>Alpha</th>
                <th>Total Hadir</th>
                <th>Total Alpha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rekapPeriode as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                <td><?= $row['hadir'] ?></td>
                <td><?= $row['izin'] ?></td>
                <td><?= $row['alpha'] ?></td>
                <td><?= $row['total_hadir'] ?></td>
                <td><?= $row['total_alpha'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
                </table>

                <h5>Tabel Per Kelas dan Export Excel</h5>
                <?php foreach ($classGroupedData as $kelas => $siswaList): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <?= htmlspecialchars($kelas) ?>
                            <button class="btn btn-sm btn-success float-end" onclick="exportTableToExcel('<?='table_' . md5($kelas)?>', 'Absensi_<?=preg_replace('/\s+/', '_', $kelas)?>')">Export ke Excel</button>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered" id="<?= 'table_' . md5($kelas) ?>">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NIS</th>
                                        <th>Nama</th>
                                        <th>Hadir</th>
                                        <th>Izin</th>
                                        <th>Alpha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($siswaList as $i => $s): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= $s['nis'] ?></td>
                                            <td><?= $s['nama'] ?></td>
                                            <td><?= $s['hadir'] ?></td>
                                            <td><?= $s['izin'] ?></td>
                                            <td><?= $s['alpha'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button class="btn btn-primary btn-sm mt-2 load-more-btn" data-table-id="<?= 'table_' . md5($kelas) ?>">Load More</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function exportTableToExcel(tableId, filename = '') {
    const table = document.getElementById(tableId);
    const tableHTML = table.outerHTML.replace(/ /g, '%20');
    const dataType = 'application/vnd.ms-excel';
    filename = filename ? filename + '.xls' : 'laporan_kelas.xls';
    const link = document.createElement('a');
    document.body.appendChild(link);
    link.href = 'data:' + dataType + ', ' + tableHTML;
    link.download = filename;
    link.click();
    document.body.removeChild(link);
}

function filterByKelas(kelas, btn) {
    document.querySelectorAll('#jadwalTable tbody tr').forEach(function(row) {
        if (!kelas || row.getAttribute('data-kelas') === kelas) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    document.querySelectorAll('.filter-btn').forEach(function(b) {
        b.classList.remove('active');
        b.classList.remove('btn-primary');
        b.classList.add('btn-outline-primary');
    });
    if (btn) {
        btn.classList.add('active');
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-primary');
    }
}
</script>

</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rowsPerPage = 10;

    function setupLoadMoreButton(button) {
        const tableId = button.getAttribute('data-table-id');
        const table = document.getElementById(tableId);
        if (!table) return;

        let currentPage = 1;
        const rows = table.querySelectorAll('tbody tr');
        const totalRows = rows.length;

        function showRows() {
            const end = currentPage * rowsPerPage;
            rows.forEach((row, index) => {
                if (index < end) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            if (end >= totalRows) {
                button.style.display = 'none';
            } else {
                button.style.display = '';
            }
        }

        button.addEventListener('click', () => {
            currentPage++;
            showRows();
        });

        showRows();
    }

    document.querySelectorAll('.load-more-btn').forEach(setupLoadMoreButton);
});
</script>
</html>
