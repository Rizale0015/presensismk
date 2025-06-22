<?php
require_once 'session.php';
require_once 'database.php';
checkAdminAccess();

$database = new Database();
$db = $database->getConnection();

$username = $_SESSION['username'];

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ambil data admin dari database
$stmt = $db->prepare("SELECT id, username, email, nama_lengkap FROM admin WHERE username = :username");
$stmt->bindParam(':username', $username);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin) {
    // Handle case when admin data is not found
    $admin = ['id' => '', 'username' => $username, 'email' => '', 'nama_lengkap' => ''];
}

$id = $admin['id'] ?? null;

// Proses update password (jika ada)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';

    // Validasi email sederhana
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        // Check unique email
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM admin WHERE email = :email AND id != :id");
        $stmtCheck->bindParam(':email', $email);
        $stmtCheck->bindParam(':id', $id);
        $stmtCheck->execute();
        $emailCount = $stmtCheck->fetchColumn();
        if ($emailCount > 0) {
            $error = "Email sudah digunakan oleh admin lain!";
        }
    }

    // Password validation and confirmation
    if (empty($error) && !empty($password)) {
        if (strlen($password) < 8) {
            $error = "Password harus minimal 8 karakter!";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = "Password harus mengandung setidaknya satu huruf kapital!";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = "Password harus mengandung setidaknya satu huruf kecil!";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = "Password harus mengandung setidaknya satu angka!";
        } elseif ($password !== $confirm_password) {
            $error = "Konfirmasi password tidak cocok!";
        }
    }

    if (empty($error)) {
        // Update email (dan password jika diisi)
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmtUpdate = $db->prepare("UPDATE admin SET email = :email, nama_lengkap = :nama_lengkap, password = :password WHERE id = :id");
            $stmtUpdate->bindParam(':email', $email);
            $stmtUpdate->bindParam(':password', $hashed);
            $stmtUpdate->bindParam(':nama_lengkap', $nama_lengkap);
            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->execute();
        } else {
            $stmtUpdate = $db->prepare("UPDATE admin SET email = :email, nama_lengkap = :nama_lengkap WHERE id = :id");
            $stmtUpdate->bindParam(':email', $email);
            $stmtUpdate->bindParam(':nama_lengkap', $nama_lengkap);
            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->execute();
        }
        header("Location: admin_profil.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Profil Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/custom.css" />
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'admin_header.php'; ?>
<div class="container mt-4">
    <h3 class="mb-4">Profil Admin</h3>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Data berhasil diperbarui!</div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($admin['username']) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-control" value="<?= isset($admin['nama_lengkap']) ? htmlspecialchars($admin['nama_lengkap']) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password Baru (opsional)</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" required minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                        <button type="button" class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted">Kosongkan jika tidak ingin mengganti password.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                </div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const pw = document.getElementById('password');
    const icon = this.querySelector('i');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Client-side password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function () {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    if (password !== confirmPassword) {
        this.setCustomValidity('Konfirmasi password tidak cocok!');
    } else {
        this.setCustomValidity('');
    }
});
</script>
</body>
</html>
