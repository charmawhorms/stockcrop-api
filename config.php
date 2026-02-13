<?php
    $host = "bkr68s67yybaqej..."; // Your Clever Cloud Host
    $user = "uejfcvkdxo0isxp..."; // Your Clever Cloud User
    $pass = "your_password";      // Your Clever Cloud Password
    $db   = "bkr68s67yybaqej..."; // Your Clever Cloud DB Name
    $port = 3306; 

    // Establishing the connection
    $conn = mysqli_connect($servername, $username, $password, $database);

    // Checking the connection
    if (!$conn) {
        // If the connection fails, die with the error message
        die("Connection failed: " . mysqli_connect_error());
    }
?>
