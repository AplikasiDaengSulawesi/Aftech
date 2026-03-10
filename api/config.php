<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_aftech";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Koneksi ke Database Gagal"]);
    exit;
}
?>