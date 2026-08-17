<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize login time if not set
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

// Fetch user data
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($user_query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // User not found -> logout for safety
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch team member data if exists
$team_member_query = "SELECT * FROM team_members WHERE email = ?";
$stmt = $pdo->prepare($team_member_query);
$stmt->execute([$user['email']]);
$team_member = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch phone from users table (phone is now in users table)
$phone = $user['phone'] ?? '';

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_post = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        try {
            $pdo->beginTransaction();

            // Update users table (including phone)
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_user_query = "UPDATE users SET username = ?, email = ?, phone = ?, password = ? WHERE id = ?";
                $stmt = $pdo->prepare($update_user_query);
                $stmt->execute([$name, $email, $phone_post, $hashed_password, $user_id]);
            } else {
                $update_user_query = "UPDATE users SET username = ?, email = ?, phone = ? WHERE id = ?";
                $stmt = $pdo->prepare($update_user_query);
                $stmt->execute([$name, $email, $phone_post, $user_id]);
            }

            // Update team_members table if record exists
            if ($team_member) {
                $update_team_member_query = "UPDATE team_members SET name = ?, email = ? WHERE id = ?";
                $stmt = $pdo->prepare($update_team_member_query);
                $stmt->execute([$name, $email, $team_member['id']]);
            }

            $pdo->commit();

            // Refresh data
            $stmt = $pdo->prepare($user_query);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare($team_member_query);
            $stmt->execute([$user['email']]);
            $team_member = $stmt->fetch(PDO::FETCH_ASSOC);

            $phone = $user['phone'] ?? '';

            $success_message = "Profile updated successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Calculate login duration
$login_duration = '';
if (isset($_SESSION['login_time'])) {
    $login_time = $_SESSION['login_time'];
    $current_time = time();
    $duration_seconds = $current_time - $login_time;
    
    $hours = floor($duration_seconds / 3600);
    $minutes = floor(($duration_seconds % 3600) / 60);
    $seconds = $duration_seconds % 60;
    
    $login_duration = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - CODETREE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f0f4f8;
    margin: 0;
    min-height: 100vh;
    color: #1f2b2b;
    display: flex;
  }

  /* Sidebar */
  .sidebar {
    width: 220px;
    background-color: #004d4d;
    color: #fff;
    padding: 30px 16px;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0;
    bottom: 0;
    transition: transform 0.3s ease;
    z-index: 1000;
  }
  .sidebar .logo {
    text-align: center;
    margin-bottom: 40px;
  }
  .sidebar .logo img {
    width: 120px;
  }
  .navlist {
    list-style: none;
    padding: 0;
    flex-grow: 1;
  }
  .navlist li { margin-bottom: 18px; }
  .navlist a {
    color: #dfeff0;
    text-decoration: none;
    display: block;
    padding: 10px 14px;
    border-radius: 6px;
    transition: 0.3s;
    font-size: 16px;
  }
  .navlist a:hover, .navlist a.active {
    background-color: #008080;
    color: #fff;
  }

  /* Content */
  .content {
    flex: 1;
    padding: 40px;
    margin-left: 220px;
    transition: margin-left 0.3s ease;
    width: 100%;
  }

  h1 {
    color: #004d4d;
    margin-bottom: 25px;
    text-align: center;
  }
.profile-header {
  display: flex;
  align-items: center;
  justify-content: center; /* centers heading + badge */
  gap: 15px;
  margin-bottom: 25px;
}

.profile-header h1 {
  margin: 0;
}

  .profile-info {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
  }

  .profile-card {
    background-color: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0,0,0,0.08);
    width: 320px;
    text-align: center;
    position: relative;
  }
  .profile-card h3 {
    color: #004d4d;
    margin-bottom: 15px;
  }
  .profile-card p {
    font-size: 15px;
    margin-bottom: 8px;
  }

  /* Profile Image */
  .profile-pic {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 15px;
    border: 3px solid #004d4d;
  }

  .form-control {
    margin-bottom: 12px;
    border-radius: 6px;
  }
  .btn-save {
    background-color: #004d4d;
    color: #fff;
    border: none;
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    font-weight: bold;
    transition: 0.3s;
  }
  .btn-save:hover {
    background-color: #008080;
  }

  /* Login Duration Badge */
  .login-duration-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(135deg, #004d4d, #008080);
    color: white;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    min-width: 85px;
    text-align: center;
  }

  .login-duration-badge .label {
    display: block;
    font-size: 10px;
    opacity: 0.9;
    margin-bottom: 2px;
  }

  .login-duration-badge .time {
    display: block;
    font-size: 14px;
    font-family: 'Courier New', monospace;
  }

  /* Toggle button */
  .menu-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1100;
    background: #004d4d;
    color: #fff;
    padding: 10px 14px;
    border-radius: 6px;
    cursor: pointer;
    border: none;
  }

  /* Success/Error messages */
  .alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
  }
  .alert-error {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
  }

  /* Responsive */
  @media (max-width: 992px) {
    .sidebar {
      transform: translateX(-100%);
    }
    .sidebar.active {
      transform: translateX(0);
    }
    .content {
      margin-left: 0;
      padding: 20px;
    }
    .menu-toggle { display: block; }
  }

  @media (max-width: 576px) {
    h1 { font-size: 20px; }
    .profile-card {
      width: 100%;
      padding: 20px;
    }
    .btn-save {
      font-size: 14px;
      padding: 8px;
    }
    .login-duration-badge {
      position: relative;
      top: 0;
      right: 0;
      margin-bottom: 15px;
      display: inline-block;
    }
  }
</style>
</head>
<body>

<!-- Sidebar -->
<button class="menu-toggle" id="menuToggle"><i class="bi bi-list"></i></button>
<nav class="sidebar" id="sidebar">
  <div class="logo">
    <img src="logo.png" alt="CODETREE Logo" onerror="this.style.display='none'">
  </div>
  <ul class="navlist">
    <li><a href="uprofile.php">Profile</a></li>
    <li><a href="umessages.php">Messages</a></li>
    <li><a href="uprojects.php">Projects</a></li>
    <li><a href="unotification.php">Notifications</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</nav>

<!-- Content -->
<div class="content">
  <div class="profile-header">
  <h1>Employee Profile</h1>
  <?php if ($login_duration): ?>
    <div class="login-duration-badge" id="loginDurationBadge">
      <span class="label">SESSION</span>
      <span class="time" id="loginTime"><?php echo $login_duration; ?></span>
    </div>
  <?php endif; ?>
</div>


  <?php if ($success_message): ?>
    <div class="alert-success"><?php echo htmlspecialchars($success_message); ?></div>
  <?php elseif ($error_message): ?>
    <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
  <?php endif; ?>

  <div class="profile-info">
    <!-- Personal Info -->
    <div class="profile-card">
      
      <img src="employee.jpg" alt="Employee Photo" class="profile-pic" onerror="this.src='https://via.placeholder.com/100?text=User'">
      <h3>Personal Info</h3>
      <p><strong>Name:</strong> <span id="displayName"><?php echo htmlspecialchars($user['username']); ?></span></p>
      <p><strong>Email:</strong> <span id="displayEmail"><?php echo htmlspecialchars($user['email']); ?></span></p>
      <p><strong>Phone:</strong> <span id="displayPhone"><?php echo htmlspecialchars($phone); ?></span></p>
      <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
      <p><strong>User ID:</strong> <?php echo htmlspecialchars($user['id']); ?></p>
      <p><strong>Member Since:</strong> <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
    </div>

    <!-- Work Details -->
    <div class="profile-card">
      <h3>Work Details</h3>
      <?php if ($team_member): ?>
        <p><strong>Profession:</strong> <?php echo htmlspecialchars($team_member['role']); ?></p>
        <p><strong>Skills:</strong> <?php echo htmlspecialchars($team_member['skills'] ?? 'Not specified'); ?></p>
        <p><strong>Availability:</strong> <?php echo htmlspecialchars($team_member['availability']); ?></p>
        <p><strong>Workload:</strong> <?php echo htmlspecialchars($team_member['workload']); ?>%</p>
        <p><strong>Team:</strong> <?php echo $team_member['team_id'] ? 'Team ' . htmlspecialchars($team_member['team_id']) : 'Not assigned'; ?></p>
      <?php else: ?>
        <p><strong>Status:</strong> User Account</p>
        <p><strong>Note:</strong> Contact admin to be added to a team</p>
      <?php endif; ?>
    </div>

    <!-- Edit Profile -->
    <div class="profile-card">
      <h3>Edit Profile</h3>
      <form id="editForm" method="POST" action="">
        <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        <input type="email" class="form-control" id="email" name="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter Phone" value="<?php echo htmlspecialchars($phone); ?>">
        <input type="password" class="form-control" id="password" name="password" placeholder="Enter New Password (leave blank to keep current)">
        <button type="submit" class="btn-save">Save Changes</button>
      </form>
    </div>
  </div>
</div>

<script>
  // Sidebar toggle
  const menuToggle = document.getElementById("menuToggle");
  const sidebar = document.getElementById("sidebar");
  menuToggle.addEventListener("click", () => {
    sidebar.classList.toggle("active");
  });

  // Update displayed values when form is submitted
  document.getElementById('editForm').addEventListener('submit', function() {
    const name = document.getElementById("name").value;
    const email = document.getElementById("email").value;
    const phone = document.getElementById("phone").value;
    
    document.getElementById("displayName").innerText = name;
    document.getElementById("displayEmail").innerText = email;
    document.getElementById("displayPhone").innerText = phone;
  });

  // Real-time login duration update
  <?php if (isset($_SESSION['login_time'])): ?>
  function updateLoginDuration() {
    const loginTimeElement = document.getElementById('loginTime');
    if (!loginTimeElement) return;
    
    // Calculate duration based on initial login time from session
    const loginTime = <?php echo $_SESSION['login_time']; ?> * 1000; // Convert to milliseconds
    const now = Date.now();
    const duration = Math.floor((now - loginTime) / 1000); // Duration in seconds
    
    const hours = Math.floor(duration / 3600);
    const minutes = Math.floor((duration % 3600) / 60);
    const seconds = duration % 60;
    
    const formattedTime = 
      String(hours).padStart(2, '0') + ':' + 
      String(minutes).padStart(2, '0') + ':' + 
      String(seconds).padStart(2, '0');
    
    loginTimeElement.textContent = formattedTime;
  }

  // Update immediately and then every second
  updateLoginDuration();
  setInterval(updateLoginDuration, 1000);
  <?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>