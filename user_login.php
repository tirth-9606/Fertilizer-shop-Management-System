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
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : '';
    $pass  = isset($_POST["password"]) ? $_POST["password"] : '';

    if ($email && $pass) {
        // Email format check
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = "❌ Invalid email format.";
        } else {
            // Prepare query
            $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($id, $hash);

            if ($stmt->num_rows > 0 && $stmt->fetch()) {
                // Compare with md5 hash (from registration)
                if (md5($pass) === $hash) {
                    $_SESSION["user_id"] = $id;
                    header("Location: user_dashboard.php");
                    exit();
                } else {
                    $msg = "❌ Incorrect password.";
                }
            } else {
                $msg = "❌ No account found with this email.";
            }
            $stmt->close();
        }
    } else {
        $msg = "❌ Both fields are required.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #56ab2f, #a8e063);
            margin: 0;
            padding: 0;
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
            color: #2e8b57;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            background: #2e8b57;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #246b44;
        }
        .msg {
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
            color: #d8000c;
        }
        .msg.success {
            color: green;
        }
        a {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #2e8b57;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>User Login</h2>
    <?php if ($msg): ?>
        <div class="msg <?= (strpos($msg, '✅') !== false) ? 'success' : '' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
    <form method="post">
        <input name="email" type="email" placeholder="Email" required>
        <input name="password" type="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <a href="user_register.php">Don't have an account? Register</a>
</div>

</body>
</html>
