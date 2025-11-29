<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $talukaId = (int)($_GET['taluka_id'] ?? 0);

    if ($talukaId <= 0) {
        echo json_encode(['success' => false, 'data' => []]);
        exit;
    }

    $villages = getVillagesByTaluka($talukaId);
    echo json_encode(['success' => true, 'data' => $villages]);

} catch (Exception $e) {
    error_log('Get Villages Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'data' => []]);
}
?>
