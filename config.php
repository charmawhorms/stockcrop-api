<?php
    $servername = "bkr68s67yybaqejy9ptf-mysql.services.clever-cloud.com"; //Clever Cloud Host
    $username = "uuejfcvkdxo0isxpe"; //Clever Cloud User
    $password = "bP4O5Zt83DMSaJKVXDXP";      // Your Clever Cloud Password
    $database   = "bkr68s67yybaqejy9ptf"; // Your Clever Cloud DB Name
    $port = 3306; 

    // Establishing the connection
    $conn = mysqli_connect($servername, $username, $password, $database);

    // Checking the connection
    if (!$conn) {
        // If the connection fails, die with the error message
        die("Connection failed: " . mysqli_connect_error());
    }
?>
