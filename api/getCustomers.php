<?php
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");

    require_once(__DIR__ . "/databaseConn.php");
    require_once('../session.php');

    // Check if user is logged in and is a farmer
    if (!isset($_SESSION['id']) || $_SESSION['roleId'] != 2) {
        echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
        exit;
    }

    // SQL to get users with roleId 3 (Customers)
    $query = "SELECT id, firstName, lastName, email, phoneNumber, createdAt 
            FROM users 
            WHERE roleId = 3 
            ORDER BY createdAt DESC";

    $result = mysqli_query($conn, $query);

    if ($result) {
        $customers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $customers[] = $row;
        }
        echo json_encode([
            "status" => "success",
            "count" => count($customers),
            "data" => $customers
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to retrieve customers: " . mysqli_error($conn)
        ]);
    }

    mysqli_close($conn);
?>