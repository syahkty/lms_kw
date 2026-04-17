<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['nim'])) {
    echo json_encode(["status" => "error", "message" => "Sesi login tidak ditemukan."]);
    exit();
}

$nim = $_SESSION['nim'];
$role = $_SESSION['role'] ?? 'mahasiswa';

// URL API Siga-8
$siga_auth_url = "https://siga-8.syahkty.dev/api/api_auth.php";

if ($role === 'dosen') {
    // Sesuaikan URL jika API dosenmu butuh parameter, misal: ?nip=...
    $siga_data_url = "https://siga-8.syahkty.dev/api/api_get_dosen_mk.php"; 
} else {
    $siga_data_url = "https://siga-8.syahkty.dev/api/api_get_krs.php?nim=" . urlencode($nim);
}

// 1. FUNGSI UNTUK MENDAPATKAN TOKEN JWT DARI SIGA-8
function getSigaToken($url) {
    $ch = curl_init($url);
    // Masukkan kredensial Siga-8 di sini (Aman karena dijalankan di backend)
    $payload = json_encode([
        "username" => "siga8_admin",
        "password" => "password_rahasia_siga8"
    ]);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return isset($data['token']) ? $data['token'] : null;
}

// 2. FUNGSI UNTUK MENARIK DATA MENGGUNAKAN BEARER TOKEN
function fetchSigaData($url, $token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $token,
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ["code" => $httpcode, "body" => $response];
}

// --- ALUR EKSEKUSI ---

// Cek apakah LMS sudah punya token Siga-8 di Session. Jika belum, ambil baru!
if (!isset($_SESSION['siga_token'])) {
    $_SESSION['siga_token'] = getSigaToken($siga_auth_url);
}

// Coba tarik data KRS/Jadwal pakai token yang ada
$result = fetchSigaData($siga_data_url, $_SESSION['siga_token']);

// Jika Siga-8 menolak (401 Unauthorized) karena token expired, kita ambil token baru lalu coba 1x lagi
if ($result['code'] == 401) {
    $_SESSION['siga_token'] = getSigaToken($siga_auth_url);
    $result = fetchSigaData($siga_data_url, $_SESSION['siga_token']);
}

// Langsung lempar hasilnya ke JavaScript (dashboard.php)
echo $result['body'];
?>