<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

// Get filter parameters
$filterApplied = false;
$schoolType = $_GET['school_type'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$districtId = $_GET['district_id'] ?? '';
$talukaId = $_GET['taluka_id'] ?? '';
$gharakId = $_GET['gharak_id'] ?? '';
$ratePerLiter = isset($_GET['rate']) && $_GET['rate'] > 0 ? floatval($_GET['rate']) : 0;
$gstPercent = isset($_GET['gst']) ? floatval($_GET['gst']) : 5.00;

// Get data for dropdowns
$districts = getDistricts();
$anganwadis = getAnganwadiList(['status' => 'active']);

// Database connection
$db = getDB();

// Build query based on filters - USING weekly_orders TABLE
$reportData = [];
if ($startDate && $endDate) {
    $filterApplied = true;
    
    $query = "
        SELECT 
            a.id,
            a.aw_code,
            a.name as anganwadi_name,
            a.type,
            v.name as village_name,
            t.name as taluka_name,
            d.name as district_name,
            COALESCE(SUM(wo.total_qty), 0) as total_quantity,
            COALESCE(SUM(wo.total_qty * $ratePerLiter), 0) as total_amount,
            COUNT(wo.id) as order_count
        FROM anganwadi a
        LEFT JOIN weekly_orders wo ON a.id = wo.anganwadi_id 
            AND wo.week_start_date >= ? 
            AND wo.week_start_date <= ?
            AND wo.status IN ('approved', 'dispatched', 'completed')
        LEFT JOIN villages v ON a.village_id = v.id
        LEFT JOIN talukas t ON v.taluka_id = t.id
        LEFT JOIN districts d ON t.district_id = d.id
        WHERE a.status = 'active'
    ";
    
    // Prepare parameters array
    $params = [$startDate, $endDate];
    $types = "ss";
    
    // Add filters dynamically
    if ($schoolType) {
        $query .= " AND a.type = ?";
        $params[] = $schoolType;
        $types .= "s";
    }
    
    if ($districtId) {
        $query .= " AND d.id = ?";
        $params[] = (int)$districtId;
        $types .= "i";
    }
    
    if ($talukaId) {
        $query .= " AND t.id = ?";
        $params[] = (int)$talukaId;
        $types .= "i";
    }
    
    $query .= " GROUP BY a.id HAVING total_quantity > 0 ORDER BY d.name, t.name, a.name";
    
    // Prepare and execute
    $stmt = $db->prepare($query);
    
    if ($stmt) {
        // Bind parameters dynamically
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $reportData[] = $row;
        }
        $stmt->close();
    } else {
        die("Database query error: " . $db->error);
    }
}

// Calculate totals
$totalQuantity = 0;
$totalAmount = 0;
$totalGST = 0;
$grandTotal = 0;

foreach ($reportData as $row) {
    $totalQuantity += $row['total_quantity'];
    $totalAmount += $row['total_amount'];
}

$totalGST = $totalAmount * ($gstPercent / 100);
$grandTotal = $totalAmount + $totalGST;

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Dispatch Bill Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .report-header {
            text-align: center;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .report-title {
            color: #c62828;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .filter-info {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filter-item {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-item label {
            font-weight: 600;
            color: #495057;
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .filter-item .value {
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-weight: 500;
        }
        
        .table-custom {
            margin-top: 20px;
        }
        
        .table-custom thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .table-custom thead th {
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        
        .table-custom tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .total-row {
            background: #fff3cd !important;
            font-weight: bold;
            border-top: 3px solid #667eea;
        }
        
        .gst-section {
            margin-top: 30px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
        
        .gst-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 16px;
        }
        
        .gst-row.grand-total {
            border-top: 2px solid #667eea;
            padding-top: 15px;
            margin-top: 10px;
            font-size: 20px;
            font-weight: bold;
            color: #c62828;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
        }
        
        .btn-print {
            background: #c62828;
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            margin: 0 10px;
            cursor: pointer;
        }
        
        .btn-print:hover {
            background: #a52222;
            color: white;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            margin: 0 10px;
            cursor: pointer;
        }
        
        .btn-back {
            background: #667eea;
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            margin: 0 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .rate-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .gst-badge {
            background: #f3e5f5;
            color: #7b1fa2;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .filter-card,
            .action-buttons,
            .no-print {
                display: none !important;
            }
            .report-card {
                box-shadow: none;
                padding: 20px;
            }
        }
        
        .copyright {
            text-align: center;
            margin-top: 40px;
            color: #6c757d;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <!-- Filter Card -->
    <div class="filter-card no-print">
        <h4 class="mb-4"><i class="fas fa-filter"></i> Generate Monthly Dispatch Bill Report</h4>
        
        <form method="GET">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">School Type</label>
                    <select class="form-select" name="school_type">
                        <option value="">All Types</option>
                        <option value="anganwadi" <?php echo $schoolType === 'anganwadi' ? 'selected' : ''; ?>>Anganwadi</option>
                        <option value="school" <?php echo $schoolType === 'school' ? 'selected' : ''; ?>>School</option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Start Date *</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>" required>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">End Date *</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>" required>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">District</label>
                    <select class="form-select" name="district_id" id="district">
                        <option value="">All Districts</option>
                        <?php foreach ($districts as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo $districtId == $d['id'] ? 'selected' : ''; ?>>
                                <?php echo $d['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Taluka</label>
                    <select class="form-select" name="taluka_id" id="taluka">
                        <option value="">All Talukas</option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Rate per Liter (₹) *</label>
                    <input type="number" class="form-control" name="rate" 
                           value="<?php echo $ratePerLiter > 0 ? $ratePerLiter : ''; ?>" 
                           min="0" step="0.01" placeholder="Enter rate" required>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">GST (%) *</label>
                    <select class="form-select" name="gst" required>
                        <option value="5" <?php echo $gstPercent == 5 ? 'selected' : ''; ?>>5%</option>
                        <option value="12" <?php echo $gstPercent == 12 ? 'selected' : ''; ?>>12%</option>
                        <option value="18" <?php echo $gstPercent == 18 ? 'selected' : ''; ?>>18%</option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Gharak Center</label>
                    <input type="text" class="form-control" name="gharak_id" value="<?php echo htmlspecialchars($gharakId); ?>" placeholder="Enter Gharak Name">
                </div>
                
                <div class="col-md-12 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Generate Report
                    </button>
                    <a href="reports.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Reports
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <?php if ($filterApplied): ?>
    <!-- Report Card -->
    <div class="report-card">
        <!-- Header -->
        <div class="report-header">
            <div class="report-title">Monthly Dispatch Bill Report</div>
            <div style="color: #6c757d; font-size: 14px;">Generated on: <?php echo date('d-m-Y h:i A'); ?></div>
        </div>
        
        <!-- Filter Info Section -->
        <div class="filter-section">
            <div class="filter-info">
                <div class="filter-item">
                    <label>School Type:</label>
                    <div class="value">
                        <?php echo $schoolType ? ucfirst($schoolType) : 'All Types'; ?>
                    </div>
                </div>
                
                <div class="filter-item">
                    <label>Start Date:</label>
                    <div class="value">
                        <?php echo date('d-m-Y', strtotime($startDate)); ?>
                    </div>
                </div>
                
                <div class="filter-item">
                    <label>End Date:</label>
                    <div class="value">
                        <?php echo date('d-m-Y', strtotime($endDate)); ?>
                    </div>
                </div>
                
                <div class="filter-item">
                    <label>District Name:</label>
                    <div class="value">
                        <?php 
                        if ($districtId) {
                            $district = array_filter($districts, fn($d) => $d['id'] == $districtId);
                            echo reset($district)['name'] ?? 'Unknown';
                        } else {
                            echo 'All Districts';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="filter-item">
                    <label>Rate per Liter:</label>
                    <div class="value">
                        <span class="rate-badge">₹<?php echo number_format($ratePerLiter, 2); ?></span>
                    </div>
                </div>
                
                <div class="filter-item">
                    <label>GST:</label>
                    <div class="value">
                        <span class="gst-badge"><?php echo $gstPercent; ?>%</span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($reportData)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h5>No Records Found</h5>
                <p>No weekly orders found for the selected date range and filters.</p>
                <p class="text-muted small">Make sure you have created weekly orders for this period.</p>
            </div>
        <?php else: ?>
            <!-- Data Table -->
            <table class="table table-bordered table-custom">
                <thead>
                    <tr>
                        <th style="width: 5%;">Sr.</th>
                        <th style="width: 10%;">Code</th>
                        <th style="width: 25%;">Anganwadi/School Name</th>
                        <th style="width: 15%;">Village</th>
                        <th style="width: 10%;">Type</th>
                        <th style="width: 12%;">Quantity (L)</th>
                        <th style="width: 12%;">Rate (₹/L)</th>
                        <th style="width: 15%;">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sr = 1;
                    foreach ($reportData as $row): 
                        $quantity = $row['total_quantity'] ?? 0;
                        $amount = $row['total_amount'] ?? 0;
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $sr++; ?></td>
                            <td><?php echo htmlspecialchars($row['aw_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['anganwadi_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['village_name']); ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?php echo $row['type'] === 'school' ? 'primary' : 'success'; ?>">
                                    <?php echo ucfirst($row['type']); ?>
                                </span>
                            </td>
                            <td class="text-end"><?php echo number_format($quantity, 2); ?></td>
                            <td class="text-end">₹<?php echo number_format($ratePerLiter, 2); ?></td>
                            <td class="text-end">₹<?php echo number_format($amount, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Total Row -->
                    <tr class="total-row">
                        <td colspan="5" class="text-end"><strong>Sub Total</strong></td>
                        <td class="text-end"><strong><?php echo number_format($totalQuantity, 2); ?></strong></td>
                        <td></td>
                        <td class="text-end"><strong>₹<?php echo number_format($totalAmount, 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
            
            <!-- GST Section -->
            <div class="gst-section">
                <div class="gst-row">
                    <span>Sub Total</span>
                    <span>₹<?php echo number_format($totalAmount, 2); ?></span>
                </div>
                <div class="gst-row">
                    <span>GST (<?php echo $gstPercent; ?>%)</span>
                    <span>₹<?php echo number_format($totalGST, 2); ?></span>
                </div>
                <div class="gst-row grand-total">
                    <span>Grand Total</span>
                    <span>₹<?php echo number_format($grandTotal, 2); ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="action-buttons no-print">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Print
            </button>
            <button onclick="window.close()" class="btn-cancel">
                <i class="fas fa-times"></i> Cancel
            </button>
            <a href="reports.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <!-- Copyright -->
        <div class="copyright">
            © AMUL 2018
        </div>
    </div>
    <?php endif; ?>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load talukas when district changes
        $('#district').change(function() {
            const districtId = $(this).val();
            if (districtId) {
                $.ajax({
                    url: 'anganwadi.php?get_talukas=1',
                    type: 'GET',
                    data: { district_id: districtId },
                    dataType: 'json',
                    success: function(data) {
                        let options = '<option value="">All Talukas</option>';
                        if (data && data.length > 0) {
                            data.forEach(t => options += `<option value="${t.id}">${t.name}</option>`);
                        }
                        $('#taluka').html(options);
                    },
                    error: function() {
                        $('#taluka').html('<option value="">Error loading talukas</option>');
                    }
                });
            } else {
                $('#taluka').html('<option value="">All Talukas</option>');
            }
        });
    </script>
</body>
</html>