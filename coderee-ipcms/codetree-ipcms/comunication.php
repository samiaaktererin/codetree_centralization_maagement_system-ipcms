<?php
require_once 'config.php'; // keep your config.php; helper will try to reuse any connection variables inside it

// -------------------------
// Helper: ensure $conn exists (tries existing vars, then creates mysqli)
// -------------------------
function ensure_db_connection() {
    global $conn, $db, $mysqli, $servername, $username, $password, $dbname, $db_host, $db_user, $db_pass, $db_name;
    if (isset($conn) && $conn instanceof mysqli) return;

    if (isset($db) && $db instanceof mysqli) { $conn = $db; return; }
    if (isset($mysqli) && $mysqli instanceof mysqli) { $conn = $mysqli; return; }

    // try common config constants or variables (from config.php)
    $dbHost = defined('DB_HOST') ? DB_HOST : (isset($db_host) ? $db_host : (isset($servername) ? $servername : '127.0.0.1'));
    $dbUser = defined('DB_USER') ? DB_USER : (isset($db_user) ? $db_user : (isset($username) ? $username : 'root'));
    $dbPass = defined('DB_PASS') ? DB_PASS : (isset($db_pass) ? $db_pass : (isset($password) ? $password : ''));
    $dbName = defined('DB_NAME') ? DB_NAME : (isset($db_name) ? $db_name : (isset($dbname) ? $dbname : 'codetree'));

    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($conn->connect_error) {
        http_response_code(500);
        // stop with a clear message (useful during dev). In production you might want to log instead.
        die("Database connection failed: " . $conn->connect_error);
    }
}

ensure_db_connection();

// require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = intval($_SESSION['user_id']);

// -------------------------
// Handle AJAX POST actions (must run before any HTML output)
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    // send client message
    if (isset($_POST['client_message'])) {
        $receiver_id = intval($_POST['receiver_id']);
        $msg = trim($_POST['client_message']);
        if ($msg === '') { echo json_encode(['status'=>'error','error'=>'empty_message']); exit(); }
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_type, receiver_id, message) VALUES (?, 'Client', ?, ?)");
        $stmt->bind_param("iis", $user_id, $receiver_id, $msg);
        if ($stmt->execute()) echo json_encode(['status'=>'success']);
        else echo json_encode(['status'=>'error','error'=>$stmt->error]);
        exit();
    }

    // send team message
    if (isset($_POST['team_message'])) {
        $team_id = intval($_POST['team_id']);
        $msg = trim($_POST['team_message']);
        if ($msg === '') { echo json_encode(['status'=>'error','error'=>'empty_message']); exit(); }
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_type, receiver_id, message) VALUES (?, 'Team', ?, ?)");
        $stmt->bind_param("iis", $user_id, $team_id, $msg);
        if ($stmt->execute()) echo json_encode(['status'=>'success']);
        else echo json_encode(['status'=>'error','error'=>$stmt->error]);
        exit();
    }

    // send announcement (also stored as Team messages)
    if (isset($_POST['announcement_message'])) {
        $team_id = intval($_POST['team_id']);
        $msg = trim($_POST['announcement_message']);
        if ($msg === '') { echo json_encode(['status'=>'error','error'=>'empty_message']); exit(); }
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_type, receiver_id, message) VALUES (?, 'Team', ?, ?)");
        $stmt->bind_param("iis", $user_id, $team_id, $msg);
        if ($stmt->execute()) echo json_encode(['status'=>'success']);
        else echo json_encode(['status'=>'error','error'=>$stmt->error]);
        exit();
    }

    // unknown action
    echo json_encode(['status'=>'error','error'=>'invalid_action']);
    exit();
}

// -------------------------
// Page rendering: fetch clients and team messages
// -------------------------
$clients = $conn->query("SELECT * FROM clients ORDER BY created_at DESC");

$team_messages = [];
$res = $conn->query("SELECT m.*, u.username FROM messages m LEFT JOIN users u ON m.sender_id=u.id WHERE m.receiver_type='Team' ORDER BY m.sent_at ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $team_messages[$row['receiver_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Communication - CODETREE</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Segoe UI',Arial,sans-serif;display:flex;background:#edf0f5;color:#333;min-height:100vh;}

  /* Sidebar */
  .sidebar{
    width:220px;
    background:#0f6b67;
    color:#fff;
    padding:30px 16px;
    position:fixed;
    top:0;
    left:0;
    min-height:100vh;
    display:flex;
    flex-direction:column;
  }
  .logo{text-align:center;margin-bottom:40px;}
  .logo img{width:120px;display:block;margin:0 auto;}
  .logo h2{font-size:22px;font-weight:bold;color:#fff;margin-top:8px;}
  .navlist{list-style:none;padding:0;flex-grow:1;}
  .navlist li{margin-bottom:18px;}
  .navlist a{
    color:#dfeff0;text-decoration:none;
    display:block;padding:10px 14px;
    border-radius:6px;transition:0.3s;
    font-size:16px;
  }
  .navlist a:hover{background: rgba(255,255,255,0.1);}

  /* Content */
  .content{margin-left:220px;padding:25px;width:calc(100% - 220px);}
  .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
  .header h1{font-size:26px;font-weight:bold;}

  /* Header buttons */
  .header-buttons{display:flex;gap:10px;}
  .header-buttons button{
    padding:6px 12px;
    background:#0f6b67;
    color:#fff;
    border:none;
    border-radius:6px;
    cursor:pointer;
  }
  .header-buttons button:hover{background:#0d5955;}

  /* Columns layout */
  .columns{display:flex;gap:20px;flex-wrap:wrap;}
  .column{flex:1;min-width:300px;max-width:500px;display:flex;flex-direction:column;gap:20px;}

  /* Cards */
  .card{background:#fff;padding:20px;border-radius:12px;box-shadow:0 2px 5px rgba(0,0,0,0.05);display:flex;flex-direction:column;}
  table{width:100%;border-collapse:collapse;margin-top:15px;}
  th,td{text-align:left;padding:10px;border-bottom:1px solid #eee;font-size:14px;cursor:pointer;}
  th{color:#666;font-size:13px;}

  /* Clients Table Scroll */
  #clientsTableWrapper{flex:1;overflow-y:auto;max-height:300px;}

  /* Messages */
  .msg{display:flex;align-items:flex-start;gap:12px;padding:10px;border-radius:8px;margin-bottom:8px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.05);}
  .msg img{width:36px;height:36px;border-radius:50%;}
  .msg b{font-size:14px;}
  .msg span{font-size:13px;color:#666;}
  .chat-input{display:flex;gap:10px;margin-top:8px;}
  .chat-input input{flex:1;padding:8px;border-radius:6px;border:1px solid #ccc;outline:none;}
  .chat-input button{padding:8px 14px;background:#0f6b67;border:none;color:#fff;border-radius:6px;cursor:pointer;}
  .chat-input button:hover{background:#0d5955;}
  .messages-box{flex:1;overflow-y:auto;margin-bottom:8px;max-height:250px;}

  /* Back Button */
  .back-btn{
    align-self:flex-end;
    padding:6px 12px;
    background:#0f6b67;
    color:#fff;
    border:none;
    border-radius:6px;
    cursor:pointer;
    margin-bottom:10px;
  }
  .back-btn:hover{background:#0d5955;}

  /* Teams */
  .teams-title{font-weight:bold;margin:20px 0 10px 0;}
  .teams-grid{display:flex;flex-direction:column;gap:20px;}
  .team-box{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 5px rgba(0,0,0,0.05);flex-direction:column;display:flex;}
  .team-box h4{margin-bottom:10px;font-size:16px;}
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
      <!-- keep the same (your project uses this filename) -->
      <li><a href="comunication.php">Communication</a></li>
      <li><a href="teams.php">Teams</a></li>
      <li><a href="clients.php">Clients</a></li>
      <li><a href="billing.php">Billing</a></li>
      <li><a href="settins.php">Settings</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

  <!-- Content -->
  <div class="content">
    <div class="header">
      <h1>Communication</h1>
      <div class="header-buttons">
        <button id="showTeamsBtn">Teams</button>
        <button id="showAnnouncementBtn">Announcement</button>
      </div>
    </div>

    <div class="columns">
      <!-- Left Column: Clients Table + Messages -->
      <div class="column">
        <!-- Clients Table -->
        <div class="card">
          <div id="clientsTableWrapper">
            <table id="clientsTable">
              <tr><th>Client</th><th>Subject / Email</th><th>Status</th></tr>
              <?php
                if ($clients && $clients->num_rows>0) {
                    while ($c = $clients->fetch_assoc()) {
                        $name = htmlspecialchars($c['name']);
                        $email = htmlspecialchars($c['email']);
                        $status = htmlspecialchars($c['status']);
                        $id = intval($c['id']);
                        echo "<tr data-clientid=\"{$id}\" data-clientname=\"{$name}\"><td>{$name}</td><td>{$email}</td><td>{$status}</td></tr>";
                    }
                } else {
                    echo '<tr><td colspan="3">No clients found.</td></tr>';
                }
              ?>
            </table>
          </div>
        </div>

        <!-- Client Messages -->
        <div class="card" id="clientMessagesCard" style="display:none;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <h3 id="msgTitle">Messages</h3>
            <button class="back-btn" id="backBtn">Back</button>
          </div>
          <div class="messages-box" id="msgContainer">
            <p>Select a client to see messages.</p>
          </div>
          <div class="chat-input">
            <input type="text" id="newMsg" placeholder="Type your message...">
            <button id="sendBtn">Send</button>
          </div>
        </div>
      </div>

      <!-- Right Column: Teams & Announcement -->
      <div class="column">
        <div class="teams-grid" id="teamsGrid">
          <div class="team-box">
            <h4>Team A Messages</h4>
            <div class="messages-box" id="team1">
              <?php
                if (!empty($team_messages[1])) {
                    foreach ($team_messages[1] as $m) {
                        $u = htmlspecialchars($m['username'] ?? 'Unknown');
                        $txt = htmlspecialchars($m['message']);
                        $t = htmlspecialchars($m['sent_at']);
                        echo "<div class=\"msg\"><img src=\"https://i.pravatar.cc/40?u={$u}\"><div><b>{$u}</b><br><span>{$txt} {$t}</span></div></div>";
                    }
                } else {
                    echo '<p style="padding:8px;color:#666">No messages yet.</p>';
                }
              ?>
            </div>
            <div class="chat-input">
              <input type="text" id="teamInput1" placeholder="Type message to Team A">
              <button data-team="1" class="teamSendBtn">Send</button>
            </div>
          </div>

          <div class="team-box">
            <h4>Team B Messages</h4>
            <div class="messages-box" id="team2">
              <?php
                if (!empty($team_messages[2])) {
                    foreach ($team_messages[2] as $m) {
                        $u = htmlspecialchars($m['username'] ?? 'Unknown');
                        $txt = htmlspecialchars($m['message']);
                        $t = htmlspecialchars($m['sent_at']);
                        echo "<div class=\"msg\"><img src=\"https://i.pravatar.cc/40?u={$u}\"><div><b>{$u}</b><br><span>{$txt} {$t}</span></div></div>";
                    }
                } else {
                    echo '<p style="padding:8px;color:#666">No messages yet.</p>';
                }
              ?>
            </div>
            <div class="chat-input">
              <input type="text" id="teamInput2" placeholder="Type message to Team B">
              <button data-team="2" class="teamSendBtn">Send</button>
            </div>
          </div>
        </div>

        <!-- Team Announcement -->
        <div class="card" id="announcementCard" style="display:none;">
          <h3>Make Team Announcement</h3>
          <div class="chat-input">
            <select id="teamSelect">
              <option value="1">Team A</option>
              <option value="2">Team B</option>
            </select>
            <input type="text" id="teamMsg" placeholder="Type your message...">
            <button id="teamSendBtn">Send</button>
          </div>
        </div>
      </div>
    </div>
  </div>

<script>
let currentClientId = null, currentClientName = null;
const msgContainer = document.getElementById('msgContainer');
const clientMessagesCard = document.getElementById('clientMessagesCard');
const msgTitle = document.getElementById('msgTitle');

// Wire up client rows (they were rendered server-side)
document.querySelectorAll('#clientsTable tr[data-clientid]').forEach(row=>{
  row.addEventListener('click', ()=>{
    currentClientId = row.dataset.clientid;
    currentClientName = row.dataset.clientname;
    msgTitle.innerText = 'Messages - ' + currentClientName;
    loadClientMessages(currentClientId);
    clientMessagesCard.style.display = 'flex';
  });
});

function loadClientMessages(cid){
  fetch('load_messages.php?client_id=' + encodeURIComponent(cid))
    .then(res => res.json())
    .then(data => {
      msgContainer.innerHTML = '';
      if (!Array.isArray(data) || data.length === 0) {
        msgContainer.innerHTML = '<p>No messages yet.</p>';
        return;
      }
      data.forEach(m => {
        const div = document.createElement('div');
        div.classList.add('msg');
        const uname = m.username ? m.username : 'Unknown';
        div.innerHTML = `<img src="https://i.pravatar.cc/40?u=${encodeURIComponent(uname)}"><div><b>${escapeHtml(uname)}</b><br><span>${escapeHtml(m.message)} (${escapeHtml(m.sent_at)})</span></div>`;
        msgContainer.appendChild(div);
      });
      msgContainer.scrollTop = msgContainer.scrollHeight;
    }).catch(err => {
      console.error('Failed to load messages', err);
      msgContainer.innerHTML = '<p style="color:#b00">Failed to load messages.</p>';
    });
}

// Send client message
document.getElementById('sendBtn').addEventListener('click', ()=>{
  const text = document.getElementById('newMsg').value.trim();
  if (!currentClientId) return alert("Select a client first!");
  if (!text) return;
  const body = `receiver_id=${encodeURIComponent(currentClientId)}&client_message=${encodeURIComponent(text)}`;
  fetch('comunication.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body})
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        document.getElementById('newMsg').value = '';
        loadClientMessages(currentClientId);
      } else {
        alert('Error: ' + (res.error || 'unknown'));
      }
    }).catch(err => { console.error(err); alert('Send failed'); });
});

document.getElementById('backBtn').addEventListener('click', ()=>{ clientMessagesCard.style.display = 'none'; });

// Team messages send (AJAX)
document.querySelectorAll('.teamSendBtn').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const tid = btn.dataset.team;
    const input = document.getElementById('teamInput' + tid);
    const text = input.value.trim();
    if (!text) return alert('Enter a message!');
    const body = `team_id=${encodeURIComponent(tid)}&team_message=${encodeURIComponent(text)}`;
    fetch('comunication.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body})
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') {
          // append to UI immediately (no full reload)
          const container = document.getElementById('team' + tid);
          const div = document.createElement('div');
          div.classList.add('msg');
          div.innerHTML = `<img src="https://i.pravatar.cc/40?u=You"><div><b>You</b><br><span>${escapeHtml(text)} Just now</span></div>`;
          container.appendChild(div);
          container.scrollTop = container.scrollHeight;
          input.value = '';
        } else {
          alert('Error: ' + (res.error || 'unknown'));
        }
      }).catch(err => { console.error(err); alert('Send failed'); });
  });
});

// Announcement send
document.getElementById('teamSendBtn').addEventListener('click', ()=>{
  const tid = document.getElementById('teamSelect').value;
  const text = document.getElementById('teamMsg').value.trim();
  if (!text) return alert('Enter a message!');
  const body = `team_id=${encodeURIComponent(tid)}&announcement_message=${encodeURIComponent(text)}`;
  fetch('comunication.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body})
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        // append locally
        const container = document.getElementById('team' + tid);
        const div = document.createElement('div');
        div.classList.add('msg');
        div.innerHTML = `<img src="https://i.pravatar.cc/40?u=You"><div><b>You</b><br><span>${escapeHtml(text)} Just now</span></div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
        document.getElementById('teamMsg').value = '';
      } else {
        alert('Error: ' + (res.error || 'unknown'));
      }
    }).catch(err => { console.error(err); alert('Send failed'); });
});

// Toggle views
document.getElementById('showTeamsBtn').addEventListener('click', ()=>{
  document.getElementById('teamsGrid').style.display = 'flex';
  document.getElementById('announcementCard').style.display = 'none';
});
document.getElementById('showAnnouncementBtn').addEventListener('click', ()=>{
  document.getElementById('teamsGrid').style.display = 'none';
  document.getElementById('announcementCard').style.display = 'flex';
});

// small helper to escape HTML in JS-inserted strings
function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
