<?php
session_start();

// Jika sudah ada session nim (sudah login), arahkan ke dashboard
if (isset($_SESSION['nim'])) {
    header("Location: pages/dashboard.php");
    exit();
}

// Jika belum login, arahkan ke halaman login
header("Location: auth/login.php");
exit();
?>
