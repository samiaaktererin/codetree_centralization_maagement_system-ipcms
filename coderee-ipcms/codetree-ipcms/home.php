<?php
// No PHP changes needed for home.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CODETREE - Home</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  /* Body and Background */
  body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(to right, #004d4d, #008080);
    color: #fff;
    margin: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  /* Navbar */
  .navbar {
    background: rgba(0,0,0,0.1);
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .navbar .logo img {
    width: 140px;
  }
  .navbar a {
    color: #fff;
    margin-left: 20px;
    text-decoration: none;
    font-weight: 500;
    transition: 0.3s;
  }
  .navbar a:hover {
    color: #ffeb3b;
  }

  /* Hero Section */
  .hero {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 50px 20px;
  }
  .hero h1 {
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 20px;
    text-shadow: 2px 2px 5px rgba(0,0,0,0.3);
  }
  .hero p {
    font-size: 1.2rem;
    margin-bottom: 30px;
    color: #e0f7f7;
  }
  .hero .btn-group a {
    margin: 0 10px;
    padding: 12px 30px;
    font-size: 1.1rem;
    font-weight: bold;
    border-radius: 50px;
    transition: 0.3s;
  }
  .hero .btn-login {
    background: #ffffff;
    color: #004d4d;
  }
  .hero .btn-login:hover {
    background: #ffeb3b;
    color: #004d4d;
  }
  .hero .btn-register {
    background: transparent;
    border: 2px solid #fff;
    color: #fff;
  }
  .hero .btn-register:hover {
    background: #ffeb3b;
    color: #004d4d;
    border-color: #ffeb3b;
  }

  /* Footer */
  footer {
    text-align: center;
    padding: 20px;
    background: rgba(0,0,0,0.1);
    color: #fff;
    font-size: 0.9rem;
  }

  @media (max-width: 768px) {
    .hero h1 {
      font-size: 2.2rem;
    }
    .hero p {
      font-size: 1rem;
    }
    .hero .btn-group a {
      display: block;
      margin: 10px auto;
    }
  }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="logo">
    <img src="logo.png" alt="CODETREE Logo">
  </div>
  <div class="nav-links">
    <a href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
    <a href="register.php"><i class="bi bi-person-plus"></i> Register</a>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero">
  <div>
    <h1>Welcome to CODETREE</h1>
    <p>Innovative solutions for modern businesses. Manage your team, projects, and clients seamlessly.</p>
    <div class="btn-group">
      <a href="login.php" class="btn btn-login">Login</a>
      <a href="register.php" class="btn btn-register">Register</a>
    </div>
  </div>
</section>

<!-- Footer -->
<footer>
  &copy; 2025 CODETREE. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
