<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $districtId = (int)($_GET['district_id'] ?? 0);

    if ($districtId <= 0) {
        echo json_encode(['success' => false, 'data' => []]);
        exit;
    }

    $talukas = getTalukasByDistrict($districtId);
    echo json_encode(['success' => true, 'data' => $talukas]);

} catch (Exception $e) {
    error_log('Get Talukas Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'data' => []]);
}
?>
