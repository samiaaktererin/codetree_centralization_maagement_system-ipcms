<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                // Set login time when user successfully logs in
                $_SESSION['login_time'] = time();
                
                if ($user['role'] == 'Admin') {
                    header("Location: adashboard.php");
                } else {
                    header("Location: uprofile.php");
                }
                exit();
            } else {
                $error = "Invalid password. Please try again.";
            }
        } else {
            $error = "No user found with this email and role combination.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CODETREE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
            color: #ffffff;
        }
        .logo {
            text-align: center;
            margin: 40px 0 20px;
        }
        .logo img {
            width: 140px;
        }
        .login-box {
            max-width: 420px;
            margin: auto;
            background-color: #004d4d;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h4 {
            text-align: center;
            margin-bottom: 25px;
            color: #ffffff;
        }
        .form-label {
            font-weight: 500;
            color: #ffffff;
        }
        .btn-login {
            background-color: #ffffff;
            color: #004d4d;
            font-weight: bold;
            width: 100%;
            border: none;
        }
        .btn-login:hover {
            background-color: black;
            color: #ffffff;
        }
        .link-group {
            text-align: center;
            margin-top: 15px;
        }
        .link-group a {
            color: #fff;
            font-size: 0.9rem;
            text-decoration: none;
        }
        .link-group a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="logo">
    <img src="logo.png" alt="CODETREE Logo">
</div>

<div class="login-box">
    <h4>Login to CODETREE</h4>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <!-- Role Selection -->
        <div class="mb-3 text-center">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" id="roleUser" name="role" value="User" required checked>
                <label class="form-check-label text-white" for="roleUser">User</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" id="roleAdmin" name="role" value="Admin" required>
                <label class="form-check-label text-white" for="roleAdmin">Admin</label>
            </div>
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-login">Login</button>

        <!-- Quick Links -->
        <div class="link-group mt-3">
            <a href="register.php">Create Account</a> |
            <a href="reset.php">Forgot Password?</a>
        </div>
    </form>
</div>

<script>
// Auto-fill test accounts for easier testing
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const testAccount = urlParams.get('test');
    
    if (testAccount === 'admin') {
        document.getElementById('roleAdmin').checked = true;
        document.getElementById('email').value = 'admin@codetree.com';
        document.getElementById('password').value = 'password';
    } else if (testAccount === 'user') {
        document.getElementById('roleUser').checked = true;
        document.getElementById('email').value = 'alice@company.com';
        document.getElementById('password').value = 'password';
    }
});
</script>

</body>
</html>