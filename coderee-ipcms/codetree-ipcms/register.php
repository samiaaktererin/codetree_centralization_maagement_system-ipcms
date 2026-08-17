<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role]);
        
        header("Location: login.php?registration=success");
        exit();
    } catch (PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - CODETREE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
        <style>
        body {
            background-color: #e9f5f2;
            font-family: 'Segoe UI', sans-serif;
        }
        .logo {
            text-align: center;
            margin-top: 40px;
            margin-bottom: 20px;
        }
        .logo img {
            width: 140px;
        }
        .register-box {
            max-width: 400px;
            margin: 0 auto;
            background-color: #004d4d;
            color: white;
            padding: 30px;
            border-radius: 10px;
        }
        .btn-register {
            background-color: #eceff1;
            border: none;
            color: #004d4d;
            font-weight: bold;
        }
        .btn-register:hover {
            background-color: #030303ff;
        }
        .form-label {
            margin-bottom: 5px;
        }
        .link-group {
            text-align: center;
            margin-top: 15px;
        }
        .link-group a {
            color: #ccc;
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

    <div class="register-box">
        <h4 class="text-center mb-4">Create Your Account</h4>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="role" value="User" checked>
                    <label class="form-check-label">User</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="role" value="Admin">
                    <label class="form-check-label">Admin</label>
                </div>
            </div>
            <button type="submit" class="btn btn-register w-100">Register</button>
        </form>

        <div class="link-group">
            <a href="login.php">Already have an account? Login</a>
        </div>
    </div>

</body>
</html>