<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "127.0.0.1";
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "codetree";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch current user data from database
$user_id = $_SESSION['user_id'];
$sql = "SELECT username, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found, destroy session and redirect
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();

// Update session with current user data
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

// Set login time if not already set
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

// Fetch projects where the current user is a team member
$current_user = $_SESSION['username'];
$sql = "SELECT * FROM projects WHERE members LIKE '%" . $conn->real_escape_string($current_user) . "%'";
$result = $conn->query($sql);

// Store projects in an array
$projects = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Close connection
$conn->close();

// Calculate session duration based on actual login time
$login_time = $_SESSION['login_time'];
$current_time = time();
$session_duration = $current_time - $login_time;

$hours = floor($session_duration / 3600);
$minutes = floor(($session_duration % 3600) / 60);
$seconds = $session_duration % 60;

$session_duration_formatted = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Projects - CODETREE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f0f4f8;
    margin: 0;
    display: flex;
    min-height: 100vh;
    color: #1f2b2b;
  }

  /* Sidebar */
  .sidebar {
    width: 220px;
    background-color: #004d4d;
    color: #fff;
    padding: 30px 16px;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s ease-in-out;
  }
  .sidebar .logo {
    text-align: center;
    margin-bottom: 40px;
  }
  .sidebar .logo img {
    width: 140px;
  }
  .navlist {
    list-style: none;
    padding: 0;
    flex-grow: 1;
  }
  .navlist li {
    margin-bottom: 18px;
  }
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

  /* Toggle button for mobile */
  .menu-toggle {
    display: none;
    font-size: 1.8rem;
    color: #004d4d;
    background: none;
    border: none;
    margin: 15px;
    cursor: pointer;
    z-index: 1000;
  }

  /* Content */
  .content {
    flex: 1;
    padding: 40px;
  }

  h1 {
    color: #004d4d;
    margin-bottom: 25px;
    text-align: center;
  }

  /* Session Info */
  .session-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #e8f4f4;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  }
  
  .session-details {
    display: flex;
    gap: 30px;
    align-items: center;
  }
  
  .session-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
  }
  
  .session-item i {
    color: #004d4d;
  }
  
  .session-badge {
    background-color: #004d4d;
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
  }

  /* Card */
  .card {
    background-color: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0,0,0,0.08);
    max-width: 1000px;
    margin: auto;
  }

  .card h3 {
    color: #004d4d;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  /* Table */
  table {
    width: 100%;
    border-collapse: collapse;
  }
  thead th {
    background-color: #f0f3f7;
    color: #004d4d;
    padding: 10px;
    text-align: left;
    font-weight: 600;
    white-space: nowrap;
  }
  tbody td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
  }
  tbody tr:hover {
    background-color: #f9f9f9;
  }

  /* Status badges */
  .badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
  }
  
  .bg-not-started {
    background-color: #6c757d;
    color: white;
  }
  
  .bg-in-progress {
    background-color: #ffc107;
    color: black;
  }
  
  .bg-completed {
    background-color: #198754;
    color: white;
  }

  /* Responsive styles */
  @media (max-width: 992px) {
    body {
      flex-direction: column;
    }
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      transform: translateX(-100%);
      z-index: 999;
    }
    .sidebar.active {
      transform: translateX(0);
    }
    .menu-toggle {
      display: block;
    }
    .content {
      padding: 20px;
    }
    
    .session-info {
      flex-direction: column;
      gap: 15px;
      text-align: center;
    }
    
    .session-details {
      flex-direction: column;
      gap: 10px;
    }
  }
  
  @media (max-width: 768px) {
    .session-details {
      flex-direction: column;
      gap: 10px;
    }
  }
</style>
</head>
<body>

<!-- Toggle button -->
<button class="menu-toggle" id="menuToggle"><i class="bi bi-list"></i></button>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
  <div class="logo">
    <img src="logo.png" alt="CODETREE Logo">
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
  <h1>Projects</h1>
  
  <!-- Session Info -->
  <div class="session-info">
    <div class="session-details">
      <div class="session-item">
        <i class="bi bi-person-circle"></i>
        <span>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
      </div>
      <div class="session-item">
        <i class="bi bi-clock"></i>
        <span>Session Duration: <strong id="session-duration"><?php echo $session_duration_formatted; ?></strong></span>
      </div>
    </div>
    <div class="session-badge"><?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?></div>
  </div>
  
  <div class="card">
    <h3><i class="bi bi-folder"></i> My Projects</h3>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Description</th>
            <th>Team Lead</th>
            <th>Status</th>
            <th>Progress</th>
            <th>Assigned Date</th>
            <th>Due Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($projects) > 0): ?>
            <?php foreach ($projects as $project): ?>
              <tr>
                <td><?php echo htmlspecialchars($project['id']); ?></td>
                <td><?php echo htmlspecialchars($project['title']); ?></td>
                <td><?php echo htmlspecialchars($project['description']); ?></td>
                <td><?php echo htmlspecialchars($project['team_lead']); ?></td>
                <td>
                  <?php 
                  $status_class = '';
                  switch($project['status']) {
                    case 'Not Started':
                      $status_class = 'bg-not-started';
                      break;
                    case 'In Progress':
                      $status_class = 'bg-in-progress';
                      break;
                    case 'Completed':
                      $status_class = 'bg-completed';
                      break;
                  }
                  ?>
                  <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($project['status']); ?></span>
                </td>
                <td>
                  <div class="progress" style="height: 20px;">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $project['progress']; ?>%;" 
                         aria-valuenow="<?php echo $project['progress']; ?>" aria-valuemin="0" aria-valuemax="100">
                      <?php echo $project['progress']; ?>%
                    </div>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($project['assigned_date']); ?></td>
                <td><?php echo htmlspecialchars($project['due_date']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center">No projects found where you are a team member</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  const menuToggle = document.getElementById("menuToggle");
  const sidebar = document.getElementById("sidebar");

  menuToggle.addEventListener("click", () => {
    sidebar.classList.toggle("active");
  });

  // Update session duration every second
  function updateSessionDuration() {
    const sessionDurationElement = document.getElementById('session-duration');
    let time = sessionDurationElement.textContent.split(':');
    let hours = parseInt(time[0]);
    let minutes = parseInt(time[1]);
    let seconds = parseInt(time[2]);
    
    seconds++;
    
    if (seconds >= 60) {
      seconds = 0;
      minutes++;
    }
    
    if (minutes >= 60) {
      minutes = 0;
      hours++;
    }
    
    sessionDurationElement.textContent = 
      hours.toString().padStart(2, '0') + ':' + 
      minutes.toString().padStart(2, '0') + ':' + 
      seconds.toString().padStart(2, '0');
  }
  
  // Update the session duration every second
  setInterval(updateSessionDuration, 1000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>