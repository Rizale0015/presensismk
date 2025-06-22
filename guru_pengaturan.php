<?php
require_once 'session.php';
require_once 'database.php';
checkGuruAccess();

// Initialize or get current settings from session
if (!isset($_SESSION['settings'])) {
    $_SESSION['settings'] = [
        'theme' => 'light',
        'timezone' => 'Asia/Jakarta'
    ];
}

$settings = $_SESSION['settings'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? 'light';
    $timezone = $_POST['timezone'] ?? 'Asia/Jakarta';

    // Save settings to session
    $_SESSION['settings']['theme'] = $theme;
    $_SESSION['settings']['timezone'] = $timezone;

    $success = "Pengaturan berhasil disimpan.";
    $settings = $_SESSION['settings'];
}

// List of common timezones for selection
$timezones = timezone_identifiers_list();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Pengaturan - Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/guru.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body.light-mode {
            background-color: #fff;
            color: #000;
        }
        body.dark-mode {
            background-color: #121212;
            color: #fff;
        }
        .card {
            background-color: inherit;
            color: inherit;
        }
        .form-control, .form-select {
            background-color: inherit;
            color: inherit;
            border-color: #ccc;
        }
    </style>
</head>
<body class="<?php echo $settings['theme'] === 'dark' ? 'dark-mode' : 'light-mode'; ?>">
    <?php include 'guru_header.php'; ?>


<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include 'guru_sidebar.php'; ?>
        </div>
        <div class="col-md-9 main-content">
            <h5>Pengaturan</h5>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST" action="guru_pengaturan.php">
                <div class="mb-3">
                    <label for="theme" class="form-label">Tema</label>
                    <select class="form-select" id="theme" name="theme" required>
                        <option value="light" <?php echo $settings['theme'] === 'light' ? 'selected' : ''; ?>>Light Mode</option>
                        <option value="dark" <?php echo $settings['theme'] === 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="timezone" class="form-label">Zona Waktu</label>
                    <select class="form-select" id="timezone" name="timezone" required>
                        <?php foreach ($timezones as $tz): ?>
                            <option value="<?php echo htmlspecialchars($tz); ?>" <?php echo $settings['timezone'] === $tz ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tz); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Apply theme class to body dynamically if needed
    const themeSelect = document.getElementById('theme');
    themeSelect.addEventListener('change', function() {
        if (this.value === 'dark') {
            document.body.classList.add('dark-mode');
            document.body.classList.remove('light-mode');
        } else {
            document.body.classList.add('light-mode');
            document.body.classList.remove('dark-mode');
        }
    });
</script>
</body>
</html>
