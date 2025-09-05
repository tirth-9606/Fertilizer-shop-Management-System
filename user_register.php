<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "fertilizer_shop");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $name  = trim(isset($_POST["name"]) ? $_POST["name"] : '');
    $email = trim(isset($_POST["email"]) ? $_POST["email"] : '');
    $pass  = trim(isset($_POST["password"]) ? $_POST["password"] : '');
    $phone = trim(isset($_POST["phone"]) ? $_POST["phone"] : '');
    $addr  = trim(isset($_POST["address"]) ? $_POST["address"] : '');

    // Basic validation
    if ($name && $email && $pass && $phone && $addr) {

        // Email format check
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = "❌ Invalid email format.";
        }
        // Password length check
        elseif (strlen($pass) < 6) {
            $msg = "❌ Password must be at least 6 characters.";
        }
        // Phone number check (digits only, 7–15 digits)
        elseif (!preg_match('/^[0-9]{7,15}$/', $phone)) {
            $msg = "❌ Enter a valid phone number.";
        } else {
            // Encrypt password (md5 for PHP 5.4 compatibility, but note: weak in security)
            $hash = md5($pass);

            // Check duplicate email
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $msg = "❌ Email already registered.";
            } else {
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $hash, $phone, $addr);

                if ($stmt->execute()) {
                    header("Location: user_login.php");
                    exit();
                } else {
                    $msg = "❌ Something went wrong. Try again.";
                }
                $stmt->close();
            }
            $check->close();
        }
    } else {
        $msg = "❌ All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #a8e063, #56ab2f);
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
            margin: 8px 0;
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
    <h2>User Registration</h2>
    <?php if ($msg): ?>
        <div class="msg <?= (strpos($msg, '✅') !== false) ? 'success' : '' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
    <form method="post">
        <input name="name" placeholder="Full Name" required>
        <input name="email" type="email" placeholder="Email" required>
        <input name="password" type="password" placeholder="Password (min 6 chars)" required>
        <input name="phone" type="text" placeholder="Phone Number" required>
        <input name="address" placeholder="Address" required>
        <button type="submit">Register</button>
    </form>
    <a href="user_login.php">Already have an account? Login</a>
</div>

</body>
</html>
