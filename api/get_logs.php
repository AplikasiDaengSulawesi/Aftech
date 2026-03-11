<?php
include 'config.php';
verify_api_access();
header('Content-Type: application/json');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

// Get Stats & Total Count
$statsSql = "SELECT 
    COUNT(*) as total_logs,
    SUM(CASE WHEN DATE(timestamp) = CURDATE() THEN 1 ELSE 0 END) as log_hari_ini,
    SUM(CASE WHEN action LIKE '%hapus%' OR action LIKE '%delete%' THEN 1 ELSE 0 END) as total_hapus
FROM activity_logs";
$statsRes = $conn->query($statsSql);
$stats = $statsRes->fetch_assoc();

$total_logs = (int)($stats['total_logs'] ?? 0);
$total_pages = ceil($total_logs / $limit);

$res = $conn->query("SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT $limit OFFSET $offset");
$logs = [];
while($row = $res->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode([
    'data' => $logs,
    'pagination' => [
        'total_items' => $total_logs,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'limit' => $limit
    ],
    'stats' => [
        'total_logs' => $total_logs,
        'log_hari_ini' => (int)($stats['log_hari_ini'] ?? 0),
        'total_hapus' => (int)($stats['total_hapus'] ?? 0)
    ]
]);
?>
