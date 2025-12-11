<?php
require_once 'config.php';

$db = getDB();
$query = "SELECT t.id, t.name, d.name as district FROM talukas t JOIN districts d ON t.district_id = d.id WHERE t.status = 'active' ORDER BY d.name, t.name";
$result = $db->query($query);

echo "Current Talukas in Database:\n";
echo "ID\tDistrict\t\tTaluka\n";
echo "--------------------------------\n";

while ($row = $result->fetch_assoc()) {
    echo $row['id'] . "\t" . $row['district'] . "\t\t" . $row['name'] . "\n";
}

$db->close();
?>
