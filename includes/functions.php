<?php
/**
 * Common Functions for Milk Distribution System
 */

// Order Management Functions

function getWeekDates($weekStartDate) {
    $dates = [];
    $start = new DateTime($weekStartDate);
    
    for ($i = 0; $i < 5; $i++) {
        $dates[] = $start->format('Y-m-d');
        $start->modify('+1 day');
    }
    
    return $dates;
}

function getOrdersByStatus($status, $userId = null) {
    $db = getDB();
    
    if ($userId) {
        $stmt = $db->prepare("
            SELECT wo.*, a.name as anganwadi_name, a.aw_code, u.name as user_name
            FROM weekly_orders wo
            JOIN anganwadi a ON wo.anganwadi_id = a.id
            JOIN users u ON wo.user_id = u.id
            WHERE wo.status = ? AND wo.user_id = ?
            ORDER BY wo.week_start_date DESC
        ");
        $stmt->bind_param("si", $status, $userId);
    } else {
        $stmt = $db->prepare("
            SELECT wo.*, a.name as anganwadi_name, a.aw_code, u.name as user_name, u.mobile
            FROM weekly_orders wo
            JOIN anganwadi a ON wo.anganwadi_id = a.id
            JOIN users u ON wo.user_id = u.id
            WHERE wo.status = ?
            ORDER BY wo.week_start_date DESC
        ");
        $stmt->bind_param("s", $status);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    $stmt->close();
    return $orders;
}

function getOrderById($orderId) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT wo.*, a.name as anganwadi_name, a.aw_code, a.type as anganwadi_type,
               a.total_children, a.pregnant_women, a.contact_person, a.mobile as anganwadi_mobile,
               u.name as user_name, u.mobile as user_mobile,
               r.route_name, r.route_number, r.vehicle_number,
               v.name as village_name, t.name as taluka_name, d.name as district_name
        FROM weekly_orders wo
        JOIN anganwadi a ON wo.anganwadi_id = a.id
        JOIN users u ON wo.user_id = u.id
        LEFT JOIN routes r ON a.route_id = r.id
        LEFT JOIN villages v ON a.village_id = v.id
        LEFT JOIN talukas t ON v.taluka_id = t.id
        LEFT JOIN districts d ON t.district_id = d.id
        WHERE wo.id = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    return $order;
}

function createWeeklyOrder($data) {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO weekly_orders 
        (user_id, anganwadi_id, week_start_date, week_end_date, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, 
         total_qty, children_allocation, pregnant_women_allocation, total_bags, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
    "iissddddddddds",  // 14 characters (added one more 'd' for total_bags)
        $data['user_id'],
        $data['anganwadi_id'],
        $data['week_start_date'],
        $data['week_end_date'],
        $data['mon_qty'],
        $data['tue_qty'],
        $data['wed_qty'],
        $data['thu_qty'],
        $data['fri_qty'],
        $data['total_qty'],
        $data['children_allocation'],
        $data['pregnant_women_allocation'],
        $data['total_bags'],
        $data['remarks']
    );
    
    $result = $stmt->execute();
    $orderId = $db->insert_id;
    $stmt->close();
    
    if ($result) {
        logActivity($data['user_id'], 'CREATE_ORDER', 'weekly_orders', $orderId, null, $data);
    }
    
    return $orderId;
}

function updateOrderStatus($orderId, $newStatus, $userId, $remarks = null) {
    $db = getDB();
    
    $order = getOrderById($orderId);
    $oldStatus = $order['status'];
    
    $stmt = $db->prepare("UPDATE weekly_orders SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
    $stmt->bind_param("sii", $newStatus, $userId, $orderId);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        // Log status change
        $historyStmt = $db->prepare("
            INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, remarks)
            VALUES (?, ?, ?, ?, ?)
        ");
        $historyStmt->bind_param("issss", $orderId, $oldStatus, $newStatus, $userId, $remarks);
        $historyStmt->execute();
        $historyStmt->close();
        
        // Send notification to user
        if ($newStatus === 'approved') {
            Auth::createNotification(
                $order['user_id'],
                'Order Approved',
                "Your order for week {$order['week_start_date']} to {$order['week_end_date']} has been approved.",
                'approval'
            );
        }
        
        logActivity($userId, 'UPDATE_ORDER_STATUS', 'weekly_orders', $orderId, ['status' => $oldStatus], ['status' => $newStatus]);
    }
    
    return $result;
}

// Master Data Functions

function getDistricts() {
    $db = getDB();
    $result = $db->query("SELECT * FROM districts WHERE status = 'active' ORDER BY name");
    $districts = [];
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    return $districts;
}

function getTalukasByDistrict($districtId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM talukas WHERE district_id = ? AND status = 'active' ORDER BY name");
    $stmt->bind_param("i", $districtId);
    $stmt->execute();
    $result = $stmt->get_result();
    $talukas = [];
    while ($row = $result->fetch_assoc()) {
        $talukas[] = $row;
    }
    $stmt->close();
    return $talukas;
}

function getVillagesByTaluka($talukaId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM villages WHERE taluka_id = ? AND status = 'active' ORDER BY name");
    $stmt->bind_param("i", $talukaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $villages = [];
    while ($row = $result->fetch_assoc()) {
        $villages[] = $row;
    }
    $stmt->close();
    return $villages;
}

function getAnganwadiList($filters = []) {
    $db = getDB();
    
    $sql = "SELECT a.*, v.name as village_name, v.taluka_id, t.name as taluka_name, t.district_id, d.name as district_name, r.route_name
            FROM anganwadi a
            LEFT JOIN villages v ON a.village_id = v.id
            LEFT JOIN talukas t ON v.taluka_id = t.id
            LEFT JOIN districts d ON t.district_id = d.id
            LEFT JOIN routes r ON a.route_id = r.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (!empty($filters['village_id'])) {
        $sql .= " AND a.village_id = ?";
        $params[] = $filters['village_id'];
        $types .= "i";
    }
    
    if (!empty($filters['type'])) {
        $sql .= " AND a.type = ?";
        $params[] = $filters['type'];
        $types .= "s";
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND a.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    $sql .= " ORDER BY a.name";
    
    $stmt = $db->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $anganwadis = [];
    
    while ($row = $result->fetch_assoc()) {
        $anganwadis[] = $row;
    }
    
    $stmt->close();
    return $anganwadis;
}

function getRoutesList() {
    $db = getDB();
    $result = $db->query("SELECT * FROM routes WHERE status = 'active' ORDER BY route_number");
    $routes = [];
    while ($row = $result->fetch_assoc()) {
        $routes[] = $row;
    }
    return $routes;
}

// Dashboard Statistics

function getDashboardStats($userId = null, $role = 'user') {
    $db = getDB();
    $stats = [];
    
    if ($role === 'admin') {
        // Total orders
        $result = $db->query("SELECT COUNT(*) as count FROM weekly_orders");
        $stats['total_orders'] = $result->fetch_assoc()['count'];
        
        // Pending approvals
        $result = $db->query("SELECT COUNT(*) as count FROM weekly_orders WHERE status = 'pending'");
        $stats['pending_orders'] = $result->fetch_assoc()['count'];
        
        // Approved this week
        $result = $db->query("SELECT COUNT(*) as count FROM weekly_orders WHERE status = 'approved' AND WEEK(created_at) = WEEK(NOW())");
        $stats['approved_this_week'] = $result->fetch_assoc()['count'];
        
        // Total anganwadis
        $result = $db->query("SELECT COUNT(*) as count FROM anganwadi WHERE status = 'active'");
        $stats['total_anganwadis'] = $result->fetch_assoc()['count'];
        
        // Total routes
        $result = $db->query("SELECT COUNT(*) as count FROM routes WHERE status = 'active'");
        $stats['total_routes'] = $result->fetch_assoc()['count'];
        
        // Total users
        $result = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $stats['total_users'] = $result->fetch_assoc()['count'];
    } else {
        // User stats
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM weekly_orders WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_orders'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM weekly_orders WHERE user_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['pending_orders'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM weekly_orders WHERE user_id = ? AND status = 'approved'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['approved_orders'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM weekly_orders WHERE user_id = ? AND status = 'dispatched'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['dispatched_orders'] = $result->fetch_assoc()['count'];
        $stmt->close();
    }
    
    return $stats;
}

// Chart Data Functions

function getWeeklyOrderTrends($weeks = 8) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(week_start_date, '%d %b') as week_label,
            COUNT(*) as order_count,
            SUM(total_qty) as total_quantity
        FROM weekly_orders
        WHERE week_start_date >= DATE_SUB(NOW(), INTERVAL ? WEEK)
        GROUP BY week_start_date
        ORDER BY week_start_date
    ");
    $stmt->bind_param("i", $weeks);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [
        'labels' => [],
        'orders' => [],
        'quantity' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['week_label'];
        $data['orders'][] = $row['order_count'];
        $data['quantity'][] = $row['total_quantity'];
    }
    
    $stmt->close();
    return $data;
}

function getDistrictWiseDistribution() {
    $db = getDB();
    
    $result = $db->query("
        SELECT d.name as district, COUNT(wo.id) as order_count, SUM(wo.total_qty) as total_qty
        FROM weekly_orders wo
        JOIN anganwadi a ON wo.anganwadi_id = a.id
        JOIN villages v ON a.village_id = v.id
        JOIN talukas t ON v.taluka_id = t.id
        JOIN districts d ON t.district_id = d.id
        WHERE wo.status IN ('approved', 'dispatched', 'completed')
        GROUP BY d.id
        ORDER BY total_qty DESC
    ");
    
    $data = [
        'labels' => [],
        'data' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['district'];
        $data['data'][] = $row['total_qty'];
    }
    
    return $data;
}

function getStatusDistribution() {
    $db = getDB();
    
    $result = $db->query("
        SELECT status, COUNT(*) as count
        FROM weekly_orders
        GROUP BY status
    ");
    
    $data = [
        'labels' => [],
        'data' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = ucfirst($row['status']);
        $data['data'][] = $row['count'];
    }
    
    return $data;
}
?>