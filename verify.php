<?php
include 'config.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Check if code exists and user is not yet verified
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE verification_code = ? AND is_verified = 0 LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // Activate account and clear the code
        $update = mysqli_prepare($conn, "UPDATE users SET is_verified = 1, verification_code = NULL WHERE verification_code = ?");
        mysqli_stmt_bind_param($update, "s", $code);
        
        if (mysqli_stmt_execute($update)) {
            echo "<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
                    <h1 style='color: #2f8f3f;'>Account Verified!</h1>
                    <p>Your StockCrop account is now active. You can now log in.</p>
                    <a href='login.php' style='color:#2f8f3f; font-weight:bold;'>Go to Login Screen</a>
                  </div>";
        }
    } else {
        echo "<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
                <h1 style='color: #dc3545;'>Invalid or Expired Link</h1>
                <p>This verification link is no longer valid.</p>
                <a href='index.php'>Back to Home</a>
              </div>";
    }
}
?>