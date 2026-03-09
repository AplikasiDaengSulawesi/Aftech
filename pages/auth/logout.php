<?php
session_start();
require_once '../../includes/db.php';

// Catat Log Logout jika user masih ada session
if (isset($_SESSION['user_id'])) {
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES ('LOGOUT', ?)");
    $logStmt->execute(["User " . ($_SESSION['full_name'] ?? 'Unknown') . " (" . ($_SESSION['role'] ?? '-') . ") keluar dari sistem"]);
}

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: login.php");
exit;
?>
