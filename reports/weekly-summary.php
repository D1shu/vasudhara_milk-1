<?php
/**
 * Weekly Summary Report
 * Generates consolidated weekly report with anganwadi-wise breakdown
 */

require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
$format = $_GET['format'] ?? 'pdf';

// Calculate week end (Friday)
$weekEnd = date('Y-m-d', strtotime($weekStart . ' +4 days'));

// Get all orders for this week
$db = getDB();
$stmt = $db->prepare("
    SELECT wo.*, 
           a.name as anganwadi_name, a.aw_code, a.type as anganwadi_type,
           a.contact_person, a.mobile,
           v.name as village_name, t.name as taluka_name, d.name as district_name,
           r.route_name, r.route_number,
           u.name as user_name
    FROM weekly_orders wo
    JOIN anganwadi a ON wo.anganwadi_id = a.id
    JOIN users u ON wo.user_id = u.id
    LEFT JOIN routes r ON a.route_id = r.id
    LEFT JOIN villages v ON a.village_id = v.id
    LEFT JOIN talukas t ON v.taluka_id = t.id
    LEFT JOIN districts d ON t.district_id = d.id
    WHERE wo.week_start_date = ?
    AND wo.status IN ('approved', 'dispatched', 'completed')
    ORDER BY d.name, t.name, a.name
");
$stmt->bind_param("s", $weekStart);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
$totalOrders = 0;
$grandTotal = [
    'mon' => 0, 'tue' => 0, 'wed' => 0, 'thu' => 0, 'fri' => 0,
    'total_qty' => 0, 'total_bags' => 0,
    'children' => 0, 'pregnant' => 0
];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
    $totalOrders++;
    $grandTotal['mon'] += $row['mon_qty'];
    $grandTotal['tue'] += $row['tue_qty'];
    $grandTotal['wed'] += $row['wed_qty'];
    $grandTotal['thu'] += $row['thu_qty'];
    $grandTotal['fri'] += $row['fri_qty'];
    $grandTotal['total_qty'] += $row['total_qty'];
    $grandTotal['total_bags'] += $row['total_bags'];
    $grandTotal['children'] += $row['children_allocation'];
    $grandTotal['pregnant'] += $row['pregnant_women_allocation'];
}
$stmt->close();

// If Excel format requested
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="weekly_summary_' . $weekStart . '.xls"');
    
    echo '<table border="1">';
    echo '<tr><th colspan="12" style="background: #333; color: white; text-align: center; font-size: 16px;">
          WEEKLY SUMMARY REPORT<br>Week: ' . date('d-m-Y', strtotime($weekStart)) . ' to ' . date('d-m-Y', strtotime($weekEnd)) . '</th></tr>';
    echo '<tr style="background: #666; color: white;">';
    echo '<th>Sr.</th><th>Code</th><th>Anganwadi/School</th><th>Location</th>';
    echo '<th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th>';
    echo '<th>Total (L)</th><th>Bags</th><th>Status</th></tr>';
    
    $sr = 1;
    foreach ($orders as $order) {
        echo '<tr>';
        echo '<td>' . $sr++ . '</td>';
        echo '<td>' . $order['aw_code'] . '</td>';
        echo '<td>' . htmlspecialchars($order['anganwadi_name']) . '</td>';
        echo '<td>' . $order['village_name'] . ', ' . $order['taluka_name'] . '</td>';
        echo '<td>' . number_format($order['mon_qty'], 2) . '</td>';
        echo '<td>' . number_format($order['tue_qty'], 2) . '</td>';
        echo '<td>' . number_format($order['wed_qty'], 2) . '</td>';
        echo '<td>' . number_format($order['thu_qty'], 2) . '</td>';
        echo '<td>' . number_format($order['fri_qty'], 2) . '</td>';
        echo '<td>' . number_format($order['total_qty'], 2) . '</td>';
        echo '<td>' . $order['total_bags'] . '</td>';
        echo '<td>' . ucfirst($order['status']) . '</td>';
        echo '</tr>';
    }
    
    echo '<tr style="background: #ffff99; font-weight: bold;">';
    echo '<td colspan="4">GRAND TOTAL</td>';
    echo '<td>' . number_format($grandTotal['mon'], 2) . '</td>';
    echo '<td>' . number_format($grandTotal['tue'], 2) . '</td>';
    echo '<td>' . number_format($grandTotal['wed'], 2) . '</td>';
    echo '<td>' . number_format($grandTotal['thu'], 2) . '</td>';
    echo '<td>' . number_format($grandTotal['fri'], 2) . '</td>';
    echo '<td>' . number_format($grandTotal['total_qty'], 2) . '</td>';
    echo '<td>' . $grandTotal['total_bags'] . '</td>';
    echo '<td>' . $totalOrders . ' Orders</td>';
    echo '</tr>';
    echo '</table>';
    exit;
}

// HTML/PDF Format
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Weekly Summary Report</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 { margin: 0; font-size: 22px; }
        .header h2 { margin: 5px 0; font-size: 16px; color: #666; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .info-item {
            padding: 5px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        th {
            background: #333;
            color: white;
            padding: 8px 4px;
            text-align: center;
            font-size: 9px;
        }
        td {
            border: 1px solid #ddd;
            padding: 6px 4px;
            text-align: center;
        }
        td.left { text-align: left; }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .total-row {
            background: #ffffcc !important;
            font-weight: bold;
            font-size: 11px;
        }
        .summary-box {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 30px;
        }
        .summary-card {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .summary-card .number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .summary-card .label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
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
            z-index: 1000;
        }
        @media print {
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
    
    <div class="header">
        <h1>VASUDHARA MILK DISTRIBUTION</h1>
        <h2>Weekly Summary Report</h2>
    </div>
    
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Week Period:</span>
            <?php echo date('d-m-Y', strtotime($weekStart)); ?> to 
            <?php echo date('d-m-Y', strtotime($weekEnd)); ?>
        </div>
        <div class="info-item">
            <span class="info-label">Total Orders</span>
            <?php echo $totalOrders; ?>
        </div>
        <div class="info-item">
            <span class="info-label">Report Date:</span>
            <?php echo date('d-m-Y H:i'); ?>
        </div>
        <div class="info-item">
            <span class="info-label">Total Quantity</span>
            <?php echo number_format($grandTotal['total_qty'], 2); ?> Liters
        </div>
        <div class="info-item">
            <span class="info-label">Total Bags:</span>
            <?php echo $grandTotal['total_bags']; ?>
        </div>
        <div class="info-item">
            <span class="info-label">Children Allocation:</span>
            <?php echo number_format($grandTotal['children'], 2); ?> L
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 50px; background: #fff3cd; border-radius: 10px;">
            <h3>No Orders Found</h3>
            <p>No approved orders for this week period.</p>
        </div>
    <?php else: ?>
    
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">Sr.</th>
                <th rowspan="2" style="width: 7%;">Code</th>
                <th rowspan="2" style="width: 18%;">Anganwadi/School</th>
                <th rowspan="2" style="width: 12%;">Location</th>
                <th colspan="5">Daily Quantity (Liters)</th>
                <th rowspan="2" style="width: 8%;">Total</th>
                <th rowspan="2" style="width: 5%;">Bags</th>
                <th rowspan="2" style="width: 8%;">Status</th>
            </tr>
            <tr>
                <th style="width: 6%;">Mon</th>
                <th style="width: 6%;">Tue</th>
                <th style="width: 6%;">Wed</th>
                <th style="width: 6%;">Thu</th>
                <th style="width: 6%;">Fri</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sr = 1;
            foreach ($orders as $order): 
            ?>
            <tr>
                <td><?php echo $sr++; ?></td>
                <td><strong><?php echo $order['aw_code']; ?></strong></td>
                <td class="left">
                    <strong><?php echo htmlspecialchars($order['anganwadi_name']); ?></strong><br>
                    <small><?php echo ucfirst($order['anganwadi_type']); ?></small>
                </td>
                <td class="left">
                    <?php echo $order['village_name']; ?>,<br>
                    <?php echo $order['taluka_name']; ?>
                </td>
                <td><?php echo number_format($order['mon_qty'], 1); ?></td>
                <td><?php echo number_format($order['tue_qty'], 1); ?></td>
                <td><?php echo number_format($order['wed_qty'], 1); ?></td>
                <td><?php echo number_format($order['thu_qty'], 1); ?></td>
                <td><?php echo number_format($order['fri_qty'], 1); ?></td>
                <td><strong><?php echo number_format($order['total_qty'], 2); ?></strong></td>
                <td><?php echo $order['total_bags']; ?></td>
                <td><?php echo ucfirst($order['status']); ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">GRAND TOTAL:</td>
                <td><?php echo number_format($grandTotal['mon'], 2); ?></td>
                <td><?php echo number_format($grandTotal['tue'], 2); ?></td>
                <td><?php echo number_format($grandTotal['wed'], 2); ?></td>
                <td><?php echo number_format($grandTotal['thu'], 2); ?></td>
                <td><?php echo number_format($grandTotal['fri'], 2); ?></td>
                <td><strong><?php echo number_format($grandTotal['total_qty'], 2); ?></strong></td>
                <td><strong><?php echo $grandTotal['total_bags']; ?></strong></td>
                <td><strong><?php echo $totalOrders; ?></strong></td>
            </tr>
        </tbody>
    </table>
    
    <div class="summary-box">
        <div class="summary-card">
            <div class="number"><?php echo $totalOrders; ?></div>
            <div class="label">Total Orders</div>
        </div>
        <div class="summary-card">
            <div class="number"><?php echo number_format($grandTotal['total_qty'], 0); ?> L</div>
            <div class="label">Total Quantity</div>
        </div>
        <div class="summary-card">
            <div class="number"><?php echo $grandTotal['total_bags']; ?></div>
            <div class="label">Total Bags</div>
        </div>
        <div class="summary-card">
            <div class="number"><?php echo number_format(($grandTotal['total_qty'] / 5), 1); ?> L</div>
            <div class="label">Daily Average</div>
        </div>
    </div>
    
    <div style="margin-top: 50px; display: flex; justify-content: space-between;">
        <div style="width: 45%; border-top: 1px solid #000; padding-top: 10px; margin-top: 30px;">
            <strong>Prepared By</strong><br>
            <small>Name & Signature</small>
        </div>
        <div style="width: 45%; border-top: 1px solid #000; padding-top: 10px; margin-top: 30px;">
            <strong>Approved By</strong><br>
            <small>Name & Signature</small>
        </div>
    </div>
    
    <div style="margin-top: 30px; text-align: center; font-size: 9px; color: #666; border-top: 1px solid #ddd; padding-top: 10px;">
        Generated on: <?php echo date('d-m-Y H:i:s'); ?> | Vasudhara Milk Distribution System
    </div>
    
    <?php endif; ?>
</body>
</html>