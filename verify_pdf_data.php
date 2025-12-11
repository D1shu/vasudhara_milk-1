<?php
require_once 'config.php';

$db = getDB();

echo "=== VERIFYING PDF DATA IN DATABASE ===\n\n";

// Check Districts
echo "1. DISTRICTS:\n";
$districtQuery = "SELECT id, name FROM districts WHERE status = 'active' ORDER BY name";
$districtResult = $db->query($districtQuery);
$districts = [];
while ($row = $districtResult->fetch_assoc()) {
    $districts[$row['name']] = $row['id'];
    echo "   - {$row['name']} (ID: {$row['id']})\n";
}

// Check if Navsari exists
if (isset($districts['Navsari'])) {
    echo "✓ Navsari district found\n";
    $navsariId = $districts['Navsari'];
} else {
    echo "✗ Navsari district NOT found\n";
    exit(1);
}

echo "\n2. TALUKAS IN NAVSARI DISTRICT:\n";
$talukaQuery = "SELECT id, name FROM talukas WHERE district_id = $navsariId AND status = 'active' ORDER BY name";
$talukaResult = $db->query($talukaQuery);
$talukas = [];
while ($row = $talukaResult->fetch_assoc()) {
    $talukas[$row['name']] = $row['id'];
    echo "   - {$row['name']} (ID: {$row['id']})\n";
}

// Check if Vansda exists
if (isset($talukas['Vansda'])) {
    echo "✓ Vansda taluka found\n";
    $vansdaId = $talukas['Vansda'];
} else {
    echo "✗ Vansda taluka NOT found\n";
    exit(1);
}

echo "\n3. VILLAGES IN VANSDA TALUKA:\n";
$villageQuery = "SELECT id, name FROM villages WHERE taluka_id = $vansdaId AND status = 'active' ORDER BY name";
$villageResult = $db->query($villageQuery);
$villages = [];
while ($row = $villageResult->fetch_assoc()) {
    $villages[$row['name']] = $row['id'];
    echo "   - {$row['name']} (ID: {$row['id']})\n";
}

// Villages from PDF
$pdfVillages = [
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

echo "\n4. VERIFICATION RESULTS:\n";

$missingVillages = [];
$foundVillages = 0;

foreach ($pdfVillages as $village) {
    if (isset($villages[$village])) {
        echo "✓ $village - FOUND\n";
        $foundVillages++;
    } else {
        echo "✗ $village - MISSING\n";
        $missingVillages[] = $village;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Districts: 1/1 found (Navsari)\n";
echo "Talukas: 1/1 found (Vansda)\n";
echo "Villages: $foundVillages/" . count($pdfVillages) . " found\n";

if (empty($missingVillages)) {
    echo "✓ ALL PDF DATA SUCCESSFULLY VERIFIED IN DATABASE!\n";
} else {
    echo "✗ MISSING VILLAGES: " . implode(', ', $missingVillages) . "\n";
}

$db->close();
?>
