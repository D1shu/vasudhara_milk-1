<?php
// File: ajax/get-villages.php
require_once '../config.php';
require_once '../auth.php';

Auth::requireLogin();

header('Content-Type: application/json');

$talukaId = $_GET['taluka_id'] ?? 0;
$villages = getVillagesByTaluka($talukaId);

echo json_encode($villages);
?>