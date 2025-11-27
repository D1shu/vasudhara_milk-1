<?php
/**
 * Monthly Report
 * Complete monthly consumption and trend analysis
 */

require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$month = $_GET['month'] ?? date('Y-m');
$format = $_GET['format'] ?? 'pdf';

list($year, $monthNum) = explode('-', $month);
$monthName = date('F Y', strtotime($month . '-01'));
$firstDay = $month . '-01';
$lastDay = date('Y-m-t', strtotime($firstDay));

// Get monthly statistics
$db = getDB();

// Total orders and quantity
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_qty) as total_quantity,
        SUM(total_bags) as total_bags,
        SUM(children_allocation) as total_children,
        SUM(pregnant_women_allocation) as total_pregnant,
        AVG(total_qty) as avg_quantity
    FROM weekly_orders
    WHERE week_start_date BETWEEN ? AND ?
    AND status IN ('approved', 'dispatched', 'completed')
");
$stmt->bind_param("ss", $firstDay, $lastDay);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// District-wise breakdown
$stmt = $db->prepare("
    SELECT 
        d.name as district_name,
        COUNT(wo.id) as order_count,
        SUM(wo.total_qty) as total_qty,
        SUM(wo.total_bags) as total_bags
    FROM weekly_orders wo
    JOIN anganwadi a ON wo.anganwadi_id = a.id
    JOIN villages v ON a.village_id = v.id
    JOIN talukas t ON v.taluka_id = t.id
    JOIN districts d ON t.district_id = d.id
    WHERE wo.week_start_date BETWEEN ? AND ?
    AND wo.status IN ('approved', 'dispatched', 'completed')
    GROUP BY d.id
    ORDER BY total_qty DESC
");
$stmt->bind_param("ss", $firstDay, $lastDay);
$stmt->execute();
$result = $stmt->get_result();
$districtData = [];
while ($row = $result->fetch_assoc()) {
    $districtData[] = $row;
}
$stmt->close();

// Week-wise breakdown
$stmt = $db->prepare("
    SELECT 
        week_start_date,
        COUNT(*) as order_count,
        SUM(total_qty) as total_qty,
        SUM(total_bags) as total_bags
    FROM weekly_orders
    WHERE week_start_date BETWEEN ? AND ?
    AND status IN ('approved', 'dispatched', 'completed')
    GROUP BY week_start_date
    ORDER BY week_start_date
");
$stmt->bind_param("ss", $firstDay, $lastDay);
$stmt->execute();
$result = $stmt->get_result();
$weekData = [];
while ($row = $result->fetch_assoc()) {
    $weekData[] = $row;
}
$stmt->close();

// Top 10 Anganwadis
$stmt = $db->prepare("
    SELECT 
        a.name as anganwadi_name,
        a.aw_code,
        SUM(wo.total_qty) as total_qty,
        COUNT(wo.id) as order_count
    FROM weekly_orders wo
    JOIN anganwadi a ON wo.anganwadi_id = a.id
    WHERE wo.week_start_date BETWEEN ? AND ?
    AND wo.status IN ('approved', 'dispatched', 'completed')
    GROUP BY wo.anganwadi_id
    ORDER BY total_qty DESC
    LIMIT 10
");
$stmt->bind_param("ss", $firstDay, $lastDay);
$stmt->execute();
$result = $stmt->get_result();
$topAnganwadis = [];
while ($row = $result->fetch_assoc()) {
    $topAnganwadis[] = $row;
}
$stmt->close();

// Excel format
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="monthly_report_' . $month . '.xls"');
    
    echo '<table border="1">';
    echo '<tr><th colspan="6" style="background: #333; color: white; font-size: 16px; text-align: center;">
          MONTHLY REPORT - ' . strtoupper($monthName) . '</th></tr>';
    
    echo '<tr><th colspan="6" style="background: #666; color: white;">SUMMARY</th></tr>';
    echo '<tr><td>Total Orders</td><td colspan="5">' . $summary['total_orders'] . '</td></tr>';
    echo '<tr><td>Total Quantity</td><td colspan="5">' . number_format($summary['total_quantity'], 2) . ' Liters</td></tr>';
    echo '<tr><td>Total Bags</td><td colspan="5">' . $summary['total_bags'] . '</td></tr>';
    echo '<tr><td>Average per Order</td><td colspan="5">' . number_format($summary['avg_quantity'], 2) . ' Liters</td></tr>';
    
    echo '<tr><td colspan="6">&nbsp;</td></tr>';
    echo '<tr><th colspan="6" style="background: #666; color: white;">DISTRICT-WISE BREAKDOWN</th></tr>';
    echo '<tr style="background: #999; color: white;">
          <th>District</th><th>Orders</th><th>Quantity (L)</th><th>Bags</th><th>Percentage</th><th>Avg/Order</th></tr>';
    
    foreach ($districtData as $district) {
        $percentage = ($district['total_qty'] / $summary['total_quantity']) * 100;
        $avgPerOrder = $district['total_qty'] / $district['order_count'];
        echo '<tr>';
        echo '<td>' . $district['district_name'] . '</td>';
        echo '<td>' . $district['order_count'] . '</td>';
        echo '<td>' . number_format($district['total_qty'], 2) . '</td>';
        echo '<td>' . $district['total_bags'] . '</td>';
        echo '<td>' . number_format($percentage, 1) . '%</td>';
        echo '<td>' . number_format($avgPerOrder, 2) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    exit;
}

// HTML/PDF Format
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Monthly Report - <?php echo $monthName; ?></title>
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
        .header h1 { margin: 0; font-size: 24px; }
        .header h2 { margin: 5px 0; font-size: 18px; color: #666; }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .summary-card .number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .summary-card .label {
            font-size: 12px;
            opacity: 0.9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
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
        .section-title {
            background: #667eea;
            color: white;
            padding: 10px 15px;
            margin: 30px 0 15px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .chart-placeholder {
            background: #f5f5f5;
            padding: 40px;
            text-align: center;
            border: 2px dashed #ddd;
            border-radius: 10px;
            margin: 20px 0;
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
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
    
    <div class="header">
        <h1>VASUDHARA MILK DISTRIBUTION</h1>
        <h2>Monthly Report - <?php echo $monthName; ?></h2>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="number"><?php echo $summary['total_orders'] ?: 0; ?></div>
            <div class="label">Total Orders</div>
        </div>
        <div class="summary-card" style="background: linear-gradient(135deg, #48bb78, #38a169);">
            <div class="number"><?php echo number_format($summary['total_quantity'] ?: 0, 0); ?> L</div>
            <div class="label">Total Quantity</div>
        </div>
        <div class="summary-card" style="background: linear-gradient(135deg, #ed8936, #dd6b20);">
            <div class="number"><?php echo $summary['total_bags'] ?: 0; ?></div>
            <div class="label">Total Bags</div>
        </div>
        <div class="summary-card" style="background: linear-gradient(135deg, #4299e1, #3182ce);">
            <div class="number"><?php echo number_format($summary['avg_quantity'] ?: 0, 1); ?> L</div>
            <div class="label">Avg per Order</div>
        </div>
    </div>
    
    <?php if ($summary['total_orders'] == 0): ?>
        <div style="text-align: center; padding: 50px; background: #fff3cd; border-radius: 10px;">
            <h3>No Data Available</h3>
            <p>No orders found for <?php echo $monthName; ?></p>
        </div>
    <?php else: ?>
    
    <!-- District-wise Breakdown -->
    <div class="section-title">📊 District-wise Distribution</div>
    <table>
        <thead>
            <tr>
                <th>District</th>
                <th>Orders</th>
                <th>Quantity (L)</th>
                <th>Bags</th>
                <th>Percentage</th>
                <th>Avg/Order</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($districtData as $district): 
                $percentage = ($district['total_qty'] / $summary['total_quantity']) * 100;
                $avgPerOrder = $district['total_qty'] / $district['order_count'];
            ?>
            <tr>
                <td><strong><?php echo $district['district_name']; ?></strong></td>
                <td><?php echo $district['order_count']; ?></td>
                <td><?php echo number_format($district['total_qty'], 2); ?></td>
                <td><?php echo $district['total_bags']; ?></td>
                <td>
                    <div style="background: #e2e8f0; border-radius: 10px; height: 20px; position: relative;">
                        <div style="background: #667eea; width: <?php echo $percentage; ?>%; height: 100%; border-radius: 10px;"></div>
                        <span style="position: absolute; right: 5px; top: 2px; font-weight: bold;">
                            <?php echo number_format($percentage, 1); ?>%
                        </span>
                    </div>
                </td>
                <td><?php echo number_format($avgPerOrder, 2); ?> L</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Week-wise Trend -->
    <div class="section-title">📈 Week-wise Trend</div>
    <table>
        <thead>
            <tr>
                <th>Week Starting</th>
                <th>Orders</th>
                <th>Quantity (L)</th>
                <th>Bags</th>
                <th>Avg per Order</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($weekData as $week): 
                $avgPerOrder = $week['total_qty'] / $week['order_count'];
            ?>
            <tr>
                <td><?php echo date('d M Y', strtotime($week['week_start_date'])); ?></td>
                <td><?php echo $week['order_count']; ?></td>
                <td><?php echo number_format($week['total_qty'], 2); ?></td>
                <td><?php echo $week['total_bags']; ?></td>
                <td><?php echo number_format($avgPerOrder, 2); ?> L</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="page-break"></div>
    
    <!-- Top 10 Anganwadis -->
    <div class="section-title">🏆 Top 10 Anganwadis by Consumption</div>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Code</th>
                <th>Anganwadi/School Name</th>
                <th>Orders</th>
                <th>Total Quantity (L)</th>
                <th>Avg per Order</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $rank = 1;
            foreach ($topAnganwadis as $aw): 
                $avgPerOrder = $aw['total_qty'] / $aw['order_count'];
            ?>
            <tr>
                <td style="text-align: center; font-weight: bold; font-size: 14px;">
                    <?php echo $rank++; ?>
                </td>
                <td><?php echo $aw['aw_code']; ?></td>
                <td><strong><?php echo htmlspecialchars($aw['anganwadi_name']); ?></strong></td>
                <td><?php echo $aw['order_count']; ?></td>
                <td><strong><?php echo number_format($aw['total_qty'], 2); ?></strong></td>
                <td><?php echo number_format($avgPerOrder, 2); ?> L</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Key Insights -->
    <div class="section-title">💡 Key Insights</div>
    <div style="background: #f7fafc; padding: 20px; border-radius: 10px;">
        <ul style="line-height: 2;">
            <li><strong>Peak Distribution:</strong> 
                <?php 
                $maxWeek = array_reduce($weekData, function($carry, $item) {
                    return ($item['total_qty'] > ($carry['total_qty'] ?? 0)) ? $item : $carry;
                }, []);
                echo date('d M', strtotime($maxWeek['week_start_date'] ?? $firstDay));
                ?> 
                (<?php echo number_format($maxWeek['total_qty'] ?? 0, 2); ?> L)
            </li>
            <li><strong>Average Weekly Distribution:</strong> 
                <?php echo number_format($summary['total_quantity'] / count($weekData), 2); ?> Liters
            </li>
            <li><strong>Total Beneficiaries Served:</strong> 
                Children: <?php echo number_format($summary['total_children'], 0); ?> | 
                Pregnant Women: <?php echo number_format($summary['total_pregnant'], 0); ?>
            </li>
            <li><strong>Distribution Efficiency:</strong> 
                <?php echo number_format(($summary['total_orders'] / 4 / count($weekData)) * 100, 1); ?>% 
                (based on weekly order submissions)
            </li>
        </ul>
    </div>
    
    <div style="margin-top: 50px; display: flex; justify-content: space-between;">
        <div style="width: 30%; border-top: 1px solid #000; padding-top: 10px; margin-top: 30px;">
            <strong>Prepared By</strong>
        </div>
        <div style="width: 30%; border-top: 1px solid #000; padding-top: 10px; margin-top: 30px;">
            <strong>Verified By</strong>
        </div>
        <div style="width: 30%; border-top: 1px solid #000; padding-top: 10px; margin-top: 30px;">
            <strong>Approved By</strong>
        </div>
    </div>
    
    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px;">
        Generated on: <?php echo date('d-m-Y H:i:s'); ?> | Vasudhara Milk Distribution System
    </div>
    
    <?php endif; ?>
</body>
</html>