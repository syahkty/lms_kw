<?php
session_start();

// Panggil file konfigurasi (pastikan path-nya benar)
require_once '../config.php'; 

$error = "";

// Proses Login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nim = trim($_POST['nim']);
    $password = md5($_POST['password']); // Tetap menggunakan MD5 sesuai databasemu saat ini

    // 1. MENGGUNAKAN PREPARED STATEMENT (Mencegah SQL Injection)
    // Pastikan query ini juga memanggil kolom 'role' dari tabel users
    $stmt = $conn->prepare("SELECT nim, nama_lengkap, role FROM users WHERE nim = ? AND password = ?");
    
    // 'ss' berarti dua parameter tersebut bertipe string
    $stmt->bind_param("ss", $nim, $password);
    $stmt->execute();
    
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // 2. MENYIMPAN DATA KE SESSION TERMASUK ROLE
        $_SESSION['nim'] = $row['nim'];
        $_SESSION['nama'] = $row['nama_lengkap'];
        
        // Jika kolom role kosong di database, otomatis jadikan 'mahasiswa'
        $_SESSION['role'] = !empty($row['role']) ? $row['role'] : 'mahasiswa'; 

        header("Location: ../pages/dashboard.php"); // Arahkan ke halaman course
        exit();
    } else {
        $error = "NIM/NIP atau Password salah!";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Universitas Tadulako</title>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .bg {
            /* Ganti dengan URL gambar gedung rektorat/fakultas yang kamu miliki */
            background-image: url('../assets/img/compress.jpg'); 
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 350px;
            text-align: center;
        }
        .logo {
            width: 80px;
            margin-bottom: 20px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #0d47a1; /* Biru Untad */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        .btn-login:hover { background-color: #082e6b; }
        .links {
            margin-top: 15px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
        }
        .links a { color: #0d47a1; text-decoration: none; }
        .error { color: red; margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>

<div class="bg">
    <div class="login-card">
        <img src="../assets/img/logo-untad.png" alt="Logo" class="logo">
        
        <?php if($error != "") { echo "<div class='error'>$error</div>"; } ?>

        <form method="POST" action="">
            <input type="text" name="nim" placeholder="NIM / NIP (misal: F52123043)" required>
            <input type="password" name="password" placeholder="Kata Sandi" required>
            <button type="submit" class="btn-login">Masuk</button>
        </form>

        <div class="links">
            <a href="#">Bahasa Indonesia (id)</a>
            <a href="#">Lupa kata sandi?</a>
        </div>
    </div>
</div>

</body>
</html>