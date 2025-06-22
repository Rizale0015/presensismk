<?php
require_once 'session.php';
require_once 'database.php';
checkAdminAccess();

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['nik'])) {
    header('Location: admin_guru.php');
    exit();
}

$old_nik = $_GET['nik'];
$error = '';

// Handle form submission
if ($_POST) {
    $query = "UPDATE guru SET nik=:nik, namaptk=:namaptk, jenis_kelamin=:jenis_kelamin, tempat_lahir=:tempat_lahir, 
              tgl_lahir=:tgl_lahir, agama=:agama, status_perkawinan=:status_perkawinan, alamat=:alamat, 
              npwp=:npwp, email=:email, hp=:hp WHERE nik=:old_nik";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nik', $_POST['nik']);
    $stmt->bindParam(':namaptk', $_POST['namaptk']);
    $stmt->bindParam(':jenis_kelamin', $_POST['jenis_kelamin']);
    $stmt->bindParam(':tempat_lahir', $_POST['tempat_lahir']);
    $stmt->bindParam(':tgl_lahir', $_POST['tgl_lahir']);
    $stmt->bindParam(':agama', $_POST['agama']);
    $stmt->bindParam(':status_perkawinan', $_POST['status_perkawinan']);
    $stmt->bindParam(':alamat', $_POST['alamat']);
    $stmt->bindParam(':npwp', $_POST['npwp']);
    $stmt->bindParam(':email', $_POST['email']);
    $stmt->bindParam(':hp', $_POST['hp']);
    $stmt->bindParam(':old_nik', $old_nik);

    if ($stmt->execute()) {
        // Jika berhasil, langsung redirect ke admin_guru.php dengan pesan sukses
        header('Location: admin_guru.php?update=success');
        exit();
    } else {
        // Jika gagal, simpan pesan error
        $error = "Gagal mengupdate data guru!";
    }
}

// Ambil data guru untuk ditampilkan di form
$query = "SELECT * FROM guru WHERE nik = :nik";
$stmt = $db->prepare($query);
$stmt->bindParam(':nik', $old_nik);
$stmt->execute();
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    header('Location: admin_guru.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Guru - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="admin_dashboard.php">Sistem Presensi - Admin</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3">Selamat datang, <?php echo $_SESSION['username']; ?></span>
            <a class="nav-link" href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3>Edit Data Guru</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">NIK</label>
                    <input type="text" class="form-control" name="nik" value="<?php echo htmlspecialchars($teacher['nik']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" name="namaptk" value="<?php echo htmlspecialchars($teacher['namaptk']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jenis Kelamin</label>
                    <select class="form-control" name="jenis_kelamin" required>
                        <option value="">Pilih...</option>
                        <option value="L" <?php if ($teacher['jenis_kelamin'] == 'L') echo 'selected'; ?>>Laki-laki</option>
                        <option value="P" <?php if ($teacher['jenis_kelamin'] == 'P') echo 'selected'; ?>>Perempuan</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tempat Lahir</label>
                    <input type="text" class="form-control" name="tempat_lahir" value="<?php echo htmlspecialchars($teacher['tempat_lahir']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" class="form-control" name="tgl_lahir" value="<?php echo htmlspecialchars($teacher['tgl_lahir']); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Agama</label>
                    <input type="text" class="form-control" name="agama" value="<?php echo htmlspecialchars($teacher['agama']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Status Perkawinan</label>
                    <select class="form-control" name="status_perkawinan">
                        <option value="">Pilih...</option>
                        <option value="Belum Menikah" <?php if ($teacher['status_perkawinan'] == 'Belum Menikah') echo 'selected'; ?>>Belum Menikah</option>
                        <option value="Menikah" <?php if ($teacher['status_perkawinan'] == 'Menikah') echo 'selected'; ?>>Menikah</option>
                        <option value="Cerai" <?php if ($teacher['status_perkawinan'] == 'Cerai') echo 'selected'; ?>>Cerai</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">NPWP</label>
                    <input type="text" class="form-control" name="npwp" value="<?php echo htmlspecialchars($teacher['npwp']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">No. HP</label>
                    <input type="text" class="form-control" name="hp" value="<?php echo htmlspecialchars($teacher['hp']); ?>">
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Alamat</label>
            <textarea class="form-control" name="alamat" rows="3"><?php echo htmlspecialchars($teacher['alamat']); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="admin_guru.php" class="btn btn-secondary">Kembali</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
