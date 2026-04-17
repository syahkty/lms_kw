<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['nim'])) { header("Location: ../auth/login.php"); exit(); }

$kode_mk = isset($_GET['id']) ? $_GET['id'] : '';
$nim = $_SESSION['nim'];
$role = $_SESSION['role'] ?? 'mahasiswa';

if(empty($kode_mk)){
    die("Kode Mata Kuliah tidak valid.");
}

// ==========================================
// FUNGSI KONVERSI NILAI (BERJALAN DI BACKGROUND)
// ==========================================
function konversiNilai($nilaiAngka) {
    if ($nilaiAngka >= 85) return ['huruf' => 'A', 'mutu' => '4'];
    if ($nilaiAngka >= 80) return ['huruf' => 'A-', 'mutu' => '3.75'];
    if ($nilaiAngka >= 75) return ['huruf' => 'B+', 'mutu' => '3.5'];
    if ($nilaiAngka >= 70) return ['huruf' => 'B', 'mutu' => '3.0'];
    if ($nilaiAngka >= 65) return ['huruf' => 'B-', 'mutu' => '2.75'];
    if ($nilaiAngka >= 60) return ['huruf' => 'C+', 'mutu' => '2.5'];
    if ($nilaiAngka >= 55) return ['huruf' => 'C', 'mutu' => '2.0'];
    if ($nilaiAngka >= 40) return ['huruf' => 'D', 'mutu' => '1.0'];
    return ['huruf' => 'E', 'mutu' => '0'];
}

// ==========================================
// 1. AMBIL NAMA MATA KULIAH DARI SIGA-8 (MENGGUNAKAN JWT BEARER)
// ==========================================
$nama_mk = "Detail Mata Kuliah"; 

if ($role === 'dosen') {
    // Sesuaikan URL jika API dosenmu butuh parameter, misal: ?nip=...
    $siga_data_url = "https://siga-8.syahkty.dev/api/api_get_dosen_mk.php"; 
} else {
    $siga_data_url = "https://siga-8.syahkty.dev/api/api_get_krs.php?nim=" . urlencode($nim);
}

// Pastikan token sudah dibuat oleh dashboard sebelumnya
if (isset($_SESSION['siga_token'])) {
    $ch = curl_init($siga_data_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $_SESSION['siga_token'],
        "Content-Type: application/json"
    ]);
    
    $api_response = curl_exec($ch);
    curl_close($ch);

    if ($api_response) {
        $api_data = json_decode($api_response, true);
        if (isset($api_data['status']) && $api_data['status'] === 'success') {
            
            // Logika pencarian nama mata kuliah (beda struktur antara dosen & mhs)
            if ($role === 'dosen') {
                $found = false;
                foreach ($api_data['data'] as $dosen) {
                    if ($found) break;
                    if ($dosen['dosen_nip'] === $nim && isset($dosen['daftar_mata_kuliah'])) {
                        foreach ($dosen['daftar_mata_kuliah'] as $course_api) {
                            if ($course_api['kode_mk'] === $kode_mk) {
                                $nama_mk = $course_api['nama_mk'];
                                $found = true;
                                break; // Keluar dari loop dalam
                            }
                        }
                    }
                }
            } else {
                foreach ($api_data['data'] as $course_api) {
                    if ($course_api['kode_mk'] === $kode_mk) {
                        $nama_mk = $course_api['nama_mk'];
                        break;
                    }
                }
            }
        }
    }
}

// ==========================================
// 2. AMBIL DESKRIPSI LOKAL & KALKULASI NILAI
// ==========================================
require_once '../config.php';

$stmt = $conn->prepare("SELECT * FROM course_details WHERE kode_mk = ?");
$stmt->bind_param("s", $kode_mk);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $course = $result->fetch_assoc();
    $cpl_list = json_decode($course['cpl_data'] ?? '[]', true);
} else {
    $course = [ 'deskripsi' => 'Deskripsi belum tersedia.', 'pengumuman' => '-', 'file_rps' => '-' ];
    $cpl_list = [];
}

// Kalkulasi nilai akhir di background dan siapkan daftar tugas untuk dinamis
$rekap_nilai = [];
$total_nilai_akhir = 0;
$hasil_konversi = null;
$daftar_tugas = [];

if ($role === 'mahasiswa') {
    $grade_sql = "SELECT a.id, a.judul, a.bobot, a.due_date, s.nilai 
                  FROM assignments a 
                  LEFT JOIN submissions s ON a.id = s.assignment_id AND s.nim = ? 
                  WHERE a.kode_mk = ? ORDER BY a.due_date ASC";
    $grade_stmt = $conn->prepare($grade_sql);
    $grade_stmt->bind_param("ss", $nim, $kode_mk);
} else {
    // Dosen: hanya butuh data tugas tanpa perlu join ke submissions satu per satu di sini
    $grade_sql = "SELECT id, judul, bobot, due_date FROM assignments WHERE kode_mk = ? ORDER BY due_date ASC";
    $grade_stmt = $conn->prepare($grade_sql);
    $grade_stmt->bind_param("s", $kode_mk);
}

$grade_stmt->execute();
$grade_result = $grade_stmt->get_result();

while ($row = $grade_result->fetch_assoc()) {
    $daftar_tugas[] = $row;
    
    // Hitung background nilai khusus untuk mahasiswa
    if ($role === 'mahasiswa') {
        $nilai_mentah = $row['nilai'] !== null ? intval($row['nilai']) : 0;
        $nilai_tertimbang = ($nilai_mentah * intval($row['bobot'])) / 100;
        $total_nilai_akhir += $nilai_tertimbang;
    }
}

if ($role === 'mahasiswa') {
    $hasil_konversi = konversiNilai($total_nilai_akhir);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($nama_mk); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f5f7; margin: 0; }
        header { background: white; padding: 10px 20px; display: flex; align-items: center; border-bottom: 1px solid #ddd; }
        header img { width: 40px; margin-right: 20px; }
        nav a { margin-right: 20px; text-decoration: none; color: #333; font-size: 14px; }
        nav a.active { font-weight: bold; }
        .user-menu { margin-left: auto; display: flex; align-items: center; gap: 15px;}
        .avatar { background: #eee; border-radius: 50%; padding: 8px 12px; font-weight: bold; color: #555; }
        
        .sub-nav { background-color: #0d47a1; padding: 0 40px; display: flex; gap: 20px; }
        .sub-nav a { color: white; text-decoration: none; padding: 12px 5px; font-size: 14px; }
        .sub-nav a.active { border-bottom: 3px solid white; font-weight: bold; }

        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .course-title { font-size: 24px; color: #333; margin-bottom: 20px; }
        
        .accordion-item { background: white; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 15px; overflow: hidden; }
        .accordion-header { padding: 15px 20px; display: flex; align-items: center; cursor: pointer; font-size: 16px; color: #333; transition: background 0.2s; }
        .accordion-header:hover { background-color: #f9f9f9; }
        
        .chevron { margin-right: 15px; color: #0d47a1; font-weight: bold; transition: transform 0.3s ease; display: inline-block; }
        .accordion-header.active .chevron { transform: rotate(90deg); }
        .accordion-title { font-weight: bold; flex-grow: 1; }
        
        .accordion-content { padding: 20px; border-top: 1px solid #eee; display: none; font-size: 14px; line-height: 1.6; }
        .expand-all { float: right; color: #0d47a1; cursor: pointer; font-size: 14px; margin-bottom: 15px;}
        
        .icon-text { display: flex; align-items: center; gap: 10px; margin: 15px 0; color: #0d47a1;}
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #f8f9fa; }
        .btn-edit { background: #0d47a1; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; float: right; }
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
        <a href="#" class="active">Course</a>
        <a href="#">Participants</a>
        <a href="#">Grades</a>
        <a href="#">Competencies</a>
        <a href="#">More ⌄</a>
    </div>

    <div class="container">
        <h1 class="course-title"><?= htmlspecialchars($nama_mk); ?></h1>
        
        <div class="expand-all" id="expandAllBtn">Expand all</div>
        <div style="clear: both;"></div>

        <div class="accordion-item">
            <div class="accordion-header active">
                <span class="chevron">❯</span>
                <span class="accordion-title">DESKRIPSI MATA KULIAH</span>
            </div>
            <div class="accordion-content" style="display: block;">
                <?php if ($role === 'dosen'): ?>
                    <a href="edit_course_detail.php?id=<?= htmlspecialchars($kode_mk); ?>" class="btn-edit">✏️ Edit Deskripsi</a>
                <?php endif; ?>
                <p><?= htmlspecialchars($course['deskripsi']); ?></p>
                <div class="icon-text"><span>💬</span> <a href="#">Pengumuman: <?= htmlspecialchars($course['pengumuman']); ?></a></div>
                <div class="icon-text" style="border-top: 1px solid #eee; padding-top: 15px;">
                    <span>📄</span> <a href="#">RPS (<?= htmlspecialchars($course['file_rps'] ?? '-'); ?>) DOCX</a>
                </div>
                
                <p style="margin-top: 20px; font-weight: bold;">CPL YANG DIBEBANKAN PADA MATA KULIAH</p>
                <table>
                    <?php if ($cpl_list): ?>
                        <?php foreach($cpl_list as $cpl): ?>
                        <tr>
                            <td width="20%"><?= htmlspecialchars($cpl['kode']); ?></td>
                            <td><?= htmlspecialchars($cpl['deskripsi']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">Data CPL belum diisi.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <?php if (!empty($daftar_tugas)): ?>
            <?php foreach ($daftar_tugas as $index => $tugas): ?>
                <div class="accordion-item">
                    <div class="accordion-header">
                        <span class="chevron">❯</span>
                        <span class="accordion-title">Tugas <?= $index + 1 ?>: <?= htmlspecialchars($tugas['judul'] ?? 'Tugas'); ?></span>
                    </div>
                    <div class="accordion-content">
                        <div class="icon-text" style="align-items: flex-start;">
                            <span style="color: #e91e63; font-size: 20px;">📄</span>
                            <div>
                                <a href="assignment.php?id=<?= $tugas['id']; ?>" style="text-decoration: none; color: #0d47a1; font-weight: bold;">
                                    Buka Detail Tugas
                                </a><br>
                                <small style="color: #666;">Bobot: <?= htmlspecialchars($tugas['bobot'] ?? '0'); ?>% | Tenggat: <?= isset($tugas['due_date']) ? date("d M Y H:i", strtotime($tugas['due_date'])) : '-'; ?></small>
                                <?php if ($role === 'mahasiswa'): ?>
                                    <br><small style="color: <?= isset($tugas['nilai']) && $tugas['nilai'] !== null ? '#4CAF50' : '#f44336'; ?>; font-weight: bold; margin-top: 5px; display: inline-block;">
                                        Nilai Kamu: <?= isset($tugas['nilai']) && $tugas['nilai'] !== null ? htmlspecialchars($tugas['nilai']) . '/100' : 'Belum dinilai/mengumpul'; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #666; margin-top: 20px; font-style: italic;">Belum ada tugas untuk mata kuliah ini.</p>
        <?php endif; ?>

    </div>

    <script>
        const headers = document.querySelectorAll('.accordion-header');
        headers.forEach(header => {
            header.addEventListener('click', function() {
                this.classList.toggle('active');
                const content = this.nextElementSibling;
                content.style.display = content.style.display === "block" ? "none" : "block";
            });
        });

        const expandAllBtn = document.getElementById('expandAllBtn');
        let isExpanded = false;
        expandAllBtn.addEventListener('click', function() {
            isExpanded = !isExpanded;
            this.textContent = isExpanded ? "Collapse all" : "Expand all";
            headers.forEach(header => {
                const content = header.nextElementSibling;
                if (isExpanded) {
                    header.classList.add('active');
                    content.style.display = "block";
                } else {
                    header.classList.remove('active');
                    content.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>