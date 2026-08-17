<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Quick Message Submission
if (isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    if ($message) {
        $stmt = $pdo->prepare("INSERT INTO quick_messages (sender_id, receiver_type, message) VALUES (?, 'All', ?)");
        $stmt->execute([$user_id, $message]);
        header("Location: adashboard.php");
        exit();
    }
}

// Handle Notification Submission - FIXED: Set user_id to NULL for global notifications
if (isset($_POST['send_notification'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['notification']);
    if ($title && $message) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (NULL, ?, ?)");
        $stmt->execute([$title, $message]);
        header("Location: adashboard.php");
        exit();
    }
}

// Fetch projects data
$projects = $pdo->query("SELECT * FROM projects")->fetchAll();

// Fetch financial data
$finance = $pdo->query("
    SELECT
    SUM(budget) as total_budget,
    SUM(progress * budget / 100) as actual_spend
    FROM projects
")->fetch();

// Fetch invoices data
$invoices = $pdo->query("
    SELECT
    SUM(amount) as total_invoices,
    SUM(paid_amount) as total_paid,
    SUM(CASE WHEN status = 'Partial' THEN amount - paid_amount ELSE 0 END) as partial_amount,
    SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as pending_amount
    FROM invoices
")->fetch();

// Fetch quick messages
$quick_messages = $pdo->query("SELECT qm.*, u.username FROM quick_messages qm JOIN users u ON qm.sender_id = u.id ORDER BY qm.sent_at ASC")->fetchAll();

// Fetch notifications - FIXED: Get all notifications (both global and user-specific)
$notifications = $pdo->query("SELECT * FROM notifications WHERE user_id IS NULL OR user_id = $user_id ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - CODETREE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* --- your CSS remains unchanged --- */
* {margin:0; padding:0; box-sizing:border-box;}
body { display: flex; font-family: 'Segoe UI', Arial, sans-serif; background: #e9eef0; color:#1f2b2b; min-height: 100vh; }
.sidebar { width:220px; background:#0f6b67; color:#fff; padding:30px 16px; position:fixed; top:0; left:0; min-height:100vh; display:flex; flex-direction:column; }
.logo { text-align:center; margin-bottom:40px; }
.logo img { width:120px; display:block; margin:0 auto; }
.navlist { list-style:none; padding:0; flex-grow:1; }
.navlist li { margin-bottom:18px; }
.navlist a { color:#dfeff0; text-decoration:none; display:block; padding:10px 14px; border-radius:6px; transition:0.3s; font-size:16px; }
.navlist a:hover { background: rgba(255,255,255,0.1); }
.content { margin-left:220px; padding:25px; width:calc(100% - 220px); }
.content h1 { font-size:26px; font-weight:bold; margin-bottom:25px; }
.grid { display:flex; flex-wrap:wrap; gap:20px; justify-content:flex-start; }
.column { display:flex; flex-direction:column; gap:20px; width:400px; }
.card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 4px 15px rgba(0,0,0,0.08); transition:transform 0.2s, box-shadow 0.2s; }
.card:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.12); }
.card h3 { font-size:18px; font-weight:bold; margin-bottom:15px; display:flex; align-items:center; gap:8px; color:#0f6b67; }
.project { background:#f6f9fc; border-radius:8px; padding:10px 12px; margin-bottom:10px; border-left:5px solid #3a88ef; }
.project.completed { border-left-color:#27ae60; background:#e9f7ef; }
.project.in-progress { border-left-color:#f39c12; background:#fff4e6; }
.project-title { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; font-size:14px; font-weight:600; }
.progress-bar { height:8px; border-radius:6px; background:#e1e6eb; overflow:hidden; }
.progress-fill { height:100%; border-radius:6px; transition: width 1s ease; }
.blue {background:#3a88ef;}
.green {background:#27ae60;}
.yellow {background:#f39c12;}
.finance p { font-size:14px; margin:6px 0; }
.finance .highlight { color:#27ae60; font-weight:bold; font-size:15px; }
.notifications { background:#f8fafc; border-radius:12px; padding:18px; height:380px; display:flex; flex-direction:column; }
.notifications ul { list-style:none; padding-left:0; max-height:280px; overflow-y:auto; flex-grow:1; }
.notifications ul li { margin:10px 0; font-size:14px; display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:10px; background:#eef5f8; transition:0.3s; }
.notifications ul li:hover { background:#d7ecf3; }
.dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
.dot-blue{background:#3a88ef;} .dot-green{background:#27ae60;} .dot-purple{background:#8e44ad;}
.chart-labels { display:flex; justify-content:space-around; margin-top:12px; font-size:13px; }
.chart-labels span { display:flex; align-items:center; gap:5px; }
.legend-box { width:12px; height:12px; display:inline-block; border-radius:3px; }
.chart-container { display:flex; justify-content:center; align-items:center; flex-direction: column; height:380px; }
.quick-message { display:flex; flex-direction:column; gap:10px; max-height:380px; position:sticky; top:20px; }
.messages { background:#f5f8fa; border-radius:10px; padding:12px; flex-grow:1; overflow-y:auto; max-height:250px; }
.message { margin-bottom:8px; font-size:14px; padding:6px 8px; border-radius:8px; }
.message.user { background:#d7ecf3; align-self:flex-end; }
.message.team { background:#eef5f8; align-self:flex-start; }
.message-input { display:flex; gap:8px; margin-top:8px; }
.message-input input { flex-grow:1; padding:8px 10px; border-radius:8px; border:1px solid #ccc; }
.message-input button { padding:8px 14px; background:#0f6b67; color:#fff; border:none; border-radius:8px; cursor:pointer; }
.message-input button:hover { background:#0d5955; }
.notification-form { display:flex; gap:8px; margin-top:8px; }
.notification-form input { flex-grow:1; padding:6px 8px; border-radius:6px; border:1px solid #ccc; }
.notification-form button { padding:6px 10px; border:none; border-radius:6px; background:#3a88ef; color:#fff; cursor:pointer; }
.notification-form button:hover { background:#2e6ab3; }
</style>
</head>
<body>
<!-- Sidebar -->
<nav class="sidebar">
<div class="logo">
<img src="logo.png" alt="CODETREE">
</div>
<ul class="navlist">
<li><a href="adashboard.php">Dashboard</a></li>
<li><a href="projects.php">Projects</a></li>
<li><a href="comunication.php">Communication</a></li>
<li><a href="teams.php">Teams</a></li>
<li><a href="clients.php">Clients</a></li>
<li><a href="billing.php">Billing</a></li>
<li><a href="settins.php">Settings</a></li>
<li><a href="logout.php" >Logout</a></li>
</ul>
</nav>

<div class="content">
<h1>Dashboard</h1>
<div class="grid">
<!-- Left Column -->
<div class="column">
<!-- Projects Section -->
<div class="card">
<h3><i class="bi bi-folder"></i> Projects</h3>
<?php foreach ($projects as $project): ?>
<div class="project <?php echo $project['status'] == 'Completed' ? 'completed' : 'in-progress'; ?>">
<div class="project-title">
<span><?php echo $project['title']; ?></span>
<span><?php echo $project['status']; ?></span>
</div>
<div class="progress-bar">
<div class="progress-fill <?php echo $project['progress'] >= 70 ? 'green' : ($project['progress'] >= 40 ? 'yellow' : 'blue'); ?>"
style="width:<?php echo $project['progress']; ?>%"></div>
</div>
</div>
<?php endforeach; ?>
</div>

<!-- Financial Summary -->
<div class="card finance">
<h3><i class="bi bi-currency-dollar"></i> Financial Summary</h3>
<p>Total Budget: <b>$<?php echo number_format($finance['total_budget'], 2); ?></b></p>
<p>Actual Spend: <b>$<?php echo number_format($finance['actual_spend'], 2); ?></b></p>
<p class="highlight">Remaining: $<?php echo number_format($finance['total_budget'] - $finance['actual_spend'], 2); ?></p>
</div>

<!-- Project Budget -->
<div class="card">
<h3><i class="bi bi-wallet2"></i> Project Budget</h3>
<?php foreach ($projects as $project): ?>
<p><?php echo $project['title']; ?>: <b>$<?php echo number_format($project['budget'], 2); ?></b></p>
<?php endforeach; ?>
<p class="highlight">Total Allocated: $<?php echo number_format($finance['total_budget'], 2); ?></p>
</div>
</div>

<!-- Right Column -->
<div style="flex:1; min-width:450px; display:flex; gap:20px;">
<!-- Overall Progress Chart -->
<div class="card chart-container" style="flex:2;">
<h3><i class="bi bi-bar-chart"></i> Progress</h3>
<canvas id="progressChart" width="200" height="200"></canvas>
<div class="chart-labels">
<?php
$colors = ["#3a88ef", "#f1c40f", "#27ae60", "#bbb"];
$i = 0;
foreach ($projects as $project): ?>
<span><span class="legend-box" style="background:<?php echo $colors[$i % count($colors)]; ?>"></span>
<?php echo $project['title']; ?> <?php echo $project['progress']; ?>%</span>
<?php $i++; endforeach; ?>
</div>
</div>

<!-- Notifications & Quick Messaging -->
<div style="flex:1; display:flex; flex-direction:column; gap:20px;">
<!-- Notifications -->
<div class="card notifications">
<h3><i class="bi bi-bell"></i> Notifications & Reminders</h3>
<ul>
<?php foreach ($notifications as $note): ?>
<li><span class="dot dot-blue"></span><strong><?php echo htmlspecialchars($note['title']); ?>:</strong> <?php echo htmlspecialchars($note['message']); ?></li>
<?php endforeach; ?>
</ul>
<form method="post" class="notification-form">
<input type="text" name="title" placeholder="Notification Title" required>
<input type="text" name="notification" placeholder="Notification Message" required>
<button type="submit" name="send_notification">Add</button>
</form>
</div>

<!-- Quick Messaging -->
<div class="card quick-message">
<h3><i class="bi bi-chat-dots"></i> Quick Messaging</h3>
<div class="messages">
<?php foreach ($quick_messages as $qm): ?>
<div class="message <?php echo $qm['sender_id']==$user_id ? 'user' : 'team'; ?>">
<?php echo htmlspecialchars($qm['username']); ?>: <?php echo htmlspecialchars($qm['message']); ?>
</div>
<?php endforeach; ?>
</div>
<form method="post" class="message-input">
<input type="text" name="message" placeholder="Type a message..." required>
<button type="submit" name="send_message">Send</button>
</form>
</div>
</div>
</div>
</div>
</div>

<script>
// Donut Chart
const canvas = document.getElementById('progressChart');
const ctx = canvas.getContext('2d');
const data = [<?php foreach ($projects as $project) { echo $project['progress'] . ','; } ?>];
const colors = ["#3a88ef","#f1c40f","#27ae60","#bbb"];
const total = data.reduce((a,b)=>a+b,0);
let startAngle = -0.5*Math.PI;
data.forEach((value,i)=>{
const slice = (value/total)*2*Math.PI;
ctx.beginPath();
ctx.moveTo(100,100);
ctx.arc(100,100,80,startAngle,startAngle+slice);
ctx.closePath();
ctx.fillStyle = colors[i];
ctx.fill();
startAngle += slice;
});
ctx.beginPath();
ctx.arc(100,100,50,0,2*Math.PI);
ctx.fillStyle="#fff";
ctx.fill();
ctx.fillStyle="#1f2b2b";
ctx.font="bold 16px Arial";
ctx.textAlign="center";
ctx.textBaseline="middle";
ctx.fillText("Overall Progress",100,100);
</script>
</body>
</html>