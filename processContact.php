<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data
    $name    = strip_tags(trim($_POST["name"]));
    $email   = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $subject = strip_tags(trim($_POST["subject"]));
    $message = trim($_POST["message"]);

    $recipient = "kififax956@nctime.com"; //temp mail

    // Email content
    $email_content = "Name: $name\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "Subject: $subject\n\n";
    $email_content .= "Message:\n$message\n";

    // Email headers
    $email_headers = "From: $name <$email>";

    // Send the email
    if (mail($recipient, "New StockCrop Inquiry: $subject", $email_content, $email_headers)) {
        // Redirect to a success state
        header("Location: contact.php?status=success#contact-form");
    } else {
        // Redirect to an error state
        header("Location: contact.php?status=error#contact-form");
    }
} else {
    // Not a POST request, redirect back to contact page
    header("Location: contact.php");
}
exit;
?>