<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

$cartData = [];
$productIds = [];
$isLoggedIn = isset($_SESSION['id']);
$userId = $isLoggedIn ? $_SESSION['id'] : null;

//Get cart source (logged in = database | guest = session)
if ($isLoggedIn) {

    $stmt = mysqli_prepare($conn, "
        SELECT ci.productId, ci.quantity, ci.price 
        FROM cartItems ci
        JOIN cart c ON ci.cartId = c.id
        WHERE c.userId = ?
    ");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $productIds[] = $row['productId'];

            $cartData[$row['productId']] = [
                'quantity' => intval($row['quantity']),
                'price' => floatval($row['price'])
            ];
        }
        mysqli_stmt_close($stmt);
    }

} elseif (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $productIds[] = $productId;
        $cartData[$productId] = ['quantity' => intval($quantity)];
    }
}

//Fetch product details
if (!empty($productIds)) {

    $idString = implode(',', array_map('intval', $productIds));

    $sql = "
        SELECT p.id, p.productName, p.price, p.unitOfSale,
               p.imagePath, p.stockQuantity, f.parish
        FROM products p
        JOIN farmers f ON p.farmerId = f.id
        WHERE p.id IN ($idString)
    ";

    $productResult = mysqli_query($conn, $sql);

    while ($product = mysqli_fetch_assoc($productResult)) {

        $id = $product['id'];
        $originalPrice = floatval($product['price']);

        //Default price (guest OR stored cart price)
        $itemPrice = $cartData[$id]['price'] ?? $originalPrice;

        //Bid validation (logged in ONLY)
        if ($isLoggedIn) {

            $bidCheck = mysqli_prepare($conn, "
                SELECT counterAmount, expiresAt
                FROM bids
                WHERE productId = ?
                AND userId = ?
                AND bidStatus = 'Accepted'
                ORDER BY id DESC
                LIMIT 1
            ");

            mysqli_stmt_bind_param($bidCheck, "ii", $id, $userId);
            mysqli_stmt_execute($bidCheck);
            $bidResult = mysqli_stmt_get_result($bidCheck);
            $bid = mysqli_fetch_assoc($bidResult);
            mysqli_stmt_close($bidCheck);

            if ($bid) {

                if (!empty($bid['expiresAt']) && strtotime($bid['expiresAt']) < time()) {

                    //Bid expired - reset to original product price
                    $itemPrice = $originalPrice;

                    //Update cartItems table
                    $update = mysqli_prepare($conn, "
                        UPDATE cartItems ci
                        JOIN cart c ON ci.cartId = c.id
                        SET ci.price = ?
                        WHERE ci.productId = ?
                        AND c.userId = ?
                    ");
                    mysqli_stmt_bind_param($update, "dii", $itemPrice, $id, $userId);
                    mysqli_stmt_execute($update);
                    mysqli_stmt_close($update);

                } else {

                    //Bid still valid
                    $itemPrice = floatval($bid['counterAmount']);
                }
            }
        }

        $quantity = $cartData[$id]['quantity'];
        $lineTotal = round($quantity * $itemPrice, 2);

        //Merge product details with cart quantity
        $cartData[$id] = [
            'id' => intval($id),
            'productName' => htmlspecialchars($product['productName']),
            'price' => $itemPrice,
            'quantity' => $quantity,
            'unitOfSale' => htmlspecialchars($product['unitOfSale']),
            'imagePath' => htmlspecialchars($product['imagePath']),
            'stockQuantity' => intval($product['stockQuantity']),
            'parish' => htmlspecialchars($product['parish']),
            'lineTotal' => $lineTotal
        ];
    }
}

//Convert the associative array (key=productId) into an indexed array for easier JavaScript iteration
echo json_encode(['items' => array_values($cartData)]);