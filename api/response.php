<?php

use JetBrains\PhpStorm\NoReturn;

header("Content-Type: application/json");

#[NoReturn]
function apiResponse($success, $message, $data = null, $code = 200): void
{
    http_response_code($code);
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}
