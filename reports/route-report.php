<?php
/**
 * FILE 1: reports/route-report.php
 * Route-wise Distribution Report (Excel Format)
 */

require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$routeId = $_GET['route_id'] ?? 0;
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-d');

// Get route details
$db = getDB();
$stmt = $db->prepare("SELECT * FROM routes WHERE id = ?");
$stmt->bind_param("i", $routeId);
$stmt->execute();
$route = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get all anganwadis and their orders
$stmt = $db->prepare("
    SELECT a.name as anganwadi_name, a.aw_code,
           v.name as village_name, t.name as taluka_name,
           SUM(wo.total_qty) as total_qty,
           SUM(wo.total_bags) as total_bags,
           COUNT(wo.id) as order_count
    FROM anganwadi a
    LEFT JOIN weekly_orders wo ON a.id = wo.anganwadi_id 
        AND wo.week_start_date BETWEEN ? AND ?
        AND wo.status IN ('approved', 'dispatched', 'completed')
    LEFT JOIN villages v ON a.village_id = v.id
    LEFT JOIN talukas t ON v.taluka_id = t.id
    WHERE a.route_id = ?
    AND a.status = 'active'
    GROUP BY a.id
    ORDER BY v.name, a.name
");
$stmt->bind_param("ssi", $fromDate, $toDate, $routeId);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
$grandTotal = ['qty' => 0, 'bags' => 0, 'orders' => 0];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    $grandTotal['qty'] += $row['total_qty'];
    $grandTotal['bags'] += $row['total_bags'];
    $grandTotal['orders'] += $row['order_count'];
}
$stmt->close();

// Excel output
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="route_report_' . $route['route_number'] . '.xls"');

echo '<table border="1">';
echo '<tr><th colspan="7" style="background: #333; color: white; font-size: 16px; text-align: center;">
      ROUTE-WISE DISTRIBUTION REPORT</th></tr>';

echo '<tr><th colspan="7" style="background: #666; color: white;">ROUTE INFORMATION</th></tr>';
echo '<tr><td>Route Number</td><td colspan="6">' . $route['route_number'] . '</td></tr>';
echo '<tr><td>Route Name</td><td colspan="6">' . $route['route_name'] . '</td></tr>';
echo '<tr><td>Vehicle Number</td><td colspan="6">' . $route['vehicle_number'] . '</td></tr>';
echo '<tr><td>Driver Name</td><td colspan="6">' . $route['driver_name'] . ' (' . $route['driver_mobile'] . ')</td></tr>';
echo '<tr><td>Report Period</td><td colspan="6">' . date('d-m-Y', strtotime($fromDate)) . ' to ' . date('d-m-Y', strtotime($toDate)) . '</td></tr>';

echo '<tr><td colspan="7">&nbsp;</td></tr>';
echo '<tr style="background: #999; color: white;">';
echo '<th>Sr.</th><th>Code</th><th>Anganwadi/School</th><th>Location</th><th>Orders</th><th>Total Qty (L)</th><th>Total Bags</th></tr>';

$sr = 1;
foreach ($data as $row) {
    echo '<tr>';
    echo '<td>' . $sr++ . '</td>';
    echo '<td>' . $row['aw_code'] . '</td>';
    echo '<td>' . htmlspecialchars($row['anganwadi_name']) . '</td>';
    echo '<td>' . $row['village_name'] . ', ' . $row['taluka_name'] . '</td>';
    echo '<td>' . $row['order_count'] . '</td>';
    echo '<td>' . number_format($row['total_qty'], 2) . '</td>';
    echo '<td>' . $row['total_bags'] . '</td>';
    echo '</tr>';
}

echo '<tr style="background: #ffff99; font-weight: bold;">';
echo '<td colspan="4">GRAND TOTAL</td>';
echo '<td>' . $grandTotal['orders'] . '</td>';
echo '<td>' . number_format($grandTotal['qty'], 2) . '</td>';
echo '<td>' . $grandTotal['bags'] . '</td>';
echo '</tr>';

echo '</table>';
?>