<?php
    session_start();
    include 'config.php';

    header('Content-Type: application/json');

    $response = ['status' => 'error', 'message' => 'Server error occurred during update.'];
    $productId = isset($_POST['productId']) ? intval($_POST['productId']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

    if ($productId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product ID.']);
        exit;
    }

    // Check if the user is logged in
    if (isset($_SESSION['id'])) {
        $userId = $_SESSION['id'];

        // Get the cartId for the user
        $cartIdQuery = mysqli_prepare($conn, "SELECT id FROM cart WHERE userId = ?");
        mysqli_stmt_bind_param($cartIdQuery, "i", $userId);
        mysqli_stmt_execute($cartIdQuery);
        $cartResult = mysqli_stmt_get_result($cartIdQuery);
        $cartRow = mysqli_fetch_assoc($cartResult);
        $cartId = $cartRow['id'] ?? null;
        mysqli_stmt_close($cartIdQuery);

        if (!$cartId) {
            $response['message'] = 'User cart not found.';
        } elseif ($quantity <= 0) {
            // Remove item from cartItems table
            $deleteStmt = mysqli_prepare($conn, "DELETE FROM cartItems WHERE cartId = ? AND productId = ?");
            mysqli_stmt_bind_param($deleteStmt, "ii", $cartId, $productId);
            
            if (mysqli_stmt_execute($deleteStmt)) {
                $response['status'] = 'success';
                $response['message'] = 'Item removed from cart.';
            } else {
                $response['message'] = 'Database error during removal: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($deleteStmt);
            
        } else {
            // Update quantity in cartItems table
            
            $updateStmt = mysqli_prepare($conn, "UPDATE cartItems SET quantity = ? WHERE cartId = ? AND productId = ?");
            mysqli_stmt_bind_param($updateStmt, "iii", $quantity, $cartId, $productId);
            
            if (mysqli_stmt_execute($updateStmt)) {
                $response['status'] = 'success';
                $response['message'] = 'Quantity updated successfully.';
            } else {
                $response['message'] = 'Database error during update: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($updateStmt);
        }

        // After success/failure, recalculate the total cart count for the navbar badge
        if ($response['status'] == 'success') {
            $countStmt = mysqli_prepare($conn, "
                SELECT SUM(ci.quantity) AS total 
                FROM cartItems ci
                JOIN cart c ON ci.cartId = c.id
                WHERE c.userId = ?
            ");
            mysqli_stmt_bind_param($countStmt, "i", $userId);
            mysqli_stmt_execute($countStmt);
            $countResult = mysqli_stmt_get_result($countStmt);
            $countRow = mysqli_fetch_assoc($countResult);
            $response['newCount'] = intval($countRow['total'] ?? 0);
            mysqli_stmt_close($countStmt);
        }
        
    } else {
        // Guest User (Updating the Session Cart)
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if ($quantity <= 0) {
            // Remove item from session
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
                $response['status'] = 'success';
                $response['message'] = 'Item removed from session cart.';
            } else {
                $response['message'] = 'Item not found in session cart.';
            }
        } else {
            // Update quantity in session
            $_SESSION['cart'][$productId] = $quantity;
            $response['status'] = 'success';
            $response['message'] = 'Session quantity updated successfully.';
        }

        // Recalculate the total cart count for the navbar badge
        $response['newCount'] = array_sum($_SESSION['cart'] ?? []);
    }

    echo json_encode($response);
?>