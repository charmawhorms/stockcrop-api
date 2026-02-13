<?php
require __DIR__ . "/databaseConn.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Email and password required"
    ]);
    exit;
}

$stmt = mysqli_prepare($conn, "
    SELECT id, roleId, password_hash 
    FROM users 
    WHERE email = ?
");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    if (password_verify($password, $user['password_hash'])) {
        echo json_encode([
            "success" => true,
            "userId" => $user['id'],
            "roleId" => $user['roleId']
        ]);
        exit;
    }
}

http_response_code(401);
echo json_encode([
    "success" => false,
    "message" => "Invalid credentials"
]);

