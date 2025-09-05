<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Fertilizer Shop</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0; font-family: 'Roboto', sans-serif; background: #f9fff9; color: #333;
    }
    header, footer {
      background: #2e8b57; color: white; text-align: center; padding: 15px 10px;
    }
    nav a {
      margin: 0 10px; color: #fff; font-weight: bold; text-decoration: none;
    }
    nav a:hover { color: #ffd700; }
    .hero {
      background: url('images/fertilizer.jpg') center/cover no-repeat;
      height: 70vh; display: flex; align-items: center; justify-content: center;
      text-align: center; color: white; position: relative;
    }
    .hero::after {
      content: ''; position: absolute; inset: 0; background: rgba(34, 139, 34, 0.6);
    }
    .hero-content {
      position: relative; z-index: 1; max-width: 700px; padding: 20px;
    }
    .hero h2 { font-size: 36px; margin-bottom: 10px; }
    .hero p { margin-bottom: 20px; }
    .btn {
      background: #ffd700; color: #333; padding: 10px 20px;
      border: none; border-radius: 5px; text-decoration: none;
    }
    .section {
      padding: 40px 20px; text-align: center;
    }
    .card-container {
      display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; margin-top: 20px;
    }
    .card {
      background: #e6ffe6; padding: 20px; border-radius: 8px;
      width: 250px; box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    form {
      display: flex; flex-direction: column; gap: 10px; max-width: 400px; margin: 20px auto;
    }
    input, textarea {
      padding: 8px; border: 1px solid #ccc; border-radius: 4px;
    }
    button {
      background: #2e8b57; color: white; padding: 10px; border: none; border-radius: 5px;
      cursor: pointer;
    }
    button:hover { background: #246b44; }
  </style>
</head>
<body>

<header>
  <h1>🌱 Fertilizer Shop</h1>
  <nav>
    <a href="#about">About</a>
    <a href="#services">Services</a>
    <a href="#contact">Contact</a>
    <a href="user_register.php">Register</a>
    <a href="user_login.php">User Login</a>
    <a href="admin_login.php">Admin Login</a>
  </nav>
</header>

<section class="hero">
  <div class="hero-content">
    <h2>Your Trusted Fertilizer Partner</h2>
    <p>Quality fertilizers and simple shop management system.</p>
    <a href="user_register.php" class="btn">Get Started</a>
  </div>
</section>

<section id="about" class="section">
  <h2>About Us</h2>
  <p>This is a college project to manage fertilizer shop operations including inventory, user orders, and admin control.</p>
</section>

<section id="services" class="section">
  <h2>Our Services</h2>
  <div class="card-container">
    <div class="card">
      <h3>Product Catalog</h3>
      <p>View and manage fertilizers with pricing and stock.</p>
    </div>
    <div class="card">
      <h3>User Accounts</h3>
      <p>Secure registration, login, and order tracking.</p>
    </div>
    <div class="card">
      <h3>Admin Panel</h3>
      <p>Admins manage products, users, and orders.</p>
    </div>
  </div>
</section>

<section id="contact" class="section">
  <h2>Contact Us</h2>
  <p><strong>Address:</strong> Green Farm Road, AgriTown</p>
  <p><strong>Email:</strong> support@fertilizershop.com</p>
</section> <!-- ✅ Added missing closing tag -->

<footer>
  <p>&copy; 2025 Fertilizer Shop Management System | College Project</p>
</footer>

</body>
</html>
