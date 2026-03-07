<?php
    $servername = "bkr68s67yybaqejy9ptf-mysql.services.clever-cloud.com";
    $username = "uejfcvkdxo0isxpe";
    $password = "bP4O5Zt83DMSaJKVXDXP";
    $database   = "bkr68s67yybaqejy9ptf";
    $port = 3306; 

    /*$servername = "localhost";
    $username = "root";
    $password = "1234";
    $database   = "stockcrop";*/

    // Establishing the connection
    $conn = mysqli_connect($servername, $username, $password, $database);

    // Checking the connection
    if (!$conn) {
        // If the connection fails, die with the error message
        die("Connection failed: " . mysqli_connect_error());
    }
?>
