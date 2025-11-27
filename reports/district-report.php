
<?php
/**
 * FILE 2: reports/district-report.php
 * District-wise Summary Report
 */

require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$districtId = $_GET['district_id'] ?? '';
$month = $_GET['month'] ?? date('Y-m');

list($year, $monthNum) = explode('-', $month);
$monthName = date('F Y', strtotime($month . '-01'));
$firstDay = $month . '-01';
$lastDay = date('Y-m-t', strtotime($firstDay));

$db = getDB();

// Get district-wise data
if ($districtId) {
    $stmt = $db->prepare("
        SELECT d.name as district_name,
               t.name as taluka_name,
               COUNT(DISTINCT a.id) as total_anganwadis,
               COUNT(wo.id) as total_orders,
               SUM(wo.total_qty) as total_qty,
               SUM(wo.total_bags) as total_bags
        FROM districts d
        LEFT JOIN talukas t ON d.id = t.district_id
        LEFT JOIN villages v ON t.id = v.taluka_id
        LEFT JOIN anganwadi a ON v.id = a.village_id
        LEFT JOIN weekly_orders wo ON a.id = wo.anganwadi_id 
            AND wo.week_start_date BETWEEN ? AND ?
            AND wo.status IN ('approved', 'dispatched', 'completed')
        WHERE d.id = ?
        GROUP BY t.id
        ORDER BY t.name
    ");
    $stmt->bind_param("ssi", $firstDay, $lastDay, $districtId);
} else {
    $stmt = $db->prepare("
        SELECT d.name as district_name,
               COUNT(DISTINCT a.id) as total_anganwadis,
               COUNT(wo.id) as total_orders,
               SUM(wo.total_qty) as total_qty,
               SUM(wo.total_bags) as total_bags
        FROM districts d
        LEFT JOIN talukas t ON d.id = t.district_id
        LEFT JOIN villages v ON t.id = v.taluka_id
        LEFT JOIN anganwadi a ON v.id = a.village_id
        LEFT JOIN weekly_orders wo ON a.id = wo.anganwadi_id 
            AND wo.week_start_date BETWEEN ? AND ?
            AND wo.status IN ('approved', 'dispatched', 'completed')
        GROUP BY d.id
        ORDER BY total_qty DESC
    ");
    $stmt->bind_param("ss", $firstDay, $lastDay);
}

$stmt->execute();
$result = $stmt->get_result();
$data = [];
$grandTotal = ['anganwadis' => 0, 'orders' => 0, 'qty' => 0, 'bags' => 0];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    $grandTotal['anganwadis'] += $row['total_anganwadis'];
    $grandTotal['orders'] += $row['total_orders'];
    $grandTotal['qty'] += $row['total_qty'];
    $grandTotal['bags'] += $row['total_bags'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>District Report - <?php echo $monthName; ?></title>
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
        }
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .total-row {
            background: #ffffcc;
            font-weight: bold;
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
    <button class="print-btn" onclick="window.print()">🖨️ Print</button>
    
    <div class="header">
        <h1>DISTRICT-WISE SUMMARY REPORT</h1>
        <h2><?php echo $monthName; ?></h2>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Sr.</th>
                <th><?php echo $districtId ? 'Taluka' : 'District'; ?></th>
                <th>Anganwadis</th>
                <th>Orders</th>
                <th>Quantity (L)</th>
                <th>Bags</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sr = 1;
            foreach ($data as $row): 
                $percentage = $grandTotal['qty'] > 0 ? ($row['total_qty'] / $grandTotal['qty']) * 100 : 0;
            ?>
            <tr>
                <td><?php echo $sr++; ?></td>
                <td><strong><?php echo $districtId ? $row['taluka_name'] : $row['district_name']; ?></strong></td>
                <td><?php echo $row['total_anganwadis']; ?></td>
                <td><?php echo $row['total_orders']; ?></td>
                <td><?php echo number_format($row['total_qty'], 2); ?></td>
                <td><?php echo $row['total_bags']; ?></td>
                <td><?php echo number_format($percentage, 1); ?>%</td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td colspan="2">GRAND TOTAL</td>
                <td><?php echo $grandTotal['anganwadis']; ?></td>
                <td><?php echo $grandTotal['orders']; ?></td>
                <td><?php echo number_format($grandTotal['qty'], 2); ?></td>
                <td><?php echo $grandTotal['bags']; ?></td>
                <td>100%</td>
            </tr>
        </tbody>
    </table>
</body>
</html>