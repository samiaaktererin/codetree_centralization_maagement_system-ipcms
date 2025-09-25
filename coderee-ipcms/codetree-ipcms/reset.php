<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate reset token (in a real application, you would send an email)
        $token = bin2hex(random_bytes(32));
        $message = "Password reset link has been sent to your email.";
    } else {
        $error = "Email not found in our system.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - CODETREE</title>
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
        .reset-box {
            max-width: 400px;
            margin: 0 auto;
            background-color: #004d4d;
            color: white;
            padding: 30px;
            border-radius: 10px;
        }
        .btn-reset {
            background-color: #eceff1;
            border: none;
            color: #004d4d;
            font-weight: bold;
        }
        .btn-reset:hover {
            background-color: black;
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

    <div class="reset-box">
        <h4 class="text-center mb-4">Reset Your Password</h4>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Enter Your Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-reset w-100">Send Reset Link</button>
        </form>

        <div class="link-group">
            <a href="login.php">Back to Login</a>
        </div>
    </div>

</body>
</html>