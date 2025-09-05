<?php
$conn = new mysqli("localhost", "root", "", "fertilizer_shop");

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    exit("Invalid request");
}

$order_id = intval($_GET['order_id']);

$stmt = $conn->prepare("SELECT o.*, p.name AS product_name, p.price, u.name AS user_name, u.address 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    exit("❌ Order not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Bill</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 600px; margin: auto; }
        h2 { text-align: center; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        td, th { padding: 8px; border: 1px solid #ccc; text-align: left; }
        .btn { padding: 8px 16px; background: #007B5E; color: white; border: none; cursor: pointer; margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Payment Bill</h2>
    <p><strong>Order Id:</strong> <?= $order_id ?></p>
    <p><strong>Name:</strong> <?= htmlspecialchars($order['user_name']) ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
    <p><strong>Date:</strong> <?= htmlspecialchars($order['ordered_at']) ?></p>

    <table>
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Total</th>
        </tr>
        <tr>
            <td><?= htmlspecialchars($order['product_name']) ?></td>
            <td><?= (int)$order['quantity'] ?></td>
            <td>₹<?= number_format($order['price'], 2) ?></td>
            <td>₹<?= number_format($order['total'], 2) ?></td>
        </tr>
    </table>

    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>

    <?php if (!empty($order['payment_detail'])): ?>
        <p><strong>Payment CardNo/ID:</strong> <?= htmlspecialchars($order['payment_detail']) ?></p>
    <?php endif; ?>

    <button onclick="window.print()" class="btn">Print / Save as PDF</button>
</body>
</html>
