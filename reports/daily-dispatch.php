<?php
/**
 * Daily Dispatch Sheet Generator
 * Generates PDF report for route-wise daily dispatch
 * 
 * NOTE: This is HTML-based report. For production, install DOMPDF:
 * composer require dompdf/dompdf
 */

require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$date = $_GET['date'] ?? date('Y-m-d');
$routeId = $_GET['route_id'] ?? '';

// Get route details
$db = getDB();
if ($routeId) {
    $stmt = $db->prepare("SELECT * FROM routes WHERE id = ?");
    $stmt->bind_param("i", $routeId);
    $stmt->execute();
    $route = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $route = ['route_number' => 'ALL', 'route_name' => 'All Routes', 'vehicle_number' => 'Multiple'];
}

// Get orders for the date
$dayOfWeek = date('w', strtotime($date)); // 0 (Sunday) to 6 (Saturday)
$dayNames = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
$dayColumn = $dayNames[$dayOfWeek] . '_qty';

// Build query
$sql = "
    SELECT wo.id, wo.$dayColumn as quantity, wo.total_bags,
           a.name as anganwadi_name, a.aw_code, a.contact_person, a.mobile,
           v.name as village_name, t.name as taluka_name,
           r.route_name, r.route_number
    FROM weekly_orders wo
    JOIN anganwadi a ON wo.anganwadi_id = a.id
    LEFT JOIN routes r ON a.route_id = r.id
    LEFT JOIN villages v ON a.village_id = v.id
    LEFT JOIN talukas t ON v.taluka_id = t.id
    WHERE wo.week_start_date <= ? 
    AND wo.week_end_date >= ?
    AND wo.status IN ('approved', 'dispatched')
";

$params = [$date, $date];
$types = "ss";

if ($routeId) {
    $sql .= " AND a.route_id = ?";
    $params[] = $routeId;
    $types .= "i";
}

$sql .= " ORDER BY r.route_number, a.name";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
$totalQuantity = 0;
$totalBags = 0;

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
    $totalQuantity += $row['quantity'];
    $totalBags += ceil(($row['quantity'] * 1000) / 500);
}
$stmt->close();

// HTML Report (can be converted to PDF using DOMPDF)
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Daily Dispatch Sheet - <?php echo date('d-m-Y', strtotime($date)); ?></title>
    <style>
        @page { margin: 20px; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 { margin: 0; font-size: 24px; }
        .header h2 { margin: 5px 0; font-size: 18px; color: #666; }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 150px;
            padding: 5px 0;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #333;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
        }
        td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .total-row {
            background: #ffffcc !important;
            font-weight: bold;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            border-top: 1px solid #000;
            padding-top: 10px;
            margin-top: 50px;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        @media print {
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
    
    <div class="header">
        <h1><?php echo getSetting('company_name', 'Vasudhara Milk Distribution'); ?></h1>
        <h2>Daily Dispatch Sheet</h2>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Date:</div>
            <div class="info-value"><?php echo date('l, d F Y', strtotime($date)); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Route Number:</div>
            <div class="info-value"><?php echo $route['route_number']; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Route Name:</div>
            <div class="info-value"><?php echo $route['route_name']; ?></div>
        </div>
        <?php if (isset($route['vehicle_number'])): ?>
        <div class="info-row">
            <div class="info-label">Vehicle Number:</div>
            <div class="info-value"><?php echo $route['vehicle_number']; ?></div>
        </div>
        <?php endif; ?>
        <?php if (isset($route['driver_name'])): ?>
        <div class="info-row">
            <div class="info-label">Driver Name:</div>
            <div class="info-value"><?php echo $route['driver_name']; ?></div>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <div class="info-label">Total Centers</div>
            <div class="info-value"><?php echo count($orders); ?></div>
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 50px; background: #fff3cd; border-radius: 10px;">
            <h3>No Orders Found</h3>
            <p>No approved/dispatched orders for this date and route.</p>
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Sr.</th>
                <th style="width: 10%;">Code</th>
                <th style="width: 25%;">Anganwadi/School Name</th>
                <th style="width: 15%;">Location</th>
                <th style="width: 10%;">Quantity (L)</th>
                <th style="width: 8%;">Bags</th>
                <th style="width: 12%;">Contact Person</th>
                <th style="width: 15%;">Signature</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $srNo = 1;
            foreach ($orders as $order): 
            ?>
            <tr>
                <td><?php echo $srNo++; ?></td>
                <td><strong><?php echo $order['aw_code']; ?></strong></td>
                <td><?php echo htmlspecialchars($order['anganwadi_name']); ?></td>
                <td><?php echo $order['village_name']; ?>, <?php echo $order['taluka_name']; ?></td>
                <td style="text-align: right;"><?php echo number_format($order['quantity'], 2); ?></td>
                <td style="text-align: center;"><?php echo ceil(($order['quantity'] * 1000) / 500); ?></td>
                <td><?php echo $order['contact_person']; ?><br><small><?php echo $order['mobile']; ?></small></td>
                <td>&nbsp;</td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td colspan="4" style="text-align: right;"><strong>TOTAL:</strong></td>
                <td style="text-align: right;"><strong><?php echo number_format($totalQuantity, 2); ?> L</strong></td>
                <td style="text-align: center;"><strong><?php echo $totalBags; ?></strong></td>
                <td colspan="2">&nbsp;</td>
            </tr>
        </tbody>
    </table>
    
    <div style="margin-top: 30px; padding: 15px; background: #f0f0f0; border-radius: 5px;">
        <strong>Delivery Instructions:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Verify quantity at each location before delivery</li>
            <li>Get signature from contact person</li>
            <li>Note any issues or shortages</li>
            <li>Return this sheet to office after completion</li>
        </ul>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <div>Driver Signature</div>
            <div style="margin-top: 5px; font-size: 10px;">Date: _______________</div>
        </div>
        <div class="signature-box">
            <div>Supervisor Signature</div>
            <div style="margin-top: 5px; font-size: 10px;">Date: _______________</div>
        </div>
    </div>
    
    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
        Generated on: <?php echo date('d-m-Y H:i:s'); ?> | System: Vasudhara Milk Distribution
    </div>
    <?php endif; ?>
</body>
</html>