<?php
require_once 'session.php';
require_once 'database.php';
date_default_timezone_set('Asia/Jakarta');

checkGuruAccess();

$database = new Database();
$db = $database->getConnection();

$nik = $_SESSION['nik'];
$today = date('Y-m-d');

// Handle form submission
if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] == 'absen_siswa') {
        $nis = $_POST['nis'];
        $id_kelas = $_POST['id_kelas'];
        $id_jam = $_POST['id_jam'];
        $id_pelajaran = $_POST['id_pelajaran'];
        $id_hari = $_POST['id_hari'];
        $keterangan = strtolower($_POST['keterangan']);
        $jam_hadir = $keterangan == 'hadir' ? date('H:i:s') : null;
        
        // Check if student already has attendance record for today
        $check_query = "SELECT * FROM absen_siswa WHERE nis = :nis AND id_tanggal = :tanggal 
                       AND id_jam = :id_jam AND id_pelajaran = :id_pelajaran";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':nis', $nis);
        $check_stmt->bindParam(':tanggal', $today);
        $check_stmt->bindParam(':id_jam', $id_jam);
        $check_stmt->bindParam(':id_pelajaran', $id_pelajaran);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing record
            $query = "UPDATE absen_siswa SET jam_hadir = :jam_hadir, keterangan = :keterangan 
                     WHERE nis = :nis AND id_tanggal = :tanggal AND id_jam = :id_jam AND id_pelajaran = :id_pelajaran";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':jam_hadir', $jam_hadir);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':nis', $nis);
            $stmt->bindParam(':tanggal', $today);
            $stmt->bindParam(':id_jam', $id_jam);
            $stmt->bindParam(':id_pelajaran', $id_pelajaran);
        } else {
            // Insert new record
            $query = "INSERT INTO absen_siswa (nis, id_kelas, id_jam, id_pelajaran, id_hari, id_tanggal, jam_hadir, keterangan) 
                     VALUES (:nis, :id_kelas, :id_jam, :id_pelajaran, :id_hari, :tanggal, :jam_hadir, :keterangan)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nis', $nis);
            $stmt->bindParam(':id_kelas', $id_kelas);
            $stmt->bindParam(':id_jam', $id_jam);
            $stmt->bindParam(':id_pelajaran', $id_pelajaran);
            $stmt->bindParam(':id_hari', $id_hari);
            $stmt->bindParam(':tanggal', $today);
            $stmt->bindParam(':jam_hadir', $jam_hadir);
            $stmt->bindParam(':keterangan', $keterangan);
        }
        
        if ($stmt->execute()) {
            $success = "Absensi siswa berhasil dicatat!";
        } else {
            $error = "Gagal mencatat absensi siswa!";
        }
    }
}

// Get teacher's schedule for today
$day_query = "SELECT CASE 
    WHEN DAYNAME(CURDATE()) = 'Monday' THEN 1
    WHEN DAYNAME(CURDATE()) = 'Tuesday' THEN 2
    WHEN DAYNAME(CURDATE()) = 'Wednesday' THEN 3
    WHEN DAYNAME(CURDATE()) = 'Thursday' THEN 4
    WHEN DAYNAME(CURDATE()) = 'Friday' THEN 5
    WHEN DAYNAME(CURDATE()) = 'Saturday' THEN 6
    WHEN DAYNAME(CURDATE()) = 'Sunday' THEN 7
END as id_hari";
$day_stmt = $db->prepare($day_query);
$day_stmt->execute();
$current_day_id = $day_stmt->fetch(PDO::FETCH_ASSOC)['id_hari'];

// Get today's schedule
$schedule_query = "SELECT jp.*, k.nama_kelas as kelas_nama, p.nama_pelajaran AS pelajaran_nama FROM jadwal_pelajaran jp 
                  LEFT JOIN kelas k ON jp.id_kelas = k.id_kelas
                  LEFT JOIN pelajaran p ON jp.id_pelajaran = p.id_pelajaran
                  WHERE jp.nik = :nik AND jp.id_hari = :id_hari
                  ORDER BY jp.id_jam";
$schedule_stmt = $db->prepare($schedule_query);
$schedule_stmt->bindParam(':nik', $nik);
$schedule_stmt->bindParam(':id_hari', $current_day_id);
$schedule_stmt->execute();
$today_schedule = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected class students if class is selected
$students = [];
if (isset($_GET['kelas']) && isset($_GET['jam']) && isset($_GET['pelajaran'])) {
    $selected_kelas = $_GET['kelas'];
    $selected_jam = $_GET['jam'];
    $selected_pelajaran = $_GET['pelajaran'];
    
    $students_query = "SELECT s.*, 
                      LOWER(COALESCE(ab.keterangan, 'belum absen')) as status_absen,
                      ab.jam_hadir
                      FROM siswa s 
                      LEFT JOIN absen_siswa ab ON s.nis = ab.nis 
                      AND ab.id_tanggal = :id_tanggal 
                      AND ab.id_jam = :jam 
                      AND ab.id_pelajaran = :pelajaran
                      WHERE s.id_kelas = :kelas
                      ORDER BY s.nama";
    $students_stmt = $db->prepare($students_query);
    $students_stmt->bindParam(':kelas', $selected_kelas);
    $students_stmt->bindParam(':id_tanggal', $today);
    $students_stmt->bindParam(':jam', $selected_jam);
    $students_stmt->bindParam(':pelajaran', $selected_pelajaran);
    $students_stmt->execute();
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_POST['submit_absensi'])) {
    $attendance = isset($_POST['attendance']) ? $_POST['attendance'] : [];

    foreach ($attendance as $nis => $keterangan) {
        $keterangan = ucfirst(strtolower($keterangan));
        $jam_hadir = $keterangan === 'Hadir' ? date('H:i:s') : null;

        $query = "INSERT INTO absen_siswa (nis, id_kelas, id_jam, id_pelajaran, id_hari, id_tanggal, jam_hadir, keterangan) 
                  VALUES (:nis, :id_kelas, :id_jam, :id_pelajaran, :id_hari, :id_tanggal, :jam_hadir, :keterangan)
                  ON DUPLICATE KEY UPDATE jam_hadir = :jam_hadir, keterangan = :keterangan";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nis', $nis);
        $stmt->bindParam(':id_kelas', $selected_kelas);
        $stmt->bindParam(':id_jam', $selected_jam);
        $stmt->bindParam(':id_pelajaran', $selected_pelajaran);
        $stmt->bindParam(':id_hari', $current_day_id);
        $stmt->bindParam(':id_tanggal', $today);
        $stmt->bindParam(':jam_hadir', $jam_hadir);
        $stmt->bindParam(':keterangan', $keterangan);
        $stmt->execute();
    }

    $success = "Absensi berhasil diperbarui!";
    // Refresh the student list
    $students_stmt->execute();
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Misal: tabel absen_siswa ada kolom id_kelas dan tanggal
$stmt = $db->prepare("SELECT COUNT(*) FROM absen_siswa WHERE id_kelas = :id_kelas AND id_tanggal = CURDATE()");
$stmt->bindParam(':id_kelas', $selected_kelas);
$stmt->execute();
$kelas_sudah_absen = $stmt->fetchColumn() > 0;

// Contoh pengambilan data
$stmt = $db->prepare("SELECT nis, LOWER(keterangan) as keterangan FROM absen_siswa WHERE id_kelas = :id_kelas AND id_tanggal = CURDATE()");
$stmt->bindParam(':id_kelas', $selected_kelas);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $absensi_siswa[$row['nis']] = $row['keterangan'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Siswa - Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tambahkan style custom -->
    <style>
.jadwal-card {
    border: none;
    border-radius: 1.2rem;
    box-shadow: 0 4px 24px rgba(79,140,255,0.10);
    transition: transform 0.15s, box-shadow 0.15s;
    background: linear-gradient(120deg, #4f8cff 0%, #6fd6ff 100%);
    color: #fff;
    margin-bottom: 1.2rem;
}
.jadwal-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 8px 32px rgba(79,140,255,0.18);
    opacity: 0.98;
}
.jadwal-card .card-body {
    display: flex;
    align-items: center;
    gap: 1.2rem;
}
.jadwal-icon {
    font-size: 2.5rem;
    background: #fff;
    color: #4f8cff;
    border-radius: 50%;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px #4f8cff22;
}
.jadwal-info {
    flex: 1;
}
.jadwal-info h5 {
    margin-bottom: 0.3rem;
    font-weight: bold;
    letter-spacing: 0.5px;
}
.jadwal-info .badge {
    font-size: 0.95rem;
    margin-right: 0.5rem;
}
.attendance-check {
    display: flex;
    gap: 0.5rem;
}
.attendance-check .form-check {
    margin-bottom: 0;
}
.attendance-table th,
.attendance-table td {
    vertical-align: middle;
}

/* Responsive design for attendance checkboxes */
@media (max-width: 576px) {
    .attendance-table tbody tr td:nth-child(3) {
        /* Student name cell */
        display: block;
        width: 100%;
    }
    .attendance-table tbody tr td:nth-child(4) {
        /* Attendance checkboxes cell */
        display: block;
        width: 100%;
        margin-top: 0.5rem;
    }
    .attendance-check {
        flex-direction: column;
        gap: 0.25rem;
    }
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
                <div class="card">
                    <div class="card-header">
                        <h5>Absensi Siswa - <?php echo date('d F Y'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h6>Jadwal Mengajar Hari Ini:</h6>
                                <?php if (empty($today_schedule)): ?>
                                    <div class="alert alert-info">Tidak ada jadwal mengajar hari ini.</div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($today_schedule as $schedule): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="card border-primary">
                                                <div class="card-body">
                                                    <h6 class="card-title">Jam ke-<?php echo $schedule['id_jam']; ?></h6>
                                                    <p class="card-text">
                                                       Kelas: <?= htmlspecialchars($schedule['kelas_nama']); ?><br>
                                                       Pelajaran: <?= htmlspecialchars($schedule['pelajaran_nama']); ?>
                                                    </p>
                                                    <a href="?kelas=<?php echo $schedule['id_kelas']; ?>&jam=<?php echo $schedule['id_jam']; ?>&pelajaran=<?php echo $schedule['id_pelajaran']; ?>" 
                                                       class="btn btn-primary btn-sm">Kelola Absensi</a>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($students)): ?>
                        <form method="post" action="">
                            <div class="table-responsive">
                                <table class="table table-bordered attendance-table align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>No</th>
                                            <th>NIS</th>
                                            <th>Nama Siswa</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $index => $s): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($s['nis']) ?></td>
                                            <td><?= htmlspecialchars($s['nama']) ?></td>
                                            <td>
                                                <div class="attendance-check d-flex flex-wrap gap-2">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                               name="attendance[<?= $s['nis'] ?>]"
                                                               value="hadir"
                                                               id="hadir_<?= $s['nis'] ?>"
                                                               <?= (isset($absensi_siswa[$s['nis']]) && $absensi_siswa[$s['nis']] == 'hadir') ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="hadir_<?= $s['nis'] ?>">Hadir</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                               name="attendance[<?= $s['nis'] ?>]"
                                                               value="izin"
                                                               id="izin_<?= $s['nis'] ?>"
                                                               <?= (isset($absensi_siswa[$s['nis']]) && $absensi_siswa[$s['nis']] == 'izin') ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="izin_<?= $s['nis'] ?>">Izin</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                               name="attendance[<?= $s['nis'] ?>]"
                                                               value="alpha"
                                                               id="alpha_<?= $s['nis'] ?>"
                                                               <?= (isset($absensi_siswa[$s['nis']]) && $absensi_siswa[$s['nis']] == 'alpha') ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="alpha_<?= $s['nis'] ?>">Alpha</label>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <button type="submit" name="submit_absensi" class="btn btn-primary fw-bold px-4">
                                    Simpan Absensi
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>

                        <?php if ($kelas_sudah_absen): ?>
    <div class="alert alert-success fw-bold">
        <i class="fas fa-check-circle"></i> Kelas ini sudah melakukan absensi hari ini.
    </div>
<?php else: ?>
    <form method="post" action="">
        <!-- ...form absensi seperti sebelumnya... -->
    </form>
<?php endif; ?>

<?php
// Kirim pesan WhatsApp untuk siswa yang tidak hadir
$siswa_tidak_hadir = array_filter($students, function($s) {
    return isset($s['status_absen']) && ($s['status_absen'] == 'alpha' || $s['status_absen'] == 'izin');
});
?>
<div class="mt-4">
    <h6>Siswa Tidak Hadir:</h6>
    <?php if (empty($siswa_tidak_hadir)): ?>
        <div class="alert alert-info">Semua siswa hadir.</div>
    <?php else: ?>
        <?php foreach ($siswa_tidak_hadir as $siswa): ?>
            <?php
                $pesan = "Assalamu'alaikum, Orangtua/Wali dari {$siswa['nama']} (NIS: {$siswa['nis']}).%0AKami informasikan bahwa putra/putri Anda hari ini tidak hadir di sekolah (izin/alpha). Mohon konfirmasi ke wali kelas jika ada keterangan tambahan. Terima kasih.";
                $nohp = isset($siswa['telp_ortu']) ? preg_replace('/^0/', '62', $siswa['telp_ortu']) : ''; // ubah 08xxx jadi 628xxx
                $link_wa = $nohp ? "https://wa.me/{$nohp}?text={$pesan}" : '#';
            ?>
            <div class="mb-2">
                <b><?= htmlspecialchars($siswa['nama']) ?></b> (<?= htmlspecialchars($siswa['nis']) ?>) <br>
                <?php if ($nohp): ?>
                <a href="<?= $link_wa ?>" target="_blank" class="btn btn-success btn-sm">
                    Kirim WhatsApp ke Orangtua
                </a>
                <?php else: ?>
                <span class="text-muted">No HP Orangtua tidak tersedia</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for attendance submission -->
    <form id="absenForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="absen_siswa">
        <input type="hidden" name="nis" id="form_nis">
        <input type="hidden" name="id_kelas" value="<?php echo $selected_kelas ?? ''; ?>">
        <input type="hidden" name="id_jam" value="<?php echo $selected_jam ?? ''; ?>">
        <input type="hidden" name="id_pelajaran" value="<?php echo $selected_pelajaran ?? ''; ?>">
        <input type="hidden" name="id_hari" value="<?php echo $current_day_id; ?>">
        <input type="hidden" name="keterangan" id="form_keterangan">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function absenSiswa(nis, keterangan) {
            if (confirm(`Tandai siswa dengan NIS ${nis} sebagai ${keterangan}?`)) {
                document.getElementById('form_nis').value = nis;
                document.getElementById('form_keterangan').value = keterangan;
                document.getElementById('absenForm').submit();
            }
        }
    </script>
</body>
</html>