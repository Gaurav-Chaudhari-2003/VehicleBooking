<?php
require_once '../response.php';
require_once '../auth/middleware.php';
require_once '../../DATABASE FILE/config.php';

$user = requireAuth();

if (!in_array($user['role'], ['ADMIN','MANAGER'])) {
    apiResponse(false, "Unauthorized", null, 403);
}

$sql = "
SELECT
 d.id,
 u.first_name,
 u.last_name,
 u.phone,
 d.license_no,
 d.experience_years,
 d.status
FROM drivers d
JOIN users u ON d.user_id = u.id
WHERE d.status = 'ACTIVE'
ORDER BY u.first_name
";

global $mysqli;
$stmt = $mysqli->prepare($sql);
$stmt->execute();
$res = $stmt->get_result();

$list = [];

while ($r = $res->fetch_assoc()) {
    $list[] = [
        "id" => (int)$r['id'],
        "name" => $r['first_name'].' '.$r['last_name'],
        "phone" => $r['phone'],
        "license_no" => $r['license_no'],
        "experience_years" => (int)$r['experience_years'],
        "status" => $r['status']
    ];
}

apiResponse(true, "Drivers fetched", $list);
