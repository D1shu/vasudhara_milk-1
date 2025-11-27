<?php
require_once '../../config.php';
require_once '../../auth.php';

Auth::requireAdmin();
header('Content-Type: application/json');

try {
    $districtId = (int)($_GET['district_id'] ?? 0);
    
    if ($districtId <= 0) {
        echo json_encode([]);
        exit;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name FROM talukas WHERE district_id = ? AND status = 'active' ORDER BY name ASC");
    $stmt->bind_param("i", $districtId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $talukas = [];
    while ($row = $result->fetch_assoc()) {
        $talukas[] = $row;
    }
    
    $stmt->close();
    echo json_encode($talukas);
    
} catch (Exception $e) {
    error_log('Get Talukas Error: ' . $e->getMessage());
    echo json_encode(['error' => 'Failed to load talukas']);
}
?>