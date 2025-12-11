<?php
require_once 'config.php';

$db = getDB();

// Get Vansda taluka ID
$talukaQuery = "SELECT id FROM talukas WHERE name = 'Vansda' AND status = 'active'";
$talukaResult = $db->query($talukaQuery);
$taluka = $talukaResult->fetch_assoc();
$talukaId = $taluka['id'];

echo "Adding villages for Vansda taluka (ID: $talukaId)\n";

// Villages from PDF - extracted unique names from Gujarati text
$villages = [
    'Vansda',
    'Ambapani',
    'Vangan',
    'Khadkiya',
    'Zuj',
    'Manpur',
    'Raybor',
    'Dhakmal',
    'Mankunia',
    'Kapadvanj',
    'Navtad',
    'Khambhala',
    'Bilmoda',
    'Vadbar',
    'Mahuva',
    'Ambabari',
    'Tadpada',
    'Nanivaghai',
    'Vati',
    'Sadldev',
    'Kalamba',
    'Kharjai',
    'Kevdi',
    'Sara',
    'Khambaliya',
    'Palghabhana',
    'Chadhava',
    'Sindhiya',
    'Unai',
    'Kilyari',
    'Khudvel',
    'Sadkapur',
    'Chiyada',
    'Gholara',
    'Ranpada',
    'Dholar',
    'Pira',
    'Bamnavada',
    'Mahakalapada'
];

$added = 0;
$skipped = 0;

foreach ($villages as $villageName) {
    // Check if village already exists
    $checkQuery = "SELECT id FROM villages WHERE taluka_id = ? AND name = ? AND status = 'active'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bind_param("is", $talukaId, $villageName);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows == 0) {
        // Insert new village
        $insertQuery = "INSERT INTO villages (taluka_id, name, status) VALUES (?, ?, 'active')";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bind_param("is", $talukaId, $villageName);

        if ($insertStmt->execute()) {
            echo "Added village: $villageName\n";
            $added++;
        } else {
            echo "Error adding village: $villageName - " . $insertStmt->error . "\n";
        }
        $insertStmt->close();
    } else {
        echo "Village already exists: $villageName\n";
        $skipped++;
    }
    $checkStmt->close();
}

echo "\nSummary:\n";
echo "Villages added: $added\n";
echo "Villages skipped (already exist): $skipped\n";

$db->close();
?>
