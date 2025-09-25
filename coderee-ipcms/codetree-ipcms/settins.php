<?php
require_once 'config.php';

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    $type = $_POST['type'];
    $id = $_POST['id'] ?? null;

    if ($type === 'employee') {
        $name = $_POST['Name'];
        $role = $_POST['Role'];
        $email = $_POST['Email'];
        $skills = $_POST['Skills'];
        $availability = $_POST['Availability'];
        $workload = $_POST['Workload'];
        $projects = $_POST['Projects'];
        $team_id = $_POST['Team'] ?: null;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE team_members SET name=?, role=?, email=?, skills=?, availability=?, workload=?, projects=?, team_id=? WHERE id=?");
            $stmt->execute([$name, $role, $email, $skills, $availability, $workload, $projects, $team_id, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO team_members (name, role, email, skills, availability, workload, projects, team_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $role, $email, $skills, $availability, $workload, $projects, $team_id]);
        }
    } elseif ($type === 'client') {
        $name = $_POST['Name'];
        $email = $_POST['Email'];
        $company = $_POST['Company'];
        if ($id) {
            $stmt = $pdo->prepare("UPDATE clients SET name=?, email=?, company=? WHERE id=?");
            $stmt->execute([$name, $email, $company, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO clients (name,email,company) VALUES (?,?,?)");
            $stmt->execute([$name,$email,$company]);
        }
    } elseif ($type === 'project') {
        $title = $_POST['Title'];
        $description = $_POST['Description'];
        $team_lead = $_POST['Team_Lead'];
        $members = $_POST['Members'];
        $client = $_POST['Client'];
        $budget = $_POST['Budget'];
        $progress = $_POST['Progress'];
        $assigned_date = $_POST['Assigned_Date'];
        $due_date = $_POST['Due_Date'];
        $status = $_POST['Status'];

        if ($id) {
            $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, team_lead=?, members=?, client=?, budget=?, progress=?, assigned_date=?, due_date=?, status=? WHERE id=?");
            $stmt->execute([$title,$description,$team_lead,$members,$client,$budget,$progress,$assigned_date,$due_date,$status,$id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO projects (title, description, team_lead, members, client, budget, progress, assigned_date, due_date, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$title,$description,$team_lead,$members,$client,$budget,$progress,$assigned_date,$due_date,$status]);
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['type'])) {
    $type = $_GET['type'];
    $id = $_GET['delete'];
    if ($type === 'employee') $pdo->prepare("DELETE FROM team_members WHERE id=?")->execute([$id]);
    if ($type === 'client') $pdo->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
    if ($type === 'project') $pdo->prepare("DELETE FROM projects WHERE id=?")->execute([$id]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Fetch data
$employees = $pdo->query("SELECT * FROM team_members")->fetchAll(PDO::FETCH_ASSOC);
$clients = $pdo->query("SELECT * FROM clients")->fetchAll(PDO::FETCH_ASSOC);
$projects = $pdo->query("SELECT * FROM projects")->fetchAll(PDO::FETCH_ASSOC);
$teams = $pdo->query("SELECT * FROM teams")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Settings - CODETREE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body { display:flex; font-family:'Segoe UI', Arial, sans-serif; background:#e9eef0; color:#1f2b2b; min-height:100vh;}
.sidebar { width:220px; background:#0f6b67; color:#fff; padding:30px 16px; position:fixed; top:0; left:0; min-height:100vh; display:flex; flex-direction:column;}
.logo { text-align:center; margin-bottom:40px; }
.logo img { width:120px; display:block; margin:0 auto; }
.navlist { list-style:none; padding:0; flex-grow:1; }
.navlist li { margin-bottom:18px; }
.navlist a { color:#dfeff0; text-decoration:none; display:block; padding:10px 14px; border-radius:6px; transition:0.3s; font-size:16px; }
.navlist a:hover { background: rgba(255,255,255,0.1); }
.content { margin-left:220px; padding:25px; width:calc(100% - 220px); }
.content h1 { font-size:26px; font-weight:bold; margin-bottom:25px; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { border:1px solid #ccc; padding:8px 10px; text-align:left; }
th { background:#f0f3f7; }
.action-btn { background:#0f6b67; color:#fff; border:none; padding:5px 8px; border-radius:5px; cursor:pointer; margin-right:5px; display:inline-block; text-decoration:none;}
.action-btn:hover { background:#0d5955; }
.delete-btn { background:#e57373; color:#fff; border:none; padding:5px 8px; border-radius:5px; cursor:pointer; text-decoration:none;}
.delete-btn:hover { background:#c0392b; }
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000; }
.modal-content { background:#fff; padding:20px; border-radius:10px; width:400px; position:relative; max-height:90vh; overflow-y:auto; }
.modal-content h3 { margin-bottom:15px; }
.modal-content label { display:block; margin-top:10px; font-size:14px; }
.modal-content input, .modal-content select { width:100%; padding:8px 10px; margin-top:5px; border-radius:6px; border:1px solid #ccc; }
.modal-content button { margin-top:15px; padding:8px 14px; border:none; border-radius:6px; background:#0f6b67; color:#fff; cursor:pointer; }
.modal-content button:hover { background:#0d5955; }
.close-btn { position:absolute; top:10px; right:15px; font-size:20px; cursor:pointer; }
.tabs { display:flex; gap:15px; margin-bottom:15px; }
.tab { padding:8px 15px; background:#f0f3f7; border-radius:8px; cursor:pointer; transition:0.3s; }
.tab.active { background:#0f6b67; color:#fff; }
</style>
</head>
<body>

<nav class="sidebar">
  <div class="logo"><img src="logo.png" alt="CODETREE"></div>
  <ul class="navlist">
    <li><a href="adashboard.php">Dashboard</a></li>
    <li><a href="projects.php">Projects</a></li>
    <li><a href="comunication.php">Communication</a></li>
    <li><a href="teams.php">Teams</a></li>
    <li><a href="clients.php">Clients</a></li>
    <li><a href="billing.php">Billing</a></li>
    <li><a href="settins.php">Settings</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</nav>

<div class="content">
  <h1>Settings</h1>

  <div class="tabs">
    <div class="tab active" data-tab="employee">Employees</div>
    <div class="tab" data-tab="client">Clients</div>
    <div class="tab" data-tab="project">Projects</div>
  </div>

  <!-- Employee Section -->
  <div class="section" id="employee">
    <button class="action-btn" onclick="openModal('employee')">Add Employee</button>
    <table>
      <thead>
        <tr><th>ID</th><th>Name</th><th>Role</th><th>Email</th><th>Skills</th><th>Availability</th><th>Workload</th><th>Projects</th><th>Team</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach($employees as $emp): ?>
        <tr>
          <td><?= $emp['id'] ?></td>
          <td><?= htmlspecialchars($emp['name']) ?></td>
          <td><?= htmlspecialchars($emp['role']) ?></td>
          <td><?= htmlspecialchars($emp['email']) ?></td>
          <td><?= htmlspecialchars($emp['skills']) ?></td>
          <td><?= htmlspecialchars($emp['availability']) ?></td>
          <td><?= htmlspecialchars($emp['workload']) ?></td>
          <td><?= htmlspecialchars($emp['projects']) ?></td>
          <td>
            <?php
                $team_name = '';
                foreach($teams as $team){
                    if($team['id']==$emp['team_id']) $team_name = $team['name'];
                }
                echo htmlspecialchars($team_name);
            ?>
          </td>
          <td>
            <button class="action-btn" onclick="editRecord('employee', <?= $emp['id'] ?>)">Edit</button>
            <button class="delete-btn" onclick="if(confirm('Are you sure?')){ window.location='?delete=<?= $emp['id'] ?>&type=employee' }">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Client Section -->
  <div class="section" id="client" style="display:none;">
    <button class="action-btn" onclick="openModal('client')">Add Client</button>
    <table>
      <thead>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Company</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach($clients as $cl): ?>
        <tr>
          <td><?= $cl['id'] ?></td>
          <td><?= htmlspecialchars($cl['name']) ?></td>
          <td><?= htmlspecialchars($cl['email']) ?></td>
          <td><?= htmlspecialchars($cl['company']) ?></td>
          <td>
            <button class="action-btn" onclick="editRecord('client', <?= $cl['id'] ?>)">Edit</button>
            <button class="delete-btn" onclick="if(confirm('Are you sure?')){ window.location='?delete=<?= $cl['id'] ?>&type=client' }">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Project Section -->
  <div class="section" id="project" style="display:none;">
    <button class="action-btn" onclick="openModal('project')">Add Project</button>
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Title</th><th>Description</th><th>Team Lead</th><th>Members</th>
          <th>Client</th><th>Budget</th><th>Progress</th><th>Assigned Date</th><th>Due Date</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($projects as $pr): ?>
        <tr>
          <td><?= $pr['id'] ?></td>
          <td><?= htmlspecialchars($pr['title']) ?></td>
          <td><?= htmlspecialchars($pr['description']) ?></td>
          <td><?= htmlspecialchars($pr['team_lead']) ?></td>
          <td><?= htmlspecialchars($pr['members']) ?></td>
          <td><?= htmlspecialchars($pr['client']) ?></td>
          <td><?= htmlspecialchars($pr['budget']) ?></td>
          <td><?= htmlspecialchars($pr['progress']) ?></td>
          <td><?= htmlspecialchars($pr['assigned_date']) ?></td>
          <td><?= htmlspecialchars($pr['due_date']) ?></td>
          <td><?= htmlspecialchars($pr['status']) ?></td>
          <td>
            <button class="action-btn" onclick="editRecord('project', <?= $pr['id'] ?>)">Edit</button>
            <button class="delete-btn" onclick="if(confirm('Are you sure?')){ window.location='?delete=<?= $pr['id'] ?>&type=project' }">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>

<script>
// Tab switching
const tabs = document.querySelectorAll('.tab');
const sections = document.querySelectorAll('.section');
tabs.forEach(tab => {
  tab.addEventListener('click', () => {
    tabs.forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    sections.forEach(s => s.style.display = 'none');
    document.getElementById(tab.dataset.tab).style.display = 'block';
  });
});

// Modal handling
let currentType = '';
let currentId = null;

function openModal(type) {
  currentType = type;
  currentId = null;
  const modal = createModal(type);
  document.body.appendChild(modal);
  modal.style.display = 'flex';
}

function editRecord(type, id) {
  currentType = type;
  currentId = id;
  const modal = createModal(type, id);
  document.body.appendChild(modal);
  modal.style.display = 'flex';
}

function createModal(type, id = null) {
  const modal = document.createElement('div');
  modal.className = 'modal';
  modal.innerHTML = `
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal(this.parentElement.parentElement)">&times;</span>
      <h3>${id ? 'Edit' : 'Add'} ${capitalize(type)}</h3>
      <form method="POST">
        <input type="hidden" name="type" value="${type}">
        ${id ? `<input type="hidden" name="id" value="${id}">` : ''}
        ${getFieldsHTML(type, id)}
        <button type="submit">${id ? 'Update' : 'Add'} ${capitalize(type)}</button>
      </form>
    </div>
  `;
  return modal;
}

function closeModal(modal) {
  modal.remove();
}

function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function getFieldsHTML(type, id = null) {
  let html = '';
  if (type === 'employee') {
    html += `
      <label>Name</label><input type="text" name="Name" value="${getValue(type, id, 'name')}">
      <label>Role</label><input type="text" name="Role" value="${getValue(type, id, 'role')}">
      <label>Email</label><input type="email" name="Email" value="${getValue(type, id, 'email')}">
      <label>Skills</label><input type="text" name="Skills" value="${getValue(type, id, 'skills')}">
      <label>Availability</label><input type="text" name="Availability" value="${getValue(type, id, 'availability')}">
      <label>Workload</label><input type="text" name="Workload" value="${getValue(type, id, 'workload')}">
      <label>Projects</label><input type="text" name="Projects" value="${getValue(type, id, 'projects')}">
      <label>Team</label>
      <select name="Team">
        <option value="">-- Select Team --</option>
        ${window.teamsOptions || ''}
      </select>
    `;
  } else if (type === 'client') {
    html += `
      <label>Name</label><input type="text" name="Name" value="${getValue(type, id, 'name')}">
      <label>Email</label><input type="email" name="Email" value="${getValue(type, id, 'email')}">
      <label>Company</label><input type="text" name="Company" value="${getValue(type, id, 'company')}">
    `;
  } else if (type === 'project') {
    html += `
      <label>Title</label><input type="text" name="Title" value="${getValue(type, id, 'title')}">
      <label>Description</label><input type="text" name="Description" value="${getValue(type, id, 'description')}">
      <label>Team Lead</label><input type="text" name="Team_Lead" value="${getValue(type, id, 'team_lead')}">
      <label>Members</label><input type="text" name="Members" value="${getValue(type, id, 'members')}">
      <label>Client</label><input type="text" name="Client" value="${getValue(type, id, 'client')}">
      <label>Budget</label><input type="text" name="Budget" value="${getValue(type, id, 'budget')}">
      <label>Progress</label><input type="text" name="Progress" value="${getValue(type, id, 'progress')}">
      <label>Assigned Date</label><input type="date" name="Assigned_Date" value="${getValue(type, id, 'assigned_date')}">
      <label>Due Date</label><input type="date" name="Due_Date" value="${getValue(type, id, 'due_date')}">
      <label>Status</label><input type="text" name="Status" value="${getValue(type, id, 'status')}">
    `;
  }
  return html;
}

function getValue(type, id, field) {
  if (!id) return '';
  const dataset = window[`${type}Data`] || [];
  const record = dataset.find(r => r.id == id);
  return record ? record[field] : '';
}

// Store data for editing
window.employeeData = <?= json_encode($employees) ?>;
window.clientData = <?= json_encode($clients) ?>;
window.projectData = <?= json_encode($projects) ?>;
window.teamsOptions = `<?php foreach($teams as $t){ echo "<option value='{$t['id']}'>{$t['name']}</option>"; } ?>`;

</script>
</body>
</html>
