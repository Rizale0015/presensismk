<?php
session_start();
require_once 'database.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check admin login with hashed password
    $admin_username = 'admin';
    $admin_hashed_password = '$2y$10$5FxBEhDZjGpCT2vJxhneUunB4Lff/2OaIlxaUzjW5uGampgnL19oa'; // example hash for 'admin123'
    if ($username === $admin_username && password_verify($password, $admin_hashed_password)) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['username'] = 'Administrator';
        header("Location: admin_dashboard.php");
        exit();
    }

    // Check guru login by NIK or by full name (namaptk)
    $query = "SELECT v.*, g.nik, g.namaptk FROM verifikasi_absen v 
              JOIN guru g ON v.nik = g.nik 
              WHERE (v.nik = :username OR g.namaptk = :username) AND v.status = 'aktif'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // Check if password is hashed (starts with $2y$)
        if (substr($user['password'], 0, 4) === '$2y$') {
            // Verify hashed password
            if (password_verify($password, $user['password'])) {
                // Login success
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_role'] = 'guru';
                $_SESSION['nik'] = $user['nik'];
                $_SESSION['username'] = $user['namaptk'];
                header("Location: guru_dashboard.php");
                exit();
            }
        } else {
            // Check plain password
            if ($password === $user['password']) {
                // Login success
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_role'] = 'guru';
                $_SESSION['nik'] = $user['nik'];
                $_SESSION['username'] = $user['namaptk'];
                header("Location: guru_dashboard.php");
                exit();
            }
        }
    }

    $error = "Username atau password salah!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #4f8cff 0%, #6fd6ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 32px rgba(0,0,0,0.12);
            padding: 2.5rem 2rem;
            background: #fff;
            width: 100%;
            max-width: 370px;
        }
        .login-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            margin-bottom: 1rem;
        }
        .input-group-text {
            background: #f4f8fb;
        }
        .form-control:focus {
            box-shadow: 0 0 0 2px #4f8cff33;
        }
        .btn-primary {
            background: #4f8cff;
            border: none;
        }
        .btn-primary:hover {
            background: #357ae8;
        }
    </style>
</head>
<body>
    <div class="login-card mx-auto">
        <div class="text-center mb-4">
            <img src="assets/img/logoAA.png" alt="Logo" class="login-logo">
            <h4 class="fw-bold mb-1">Sistem Presensi</h4>
            <div class="text-muted mb-2">Login</div>
        </div>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['reset'])): ?>
            <?php if ($_GET['reset'] == 'success'): ?>
                <div class="alert alert-success">
                    Password baru telah dikirim ke email Anda. Silakan cek email Anda.
                </div>
            <?php elseif ($_GET['reset'] == 'error'): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_GET['msg'] ?? 'Terjadi kesalahan saat reset password') ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" class="form-control" required autofocus placeholder="Username">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Password">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="d-grid mb-2">
                <button type="submit" class="btn btn-primary py-2 fw-bold">Login</button>
            </div>
        </form>
        <div class="text-center mt-3">
            <small class="text-muted">&copy; <?= date('Y') ?> Sistem Presensi</small>
        </div>
    </div>

    <!-- Forget Password Modal -->
    <div class="modal fade" id="forgetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="reset_password.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                            <small class="text-muted">Masukkan email yang terdaftar pada akun Anda</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
    </script>
</body>
</html>