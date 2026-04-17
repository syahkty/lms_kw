<?php
session_start();

// 1. PROTEKSI HALAMAN (RBAC & Login Check)
if (!isset($_SESSION['nim']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') {
    echo "<script>
            alert('Akses Ditolak! Halaman ini hanya diperuntukkan bagi Dosen pengampu.');
            window.location.href = 'dashboard.php';
          </script>";
    exit();
}

require_once '../config.php';

// Gunakan string untuk kode_mk, bukan intval()
$kode_mk = isset($_GET['id']) ? $_GET['id'] : '';
$nim = $_SESSION['nim'];
$pesan = "";

if (empty($kode_mk)) {
    die("Kode Mata Kuliah tidak valid.");
}

// 2. PROSES UPDATE DATA (Jika tombol Simpan ditekan)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deskripsi_baru = trim($_POST['deskripsi']);
    $pengumuman_baru = trim($_POST['pengumuman']);

    // Menggunakan INSERT ... ON DUPLICATE KEY UPDATE
    // Ini memastikan jika course belum ada di tabel course_details, ia akan di-insert.
    // Jika sudah ada, ia hanya akan meng-update deskripsi dan pengumumannya.
    $update_query = "INSERT INTO course_details (kode_mk, deskripsi, pengumuman) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE deskripsi = VALUES(deskripsi), pengumuman = VALUES(pengumuman)";
                     
    $update_stmt = $conn->prepare($update_query);
    // "sss" karena ketiga variabel (kode_mk, deskripsi, pengumuman) adalah string
    $update_stmt->bind_param("sss", $kode_mk, $deskripsi_baru, $pengumuman_baru);
    
    if ($update_stmt->execute()) {
        header("Location: course_detail.php?id=" . urlencode($kode_mk));
        exit();
    } else {
        $pesan = "<div class='alert-error'>Gagal menyimpan perubahan: " . $conn->error . "</div>";
    }
    $update_stmt->close();
}

// 3. AMBIL NAMA MATA KULIAH DARI API
$api_url = "http://192.168.1.9/Siga-8%20kw/api_get_krs.php?nim=" . urlencode($nim);
$api_response = @file_get_contents($api_url);
$nama_mk = "Edit Mata Kuliah"; // Default jika gagal

if ($api_response) {
    $api_data = json_decode($api_response, true);
    if ($api_data['status'] === 'success') {
        foreach ($api_data['data'] as $course_api) {
            if ($course_api['kode_mk'] === $kode_mk) {
                $nama_mk = $course_api['nama_mk'];
                break;
            }
        }
    }
}

// 4. AMBIL DATA DESKRIPSI SAAT INI DARI DATABASE LOKAL
$stmt = $conn->prepare("SELECT deskripsi, pengumuman FROM course_details WHERE kode_mk = ?");
$stmt->bind_param("s", $kode_mk);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
} else {
    // Jika belum ada data di database lokal, siapkan array kosong
    $data = [
        'deskripsi' => '',
        'pengumuman' => ''
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Deskripsi - <?= htmlspecialchars($nama_mk); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f5f7; margin: 0; }
        
        /* Navbar Utama */
        header { background: white; padding: 10px 20px; display: flex; align-items: center; border-bottom: 1px solid #ddd; }
        header img { width: 40px; margin-right: 20px; }
        nav a { margin-right: 20px; text-decoration: none; color: #333; font-size: 14px; }
        nav a.active { font-weight: bold; }
        .user-menu { margin-left: auto; display: flex; align-items: center; gap: 15px;}
        .avatar { background: #eee; border-radius: 50%; padding: 8px 12px; font-weight: bold; color: #555; }
        
        /* Sub-Navbar Biru */
        .sub-nav {
            background-color: #0d47a1; /* Biru Untad */
            padding: 0 40px;
            display: flex;
            gap: 20px;
        }
        .sub-nav a {
            color: white;
            text-decoration: none;
            padding: 12px 5px;
            font-size: 14px;
        }
        .sub-nav a.active { border-bottom: 3px solid white; font-weight: bold; }

        /* Konten Halaman Form */
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .course-title { font-size: 24px; color: #333; margin-bottom: 20px; }
        
        .form-card {
            background: white;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; font-size: 14px; }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
            font-family: inherit;
        }
        .form-control:focus { outline: none; border-color: #0d47a1; }
        
        .btn-group { display: flex; justify-content: flex-end; gap: 10px; margin-top: 30px; }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
        }
        .btn-primary { background-color: #0d47a1; color: white; }
        .btn-primary:hover { background-color: #082e6b; }
        .btn-secondary { background-color: #e0e0e0; color: #333; }
        .btn-secondary:hover { background-color: #ccc; }
        
        .alert-error { background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #c62828;}
    </style>
</head>
<body>

    <header>
        <img src="../assets/img/logo-untad.png" alt="Logo">
        <nav>
            <a href="">Home</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="dashboard.php" class="active">My courses</a>
        </nav>
        <div class="user-menu">
            <span>🔔</span> <span>💬</span>
            <div class="avatar">AS</div>
        </div>
    </header>

    <div class="sub-nav">
        <a href="course_detail.php?id=<?= htmlspecialchars($kode_mk); ?>" class="active">⚙️ Edit Settings</a>
    </div>

    <div class="container">
        <h1 class="course-title">Edit Deskripsi: <?= htmlspecialchars($nama_mk); ?></h1>

        <div class="form-card">
            <?= $pesan; ?>
            
            <form method="POST" action="">
                
                <div class="form-group">
                    <label for="deskripsi">Deskripsi Mata Kuliah</label>
                    <textarea name="deskripsi" id="deskripsi" class="form-control" rows="6" placeholder="Masukkan ringkasan mata kuliah di sini..." required><?= htmlspecialchars($data['deskripsi'] ?? '') ?></textarea>
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">Teks ini akan muncul di halaman utama mata kuliah.</small>
                </div>

                <div class="form-group">
                    <label for="pengumuman">Pengumuman Terbaru (Opsional)</label>
                    <input type="text" name="pengumuman" id="pengumuman" class="form-control" placeholder="Contoh: Tugas Kelompok 1 sudah dibuka" value="<?= htmlspecialchars($data['pengumuman'] ?? '') ?>">
                </div>

                <div class="btn-group">
                    <a href="course_detail.php?id=<?= htmlspecialchars($kode_mk); ?>" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>

            </form>
        </div>
    </div>

</body>
</html>