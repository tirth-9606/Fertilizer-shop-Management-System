<?php
session_start();
$conn = new mysqli("localhost", "root", "", "fertilizer_shop");
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit();
}

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// ADD PRODUCT
if (isset($_POST["add_product"])) {
    $name = $_POST["name"];
    $price = $_POST["price"];
    $desc = $_POST["description"];
    $image = '';
    if ($_FILES['image']['name']) {
        if (!is_dir("uploads")) mkdir("uploads");
        $image = "uploads/" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    }
    $stmt = $conn->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $name, $price, $desc, $image);
    $stmt->execute();
}

// UPDATE PRODUCT
if (isset($_POST["update_product"])) {
    $id = $_POST["id"];
    $name = $_POST["name"];
    $price = $_POST["price"];
    $desc = $_POST["description"];
    $imageQuery = "";
    if ($_FILES['new_image']['name']) {
        $image = "uploads/" . basename($_FILES['new_image']['name']);
        move_uploaded_file($_FILES['new_image']['tmp_name'], $image);
        $imageQuery = ", image='$image'";
    }
    $conn->query("UPDATE products SET name='$name', price='$price', description='$desc' $imageQuery WHERE id=$id");
}

// DELETE PRODUCT
if (isset($_GET["delete_product"])) {
    $id = $_GET["delete_product"];
    $conn->query("DELETE FROM products WHERE id=$id");
    header("Location: admin_dashboard.php");
    exit();
}

// ADD USER
if (isset($_POST["add_user"])) {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $pass = $_POST["password"];
    $phone = $_POST["phone"];
    $addr = $_POST["address"];
    $conn->query("INSERT INTO users (name, email, password, phone, address) VALUES ('$name', '$email', '$pass', '$phone', '$addr')");
}

// UPDATE USER
if (isset($_POST["update_user"])) {
    $id = $_POST["id"];
    $name = $_POST["name"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $addr = $_POST["address"];
    $conn->query("UPDATE users SET name='$name', email='$email', phone='$phone', address='$addr' WHERE id=$id");
}

// DELETE USER
if (isset($_GET["delete_user"])) {
    $id = $_GET["delete_user"];
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: admin_dashboard.php");
    exit();
}

// FETCH CURRENT ADMIN DETAILS
$admin_id = $_SESSION['admin_id'];
$admin = $conn->query("SELECT * FROM admins WHERE id=$admin_id")->fetch_assoc();

// UPDATE ADMIN PROFILE
if (isset($_POST['update_admin'])) {
    $username = $_POST['username'];
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone'];
    $address  = $_POST['address'];
    $password = $_POST['password'];

    $sql = "UPDATE admins SET 
                username='$username', 
                name='$name', 
                email='$email', 
                phone='$phone', 
                address='$address'";

    if (!empty($password)) {
        $sql .= ", password='$password'";
    }

    $sql .= " WHERE id={$_SESSION['admin_id']}";
    $conn->query($sql);

    $msg = "Admin profile updated.";
    $admin = $conn->query("SELECT * FROM admins WHERE id={$_SESSION['admin_id']}")->fetch_assoc();
}

// Submit reply to feedback
if (isset($_POST['submit_reply'])) {
    $id = $_POST['feedback_id'];
    $reply = $_POST['reply'];
    $conn->query("UPDATE feedbacks SET reply='$reply' WHERE id=$id");
    header("Location: admin_dashboard.php?tab=feedbacks");
    exit();
}

// Fetch all feedbacks
$all_feedbacks = $conn->query("SELECT f.*, u.name AS uname FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC");

$products = $conn->query("SELECT * FROM products");
$orders = $conn->query("SELECT o.*, 
                               p.name AS pname, 
                               p.image, 
                               u.name AS uname, 
                               u.phone, 
                               u.address 
                        FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        JOIN users u ON o.user_id = u.id 
                        ORDER BY o.ordered_at DESC");

$users = $conn->query("SELECT * FROM users");
$filter_date = $_POST['filter_date'] ?? '';
$where = $filter_date ? "WHERE DATE(ordered_at) = '$filter_date'" : '';
$payments = $conn->query("SELECT o.*, p.name AS pname, u.name AS uname FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.user_id = u.id $where");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial; margin: 0; background: #f4f4f4; }
        header { background: #2e8b57; padding: 15px; color: white; display: flex; justify-content: space-between; align-items: center; }
        header div span { margin-left: 20px; }
        nav { display: flex; background: #3b9c6e; }
        nav a { flex: 1; padding: 12px; color: white; text-align: center; text-decoration: none; border-right: 1px solid #2e8b57; }
        nav a:hover, nav a.active { background: #256e4b; }
        .section { display: none; padding: 20px; }
        .active-section { display: block; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #eee; }
        input, textarea { width: 100%; padding: 5px; margin: 5px 0; }
        .btn { padding: 6px 12px; background: #2e8b57; color: white; border: none; border-radius: 4px; cursor: pointer; }
        img { height: 40px; }
    </style>
    <script>
        function showTab(id) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active-section'));
            document.getElementById(id).classList.add('active-section');
            document.querySelectorAll('nav a').forEach(a => a.classList.remove('active'));
            document.querySelector(`[data-tab="${id}"]`).classList.add('active');
        }
        window.onload = () => showTab('products');
    </script>
</head>
<body>
<header>
    <h2>Admin Dashboard</h2>
    <div>
        <a class="btn" href="?logout=1">Logout</a>
    </div>
</header>
<nav>
    <a href="#" data-tab="products" onclick="showTab('products')">🛒 Products</a>
    <a href="#" data-tab="orders" onclick="showTab('orders')">📦 Orders & Payments</a>
    <a href="#" data-tab="users" onclick="showTab('users')">👤 Users</a>
    <a href="#" data-tab="admin_profile" onclick="showTab('admin_profile')">🧑 Admin Profile</a>
    <a href="#" data-tab="admin_feedback" onclick="showTab('admin_feedback')">📬 Feedback & Complain</a>
</nav>

<div class="section" id="products">
    <h3>Add Product</h3>
    <form method="post" enctype="multipart/form-data">
        <input name="name" placeholder="Name" required>
        <input type="number" step="0.01" name="price" placeholder="Price" required>
        <textarea name="description" placeholder="Description" required></textarea>
        <input type="file" name="image" required>
        <button class="btn" name="add_product">Add</button>
    </form>
    <h3>Manage Products</h3>
    <table>
        <tr><th>ID</th><th>Name</th><th>Price</th><th>Description</th><th>Image</th><th>Actions</th></tr>
        <?php while ($p = $products->fetch_assoc()): ?>
        <tr>
            <form method="post" enctype="multipart/form-data">
                <td><?= $p['id'] ?><input type="hidden" name="id" value="<?= $p['id'] ?>"></td>
                <td><input name="name" value="<?= htmlspecialchars($p['name']) ?>"></td>
                <td><input type="number" step="0.01" name="price" value="<?= $p['price'] ?>"></td>
                <td><input name="description" value="<?= htmlspecialchars($p['description']) ?>"></td>
                <td><?php if ($p['image']) echo "<img src='{$p['image']}'>"; ?><input type="file" name="new_image"></td>
                <td>
                    <button class="btn" name="update_product">Update</button>
                    <a class="btn" href="?delete_product=<?= $p['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
                </td>
            </form>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="section" id="orders">
    <h3>All Orders & Payments</h3>
    <form method="post">
        <input type="date" name="filter_date" value="<?= $filter_date ?>">
        <button class="btn">Filter by Date</button>
    </form>
    <table>
        <tr>
            <th>Order ID</th>
            <th>User</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Product</th>
            <th>Image</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Payment Method</th>
            <th>Payment Info</th>
            <th>Date</th>
            <th>Invoice</th>
        </tr>
        <?php 
        mysqli_data_seek($orders, 0); 
        while ($o = $orders->fetch_assoc()): 
        ?>
        <tr>
            <td><?= $o['id'] ?></td>
            <td><?= $o['uname'] ?></td>
            <td><?= $o['phone'] ?></td>
            <td><?= $o['address'] ?></td>
            <td><?= $o['pname'] ?></td>
            <td><?php if ($o['image']) echo "<img src='{$o['image']}' height='40'>"; ?></td>
            <td><?= $o['quantity'] ?></td>
            <td>₹<?= $o['total'] ?></td>
            <td><?= $o['payment_method'] ?></td>
            <td><?= $o['payment_detail'] ?: '-' ?></td>
            <td><?= $o['ordered_at'] ?></td>
            <td>
                <a class="btn" target="_blank" href="invoice_view.php?order_id=<?= $o['id'] ?>">🧾 Payment bill </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="section" id="users">
    <h3>Add User</h3>
    <form method="post">
        <input name="name" placeholder="Name" required>
        <input name="email" placeholder="Email" required>
        <input name="password" placeholder="Password" required>
        <input name="phone" placeholder="Phone">
        <textarea name="address" placeholder="Address"></textarea>
        <button class="btn" name="add_user">Add User</button>
    </form>
    <h3>Manage Users</h3>
    <table>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Action</th></tr>
        <?php mysqli_data_seek($users, 0); while ($u = $users->fetch_assoc()): ?>
        <tr>
            <form method="post">
                <td><?= $u['id'] ?><input type="hidden" name="id" value="<?= $u['id'] ?>"></td>
                <td><input name="name" value="<?= htmlspecialchars($u['name']) ?>"></td>
                <td><input name="email" value="<?= htmlspecialchars($u['email']) ?>"></td>
                <td><input name="phone" value="<?= $u['phone'] ?>"></td>
                <td><input name="address" value="<?= htmlspecialchars($u['address']) ?>"></td>
                <td>
                    <button class="btn" name="update_user">Update</button>
                    <a class="btn" href="?delete_user=<?= $u['id'] ?>" onclick="return confirm('Delete user?')">Delete</a>
                </td>
            </form>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="section" id="admin_profile">
    <center> <h3>Admin Profile</h3> </center>
    <?php if (isset($msg)) echo "<p style='color:green; text-align:center;'>$msg</p>"; ?>
    <form method="post" style="max-width: 400px; margin: auto;">
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($admin['username'] ?? '') ?>" required>

        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($admin['name'] ?? '') ?>">

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>">

        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($admin['phone'] ?? '') ?>">

        <label>Address</label>
        <textarea name="address"><?= htmlspecialchars($admin['address'] ?? '') ?></textarea>

        <label>New Password (optional)</label>
        <input type="password" name="password" placeholder="Leave blank to keep current password">

        <button class="btn" name="update_admin">Update Profile</button>
    </form>
</div>

<h3>User Feedback & Complaints</h3>
<table>
    <tr><th>User</th><th>Type</th><th>Message</th><th>Reply</th><th>Date</th><th>Action</th></tr>
    <?php while($f = $all_feedbacks->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($f['uname']) ?></td>
        <td><?= htmlspecialchars($f['type']) ?></td>
        <td><?= nl2br(htmlspecialchars($f['message'])) ?></td>
        <td><?= $f['reply'] ? htmlspecialchars($f['reply']) : 'No reply yet' ?></td>
        <td><?= $f['created_at'] ?></td>
        <td>
            <form method="post">
                <input type="hidden" name="feedback_id" value="<?= $f['id'] ?>">
                <textarea name="reply" rows="3" style="width:200px;" required><?= htmlspecialchars($f['reply']) ?></textarea><br>
                <button class="btn" name="submit_reply">Reply</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>
