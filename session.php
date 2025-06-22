<?php
session_start();

// Session timeout: 30 menit (1800 detik)
$timeout_duration = 1800;

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Session hijacking protection
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '';

if (!isset($_SESSION['USER_AGENT'])) {
    $_SESSION['USER_AGENT'] = $user_agent;
    $_SESSION['USER_IP'] = $user_ip;
} else {
    if ($_SESSION['USER_AGENT'] !== $user_agent || $_SESSION['USER_IP'] !== $user_ip) {
        session_unset();
        session_destroy();
        header("Location: login.php?hijack=1");
        exit();
    }
}

function checkLogin() {
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }
}

function checkAdminAccess() {
    checkLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header("Location: guru_dashboard.php");
        exit();
    }
}

function checkGuruAccess() {
    checkLogin();
    if ($_SESSION['user_role'] !== 'guru') {
        header("Location: admin_dashboard.php");
        exit();
    }
}
?>