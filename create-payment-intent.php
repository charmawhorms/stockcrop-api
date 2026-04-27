<?php
require_once('stripe-php/init.php'); 
session_start();
include 'config.php';

\Stripe\Stripe::setApiKey($stripe_secret_key);

header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['id'];

/**
 * Calculate the total from the cartItems table
 * We sum up the lineTotal for everything currently in the user's cart.
 */
$query = "SELECT SUM(ci.quantity * ci.price) as subtotal 
          FROM cartitems ci 
          JOIN cart c ON ci.cartId = c.id 
          WHERE c.userId = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cart = mysqli_fetch_assoc($result);

$subtotal = $cart['subtotal'] ?? 0;

// If cart is empty, don't create an intent
if ($subtotal <= 0) {
    echo json_encode(['error' => 'Cart is empty']);
    exit;
}

/**
 * Add Shipping (Optional)
 * If you want to include the J$500 delivery fee in the Stripe total:
 * $total = $subtotal + 500; 
 */
$total = $subtotal; 

// Convert to cents (Stripe requirement)
$amountInCents = round($total * 100);

try {
    /**
     * Create the Payment Intent
     */
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amountInCents, 
        'currency' => 'usd', // JMD isn't supported as yet
        'automatic_payment_methods' => ['enabled' => true],
        'metadata' => [
            'user_id' => $userId,
            'description' => 'StockCrop Marketplace Purchase'
        ]
    ]);

    echo json_encode(['clientSecret' => $paymentIntent->client_secret]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>