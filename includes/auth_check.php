<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

function can_access($page_slug) {
    global $pdo;
    
    $role = $_SESSION['role'] ?? '';
    
    // Admin has full access to everything
    if ($role === 'admin') return true;
    
    if (empty($role) || empty($page_slug)) return false;
    
    // Check in database
    $stmt = $pdo->prepare("SELECT id FROM role_permissions WHERE role = ? AND page_slug = ? AND can_access = 1");
    $stmt->execute([$role, $page_slug]);
    
    return $stmt->fetch() ? true : false;
}

// Security Enforcement for the current page
function protect_page($page_slug) {
    if (!can_access($page_slug)) {
        header("Location: index.php?error=unauthorized");
        exit;
    }
}
?>