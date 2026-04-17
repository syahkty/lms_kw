<?php
session_start();
if (!isset($_SESSION['nim'])) { header("Location: ../auth/login.php"); exit(); }

$conn = new mysqli("localhost", "root", "", "untad_lms");
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['nim'];
$role = $_SESSION['role'] ?? 'mahasiswa';

// 1. Ambil detail tugas
$stmt = $conn->prepare("SELECT * FROM assignments WHERE id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) { die("Tugas tidak ditemukan."); }

// 2. PROSES UPLOAD TUGAS (MAHASISWA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'mahasiswa' && isset($_FILES['file_tugas'])) {
    $target_dir = "../uploads/";
    // Pastikan folder uploads sudah ada
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $file_name = time() . "_" . basename($_FILES["file_tugas"]["name"]);
    $target_file = $target_dir . $file_name;
    $waktu_kumpul = date("Y-m-d H:i:s");

    if (move_uploaded_file($_FILES["file_tugas"]["tmp_name"], $target_file)) {
        // Simpan ke database
        $insert = $conn->prepare("INSERT INTO submissions (assignment_id, nim, file_path, submitted_at) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isss", $assignment_id, $user_id, $file_name, $waktu_kumpul);
        $insert->execute();
        header("Location: assignment.php?id=" . $assignment_id);
        exit();
    }
}

// 3. PROSES PENILAIAN (DOSEN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'dosen' && isset($_POST['nilai'])) {
    $submission_id = $_POST['submission_id'];
    $nilai = intval($_POST['nilai']);
    
    $update = $conn->prepare("UPDATE submissions SET nilai = ? WHERE id = ?");
    $update->bind_param("ii", $nilai, $submission_id);
    $update->execute();
    header("Location: assignment.php?id=" . $assignment_id);
    exit();
}

// 4. Ambil status submission khusus mahasiswa ini
$sub_stmt = $conn->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND nim = ?");
$sub_stmt->bind_param("is", $assignment_id, $user_id);
$sub_stmt->execute();
$my_submission = $sub_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($assignment['judul']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f5f7; margin: 0; }
        header { background: white; padding: 10px 20px; display: flex; align-items: center; border-bottom: 1px solid #ddd; }
        header img { width: 40px; margin-right: 20px; }
        nav a { margin-right: 20px; text-decoration: none; color: #333; font-size: 14px; }
        
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        h2 { color: #333; margin-bottom: 5px; }
        
        .assignment-info { background: white; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 20px; }
        
        /* Tabel Moodle Style */
        h3 { color: #333; margin-top: 30px; }
        table.moodle-table { width: 100%; border-collapse: collapse; background: white; }
        table.moodle-table th, table.moodle-table td { padding: 12px 15px; border: 1px solid #ddd; text-align: left; font-size: 14px; }
        table.moodle-table th { background-color: #f8f9fa; width: 25%; color: #333;}
        
        /* Warna Status */
        .bg-success { background-color: #d4edda !important; color: #155724; }
        .bg-warning { background-color: #fff3cd !important; color: #856404; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; color: white; background-color: #0d47a1; font-weight: bold; margin-top: 20px; text-decoration: none; display: inline-block;}
        .form-upload { background: white; padding: 20px; border: 1px dashed #ccc; border-radius: 8px; margin-top: 20px; text-align: center; }
        
        /* Input Nilai Dosen */
        .input-nilai { width: 60px; padding: 5px; text-align: center; border: 1px solid #ccc; border-radius: 4px;}
        .btn-sm { background-color: #28a745; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;}

        /* Navbar Utama */
        header { background: white; padding: 10px 20px; display: flex; align-items: center; border-bottom: 1px solid #ddd; }
        header img { width: 40px; margin-right: 20px; }
        nav a { margin-right: 20px; text-decoration: none; color: #333; font-size: 14px; }
        nav a.active { font-weight: bold; }
        .user-menu { margin-left: auto; display: flex; align-items: center; gap: 15px;}
        .avatar { background: #eee; border-radius: 50%; padding: 8px 12px; font-weight: bold; color: #555; }
        
        /* Sub-Navbar Biru (Persis di gambar) */
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

    <div class="container">
        <h2>📄 <?= htmlspecialchars($assignment['judul']); ?></h2>
        
        <div class="assignment-info">
            <p><?= nl2br(htmlspecialchars($assignment['deskripsi'])); ?></p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
            <small style="color: #666;">
                <strong>Due date:</strong> <?= date("l, d F Y, h:i A", strtotime($assignment['due_date'])); ?>
            </small>
        </div>

        <?php if ($role === 'mahasiswa'): ?>
            <h3>Submission status</h3>
            <table class="moodle-table">
                <tr>
                    <th>Submission status</th>
                    <td class="<?= $my_submission ? 'bg-success' : ''; ?>">
                        <?= $my_submission ? 'Submitted for grading' : 'No attempt'; ?>
                    </td>
                </tr>
                <tr>
                    <th>Grading status</th>
                    <td class="<?= ($my_submission && $my_submission['nilai'] !== null) ? 'bg-success' : 'bg-warning'; ?>">
                        <?php 
                            if (!$my_submission) echo "Not graded";
                            elseif ($my_submission['nilai'] === null) echo "Not graded";
                            else echo "Graded (" . $my_submission['nilai'] . "/100)";
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Last modified</th>
                    <td><?= $my_submission ? date("l, d F Y, h:i A", strtotime($my_submission['submitted_at'])) : '-'; ?></td>
                </tr>
                <tr>
                    <th>File submissions</th>
                    <td>
                        <?php if($my_submission): ?>
                            <a href="../uploads/<?= $my_submission['file_path']; ?>" target="_blank">📥 Download File Tugas</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php if (!$my_submission): ?>
                <div class="form-upload">
                    <form method="POST" enctype="multipart/form-data">
                        <p>Upload file tugas Anda di sini (PDF/DOCX):</p>
                        <input type="file" name="file_tugas" required accept=".pdf,.doc,.docx">
                        <br>
                        <button type="submit" class="btn">Add submission</button>
                    </form>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <h3>Daftar Mahasiswa yang Mengumpulkan</h3>
            <table class="moodle-table">
                <tr>
                    <th style="width: 20%;">NIM</th>
                    <th style="width: 30%;">Waktu Kumpul</th>
                    <th style="width: 20%;">File Tugas</th>
                    <th style="width: 30%;">Nilai (0-100)</th>
                </tr>
                <?php
                // Ambil semua mahasiswa yang sudah mengumpul tugas ini
                $all_subs = $conn->query("SELECT * FROM submissions WHERE assignment_id = $assignment_id");
                while ($sub = $all_subs->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($sub['nim']); ?></td>
                    <td><?= date("d M Y H:i", strtotime($sub['submitted_at'])); ?></td>
                    <td><a href="../uploads/<?= $sub['file_path']; ?>" target="_blank">Lihat File</a></td>
                    <td>
                        <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                            <input type="hidden" name="submission_id" value="<?= $sub['id']; ?>">
                            <input type="number" name="nilai" class="input-nilai" min="0" max="100" value="<?= $sub['nilai'] ?? ''; ?>" required placeholder="0">
                            <button type="submit" class="btn-sm">Simpan</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($all_subs->num_rows == 0): ?>
                    <tr><td colspan="4" style="text-align: center;">Belum ada mahasiswa yang mengumpulkan.</td></tr>
                <?php endif; ?>
            </table>
        <?php endif; ?>

    </div>
</body>
</html>