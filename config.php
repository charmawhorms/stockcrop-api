<?php
    /*$servername = "bkr68s67yybaqejy9ptf-mysql.services.clever-cloud.com";
    $username = "uejfcvkdxo0isxpe";
    $password = "bP4O5Zt83DMSaJKVXDXP";
    $database   = "bkr68s67yybaqejy9ptf";
    $port = 3306; */

    $servername = "localhost";
    $username = "root";
    $password = "1234";
    $database   = "stockcrop";

    //Stripe API Keys
    $stripe_secret_key = "sk_test_51TOqa1I6mtrD5rnZHtTk8XK8GGyfj8bInAq5P5zIYvmoh1DqnYt7riliThGPxVeBCX71cqfu44RybuM3V5MFfOV700fRC1jTDX";
    $stripe_publishable_key ="pk_test_51TOqa1I6mtrD5rnZ1m7wW0UKr17f89BfIHid6ny5Y7gXpWYfx2tq566mPa7KqGSOTCCi5QCt6qDxdeSJ8xH2BpEz00xWOhAYgy";

    //PHPMailer Credentials
    $php_Mailer_Username = "charmawhorms28@gmail.com";
    $php_Mailer_Password = "cybowmldwtrwfrxl";

    // Establishing the connection
    $conn = mysqli_connect($servername, $username, $password, $database);

    // Checking the connection
    if (!$conn) {
        // If the connection fails, die with the error message
        die("Connection failed: " . mysqli_connect_error());
    }
?>
