<?php
header("Content-Type: application/json");

$conn = mysqli_connect(
    "bkr68s67yybaqejy9ptf-mysql.services.clever-cloud.com",
    "uejfcvkdxo0isxpe",
    "bP4O5Zt83DMSaJKVXDXP",
    "bkr68s67yybaqejy9ptf"
);

if (!$conn) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit;
}
