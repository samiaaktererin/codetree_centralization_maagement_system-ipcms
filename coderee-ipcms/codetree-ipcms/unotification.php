<?php
require_once 'config.php'; // Make sure this defines $pdo as a PDO connection

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize or update session duration tracking
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}
$_SESSION['last_activity'] = time();

// Function to calculate time ago
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $time_difference = time() - $time;
    if ($time_difference < 1) { return 'just now'; }
    $condition = array(
        12 * 30 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60 => 'month',
        24 * 60 * 60 => 'day',
        60 * 60 => 'hour',
        60 => 'minute',
        1 => 'second'
    );
    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
        }
    }
}

// Function to format session duration
function getSessionDuration($login_time) {
    $duration = time() - $login_time;
    if ($duration < 60) {
        return $duration . ' second' . ($duration > 1 ? 's' : '');
    } elseif ($duration < 3600) {
        $minutes = floor($duration / 60);
        $seconds = $duration % 60;
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ' . $seconds . ' second' . ($seconds > 1 ? 's' : '');
    } else {
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }
}

// Fetch notifications (exclude reminders)
$notifications_stmt = $pdo->prepare("
    SELECT *
    FROM notifications
    WHERE (user_id IS NULL OR user_id = :uid) AND title NOT LIKE '%Reminder%'
    ORDER BY created_at DESC
");
$notifications_stmt->execute(['uid' => $user_id]);
$notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch reminders only
$reminders_stmt = $pdo->prepare("
    SELECT *
    FROM notifications
    WHERE (user_id IS NULL OR user_id = :uid) AND title LIKE '%Reminder%'
    ORDER BY created_at DESC
");
$reminders_stmt->execute(['uid' => $user_id]);
$reminders = $reminders_stmt->fetchAll(PDO::FETCH_ASSOC);

$login_time = $_SESSION['login_time'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - CODETREE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary-color: #004d4d;
    --secondary-color: #008080;
    --accent-color: #00a3a3;
    --text-dark: #1f2b2b;
    --text-light: #6c757d;
    --border-color: #e3e6f0;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --deep-green: #117a2a;
}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f4f8;
    margin: 0;
    display: flex;
    min-height: 100vh;
    color: var(--text-dark);
}
.sidebar {
    width: 250px;
    background: linear-gradient(180deg, var(--primary-color) 0%, #004d4d 100%);
    color: #fff;
    padding: 30px 16px;
    display: flex;
    flex-direction: column;
    transition: left 0.3s ease-in-out;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar .logo {
    text-align: center;
    margin-bottom: 40px;
}
.sidebar .logo img {
    width: 120px;
  }
.navlist { list-style: none; padding: 0; }
.navlist li { margin-bottom: 8px; }
.navlist a { color: #dfeff0; text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 10px; font-size: 16px; font-weight: 500; }
.navlist a:hover, .navlist a.active { background: rgba(255,255,255,0.15); color: #fff; }
.menu-toggle { display: none; position: fixed; top: 20px; left: 20px; z-index: 1100; font-size: 1.8rem; color: var(--primary-color); background: #fff; border-radius: 10px; padding: 10px 14px; cursor: pointer; }
.content { flex: 1; padding: 40px; max-width: calc(100% - 280px); overflow-y: auto; }
.page-header { text-align: center; margin-bottom: 40px; padding-bottom: 25px; border-bottom: 1px solid rgba(0,0,0,0.05); }
.page-title { color: var(--primary-color); margin-bottom: 10px; font-weight: 800; font-size: 2.5rem; }
.cards-container { display: flex; gap: 30px; flex-wrap: wrap; justify-content: center; }
.card { background-color: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); flex: 1; min-width: 380px; max-width: 520px; border: none; overflow: hidden; transition: transform 0.4s, box-shadow 0.4s; position: relative; }
.card-header { background: #f8f9fa; border-bottom: 1px solid var(--border-color); padding: 1.2rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
.card-body { padding: 1.5rem; max-height: 500px; overflow-y: auto; }
.notification { display: flex; justify-content: space-between; align-items: flex-start; padding: 15px; border-bottom: 1px solid #eee; border-radius: 12px; margin-bottom: 10px; position: relative; }
.notification.unread { background: #e6f2ff; border-left: 5px solid var(--accent-color); }
.notification.unread::before { content: ''; position: absolute; left: 5px; top: 50%; transform: translateY(-50%); width: 10px; height: 10px; background-color: var(--accent-color); border-radius: 50%; animation: pulse 2s infinite; }
@keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(0, 128, 128, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(0, 128, 128, 0); } 100% { box-shadow: 0 0 0 0 rgba(0, 128, 128, 0); } }
.notification .content-wrapper { flex: 1; margin-right: 15px; }
.notification .title { font-weight: 700; margin-bottom: 6px; color: var(--text-dark); font-size: 1.05rem; display: flex; align-items: center; gap: 8px; }
.notification .message { color: var(--text-light); font-size: 0.95rem; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }
.notification .time { font-size: 0.8rem; color: var(--text-light); min-width: 80px; text-align: right; font-weight: 500; }
.notification-actions a { font-size: 0.85rem; padding: 5px 10px; border-radius: 6px; text-decoration: none; font-weight: 500; transition: all 0.3s; background: var(--deep-green); color: #fff; }
.notification-actions a:hover { background: #0e5c1e; }
.mark-all-read .btn { background: var(--deep-green); color: white; border: none; padding: 6px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; transition: all 0.3s; }
.mark-all-read .btn:hover { background: #0e5c1e; }
.footer { text-align: center; margin-top: 40px; padding: 20px; color: var(--text-light); font-size: 0.9rem; border-top: 1px solid var(--border-color); }
@media (max-width: 992px) { body { flex-direction: column; } .sidebar { position: fixed; transform: translateX(-100%); width: 280px; } .sidebar.active { transform: translateX(0); } .menu-toggle { display: block; } .content { padding: 30px 20px; max-width: 100%; margin-top: 80px; } .cards-container { flex-direction: column; align-items: center; } .card { min-width: 100%; max-width: 100%; } }
</style>
</head>
<body>

<button class="menu-toggle" id="menuToggle"><i class="bi bi-list"></i></button>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
<div class="logo"><img src="logo.png" alt="CODETREE Logo"></div>
<ul class="navlist">
<li><a href="uprofile.php">Profile</a></li>
<li><a href="umessages.php">Messages</a></li>
<li><a href="uprojects.php">Projects</a></li>
<li><a href="unotification.php" class="active">Notifications</a></li>
<li><a href="logout.php">Logout</a></li>
</ul>
</nav>

<div class="content">
<div class="page-header">
<h1 class="page-title">Notifications & Reminders</h1>
<p class="page-subtitle">Stay updated with your latest activities and important reminders</p>
</div>

<div class="cards-container">

<!-- Notifications Card -->
<div class="card">
<div class="card-header">
<h3><i class="bi bi-bell"></i> Notifications</h3>
<div class="mark-all-read">
<button class="btn" id="markAllNotifications">Mark All as Read</button>
</div>
</div>
<div class="card-body">
<?php if(!empty($notifications)): ?>
<?php foreach(array_slice($notifications,0,5) as $n): ?>
<div class="notification <?php echo (!$n['is_read'] && $n['user_id'] != NULL) || $n['user_id'] == NULL ? 'unread' : ''; ?> <?php echo $n['user_id'] == NULL ? 'global-notification' : ''; ?>" id="notif-<?= $n['id'] ?>">
<div class="content-wrapper">
<div class="title">
<?php if((!$n['is_read'] && $n['user_id'] != NULL) || $n['user_id'] == NULL): ?>
<?php if($n['user_id'] == NULL): ?><?php else: ?><span class="badge bg-primary">New</span><?php endif; ?>
<?php endif; ?>
<?= htmlspecialchars($n['title']) ?>
</div>
<div class="message"><?= htmlspecialchars($n['message']) ?></div>
<?php if(!$n['is_read'] && $n['user_id'] != NULL): ?>
<div class="notification-actions">
<a href="#" class="mark-read-btn" data-id="<?= $n['id'] ?>">Mark as Read</a>
</div>
<?php endif; ?>
</div>
<div class="time"><?= getTimeAgo($n['created_at']) ?></div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="empty-state"><i class="bi bi-bell-slash"></i><p>No notifications found</p></div>
<?php endif; ?>
</div>
</div>

<!-- Reminders Card -->
<div class="card">
<div class="card-header">
<h3><i class="bi bi-clock"></i> Reminders</h3>
<div class="mark-all-read">
<button class="btn" id="markAllReminders">Mark All as Read</button>
</div>
</div>
<div class="card-body">
<?php if(!empty($reminders)): ?>
<?php foreach(array_slice($reminders,0,5) as $r): ?>
<div class="notification <?php echo (!$r['is_read'] && $r['user_id'] != NULL) || $r['user_id'] == NULL ? 'unread' : ''; ?> <?php echo $r['user_id'] == NULL ? 'global-notification' : ''; ?>" id="reminder-<?= $r['id'] ?>">
<div class="content-wrapper">
<div class="title">
<?php if((!$r['is_read'] && $r['user_id'] != NULL) || $r['user_id'] == NULL): ?>
<?php if($r['user_id'] == NULL): ?><?php else: ?><span class="badge bg-warning">Reminder</span><?php endif; ?>
<?php endif; ?>
<?= htmlspecialchars($r['title']) ?>
</div>
<div class="message"><?= htmlspecialchars($r['message']) ?></div>
<?php if(!$r['is_read'] && $r['user_id'] != NULL): ?>
<div class="notification-actions">
<a href="#" class="mark-read-btn" data-id="<?= $r['id'] ?>">Mark as Read</a>
</div>
<?php endif; ?>
</div>
<div class="time"><?= getTimeAgo($r['created_at']) ?></div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="empty-state"><i class="bi bi-clock-history"></i><p>No reminders found</p></div>
<?php endif; ?>
</div>
</div>

</div>

<div class="footer">&copy; <?= date('Y') ?> CODETREE. All rights reserved.</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Menu toggle
$('#menuToggle').click(function(){ $('#sidebar').toggleClass('active'); });

// Mark single notification/reminder as read
$('.mark-read-btn').click(function(e){
    e.preventDefault();
    let id = $(this).data('id');
    let parentDiv = $(this).closest('.notification');
    $.post('mark_read_ajax.php',{id:id}, function(){
        parentDiv.removeClass('unread');
        parentDiv.find('.notification-actions').remove();
    });
});

// Mark all notifications as read
$('#markAllNotifications').click(function(){
    $.post('mark_read_ajax.php',{mark_all:'notifications'}, function(){
        $('.card:first-child .notification.unread').removeClass('unread');
        $('.card:first-child .notification-actions').remove();
    });
});

// Mark all reminders as read
$('#markAllReminders').click(function(){
    $.post('mark_read_ajax.php',{mark_all:'reminders'}, function(){
        $('.card:last-child .notification.unread').removeClass('unread');
        $('.card:last-child .notification-actions').remove();
    });
});
</script>
</body>
</html>
