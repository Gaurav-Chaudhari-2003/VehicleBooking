<?php
require_once '../response.php';
require_once '../auth/middleware.php';
require_once '../../DATABASE FILE/config.php';

$user = requireAuth();

global $mysqli;

// Fetch full user details
$query = "SELECT first_name, last_name, email, phone, address, role, created_at FROM users WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

if (!$userData) {
    apiResponse(false, "User not found", null, 404);
}

// Fetch booking statistics
$statsQuery = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'APPROVED' THEN 1 ELSE 0 END) as approved_bookings,
    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_bookings
FROM bookings WHERE user_id = ?";

$statsStmt = $mysqli->prepare($statsQuery);
$statsStmt->bind_param('i', $user['id']);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

// Format response
$response = [
    "user" => [
        "first_name" => $userData['first_name'],
        "last_name" => $userData['last_name'],
        "email" => $userData['email'],
        "phone" => $userData['phone'],
        "address" => $userData['address'],
        "role" => $userData['role'],
        "created_at" => $userData['created_at']
    ],
    "stats" => [
        "total_bookings" => (int)$stats['total_bookings'],
        "approved_bookings" => (int)$stats['approved_bookings'],
        "pending_bookings" => (int)$stats['pending_bookings']
    ]
];

apiResponse(true, "Profile data fetched", $response);
?>