<?php
session_start();
// Cek apakah user sudah login, jika belum kembalikan ke login
if (!isset($_SESSION['nim'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Logika untuk membuat inisial (Contoh: "Andi Syahkty" menjadi "AS")
$nama_lengkap = $_SESSION['nama'] ?? 'User Name';
$kata = explode(" ", $nama_lengkap);
$inisial = "";
if (count($kata) >= 2) {
    $inisial = strtoupper(substr($kata[0], 0, 1) . substr($kata[1], 0, 1));
} elseif (count($kata) == 1) {
    $inisial = strtoupper(substr($kata[0], 0, 2));
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f5f7;
            margin: 0;
        }
        /* Navbar */
        header {
            background: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        header img { width: 40px; margin-right: 20px; }
        nav a {
            margin-right: 20px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
        }
        nav a.active { font-weight: bold; }
        /* Styling Navbar & Icon Moodle */
.user-menu { 
    margin-left: auto; 
    display: flex; 
    align-items: center; 
    gap: 20px;
}

.icon-wrapper {
    position: relative;
    cursor: pointer;
    color: #555;
    font-size: 18px; /* Ukuran icon */
}

.icon-wrapper:hover { color: #0d47a1; }

.badge {
    position: absolute;
    top: -6px;
    right: -8px;
    background-color: #e53935; /* Merah notifikasi */
    color: white;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 5px;
    border-radius: 10px;
}

/* Styling Avatar & Dropdown */
.user-dropdown-container {
    position: relative;
    display: inline-block;
}

.avatar-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background 0.2s;
}

.avatar-wrapper:hover { background-color: #f0f0f0; }

.avatar { 
    background: #e9ecef; 
    border-radius: 50%; 
    width: 35px;
    height: 35px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: bold; 
    color: #495057; 
    font-size: 14px;
}

/* Menu Dropdown yang disembunyikan */
.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 50px;
    background-color: white;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1);
    border-radius: 4px;
    border: 1px solid #ddd;
    z-index: 1000;
    overflow: hidden;
}

/* Class untuk memunculkan dropdown via JS */
.dropdown-menu.show { display: block; }

.dropdown-menu a {
    color: #333;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    font-size: 14px;
    border-bottom: 1px solid #f1f1f1;
}

.dropdown-menu a i { margin-right: 10px; color: #666; width: 16px; text-align: center;}
.dropdown-menu a:hover { background-color: #f8f9fa; color: #0d47a1; }
.dropdown-menu a:hover i { color: #0d47a1; }

        /* Konten Utama */
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        h1 { font-size: 24px; color: #333; }
        .course-overview { background: white; padding: 20px; border-radius: 8px; }
        
        .filters { display: flex; gap: 10px; margin-bottom: 20px; }
        .filters select, .filters input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }

        /* Grid Kelas */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        .card-header { height: 120px; padding: 10px; }
        .card-header span { background: #0d47a1; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; }

        .card-body { padding: 15px; font-size: 14px; color: #0d47a1; height: 60px;}
        
        /* Footer */
        footer { background: #333; color: white; padding: 30px; text-align: center; margin-top: 40px; font-size: 14px;}
        .footer-bottom { background: #e67e22; padding: 15px; margin: -30px -30px -30px -30px; margin-top: 20px; color: white;}
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../assets/js/gambar.js"></script>
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
        <div class="icon-wrapper">
            <i class="far fa-bell"></i>
            <span class="badge">12</span> </div>
        
        <div class="icon-wrapper">
            <i class="far fa-comment"></i>
        </div>

        <div class="user-dropdown-container">
            <div class="avatar-wrapper" onclick="toggleDropdown(event)">
                <div class="avatar"><?= $inisial; ?></div>
                <i class="fas fa-chevron-down" style="font-size: 12px; color: #888;"></i>
            </div>
            
            <div class="dropdown-menu" id="userDropdown">
                <a href="#"><i class="far fa-user"></i> Profile</a>
                <a href="#"><i class="fas fa-cog"></i> Preferences</a>
                <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
            </div>
        </div>
    </div>
</header>

    <div class="container">
        <h1>My courses</h1>
        <div class="course-overview">
            <h4>Course overview</h4>
            <div class="filters">
                <select><option>All</option></select>
                <input type="text" placeholder="Search">
                <select><option>Sort by course name</option></select>
                <select><option>Card</option></select>
            </div>

            <div class="grid" id="courseGrid">
            </div>
        </div>
    </div>

    <footer>
        <p>You are logged in as <strong><?php echo $_SESSION['nama'] . " " . strtoupper($_SESSION['nim']); ?></strong> (<a href="../auth/logout.php" style="color:white;">Log out</a>)</p>
        <p>Data retention summary | Get the mobile app</p>
        <div class="footer-bottom">
            This theme was proudly developed by conecti.me
        </div>
    </footer>

   <script>
    document.addEventListener("DOMContentLoaded", function() {
        const grid = document.getElementById("courseGrid");
        
        // Ambil ID (NIM/NIP) dan Role dari session
        const userId = "<?php echo $_SESSION['nim']; ?>"; // Berisi NIM jika mahasiswa, NIP jika dosen
        const userRole = "<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'mahasiswa'; ?>";
        
        // URL API SEKARANG MENGARAH KE JEMBATAN LOKAL KITA (api_bridge_siga.php)
        const apiUrl = "../api/api_bridge_siga.php"; 

        // Tampilkan loading text sesuai peran
        if (userRole === 'dosen') {
            grid.innerHTML = "<p>Memuat kelas yang Anda ampu...</p>";
        } else {
            grid.innerHTML = "<p>Memuat mata kuliah KRS...</p>";
        }

        // Fetch data dari API Bridge
        fetch(apiUrl)
            .then(response => response.json())
            .then(res => {
                if(res.status === "success" && res.data) {
                    grid.innerHTML = ""; // Bersihkan loading state
                    
                    let coursesToRender = [];

                    // Logika Ekstraksi Data JSON
                    if (userRole === 'dosen') {
                        // Karena API dosen mengembalikan semua dosen, kita cari dosen yang NIP-nya cocok dengan session
                        const dosenData = res.data.find(d => d.dosen_nip === userId);
                        
                        // Jika dosen ditemukan dan punya daftar mata kuliah, masukkan ke array render
                        if (dosenData && dosenData.daftar_mata_kuliah) {
                            coursesToRender = dosenData.daftar_mata_kuliah;
                        }
                    } else {
                        // Jika mahasiswa, datanya langsung berupa array course
                        coursesToRender = res.data;
                    }

                    // Render Card Mata Kuliah
                    if (coursesToRender.length > 0) {
                        coursesToRender.forEach(course => {
                            
                            // Siapkan info tambahan di bagian bawah card
                            let extraInfo = "";
                            if (userRole === 'mahasiswa') {
                                extraInfo = `<br><small style="color: #444; font-size: 11px; display: block; margin-top: 5px;">
                                                👨‍🏫 ${course.dosen_nama || 'Dosen Belum Diatur'}
                                             </small>`;
                            } else if (userRole === 'dosen') {
                                extraInfo = `<br><small style="color: #e67e22; font-size: 11px; display: block; margin-top: 5px;">
                                                ⭐ Kelas ${course.kelas} | 🕒 ${course.waktu_mengajar}
                                             </small>`;
                            }

                            // Membuat elemen HTML card
                            const cardHTML = `
                            <div class="card">
                                <a href="course_detail.php?id=${course.kode_mk}" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                                    <div class="card-header" 
                                        style="background-image: url('${generateMoodlePattern(course.nama_mk)}'); background-size: cover; background-position: center; padding: 15px; color: white; font-weight: bold; border-radius: 5px 5px 0 0; min-height: 100px;">
                                        <span style="background: rgba(0,0,0,0.3); padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                                            S1 Sistem Informasi
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <strong>${course.nama_mk}</strong> <br>
                                    </div>
                                </a>
                            </div>
                            `;
                            grid.insertAdjacentHTML('beforeend', cardHTML); 
                        });
                    } else {
                        grid.innerHTML = `<p style='color:#666;'>Tidak ada mata kuliah yang ditemukan.</p>`;
                    }

                } else {
                    grid.innerHTML = `<p style='color:red;'>Gagal memuat mata kuliah. Data kosong atau terjadi masalah pada jembatan API.</p>`;
                }
            })
            .catch(error => {
                grid.innerHTML = "<p style='color:red;'>Gagal terhubung ke API. Pastikan file api_bridge_siga.php sudah dibuat.</p>";
                console.error("Error fetching API:", error);
            });
    });
</script>
<script>
    // Fungsi memunculkan dropdown
    function toggleDropdown(event) {
        document.getElementById("userDropdown").classList.toggle("show");
        event.stopPropagation(); // Mencegah event klik menyebar ke window
    }

    // Menutup dropdown jika user klik di luar area avatar
    window.onclick = function(event) {
        if (!event.target.matches('.avatar-wrapper') && !event.target.closest('.avatar-wrapper')) {
            var dropdowns = document.getElementsByClassName("dropdown-menu");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
</script>
</body>
</html>