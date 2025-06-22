<?php
require_once 'session.php';
require_once 'database.php';
checkGuruAccess();

$database = new Database();
$db = $database->getConnection();

$nik = $_SESSION['nik']; // Ambil NIK guru dari session

// Ambil data guru termasuk foto, tanpa last update karena kolom tidak ada
$stmt = $db->prepare("SELECT * FROM guru WHERE nik = :nik");
$stmt->bindParam(':nik', $nik);
$stmt->execute();
$guru = $stmt->fetch(PDO::FETCH_ASSOC);

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaptk = $_POST['namaptk'];
    $email = $_POST['email'];
    $hp = $_POST['hp'];
    $alamat = $_POST['alamat'];
    $password = $_POST['password'];

    // Handle file upload for photo
    $photoPath = $guru['foto'] ?? null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName = $_FILES['foto']['name'];
        $fileSize = $_FILES['foto']['size'];
        $fileType = $_FILES['foto']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = $nik . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = './uploads/guru_photos/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $photoPath = $dest_path;
            } else {
                $error = 'Terjadi kesalahan saat mengupload foto profil.';
            }
        } else {
            $error = 'Format file foto tidak diizinkan. Hanya jpg, jpeg, png, gif yang diperbolehkan.';
        }
    }

    if (empty($error)) {
        // Update data guru termasuk foto jika ada
        $update = $db->prepare("UPDATE guru SET namaptk=:namaptk, email=:email, hp=:hp, alamat=:alamat, foto=:foto, updated_at=NOW() WHERE nik=:nik");
        $update->bindParam(':namaptk', $namaptk);
        $update->bindParam(':email', $email);
        $update->bindParam(':hp', $hp);
        $update->bindParam(':alamat', $alamat);
        $update->bindParam(':foto', $photoPath);
        $update->bindParam(':nik', $nik);
        $update->execute();

        // Jika password diisi, update juga password
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("UPDATE verifikasi_absen SET password=:password, password_plain=:password_plain WHERE nik=:nik");
            $stmt->bindParam(':password', $hashed);
            $stmt->bindParam(':password_plain', $password);
            $stmt->bindParam(':nik', $nik);
            $stmt->execute();
        }

        // Refresh halaman
        header("Location: guru_profil.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-pic-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .profile-pic-wrapper img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #4f8cff;
            box-shadow: 0 4px 16px #4f8cff22;
            transition: filter 0.2s;
        }
        .profile-pic-wrapper:hover img {
            filter: brightness(0.85);
        }
        .profile-pic-upload-label {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #4f8cff;
            color: #fff;
            border-radius: 50%;
            padding: 0.5rem 0.6rem;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px #4f8cff33;
            font-size: 1.2rem;
            transition: background 0.2s;
        }
        .profile-pic-upload-label:hover {
            background: #2563eb;
        }
        .profile-pic-input {
            display: none;
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
            <h3 class="mb-4">Profil Saya</h3>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Data berhasil diperbarui!</div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3 text-center">
                            <div class="profile-pic-wrapper mx-auto">
                                <?php if (!empty($guru['foto']) && file_exists($guru['foto'])): ?>
                                    <img src="<?= htmlspecialchars($guru['foto']) ?>" alt="Foto Profil" id="profilePicPreview">
                                <?php else: ?>
                                    <img src="assets/img/default-profile.png" alt="Foto Profil" id="profilePicPreview">
                                <?php endif; ?>
                                <label for="profilePicInput" class="profile-pic-upload-label" title="Ubah Foto">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" name="foto" id="profilePicInput" class="profile-pic-input" accept="image/*" onchange="previewProfilePic(event)">
                            </div>
                            <div class="small text-muted">Klik ikon kamera untuk mengubah foto profil</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">NIK</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($guru['nik']) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="namaptk" class="form-control" value="<?= htmlspecialchars($guru['namaptk']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($guru['email']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No HP</label>
                            <input type="text" name="hp" class="form-control" value="<?= htmlspecialchars($guru['hp']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control"><?= htmlspecialchars($guru['alamat']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru (opsional)</label>
                            <input type="password" name="password" class="form-control">
                            <small class="text-muted">Kosongkan jika tidak ingin mengganti password.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Terakhir Aktif</label>
                            <input type="text" class="form-control" value="<?= !empty($guru['last_activity']) ? htmlspecialchars($guru['last_activity']) : '-' ?>" disabled>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function previewProfilePic(event) {
        const input = event.target;
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePicPreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</body>
</html>