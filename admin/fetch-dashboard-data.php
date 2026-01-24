<?php
include('../DATABASE FILE/config.php');
header('Content-Type: application/json');

global $mysqli;

// Helper function
function getCount($table, $where = "") {
    global $mysqli;
    $sql = "SELECT COUNT(*) as count FROM $table $where";
    $result = $mysqli->query($sql);
    return $result->fetch_assoc()['count'];
}

// 1. Detailed Counts
$data = [
    'counts' => [
        'employees' => getCount('users', "WHERE role='EMPLOYEE'"),
        'drivers'   => getCount('users', "WHERE role='DRIVER'"),
        'vehicles'  => getCount('vehicles'),
        'bookings'  => getCount('bookings'),
        'pending'   => getCount('bookings', "WHERE status='PENDING'"),
        'approved'  => getCount('bookings', "WHERE status='APPROVED'"),
        'rejected'  => getCount('bookings', "WHERE status='REJECTED'"),
        'bookings_today' => getCount('bookings', "WHERE DATE(created_at) = CURDATE()"),
        'active_vehicles' => getCount('vehicles', "WHERE status IN ('AVAILABLE', 'BOOKED')"),
        'maintenance' => getCount('vehicles', "WHERE status='MAINTENANCE'"),
        'vendor_vehicles' => getCount('vehicles', "WHERE ownership_type='VENDOR'"),
        'dept_vehicles' => getCount('vehicles', "WHERE ownership_type='DEPARTMENT'")
    ],
    'last_updated' => date("h:i A")
];

// 2. Booking Status Distribution
$status_query = "SELECT status, COUNT(*) as count FROM bookings GROUP BY status";
$status_res = $mysqli->query($status_query);
$booking_stats = [];
while($row = $status_res->fetch_assoc()) {
    $booking_stats[$row['status']] = $row['count'];
}
$data['booking_stats'] = $booking_stats;

// 3. Weekly Booking Trends (Last 7 Days)
$trend_query = "
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM bookings 
    WHERE created_at >= DATE(NOW()) - INTERVAL 7 DAY 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
";
$trend_res = $mysqli->query($trend_query);
$trends = [];
$dates = [];
$counts = [];

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trends[$d] = 0;
}

while($row = $trend_res->fetch_assoc()) {
    $trends[$row['date']] = $row['count'];
}

foreach ($trends as $date => $count) {
    $dates[] = date('d M', strtotime($date));
    $counts[] = $count;
}

$data['trends'] = ['labels' => $dates, 'data' => $counts];

// 4. Vehicle Categories
$cat_query = "SELECT category, COUNT(*) as count FROM vehicles GROUP BY category";
$cat_res = $mysqli->query($cat_query);
$data['vehicle_categories'] = ['labels' => [], 'data' => []];
while($row = $cat_res->fetch_assoc()) {
    $data['vehicle_categories']['labels'][] = $row['category'];
    $data['vehicle_categories']['data'][] = $row['count'];
}

// 5. Fuel Type Distribution
$fuel_query = "SELECT fuel_type, COUNT(*) as count FROM vehicles GROUP BY fuel_type";
$fuel_res = $mysqli->query($fuel_query);
$data['vehicle_fuel'] = ['labels' => [], 'data' => []];
while($row = $fuel_res->fetch_assoc()) {
    $data['vehicle_fuel']['labels'][] = $row['fuel_type'];
    $data['vehicle_fuel']['data'][] = $row['count'];
}

// 6. Top 5 Most Booked Vehicles
$top_veh_query = "
    SELECT v.name, COUNT(b.id) as booking_count 
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    GROUP BY v.id 
    ORDER BY booking_count DESC 
    LIMIT 5
";
$top_veh_res = $mysqli->query($top_veh_query);
$data['top_vehicles'] = ['labels' => [], 'data' => []];
while($row = $top_veh_res->fetch_assoc()) {
    $data['top_vehicles']['labels'][] = $row['name'];
    $data['top_vehicles']['data'][] = $row['booking_count'];
}

// 7. Top 5 Drivers
$top_driver_query = "
    SELECT u.first_name, u.last_name, COUNT(b.id) as trip_count 
    FROM bookings b
    JOIN drivers d ON b.driver_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE b.status IN ('APPROVED', 'COMPLETED')
    GROUP BY d.id 
    ORDER BY trip_count DESC 
    LIMIT 5
";
$top_driver_res = $mysqli->query($top_driver_query);
$data['top_drivers'] = ['labels' => [], 'data' => []];
while($row = $top_driver_res->fetch_assoc()) {
    $data['top_drivers']['labels'][] = $row['first_name'] . ' ' . $row['last_name'];
    $data['top_drivers']['data'][] = $row['trip_count'];
}

// 8. Top 5 Requesters
$top_user_query = "
    SELECT u.first_name, u.last_name, COUNT(b.id) as booking_count 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    GROUP BY u.id 
    ORDER BY booking_count DESC 
    LIMIT 5
";
$top_user_res = $mysqli->query($top_user_query);
$data['top_users'] = ['labels' => [], 'data' => []];
while($row = $top_user_res->fetch_assoc()) {
    $data['top_users']['labels'][] = $row['first_name'] . ' ' . $row['last_name'];
    $data['top_users']['data'][] = $row['booking_count'];
}

// 9. Fleet Ownership
$own_query = "SELECT ownership_type, COUNT(*) as count FROM vehicles GROUP BY ownership_type";
$own_res = $mysqli->query($own_query);
$data['fleet_ownership'] = ['labels' => [], 'data' => []];
while($row = $own_res->fetch_assoc()) {
    $data['fleet_ownership']['labels'][] = $row['ownership_type'];
    $data['fleet_ownership']['data'][] = $row['count'];
}

// 10. Upcoming Trips (Next 48 Hours)
$upcoming_query = "
    SELECT b.id, b.from_datetime, u.first_name, u.last_name, v.name as v_name 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.status = 'APPROVED' 
    AND b.from_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
    ORDER BY b.from_datetime ASC LIMIT 5
";
$upcoming_res = $mysqli->query($upcoming_query);
$data['upcoming_trips'] = [];
while($row = $upcoming_res->fetch_assoc()) {
    $data['upcoming_trips'][] = $row;
}

// 11. Recent Bookings Table
$booking_query = "
    SELECT b.id as booking_id, b.from_datetime, b.to_datetime, b.status, b.created_at,
           u.first_name as u_fname, u.last_name as u_lname, u.phone as u_phone,
           v.name as v_name, v.reg_no as v_reg_no
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    ORDER BY b.created_at DESC LIMIT 10
";
$booking_res = $mysqli->query($booking_query);
$data['bookings'] = [];
while($row = $booking_res->fetch_assoc()) {
    $data['bookings'][] = $row;
}

echo json_encode($data);
?>
