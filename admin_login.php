<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "fertilizer_shop");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST["username"]) ? trim($_POST["username"]) : '';
    $pass     = isset($_POST["password"]) ? $_POST["password"] : '';

    if ($username && $pass) {
        // Prepare query
        $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $db_pass);

        if ($stmt->num_rows > 0 && $stmt->fetch()) {
            // ✅ If you store passwords as plain text in DB
            if ($pass === $db_pass) {
                $_SESSION["admin_id"] = $id;
                header("Location: admin_dashboard.php");
                exit();
            }
            // ✅ If you store passwords as md5 hash (better for PHP 5.4)
            elseif (md5($pass) === $db_pass) {
                $_SESSION["admin_id"] = $id;
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $msg = "❌ Incorrect password.";
            }
        } else {
            $msg = "❌ Invalid username.";
        }
        $stmt->close();
    } else {
        $msg = "❌ Both fields are required.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #ff9966, #ff5e62);
            margin: 0; padding: 0;
        }
        .container {
            width: 360px;
            margin: 60px auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }
        h2 {
            text-align: center;
            color: #d35400;
        }
        input {
            width: 100%; padding: 10px;
            margin: 10px 0; border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%; background: #d35400;
            color: white; border: none;
            padding: 10px; border-radius: 5px;
            cursor: pointer; font-weight: bold;
        }
        button:hover { background: #a84300; }
        .msg {
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
            color: #c0392b;
        }
        .msg.success { color: green; }
    </style>
</head>
<body>

<div class="container">
    <h2>Admin Login</h2>
    <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="post">
        <input name="username" placeholder="Admin Username" required>
        <input name="password" type="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>
