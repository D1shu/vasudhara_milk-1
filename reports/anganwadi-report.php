<?php
/**
 * Anganwadi-wise Report
 * Detailed consumption report for specific anganwadi/school
 */

require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$anganwadiId = $_GET['anganwadi_id'] ?? 0;
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-d');

// Get anganwadi details
$db = getDB();
$stmt = $db->prepare("
    SELECT a.*, 
           v.name as village_name, t.name as taluka_name, d.name as district_name,
           r.route_name, r.route_number, r.vehicle_number
    FROM anganwadi a
    LEFT JOIN villages v ON a.village_id = v.id
    LEFT JOIN talukas t ON v.taluka_id = t.id
    LEFT JOIN districts d ON t.district_id = d.id
    LEFT JOIN routes r ON a.route_id = r.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $anganwadiId);
$stmt->execute();
$anganwadi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$anganwadi) {
    die('Anganwadi not found');
}

// Get all orders in date range
$stmt = $db->prepare("
    SELECT wo.*, u.name as user_name
    FROM weekly_orders wo
    JOIN users u ON wo.user_id = u.id
    WHERE wo.anganwadi_id = ?
    AND wo.week_start_date BETWEEN ? AND ?
    ORDER BY wo.week_start_date DESC
");
$stmt->bind_param("iss", $anganwadiId, $fromDate, $toDate);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
$totalQty = 0;
$totalBags = 0;
$totalChildren = 0;
$totalPregnant = 0;

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
    $totalQty += $row['total_qty'];
    $totalBags += $row['total_bags'];
    $totalChildren += $row['children_allocation'];
    $totalPregnant += $row['pregnant_women_allocation'];
}
$stmt->close();

$avgPerOrder = count($orders) > 0 ? $totalQty / count($orders) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Anganwadi Report - <?php echo $anganwadi['name']; ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px;
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
        .anganwadi-info {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .anganwadi-info h3 {
            margin: 0 0 15px 0;
            font-size: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        .info-item {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 5px;
        }
        .info-label {
            font-size: 10px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: bold;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .summary-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        .summary-card .label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
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
        .status-approved { color: #38a169; font-weight: bold; }
        .status-pending { color: #ed8936; font-weight: bold; }
        .status-dispatched { color: #4299e1; font-weight: bold; }
        .status-rejected { color: #e53e3e; font-weight: bold; }
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
        <h1>VASUDHARA MILK DISTRIBUTION</h1>
        <h2>Anganwadi Consumption Report</h2>
    </div>
    
    <!-- Anganwadi Details -->
    <div class="anganwadi-info">
        <h3><?php echo htmlspecialchars($anganwadi['name']); ?></h3>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Code</div>
                <div class="info-value"><?php echo $anganwadi['aw_code']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Type</div>
                <div class="info-value"><?php echo ucfirst($anganwadi['type']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Location</div>
                <div class="info-value">
                    <?php echo $anganwadi['village_name']; ?>,<br>
                    <?php echo $anganwadi['taluka_name']; ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">District</div>
                <div class="info-value"><?php echo $anganwadi['district_name']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Total Children</div>
                <div class="info-value"><?php echo $anganwadi['total_children']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Pregnant Women</div>
                <div class="info-value"><?php echo $anganwadi['pregnant_women']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Contact Person</div>
                <div class="info-value"><?php echo $anganwadi['contact_person']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Mobile</div>
                <div class="info-value"><?php echo $anganwadi['mobile']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Route</div>
                <div class="info-value">
                    <?php echo $anganwadi['route_number'] ?: 'Not Assigned'; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div style="text-align: center; margin-bottom: 20px; padding: 10px; background: #e2e8f0; border-radius: 5px;">
        <strong>Report Period:</strong> 
        <?php echo date('d M Y', strtotime($fromDate)); ?> to 
        <?php echo date('d M Y', strtotime($toDate)); ?>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="number"><?php echo count($orders); ?></div>
            <div class="label">Total Orders</div>
        </div>
        <div class="summary-card">
            <div class="number"><?php echo number_format($totalQty, 2); ?> L</div>
            <div class="label">Total Quantity</div>
        </div>
        <div class="summary-card">
            <div class="number"><?php echo $totalBags; ?></div>
            <div class="label">Total Bags</div>
        </div>
        <div class="summary-card">
            <div class="number"><?php echo number_format($avgPerOrder, 2); ?> L</div>
            <div class="label">Avg per Order</div>
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 50px; background: #fff3cd; border-radius: 10px;">
            <h3>No Orders Found</h3>
            <p>No orders submitted in this date range.</p>
        </div>
    <?php else: ?>
    
    <!-- Orders Table -->
    <h3 style="margin-bottom: 15px;">Order History</h3>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Week Period</th>
                <th>Mon</th>
                <th>Tue</th>
                <th>Wed</th>
                <th>Thu</th>
                <th>Fri</th>
                <th>Total (L)</th>
                <th>Bags</th>
                <th>Status</th>
                <th>Submitted By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><strong>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                <td>
                    <?php echo date('d/m', strtotime($order['week_start_date'])); ?> - 
                    <?php echo date('d/m', strtotime($order['week_end_date'])); ?>
                </td>
                <td><?php echo number_format($order['mon_qty'], 1); ?></td>
                <td><?php echo number_format($order['tue_qty'], 1); ?></td>
                <td><?php echo number_format($order['wed_qty'], 1); ?></td>
                <td><?php echo number_format($order['thu_qty'], 1); ?></td>
                <td><?php echo number_format($order['fri_qty'], 1); ?></td>
                <td><strong><?php echo number_format($order['total_qty'], 2); ?></strong></td>
                <td><?php echo $order['total_bags']; ?></td>
                <td class="status-<?php echo $order['status']; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </td>
                <td><?php echo $order['user_name']; ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td colspan="2">TOTAL</td>
                <td><?php 
                    $monTotal = array_sum(array_column($orders, 'mon_qty'));
                    echo number_format($monTotal, 2);
                ?></td>
                <td><?php 
                    $tueTotal = array_sum(array_column($orders, 'tue_qty'));
                    echo number_format($tueTotal, 2);
                ?></td>
                <td><?php 
                    $wedTotal = array_sum(array_column($orders, 'wed_qty'));
                    echo number_format($wedTotal, 2);
                ?></td>
                <td><?php 
                    $thuTotal = array_sum(array_column($orders, 'thu_qty'));
                    echo number_format($thuTotal, 2);
                ?></td>
                <td><?php 
                    $friTotal = array_sum(array_column($orders, 'fri_qty'));
                    echo number_format($friTotal, 2);
                ?></td>
                <td><strong><?php echo number_format($totalQty, 2); ?></strong></td>
                <td><strong><?php echo $totalBags; ?></strong></td>
                <td colspan="2"><?php echo count($orders); ?> Orders</td>
            </tr>
        </tbody>
    </table>
    
    <!-- Allocation Summary -->
    <h3 style="margin: 30px 0 15px 0;">Allocation Breakdown</h3>
    <table style="width: 50%;">
        <tr>
            <th>Category</th>
            <th>Total Allocation</th>
            <th>Percentage</th>
        </tr>
        <tr>
            <td>Children</td>
            <td><?php echo number_format($totalChildren, 2); ?> L</td>
            <td><?php echo number_format(($totalChildren / $totalQty) * 100, 1); ?>%</td>
        </tr>
        <tr>
            <td>Pregnant Women</td>
            <td><?php echo number_format($totalPregnant, 2); ?> L</td>
            <td><?php echo number_format(($totalPregnant / $totalQty) * 100, 1); ?>%</td>
        </tr>
        <tr class="total-row">
            <td>TOTAL</td>
            <td><?php echo number_format($totalQty, 2); ?> L</td>
            <td>100%</td>
        </tr>
    </table>
    
    <!-- Performance Metrics -->
    <h3 style="margin: 30px 0 15px 0;">Performance Metrics</h3>
    <div style="background: #f7fafc; padding: 20px; border-radius: 10px;">
        <ul style="line-height: 2;">
            <li><strong>Capacity Utilization:</strong> 
                <?php 
                $capacity = ($anganwadi['total_children'] + $anganwadi['pregnant_women']) * 0.5; // Assuming 0.5L per person
                $utilization = $capacity > 0 ? ($avgPerOrder / $capacity) * 100 : 0;
                echo number_format($utilization, 1); 
                ?>% of estimated capacity
            </li>
            <li><strong>Order Consistency:</strong> 
                <?php 
                $expectedWeeks = ceil((strtotime($toDate) - strtotime($fromDate)) / (7 * 24 * 60 * 60));
                $consistency = $expectedWeeks > 0 ? (count($orders) / $expectedWeeks) * 100 : 0;
                echo number_format($consistency, 1); 
                ?>% (<?php echo count($orders); ?> orders in <?php echo $expectedWeeks; ?> weeks)
            </li>
            <li><strong>Daily Average Distribution:</strong> 
                <?php echo number_format($totalQty / (count($orders) * 5), 2); ?> L per day
            </li>
            <li><strong>Per Capita Distribution:</strong> 
                <?php 
                $totalBeneficiaries = $anganwadi['total_children'] + $anganwadi['pregnant_women'];
                $perCapita = $totalBeneficiaries > 0 ? $avgPerOrder / $totalBeneficiaries : 0;
                echo number_format($perCapita, 2); 
                ?> L per beneficiary per week
            </li>
        </ul>
    </div>
    
    <div style="margin-top: 50px; border-top: 1px solid #000; padding-top: 10px; margin-top: 50px;">
        <strong>Authorized Signatory</strong><br>
        <small>Name & Signature with Date</small>
    </div>
    
    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px;">
        Generated on: <?php echo date('d-m-Y H:i:s'); ?> | Vasudhara Milk Distribution System
    </div>
    
    <?php endif; ?>
</body>
</html>