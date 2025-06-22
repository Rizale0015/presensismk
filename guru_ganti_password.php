<?php
session_start();
require 'database.php'; // Assuming this file contains the database connection code

if (isset($_POST['ganti_password'])) {
    $nik = $_SESSION['nik'];
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    
    // Get current password
    $stmt = $db->prepare("SELECT password FROM verifikasi_absen WHERE nik = :nik");
    $stmt->bindParam(':nik', $nik);
    $stmt->execute();
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify current password
    $valid = substr($current['password'], 0, 4) === '$2y$' 
        ? password_verify($password_lama, $current['password'])
        : ($password_lama === $current['password']);
        
    if ($valid) {
        // Hash new password
        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $db->prepare("UPDATE verifikasi_absen SET password = :password WHERE nik = :nik");
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':nik', $nik);
        
        if ($stmt->execute()) {
            $success = "Password berhasil diubah!";
        } else {
            $error = "Gagal mengubah password";
        }
    } else {
        $error = "Password lama tidak sesuai!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password</title>
</head>
<body>
    <h1>Ganti Password</h1>
    
    <?php if (isset($success)): ?>
        <p style="color:green;"><?php echo $success; ?></p>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div>
            <label for="password_lama">Password Lama:</label>
            <input type="password" id="password_lama" name="password_lama" required>
        </div>
        <div>
            <label for="password_baru">Password Baru:</label>
            <input type="password" id="password_baru" name="password_baru" required>
        </div>
        <div>
            <button type="submit" name="ganti_password">Ubah Password</button>
        </div>
    </form>
</body>
</html>