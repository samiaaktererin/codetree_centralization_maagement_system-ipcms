<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle Create Project
if(isset($_POST['create_project'])){
    $title = $_POST['title'];
    $description = $_POST['description'];
    $team_lead = $_POST['team_lead'];
    $members = $_POST['members'];
    $client = $_POST['client'];
    $budget = $_POST['budget'];
    $progress = $_POST['progress'];
    $assigned_date = $_POST['assigned_date'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("INSERT INTO projects (title, description, team_lead, members, client, budget, progress, assigned_date, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title,$description,$team_lead,$members,$client,$budget,$progress,$assigned_date,$due_date,$status]);
    header("Location: projects.php");
    exit();
}

// Handle Edit Project
if(isset($_POST['edit_project'])){
    $id = $_POST['project_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $team_lead = $_POST['team_lead'];
    $members = $_POST['members'];
    $client = $_POST['client'];
    $budget = $_POST['budget'];
    $progress = $_POST['progress'];
    $assigned_date = $_POST['assigned_date'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, team_lead=?, members=?, client=?, budget=?, progress=?, assigned_date=?, due_date=?, status=? WHERE id=?");
    $stmt->execute([$title,$description,$team_lead,$members,$client,$budget,$progress,$assigned_date,$due_date,$status,$id]);
    header("Location: projects.php");
    exit();
}

// Handle Delete Project
if(isset($_GET['delete_id'])){
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id=?");
    $stmt->execute([$id]);
    header("Location: projects.php");
    exit();
}

// Fetch projects
$projects = $pdo->query("SELECT * FROM projects")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Projects - CODETREE</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f7fa; color:#222; display:flex;}

/* Sidebar */
.sidebar {width:220px; background:#0f6b67; color:#fff; padding:30px 16px; position:fixed; top:0; left:0; min-height:100vh; display:flex; flex-direction:column;}
.sidebar .brand {text-align:center; margin-bottom:40px;}
.sidebar .brand img {width:120px; display:block; margin:0 auto;}
.sidebar ul.navlist{list-style:none; padding:0; flex-grow:1;}
.sidebar ul.navlist li{margin-bottom:18px;}
.sidebar ul.navlist a{color:#dfeff0; text-decoration:none; display:block; padding:10px 14px; border-radius:6px; transition:0.3s; font-size:16px;}
.sidebar ul.navlist a:hover{background: rgba(255,255,255,0.1);}

/* Content */
.content {margin-left:240px; padding:30px; width:calc(100% - 240px);}
h1 {font-size:24px; font-weight:bold; margin-bottom:20px;}

/* Toolbar */
.toolbar {display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;}
.toolbar input {padding:8px 12px; border-radius:6px; border:1px solid #ccc; outline:none; width:200px;}
.btn-new {padding:10px 16px; border-radius:6px; border:none; cursor:pointer; background:#014d44; color:#fff; font-weight:500; transition:0.3s;}
.btn-new:hover {background:#02675b;}

/* Projects Table */
table {width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 8px rgba(0,0,0,0.05);}
th,td {padding:14px 12px; text-align:left; border-bottom:1px solid #eee; font-size:14px;}
th {background:#e4e4e4; color:#333;}
tr:hover {background:#f1f6f8;}

/* Make table rows clickable */
tr.clickable-row {cursor: pointer;}

/* Progress bar container */
.progress-container {background:#e0e0e0; border-radius:6px; height:14px; overflow:hidden;}
.progress-inner {height:100%; border-radius:6px; transition:0.5s;}
.progress-inner.not-started {background:#bbb; width:0%;}
.progress-inner.in-progress {background:#f39c12; width:50%;}
.progress-inner.completed {background:#27ae60; width:100%;}

/* Buttons side by side */
.button-group {display:flex; gap:6px;}
button.edit-btn {flex:1; padding:6px 10px; border:none; border-radius:6px; background:#0f6b67; color:#fff; font-size:13px; cursor:pointer; transition:0.3s;}
button.edit-btn:hover {background:#0d5c59;}
button.delete-btn {flex:1; padding:6px 10px; border:none; border-radius:6px; background:#dc3545; color:#fff; font-size:13px; cursor:pointer; transition:0.3s;}
button.delete-btn:hover {background:#c82333;}

/* Modal */
.modal-bg {position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; justify-content:center; align-items:center; z-index:1000;}
.modal-content {background:#fff; padding:30px 25px; border-radius:12px; width:600px; max-width:90%; max-height:90%; overflow:auto; position:relative; box-shadow:0 8px 20px rgba(0,0,0,0.15);}
.close-btn {position:absolute; top:15px; right:15px; background:#014d44; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; transition:0.3s;}
.close-btn:hover {background:#02675b;}
.modal-content form div {margin-bottom:12px;}
.modal-content label {font-weight:500; margin-bottom:4px; display:block;}
.modal-content input, .modal-content textarea, .modal-content select {width:100%; padding:8px 10px; border-radius:6px; border:1px solid #ccc; outline:none;}
.modal-content button[type="submit"] {padding:10px 16px; border:none; border-radius:6px; background:#014d44; color:#fff; cursor:pointer; transition:0.3s; font-weight:500;}
.modal-content button[type="submit"]:hover {background:#02675b;}

/* Status row colors */
.project.completed {border-left:5px solid #27ae60; background:#e9f7ef;}
.project.in-progress {border-left:5px solid #f39c12; background:#fff4e6;}
.project.not-started {border-left:5px solid #bbb; background:#f0f0f0;}

/* Status prompt */
.status-prompt {cursor:pointer; padding:4px 8px; border-radius:4px; transition:0.3s;}
.status-prompt:hover {background:rgba(0,0,0,0.05);}

/* Project Details Prompt */
.project-details-prompt {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    width: 500px;
    max-width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    z-index: 2000;
    display: none;
}

.project-details-header {
    background: #0f6b67;
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.project-details-header h3 {
    margin: 0;
    font-size: 1.5rem;
}

.project-details-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

.project-details-body {
    padding: 20px;
}

.project-details-section {
    margin-bottom: 20px;
}

.project-details-section h4 {
    margin-bottom: 10px;
    color: #0f6b67;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.project-details-row {
    display: flex;
    margin-bottom: 10px;
}

.project-details-label {
    font-weight: bold;
    width: 120px;
    color: #555;
}

.project-details-value {
    flex: 1;
}

.project-details-progress {
    background: #e0e0e0;
    border-radius: 6px;
    height: 14px;
    margin-top: 5px;
    overflow: hidden;
}

.project-details-progress-bar {
    height: 100%;
    background: #27ae60;
    border-radius: 6px;
}

.project-details-invoices {
    margin-top: 10px;
}

.project-details-invoices table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 5px;
}

.project-details-invoices th, 
.project-details-invoices td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.project-details-invoices th {
    background: #f5f5f5;
}

.project-details-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1999;
    display: none;
}

/* Fixed prompt styles */
.project-details-prompt.fixed {
    max-height: 90vh;
    overflow-y: auto;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 600px;
    max-width: 95%;
}
</style>
</head>
<body>
<!-- Sidebar -->
<nav class="sidebar">
    <div class="brand">
      <img src="logo.png" alt="CODETREE" />
    </div>
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
<div class="toolbar">
    <h1>Project Management</h1>
    <button class="btn-new" onclick="openCreateModal()">+ New Project</button>
</div>

<table id="projectsTable">
<thead>
<tr>
<th>Project</th>
<th>Status</th>
<th>Team Lead</th>
<th>Members</th>
<th>Client</th>
<th>Budget</th>
<th>Progress</th>
<th>Assigned</th>
<th>Due</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($projects as $project): ?>
<tr data-project='<?php echo json_encode($project); ?>' 
    class="clickable-row <?php echo strtolower(str_replace(' ','-',$project['status'])); ?>">
<td><?php echo htmlspecialchars($project['title']); ?></td>
<td class="project-status">
    <span class="status-prompt" onclick="showProjectDetails(<?php echo $project['id']; ?>)">
        <?php echo $project['status']; ?>
    </span>
</td>
<td><?php echo htmlspecialchars($project['team_lead']); ?></td>
<td><?php echo htmlspecialchars($project['members']); ?></td>
<td><?php echo htmlspecialchars($project['client']); ?></td>
<td>$<?php echo number_format($project['budget'],2); ?></td>
<td>
    <div class="progress-container">
        <div class="progress-inner <?php echo strtolower(str_replace(' ','-',$project['status'])); ?>"></div>
    </div>
</td>
<td><?php echo $project['assigned_date']; ?></td>
<td><?php echo $project['due_date']; ?></td>
<td>
    <div class="button-group">
        <button class="edit-btn" onclick="openEditModal(<?php echo $project['id']; ?>)">Edit</button>
        <button class="delete-btn" onclick="if(confirm('Delete project?')){window.location='?delete_id=<?php echo $project['id']; ?>';}">Delete</button>
    </div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Create/Edit Modal -->
<div class="modal-bg" id="modalBg">
<div class="modal-content">
<button class="close-btn" onclick="closeModal()">Close</button>
<h3 id="modalTitle">Project</h3>
<form method="post" id="projectForm">
<input type="hidden" name="project_id" id="project_id">
<div>
<label>Title</label>
<input type="text" name="title" id="title" required>
</div>
<div>
<label>Description</label>
<textarea name="description" id="description" rows="3" required></textarea>
</div>
<div>
<label>Team Lead</label>
<input type="text" name="team_lead" id="team_lead">
</div>
<div>
<label>Members</label>
<input type="text" name="members" id="members">
</div>
<div>
<label>Client</label>
<input type="text" name="client" id="client">
</div>
<div>
<label>Budget</label>
<input type="number" step="0.01" name="budget" id="budget">
</div>
<div>
<label>Status</label>
<select name="status" id="status" onchange="toggleProgressInput()">
<option value="Not Started">Not Started</option>
<option value="In Progress">In Progress</option>
<option value="Completed">Completed</option>
</select>
</div>
<div id="progressDiv">
<label>Progress %</label>
<input type="number" name="progress" id="progress" min="0" max="100">
</div>
<div>
<label>Assigned Date</label>
<input type="date" name="assigned_date" id="assigned_date">
</div>
<div>
<label>Due Date</label>
<input type="date" name="due_date" id="due_date">
</div>
<div style="margin-top:10px;">
<button type="submit" name="create_project" id="submitBtn">Save</button>
</div>
</form>
</div>
</div>

<!-- Project Details Prompt -->
<div class="project-details-overlay" id="projectDetailsOverlay"></div>
<div class="project-details-prompt fixed" id="projectDetailsPrompt">
    <div class="project-details-header">
        <h3 id="projectDetailsTitle">Project Details</h3>
        <button class="project-details-close" onclick="closeProjectDetails()">&times;</button>
    </div>
    <div class="project-details-body">
        <div class="project-details-section">
            <h4>Project Information</h4>
            <div class="project-details-row">
                <div class="project-details-label">Title:</div>
                <div class="project-details-value" id="detailsTitle"></div>
            </div>
            <div class="project-details-row">
                <div class="project-details-label">Description:</div>
                <div class="project-details-value" id="detailsDescription"></div>
            </div>
            <div class="project-details-row">
                <div class="project-details-label">Status:</div>
                <div class="project-details-value" id="detailsStatus"></div>
            </div>
            <div class="project-details-row">
                <div class="project-details-label">Progress:</div>
                <div class="project-details-value">
                    <div id="detailsProgressText"></div>
                    <div class="project-details-progress">
                        <div class="project-details-progress-bar" id="detailsProgressBar"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="project-details-section">
            <h4>Team</h4>
            <div class="project-details-row">
                <div class="project-details-label">Team Lead:</div>
                <div class="project-details-value" id="detailsTeamLead"></div>
            </div>
            <div class="project-details-row">
                <div class="project-details-label">Members:</div>
                <div class="project-details-value" id="detailsMembers"></div>
            </div>
        </div>
        
        <div class="project-details-section">
            <h4>Client & Budget</h4>
            <div class="project-details-row">
                <div class="project-details-label">Client:</div>
                <div class="project-details-value" id="detailsClient"></div>
            </div>
            <div class="project-details-row">
                <div class="project-details-label">Budget:</div>
                <div class="project-details-value" id="detailsBudget"></div>
            </div>
        </div>
        
        <div class="project-details-section">
            <h4>Timeline</h4>
            <div class="project-details-row">
                <div class="project-details-label">Assigned Date:</div>
                <div class="project-details-value" id="detailsAssignedDate"></div>
            </div>
            <div class="project-details-row">
                <div class="project-details-label">Due Date:</div>
                <div class="project-details-value" id="detailsDueDate"></div>
            </div>
        </div>
        
        <div class="project-details-section" id="invoicesSection">
            <h4>Invoices</h4>
            <div class="project-details-invoices" id="detailsInvoices">
                <!-- Invoices will be dynamically inserted here -->
            </div>
        </div>
    </div>
</div>

<script>
const modalBg = document.getElementById('modalBg');
const projectForm = document.getElementById('projectForm');
const submitBtn = document.getElementById('submitBtn');
const progressDiv = document.getElementById('progressDiv');
const projectDetailsPrompt = document.getElementById('projectDetailsPrompt');
const projectDetailsOverlay = document.getElementById('projectDetailsOverlay');
const invoicesSection = document.getElementById('invoicesSection');

// Add click event listeners to table rows
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('#projectsTable tbody tr');
    
    tableRows.forEach(row => {
        // Add click event to the entire row
        row.addEventListener('click', function(e) {
            // Check if the click was on an edit or delete button
            if (!e.target.closest('.button-group')) {
                const projectId = JSON.parse(this.dataset.project).id;
                showProjectDetails(projectId);
            }
        });
    });
});

function openCreateModal(){
    document.getElementById('modalTitle').innerText = 'Create Project';
    projectForm.reset();
    submitBtn.name = 'create_project';
    progressDiv.style.display = 'none';
    modalBg.style.display = 'flex';
}

function openEditModal(id){
    document.getElementById('modalTitle').innerText = 'Edit Project';
    submitBtn.name = 'edit_project';
    const tr = [...document.querySelectorAll('#projectsTable tbody tr')].find(r=>JSON.parse(r.dataset.project).id==id);
    const data = JSON.parse(tr.dataset.project);
    document.getElementById('project_id').value = data.id;
    document.getElementById('title').value = data.title;
    document.getElementById('description').value = data.description;
    document.getElementById('team_lead').value = data.team_lead;
    document.getElementById('members').value = data.members;
    document.getElementById('client').value = data.client;
    document.getElementById('budget').value = data.budget;
    document.getElementById('status').value = data.status;
    document.getElementById('assigned_date').value = data.assigned_date;
    document.getElementById('due_date').value = data.due_date;
    document.getElementById('progress').value = data.progress;
    toggleProgressInput();
    modalBg.style.display = 'flex';
}

function closeModal(){modalBg.style.display='none';}

function toggleProgressInput(){
    const status = document.getElementById('status').value;
    progressDiv.style.display = (status=='In Progress') ? 'block' : 'none';
}

function showProjectDetails(id){
    const tr = [...document.querySelectorAll('#projectsTable tbody tr')].find(r=>JSON.parse(r.dataset.project).id==id);
    const data = JSON.parse(tr.dataset.project);
    
    // Set the project details
    document.getElementById('projectDetailsTitle').innerText = data.title;
    document.getElementById('detailsTitle').innerText = data.title;
    document.getElementById('detailsDescription').innerText = data.description;
    document.getElementById('detailsStatus').innerText = data.status;
    document.getElementById('detailsTeamLead').innerText = data.team_lead;
    document.getElementById('detailsMembers').innerText = data.members;
    document.getElementById('detailsClient').innerText = data.client;
    document.getElementById('detailsBudget').innerText = '$' + parseFloat(data.budget).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('detailsAssignedDate').innerText = data.assigned_date;
    document.getElementById('detailsDueDate').innerText = data.due_date;
    
    // Set progress
    const progress = data.progress || 0;
    document.getElementById('detailsProgressText').innerText = progress + '%';
    document.getElementById('detailsProgressBar').style.width = progress + '%';
    
    // Set progress bar color based on status
    if(data.status === 'Completed') {
        document.getElementById('detailsProgressBar').style.background = '#27ae60';
    } else if(data.status === 'In Progress') {
        document.getElementById('detailsProgressBar').style.background = '#f39c12';
    } else {
        document.getElementById('detailsProgressBar').style.background = '#bbb';
    }
    
    // Show or hide invoices section based on project status
    if(data.status === 'Not Started') {
        invoicesSection.style.display = 'none';
    } else {
        invoicesSection.style.display = 'block';
        
        // For demonstration, adding some sample invoice data
        // In a real application, you would fetch this from the database
        const invoicesHtml = `
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1001</td>
                        <td>$${(data.budget * 0.46).toFixed(2)}</td>
                        <td>${data.status === 'Completed' ? 'Paid' : 'Pending'}</td>
                    </tr>
                    <tr>
                        <td>1002</td>
                        <td>$${(data.budget * 0.54).toFixed(2)}</td>
                        <td>${data.status === 'Completed' ? 'Paid' : 'Pending'}</td>
                    </tr>
                </tbody>
            </table>
        `;
        document.getElementById('detailsInvoices').innerHTML = invoicesHtml;
    }
    
    // Show the prompt
    projectDetailsPrompt.style.display = 'block';
    projectDetailsOverlay.style.display = 'block';
}

function closeProjectDetails(){
    projectDetailsPrompt.style.display = 'none';
    projectDetailsOverlay.style.display = 'none';
}

// Close project details when clicking outside
projectDetailsOverlay.addEventListener('click', closeProjectDetails);
</script>
</body>
</html>