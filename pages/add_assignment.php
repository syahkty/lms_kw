<?php
session_start();

// 1. PROTEKSI HALAMAN
if (!isset($_SESSION['nim']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') {
    echo "<script>
            alert('Akses Ditolak! Halaman ini hanya diperuntukkan bagi Dosen pengampu.');
            window.location.href = '../auth/login.php';
          </script>";
    exit();
}

require_once '../config.php';

$kode_mk = isset($_GET['kode_mk']) ? $_GET['kode_mk'] : '';
if (empty($kode_mk)) {
    die("Kode Mata Kuliah tidak valid.");
}

$pesan = "";

// 2. PROSES INSERT TUGAS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul']);
    $pertemuan_ke = intval($_POST['pertemuan_ke']);
    $deskripsi = trim($_POST['deskripsi']);
    $due_date_input = $_POST['due_date']; // format HTML datetime-local: YYYY-MM-DDThh:mm
    $due_date = date("Y-m-d H:i:s", strtotime($due_date_input));
    $bobot = intval($_POST['bobot']);

    $stmt = $conn->prepare("INSERT INTO assignments (kode_mk, pertemuan_ke, judul, deskripsi, due_date, bobot) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisssi", $kode_mk, $pertemuan_ke, $judul, $deskripsi, $due_date, $bobot);
    
    if ($stmt->execute()) {
        header("Location: course_detail.php?id=" . urlencode($kode_mk));
        exit();
    } else {
        $pesan = "<div class='alert-error'>Gagal menambahkan tugas: " . $conn->error . "</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Tugas Baru</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f5f7; margin: 0; }
        
        /* Navbar Utama */
        header { background: white; padding: 10px 20px; display: flex; align-items: center; border-bottom: 1px solid #ddd; }
        header img { width: 40px; margin-right: 20px; }
        nav a { margin-right: 20px; text-decoration: none; color: #333; font-size: 14px; }
        .user-menu { margin-left: auto; display: flex; align-items: center; gap: 15px;}
        .avatar { background: #eee; border-radius: 50%; padding: 8px 12px; font-weight: bold; color: #555; }
        
        /* Sub-Navbar Biru */
        .sub-nav {
            background-color: #0d47a1; 
            padding: 0 40px;
            display: flex;
            gap: 20px;
        }
        .sub-nav a { color: white; text-decoration: none; padding: 12px 5px; font-size: 14px; }
        .sub-nav a.active { border-bottom: 3px solid white; font-weight: bold; }

        /* Form */
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .form-card { background: white; border: 1px solid #ccc; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; font-size: 14px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; font-family: inherit; }
        .form-control:focus { outline: none; border-color: #0d47a1; }
        
        .btn-group { display: flex; justify-content: flex-end; gap: 10px; margin-top: 30px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; }
        .btn-primary { background-color: #0d47a1; color: white; }
        .btn-primary:hover { background-color: #082e6b; }
        .btn-secondary { background-color: #e0e0e0; color: #333; }
        .alert-error { background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <header>
        <img src="../assets/img/logo-untad.png" alt="Logo">
        <nav>
            <a href="#">Home</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="dashboard.php" class="active">My courses</a>
        </nav>
        <div class="user-menu">
            <div class="avatar">⚙️</div>
        </div>
    </header>

    <div class="sub-nav">
        <a href="course_detail.php?id=<?= htmlspecialchars($kode_mk); ?>" class="active">← Kembali ke Mata Kuliah</a>
    </div>

    <div class="container">
        <h1 style="color: #333;">Tambah Tugas Baru</h1>

        <div class="form-card">
            <?= $pesan; ?>
            
            <form method="POST" action="">
                
                <div class="form-group">
                    <label>Judul Tugas</label>
                    <input type="text" name="judul" class="form-control" placeholder="Contoh: Kuis 1 - Pemrograman Dasar" required>
                </div>

                <div class="form-group" style="display: flex; gap: 20px;">
                    <div style="flex: 1;">
                        <label>Pertemuan Ke-</label>
                        <input type="number" name="pertemuan_ke" class="form-control" min="1" max="16" required placeholder="Contoh: 4">
                    </div>
                    <div style="flex: 1;">
                        <label>Bobot Nilai (%)</label>
                        <input type="number" name="bobot" class="form-control" min="1" max="100" required placeholder="Contoh: 10">
                    </div>
                </div>

                <div class="form-group">
                    <label>Tenggat Waktu Pengumpulan (Due Date)</label>
                    <input type="datetime-local" name="due_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Deskripsi Tugas</label>
                    <textarea name="deskripsi" class="form-control" rows="5" placeholder="Tuliskan petunjuk mengerjakan tugas di sini..." required></textarea>
                </div>

                <div class="btn-group">
                    <a href="course_detail.php?id=<?= htmlspecialchars($kode_mk); ?>" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">➕ Buat Tugas</button>
                </div>

            </form>
        </div>
    </div>

</body>
</html>
