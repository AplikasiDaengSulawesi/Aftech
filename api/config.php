<?php
date_default_timezone_set('Asia/Makassar');
require __DIR__ . '/../includes/db_credentials.php';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Koneksi ke Database Gagal"]);
    exit;
}

$conn->query("SET time_zone = '+08:00'");

// ==========================================
// 🛡️ SECURITY GATEKEEPER (HIBRIDA)
// ==========================================

function verify_api_access() {
    global $conn;
    
    // Polyfill for getallheaders() if not available
    if (!function_exists('getallheaders')) {
        function getallheaders() {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return $headers;
        }
    }
    
    // 1. Cek apakah ada API Key dari aplikasi Flutter (via Header)
    $headers = getallheaders();
    $client_key = '';
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'x-api-key') {
            $client_key = $value;
            break;
        }
    }

    if (!empty($client_key)) {
        // Cek ke database
        $stmt = $conn->prepare("SELECT id FROM api_keys WHERE api_key = ? AND is_active = 1");
        $stmt->bind_param("s", $client_key);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            return true; // Lolos lewat API Key valid
        }
    }

    // 2. Jika tidak ada API Key valid, cek apakah user Web sedang Login (via Session)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['role']) && !empty($_SESSION['role'])) {
        return true; // Lolos lewat Web Session (Browser Admin/Gudang)
    }

    // 3. Jika keduanya gagal, usir!
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        "status" => "error", 
        "message" => "401 Unauthorized: API Key Tidak Valid atau Session Login Tidak Ditemukan."
    ]);
    exit;
}
?>