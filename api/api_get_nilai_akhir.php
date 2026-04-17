<?php

function getAuthorizationHeader(){
    $headers = null;
    
    // Cek jalur standar Nginx / FastCGI
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } 
    // Cek jalur alternatif
    elseif (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } 
    // Cek jalur Apache (jaga-jaga jika dipakai di Laragon lokal)
    elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

// Cara menggunakannya untuk mengambil token:
$authHeader = getAuthorizationHeader();

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    echo json_encode(["status" => "error", "message" => "Akses Ditolak! Token Bearer tidak ditemukan."]);
    exit;
}

$jwt_token_yang_diterima = $matches[1];
// Mengatur header agar response dibaca sebagai JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Mengizinkan Siga-8 untuk request (CORS)

// ==========================================
// PENGAMANAN MENGGUNAKAN JWT (BEARER TOKEN)
// ==========================================
$secret_key = "KUNCI_RAHASIA_JWT_UNTAD_2026"; // Harus sama persis dengan di api_auth.php

// Ambil Header Authorization
$headers = apache_request_headers();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Ekstrak token dari format "Bearer <token>"
if (!preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Akses Ditolak! Token Bearer tidak ditemukan."]);
    exit();
}

$jwt = $matches[1];
$tokenParts = explode('.', $jwt);

if (count($tokenParts) != 3) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Format Token tidak valid."]);
    exit();
}

$header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
$payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
$signature_provided = $tokenParts[2];

// Cek apakah tanda tangan valid (Token tidak diedit di tengah jalan)
$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);
$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

if ($base64UrlSignature !== $signature_provided) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token tidak valid (Signature gagal)."]);
    exit();
}

// Cek apakah token sudah kedaluwarsa (Expired)
$payload_data = json_decode($payload, true);
if (isset($payload_data['exp']) && time() > $payload_data['exp']) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token sudah kedaluwarsa. Silakan login ulang."]);
    exit();
}



// Fungsi konversi nilai standar Untad
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

// 1. Tangkap Parameter dari URL
$nim = isset($_GET['nim']) ? $_GET['nim'] : '';
$kode_mk = isset($_GET['kode_mk']) ? $_GET['kode_mk'] : '';

if (empty($nim) || empty($kode_mk)) {
    echo json_encode([
        "status" => "error",
        "message" => "Parameter 'nim' dan 'kode_mk' wajib diisi!"
    ]);
    exit();
}

// 2. Koneksi ke Database LMS
$target_config = file_exists('../config.php') ? '../config.php' : 'config.php';
require_once $target_config;

// 3. Kalkulasi Nilai
$total_nilai_akhir = 0;
$rincian_nilai = [];

// Query untuk mengambil semua tugas di MK ini + nilai mahasiswa yang bersangkutan
$grade_sql = "SELECT a.id, a.judul, a.bobot, s.nilai 
              FROM assignments a 
              LEFT JOIN submissions s ON a.id = s.assignment_id AND s.nim = ? 
              WHERE a.kode_mk = ?";
              
$stmt = $conn->prepare($grade_sql);
$stmt->bind_param("ss", $nim, $kode_mk);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Tidak ada data tugas/penilaian untuk mata kuliah ini."
    ]);
    exit();
}

while ($row = $result->fetch_assoc()) {
    $nilai_mentah = $row['nilai'] !== null ? intval($row['nilai']) : 0;
    $nilai_tertimbang = ($nilai_mentah * intval($row['bobot'])) / 100;
    $total_nilai_akhir += $nilai_tertimbang;
    
    $rincian_nilai[] = [
        "assignment_id" => $row['id'],
        "judul_tugas" => $row['judul'],
        "bobot_persen" => intval($row['bobot']),
        "nilai_asli" => $nilai_mentah,
        "nilai_tertimbang" => $nilai_tertimbang
    ];
}

// 4. Konversi ke Huruf Mutu
$hasil_konversi = konversiNilai($total_nilai_akhir);

// 5. Susun Response JSON untuk Siga-8
$response = [
    "status" => "success",
    "data" => [
        "nim" => $nim,
        "kode_mk" => $kode_mk,
        "nilai_akhir" => [
            "angka" => round($total_nilai_akhir, 2),
            "huruf" => $hasil_konversi['huruf'],
            "mutu" => $hasil_konversi['mutu']
        ],
        "rincian" => $rincian_nilai
    ]
];

// Cetak Output
echo json_encode($response, JSON_PRETTY_PRINT);

$stmt->close();
$conn->close();
?>