<?php
// user_dashboard.php
session_start();

$msg = "";
$conn = new mysqli("localhost", "root", "", "fertilizer_shop");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION["user_id"])) {
    header("Location: user_login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Small helper for safe output (works on PHP 5.4)
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* ===============================
   ADD TO CART
================================*/
if (isset($_POST['add_cart'])) {
    $pid = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $qty = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

    // Check existing
    $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_id=?");
    $check->bind_param("ii", $user_id, $pid);
    $check->execute();
    $check->store_result();
    $check->bind_result($cid, $old_qty);

    if ($check->num_rows > 0 && $check->fetch()) {
        $new_qty = $old_qty + $qty;
        $upd = $conn->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?");
        $upd->bind_param("iii", $new_qty, $cid, $user_id);
        $upd->execute();
        $upd->close();
    } else {
        $ins = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $ins->bind_param("iii", $user_id, $pid, $qty);
        $ins->execute();
        $ins->close();
    }
    $check->close();

    $_SESSION['added_product_id'] = $pid;
    header("Location: user_dashboard.php#products");
    exit();
}

/* ===============================
   REMOVE FROM CART
================================*/
if (isset($_POST['remove_cart'])) {
    $cid = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
    $del = $conn->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
    $del->bind_param("ii", $cid, $user_id);
    $del->execute();
    $del->close();

    $msg = "❌ Product removed from cart.";
}

/* ===============================
   UPDATE CART QUANTITY
================================*/
if (isset($_POST['update_qty'])) {
    $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
    $new_qty = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

    $upd = $conn->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?");
    $upd->bind_param("iii", $new_qty, $cart_id, $user_id);
    $upd->execute();
    $upd->close();

    header("Location: user_dashboard.php#cart");
    exit();
}

/* ===============================
   CHECKOUT
================================*/
if (isset($_POST['checkout'])) {
    $method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
    $allowed_methods = array('COD', 'Credit Card', 'Debit Card', 'UPI');
    if (!in_array($method, $allowed_methods)) {
        $msg = "❌ Invalid payment method.";
    } else {
        // Build detail based on method
        $detail = '';
        if ($method === 'Credit Card') {
            $detail = isset($_POST['credit_card']) ? preg_replace('/\D+/', '', $_POST['credit_card']) : '';
            if (strlen($detail) !== 16) $msg = "❌ Enter a valid 16-digit credit card number.";
        } elseif ($method === 'Debit Card') {
            $detail = isset($_POST['debit_card']) ? preg_replace('/\D+/', '', $_POST['debit_card']) : '';
            if (strlen($detail) !== 16) $msg = "❌ Enter a valid 16-digit debit card number.";
        } elseif ($method === 'UPI') {
            $detail = isset($_POST['upi_id']) ? trim($_POST['upi_id']) : '';
            if (!preg_match('/^[a-zA-Z0-9.\-_]{2,}@[a-zA-Z]{2,}$/', $detail)) $msg = "❌ Enter a valid UPI ID.";
        } else { // COD
            $detail = 'COD';
        }

        if ($msg === "") {
            // Get cart items
            $csel = $conn->prepare("SELECT product_id, quantity FROM cart WHERE user_id=?");
            $csel->bind_param("i", $user_id);
            $csel->execute();
            $cres = $csel->get_result();

            // Prepare price lookup and order insert
            $psel = $conn->prepare("SELECT price FROM products WHERE id=?");
            $pins = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity, total, ordered_at, payment_method, payment_detail) VALUES (?, ?, ?, ?, NOW(), ?, ?)");

            while ($row = $cres->fetch_assoc()) {
                $pid = intval($row['product_id']);
                $qty = intval($row['quantity']);

                $psel->bind_param("i", $pid);
                $psel->execute();
                $pres = $psel->get_result();
                $prow = $pres ? $pres->fetch_assoc() : null;
                $price = $prow ? floatval($prow['price']) : 0.0;

                $total = $qty * $price;

                $pins->bind_param("iiidss", $user_id, $pid, $qty, $total, $method, $detail);
                $pins->execute();
            }
            $pins->close();
            $psel->close();
            $csel->close();

            // Clear cart
            $cdel = $conn->prepare("DELETE FROM cart WHERE user_id=?");
            $cdel->bind_param("i", $user_id);
            $cdel->execute();
            $cdel->close();

            header("Location: user_dashboard.php#orders");
            exit();
        }
    }
}

/* ===============================
   FEEDBACK SUBMISSION
================================*/
if (isset($_POST['submit_feedback'])) {
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if ($type === '' || $message === '') {
        $msg = "❌ Please select a type and enter a message.";
    } else {
        // Limit type to allowed values
        $allowed_types = array('Feedback', 'Complain');
        if (!in_array($type, $allowed_types)) $type = 'Feedback';

        $ins = $conn->prepare("INSERT INTO feedbacks (user_id, type, message) VALUES (?, ?, ?)");
        $ins->bind_param("iss", $user_id, $type, $message);
        $ins->execute();
        $ins->close();

        header("Location: user_dashboard.php?tab=feedback");
        exit();
    }
}

/* ===============================
   PROFILE UPDATE
================================*/
$profile_msg = "";
if (isset($_POST['update_profile'])) {
    $name    = isset($_POST['name'])    ? trim($_POST['name'])    : '';
    $email   = isset($_POST['email'])   ? trim($_POST['email'])   : '';
    $phone   = isset($_POST['phone'])   ? trim($_POST['phone'])   : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $password= isset($_POST['password'])? $_POST['password']      : '';

    $errors = array();
    if ($name === '' || $email === '') $errors[] = "Name and Email are required.";
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if ($phone !== '' && !preg_match('/^[0-9]{7,15}$/', $phone)) $errors[] = "Enter a valid phone number (7–15 digits).";

    if (empty($errors)) {
        if ($password !== '') {
            $hashed = md5($password); // PHP 5.4-compatible
            $upd = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, address=?, password=? WHERE id=?");
            $upd->bind_param("sssssi", $name, $email, $phone, $address, $hashed, $user_id);
        } else {
            $upd = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, address=? WHERE id=?");
            $upd->bind_param("ssssi", $name, $email, $phone, $address, $user_id);
        }
        if ($upd->execute()) {
            $profile_msg = "✅ Profile updated successfully.";
        } else {
            $profile_msg = "❌ Could not update profile.";
        }
        $upd->close();
    } else {
        $profile_msg = "❌ " . implode(" ", $errors);
    }
}

/* ===============================
   DATA FETCH FOR DISPLAY
================================*/
// Products
$products = $conn->query("SELECT id, name, price, description, image FROM products");

// Cart (for listing)
$cart_stmt = $conn->prepare("SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image 
                             FROM cart c JOIN products p ON c.product_id=p.id
                             WHERE c.user_id=?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_res = $cart_stmt->get_result();

// Orders
$orders_stmt = $conn->prepare("SELECT o.id, o.product_id, o.quantity, o.total, o.ordered_at, o.payment_method, o.payment_detail, p.name, p.image
                               FROM orders o JOIN products p ON o.product_id = p.id
                               WHERE o.user_id=?
                               ORDER BY o.ordered_at DESC");
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_res = $orders_stmt->get_result();

// Feedbacks
$fb_stmt = $conn->prepare("SELECT id, type, message, reply, created_at FROM feedbacks WHERE user_id=? ORDER BY created_at DESC");
$fb_stmt->bind_param("i", $user_id);
$fb_stmt->execute();
$feedbacks_res = $fb_stmt->get_result();

// User info for profile + address for COD preview
$u_stmt = $conn->prepare("SELECT name, email, phone, address FROM users WHERE id=?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$u_info = $u_stmt->get_result()->fetch_assoc();
$user_address = isset($u_info['address']) ? $u_info['address'] : "";

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <style>
        body { font-family: Arial; margin: 0; background: #f0f0f0; }
        header { background: #007B5E; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        nav { display: flex; background: #009973; }
        nav a { flex: 1; padding: 12px; color: white; text-align: center; text-decoration: none; border-right: 1px solid #007B5E; }
        nav a:hover, nav a.active { background: #00664d; }
        .section { display: none; padding: 20px; }
        .active-section { display: block; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #eee; }
        input, select { padding: 5px; }
        .btn { padding: 6px 12px; background: #007B5E; color: white; border: none; border-radius: 4px; cursor: pointer; }
        input[type="text"], input[type="email"], input[type="password"] { width: 300px; padding: 8px; margin-bottom: 10px; }
        .card { background: #fff; border-radius: 8px; padding: 15px; width: 220px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    </style>
    <script>
    function showTab(id) {
        var sections = document.querySelectorAll('.section');
        for (var i = 0; i < sections.length; i++) sections[i].classList.remove('active-section');
        document.getElementById(id).classList.add('active-section');

        var links = document.querySelectorAll('nav a');
        for (var j = 0; j < links.length; j++) links[j].classList.remove('active');
        var link = document.querySelector('[data-tab="' + id + '"]');
        if (link) link.classList.add('active');

        // Save current tab
        try { localStorage.setItem('activeTab', id); } catch (e) {}
    }

    window.onload = function() {
        var savedTab = 'products';
        try { savedTab = localStorage.getItem('activeTab') || 'products'; } catch (e) {}
        showTab(savedTab);
    };

    var userAddress = <?php echo json_encode($user_address); ?>;

    function showPaymentFields(methodKey) {
        var container = document.getElementById('payment-fields');
        container.innerHTML = "";

        if (methodKey === "Credit") {
            container.innerHTML = '<label>Credit Card No: </label><input type="text" name="credit_card" required pattern="\\d{16}" placeholder="Enter 16-digit card number">';
        } else if (methodKey === "Debit") {
            container.innerHTML = '<label>Debit Card No: </label><input type="text" name="debit_card" required pattern="\\d{16}" placeholder="Enter 16-digit card number">';
        } else if (methodKey === "UPI") {
            container.innerHTML = '<label>UPI ID: </label><input type="text" name="upi_id" required placeholder="example@upi">';
        } else if (methodKey === "COD") {
            var addr = userAddress ? userAddress : 'No address available. Please update profile.';
            container.innerHTML = '<label>Delivery Address:</label><div style="margin-top:5px; padding:10px; background:#f9f9f9; border:1px solid #ccc;">' + addr.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>';
        }
    }

    function validatePayment() {
        var methods = document.getElementsByName('payment_method');
        for (var i = 0; i < methods.length; i++) {
            if (methods[i].checked) return true;
        }
        alert("Please select a payment method before checkout.");
        return false;
    }
    </script>
</head>
<body>
<header>
    <h2>User Dashboard</h2>
    <a class="btn" href="?logout=1">Logout</a>
</header>

<?php if ($msg): ?>
    <div style="background:#fff3f3;color:#b30000;border:1px solid #ffcece;padding:10px;margin:10px 20px;border-radius:4px;">
        <?php echo e($msg); ?>
    </div>
<?php endif; ?>

<nav>
    <a href="#" data-tab="products" onclick="showTab('products')">🛒 Products</a>
    <a href="#" data-tab="cart" onclick="showTab('cart')">🧺 Your Cart</a>
    <a href="#" data-tab="orders" onclick="showTab('orders')">📜 Order History</a>
    <a href="#" data-tab="profile" onclick="showTab('profile')">👤 Profile</a>
    <a href="#" data-tab="feedback" onclick="showTab('feedback')">📝 Feedback & Complain</a>
</nav>

<!-- PRODUCTS -->
<div class="section" id="products">
    <h3>Available Products</h3>
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        <?php while ($p = $products->fetch_assoc()): ?>
        <form method="post" class="card">
            <input type="hidden" name="product_id" value="<?php echo intval($p['id']); ?>">
            <?php if (!empty($p['image'])): ?>
                <img src="<?php echo e($p['image']); ?>" style="width:100%; height: 180px; object-fit: cover; border-radius: 5px; margin-bottom: 10px;">
            <?php endif; ?>
            <h4 style="margin: 0 0 5px;"><?php echo e($p['name']); ?></h4>
            <p style="margin: 0 0 5px;"><strong>₹<?php echo e($p['price']); ?></strong></p>
            <p style="font-size: 13px; color: #555; margin-bottom: 10px;"><?php echo e($p['description']); ?></p>
            <input type="number" name="quantity" value="1" min="1" style="width: 60px;">
            <?php
                $added_id = isset($_SESSION['added_product_id']) ? $_SESSION['added_product_id'] : null;
                $is_added = ($added_id == $p['id']);
            ?>
            <button class="btn" name="add_cart" style="<?php echo $is_added ? 'background: gray;' : ''; ?>">
                <?php echo $is_added ? 'Added' : 'Add'; ?>
            </button>
        </form>
        <?php endwhile; ?>
    </div>
</div>

<!-- CART -->
<div class="section" id="cart">
    <h3>Your Cart</h3>

    <table>
        <tr><th>Product</th><th>Image</th><th>Qty</th><th>Price</th><th>Total</th><th>Action</th></tr>
        <?php
        $sum = 0.0;
        mysqli_data_seek($cart_res, 0);
        while ($c = $cart_res->fetch_assoc()):
            $line = floatval($c['price']) * intval($c['quantity']);
            $sum += $line;
        ?>
        <tr>
            <td><?php echo e($c['name']); ?></td>
            <td><?php if (!empty($c['image'])) echo "<img src='".e($c['image'])."' height='60'>"; ?></td>
            <td>
                <form method="post" style="display:inline-flex; gap:5px; align-items:center;">
                    <input type="hidden" name="cart_id" value="<?php echo intval($c['id']); ?>">
                    <input type="number" name="quantity" value="<?php echo intval($c['quantity']); ?>" min="1" style="width:60px;">
                    <button class="btn" name="update_qty">Update</button>
                </form>
            </td>
            <td>₹<?php echo e($c['price']); ?></td>
            <td>₹<?php echo number_format($line, 2); ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="cart_id" value="<?php echo intval($c['id']); ?>">
                    <button class="btn" name="remove_cart" onclick="return confirm('Remove this item?')">Remove</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        <tr><th colspan="4" style="text-align:right;">Total</th><th colspan="2">₹<?php echo number_format($sum, 2); ?></th></tr>
    </table>

    <!-- Separate checkout form (avoid nested forms) -->
    <form method="post" onsubmit="return validatePayment();">
        <h4>Choose Payment Method:</h4>
        <div style="display: flex; gap: 20px; align-items: center;">
            <label><input type="radio" name="payment_method" value="COD" onclick="showPaymentFields('COD')"> Cash on Delivery</label>
            <label><input type="radio" name="payment_method" value="Credit Card" onclick="showPaymentFields('Credit')"> Credit Card</label>
            <label><input type="radio" name="payment_method" value="Debit Card" onclick="showPaymentFields('Debit')"> Debit Card</label>
            <label><input type="radio" name="payment_method" value="UPI" onclick="showPaymentFields('UPI')"> UPI</label>
        </div><br>
        <div id="payment-fields" style="margin-top: 10px;"></div>
        <button class="btn" name="checkout">Checkout</button>
    </form>
</div>

<!-- ORDERS -->
<div class="section" id="orders">
    <h3>Order History</h3>
    <table>
        <tr>
            <th>Product</th><th>Image</th><th>Qty</th><th>Total</th>
            <th>Date</th><th>Payment Method</th><th>Payment Info</th><th>Invoice</th>
        </tr>
        <?php
        mysqli_data_seek($orders_res, 0);
        while ($o = $orders_res->fetch_assoc()): ?>
        <tr>
            <td><?php echo e($o['name']); ?></td>
            <td><?php if (!empty($o['image'])) echo "<img src='".e($o['image'])."' height='60'>"; ?></td>
            <td><?php echo intval($o['quantity']); ?></td>
            <td>₹<?php echo e($o['total']); ?></td>
            <td><?php echo e($o['ordered_at']); ?></td>
            <td><?php echo e($o['payment_method']); ?></td>
            <td><?php echo $o['payment_detail'] ? e($o['payment_detail']) : '-'; ?></td>
            <td><a class="btn" target="_blank" href="invoice_view.php?order_id=<?php echo intval($o['id']); ?>">🧾 Payment bill</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- PROFILE -->
<div class="section" id="profile">
    <h3 style="text-align:center;">Update Profile</h3>

    <?php if ($profile_msg): ?>
        <p style="color:<?php echo (strpos($profile_msg, '✅') !== false) ? 'green' : 'red'; ?>; text-align:center;">
            <?php echo e($profile_msg); ?>
        </p>
    <?php endif; ?>

    <form method="post" style="max-width: 900px; margin: auto; background: #fff; padding: 50px; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1); display: flex; flex-direction: column; gap: 15px;">
        <label for="name">Full Name:</label>
        <input type="text" name="name" id="name" value="<?php echo e($u_info['name']); ?>" required>

        <label for="email">Email Address:</label>
        <input type="email" name="email" id="email" value="<?php echo e($u_info['email']); ?>" required>

        <label for="phone">Phone Number:</label>
        <input type="text" name="phone" id="phone" value="<?php echo e($u_info['phone']); ?>">

        <label for="address">Address:</label>
        <textarea name="address" id="address" rows="3" style="resize: vertical;"><?php echo e($u_info['address']); ?></textarea>

        <label for="password">New Password (leave blank to keep current):</label>
        <input type="password" name="password" id="password">

        <button class="btn" name="update_profile">Update Profile</button>
    </form>
</div>

<!-- FEEDBACK -->
<div class="section" id="feedback">
    <h3>Feedback & Complain</h3>
    <form method="post">
        <label>Type:</label>
        <select name="type" required>
            <option value="Feedback">Feedback</option>
            <option value="Complain">Complain</option>
        </select><br><br>

        <label>Message:</label><br>
        <center>
            <textarea name="message" rows="8" style="width:1300px; font-size:16px;" required></textarea><br><br>
        </center>

        <button class="btn" name="submit_feedback">Submit</button>
    </form>

    <h3>Your Submitted Feedback & Complaints</h3>
    <table>
        <tr><th>Type</th><th>Message</th><th>Reply</th><th>Date</th></tr>
        <?php while ($f = $feedbacks_res->fetch_assoc()): ?>
        <tr>
            <td><?php echo e($f['type']); ?></td>
            <td><?php echo nl2br(e($f['message'])); ?></td>
            <td><?php echo $f['reply'] ? e($f['reply']) : 'No reply yet'; ?></td>
            <td><?php echo e($f['created_at']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php unset($_SESSION['added_product_id']); ?>

</body>
</html>
