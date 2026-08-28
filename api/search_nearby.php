<?php
require_once '../config/db.php';
header('Content-Type: application/json');

// Session sudah diinisialisasi oleh initMysqlSession() di dalam db.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$lat = floatval($_GET['lat'] ?? 0);
$lng = floatval($_GET['lng'] ?? 0);
$radius = intval($_GET['radius'] ?? 30); // meters

if ($lat == 0 || $lng == 0) {
    echo json_encode(['error' => 'Koordinat tidak valid']);
    exit;
}

// Clamp radius between 5 and 500 meters
$radius = max(5, min(500, $radius));

try {
    $pdo = getDB();

    // Haversine formula in SQL to find nearby points
    // 6371000 = Earth radius in meters
    $sql = "
        SELECT *, 
        (6371000 * ACOS(
            COS(RADIANS(:lat)) * COS(RADIANS(lat)) *
            COS(RADIANS(lng) - RADIANS(:lng)) +
            SIN(RADIANS(:lat2)) * SIN(RADIANS(lat))
        )) AS distance_m
        FROM tikor
        WHERE lat IS NOT NULL AND lng IS NOT NULL
        AND lat BETWEEN (:lat3 - 0.01) AND (:lat4 + 0.01)
        AND lng BETWEEN (:lng2 - 0.01) AND (:lng3 + 0.01)
        HAVING distance_m <= :radius
        ORDER BY distance_m ASC
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':lat' => $lat,
        ':lng' => $lng,
        ':lat2' => $lat,
        ':lat3' => $lat,
        ':lat4' => $lat,
        ':lng2' => $lng,
        ':lng3' => $lng,
        ':radius' => $radius
    ]);

    $results = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count' => count($results),
        'results' => $results,
        'search_lat' => $lat,
        'search_lng' => $lng,
        'radius_m' => $radius
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
