<?php
include 'config.php';
header('Content-Type: application/json');

// Get Stats
$statsSql = "SELECT 
    COUNT(*) as total_logs,
    SUM(CASE WHEN DATE(timestamp) = CURDATE() THEN 1 ELSE 0 END) as log_hari_ini,
    SUM(CASE WHEN action LIKE '%hapus%' OR action LIKE '%delete%' THEN 1 ELSE 0 END) as total_hapus
FROM activity_logs";
$statsRes = $conn->query($statsSql);
$stats = $statsRes->fetch_assoc();

$res = $conn->query("SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT 100");
$logs = [];
while($row = $res->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode([
    'data' => $logs,
    'stats' => [
        'total_logs' => (int)($stats['total_logs'] ?? 0),
        'log_hari_ini' => (int)($stats['log_hari_ini'] ?? 0),
        'total_hapus' => (int)($stats['total_hapus'] ?? 0)
    ]
]);
?>
