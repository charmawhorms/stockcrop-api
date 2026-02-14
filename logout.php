<?php
    session_start(); // Start the session FIRST
    $_SESSION = [];
    session_unset();
    session_destroy();
    header("location: login.php");
    exit();
?>
