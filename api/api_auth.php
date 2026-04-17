<?php
header('Content-Type: application/json');

// Kredensial khusus untuk sistem Siga-8 (Jangan beritahu siapa-siapa)
$valid_username = "siga8_admin";
$valid_password = "password_rahasia_siga8";
$secret_key = "KUNCI_RAHASIA_JWT_UNTAD_2026"; // Kunci untuk enkripsi

// Tangkap input dari Siga-8 (menggunakan metode POST)
$input = json_decode(file_get_contents('php://input'), true);
$user = $input['username'] ?? '';
$pass = $input['password'] ?? '';

if ($user === $valid_username && $pass === $valid_password) {
    // 1. Buat Header JWT
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

    // 2. Buat Payload JWT (Isi Token: Waktu dibuat & Kedaluwarsa dalam 1 jam)
    $issuedAt = time();
    $expirationTime = $issuedAt + 3600;  // Token hangus dalam 3600 detik (1 jam)
    $payload = json_encode([
        'iss' => 'lms_untad',
        'aud' => 'siga8',
        'iat' => $issuedAt,
        'exp' => $expirationTime
    ]);
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    // 3. Buat Signature (Tanda Tangan Digital)
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // 4. Gabungkan menjadi satu string JWT
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    echo json_encode([
        "status" => "success",
        "message" => "Login berhasil",
        "token" => $jwt,
        "expires_in" => 3600
    ]);
} else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Username atau Password salah"]);
}
?>