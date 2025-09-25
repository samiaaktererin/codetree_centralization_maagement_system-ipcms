<?php
require_once 'config.php'; // Make sure config.php initializes $pdo and session

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' || $action === 'edit') {
            $name = $_POST['name'];
            $role = $_POST['role'];
            $skills = $_POST['skills'];
            $availability = $_POST['availability'];
            $workload = intval($_POST['workload']);
            $projects = $_POST['projects'];
            $team_id = !empty($_POST['team_id']) ? intval($_POST['team_id']) : null;

            $projects_array = array_map('trim', explode(',', $projects));
            $projects_json = json_encode($projects_array);

            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO team_members (name, role, skills, availability, workload, projects, team_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $role, $skills, $availability, $workload, $projects_json, $team_id]);
            } else {
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("UPDATE team_members SET name=?, role=?, skills=?, availability=?, workload=?, projects=?, team_id=? WHERE id=?");
                $stmt->execute([$name, $role, $skills, $availability, $workload, $projects_json, $team_id, $id]);
            }
            header("Location: teams.php");
            exit();
        }

        if ($action === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM team_members WHERE id=?");
            $stmt->execute([$id]);
            header("Location: teams.php");
            exit();
        }

        if ($action === 'add_team' || $action === 'edit_team') {
            $team_name = $_POST['team_name'];
            $team_leader = $_POST['team_leader'];
            $team_members = $_POST['team_members'];

            $members_array = array_map('trim', explode(',', $team_members));
            $members_json = json_encode($members_array);

            if ($action === 'add_team') {
                $stmt = $pdo->prepare("INSERT INTO teams (name, leader, members) VALUES (?, ?, ?)");
                $stmt->execute([$team_name, $team_leader, $members_json]);
            } else {
                $team_id = intval($_POST['team_id']);
                $stmt = $pdo->prepare("UPDATE teams SET name=?, leader=?, members=? WHERE id=?");
                $stmt->execute([$team_name, $team_leader, $members_json, $team_id]);
            }
            header("Location: teams.php");
            exit();
        }

        if ($action === 'delete_team') {
            $team_id = intval($_POST['team_id']);
            // Remove team association from members
            $stmt = $pdo->prepare("UPDATE team_members SET team_id = NULL WHERE team_id=?");
            $stmt->execute([$team_id]);

            $stmt = $pdo->prepare("DELETE FROM teams WHERE id=?");
            $stmt->execute([$team_id]);
            header("Location: teams.php");
            exit();
        }
    }
}

// Fetch team members
$team_members = $pdo->query("SELECT tm.*, t.name as team_name FROM team_members tm LEFT JOIN teams t ON tm.team_id = t.id ORDER BY tm.name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch teams for dropdown
$teams = $pdo->query("SELECT id, name FROM teams")->fetchAll(PDO::FETCH_ASSOC);
?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Team - CODETREE</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root{--sidebar-w:220px;--bg:#e9eef0;--panel:#f1f4f5;--accent:#0f6b67;--muted:#9aa6a8}
    html,body{height:100%;margin:0;font-family:Segoe UI,Arial;color:#1f2b2b;background:var(--bg);}
    .main { margin-left:var(--sidebar-w); display:flex; min-height:100vh; padding:30px; gap:24px; box-sizing:border-box; }
    .left { flex:2; }
    .right { width:320px; background:var(--panel); padding:22px; border-left:1px solid rgba(0,0,0,0.06) }
    .card { background:#fff; border-radius:10px; padding:18px; margin-bottom:18px; }
    h2,h3{ margin-bottom:14px; }
    table th { text-align:left; color:var(--muted); font-weight:600; }
    .chip { display:inline-block; padding:6px 10px; border-radius:8px; background:#f1f7f6; font-weight:600; font-size:.85rem; }
    .team-grid { display:flex; gap:18px; margin-top:12px; flex-wrap:wrap; }
    .team-card { background:#fff; padding:16px; border-radius:10px; width:220px; box-shadow:0 6px 18px rgba(15,107,103,0.03) }
    .progress-bar { height:10px; border-radius:8px; background:#e6f0ef; overflow:hidden; }
    .progress-bar > span { display:block; height:100%; background:#0f6b67; }
    .muted { color:var(--muted); font-size:.9rem; }
    .action-btns { display: flex; gap: 8px; }
    .action-btns { display:flex; gap:8px; }
.btn-action { padding:6px 10px; border:none; border-radius:6px; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:4px; transition:all 0.2s; }
.btn-edit { background:#0f6b67; color:white; }
.btn-edit:hover { background:#0d5c59; transform:translateY(-1px); box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.btn-delete { background:#dc3545; color:white; }
.btn-delete:hover { background:#c82333; transform:translateY(-1px); box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.btn-edit-team { background:#053634ff; color:white; }
.btn-edit-team:hover { background:#12615eff; transform:translateY(-1px); box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.btn-delete-team { background:#dc3545; color:white; }
.btn-delete-team:hover { background:#c82333; transform:translateY(-1px); box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.btn-add { 
    background: #085219ff; 
    color: white; 
    padding: 8px 16px; 
    border-radius: 8px; 
    border: none; 
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-add:hover { 
    background: #119b3fff; 
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

    .modal-content { border-radius:10px; }
    .form-group { margin-bottom:15px; }
    .form-label { font-weight:600; margin-bottom:5px; }
    .success-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        animation: slideIn 0.3s, fadeOut 0.5s 2.5s forwards;
    }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
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

<style>
.sidebar {
  width: 220px;
  min-height: 100vh;
  background:#0f6b67;
  color:#fff;
  padding:24px 16px;
  box-sizing:border-box;
  position:fixed;
  left:0; top:0;
}
.brand img { width:120px; display:block; margin:6px auto 20px; }
.navlist { list-style:none; padding:0; margin:0; }
.navlist li { margin:14px 0; }
.navlist a { color:#dfeff0; text-decoration:none; display:block; padding:8px 10px; border-radius:6px; }
.navlist a:hover { background: rgba(255,255,255,0.05); }
</style>

<!-- Success Alert -->
<?php if (isset($_GET['success'])): 
    $success_messages = [
        'added' => 'Team member successfully added!',
        'updated' => 'Team member successfully updated!',
        'deleted' => 'Team member successfully deleted!',
        'team_added' => 'Team successfully added!',
        'team_updated' => 'Team successfully updated!',
        'team_deleted' => 'Team successfully deleted!'
    ];
    $message = $success_messages[$_GET['success']] ?? 'Operation completed successfully!';
?>
<div class="success-alert alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Main Content -->
<div class="main">
  <div class="left">
    <h2>Team & Resource Management</h2>

    <!-- Search + Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center mb-2">
  <h3>Members</h3>
  <button class="btn-add" id="addMemberBtn">
    <i class="fas fa-plus"></i> Add Member
  </button>
</div>


      <table class="table table-borderless">
        <thead>
          <tr>
            <th>Name</th>
            <th>Role</th>
            <th>Skills</th>
            <th>Availability</th>
            <th>Team</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="teamBody">
          <?php foreach ($team_members as $member): 
            $projects = json_decode($member['projects'] ?? '[]', true) ?: [];
          ?>
          <tr class="member-row" 
              data-id="<?php echo $member['id']; ?>"
              data-name="<?php echo htmlspecialchars($member['name']); ?>" 
              data-role="<?php echo htmlspecialchars($member['role']); ?>" 
              data-skills="<?php echo htmlspecialchars($member['skills']); ?>"
              data-availability="<?php echo htmlspecialchars($member['availability']); ?>" 
              data-workload="<?php echo $member['workload']; ?>" 
              data-projects='<?php echo json_encode($projects); ?>'
              data-team-id="<?php echo $member['team_id']; ?>">
            <td><strong><?php echo htmlspecialchars($member['name']); ?></strong></td>
            <td><?php echo htmlspecialchars($member['role']); ?></td>
            <td><?php echo htmlspecialchars($member['skills']); ?></td>
            <td><span class="chip"><?php echo htmlspecialchars($member['availability']); ?></span></td>
            <td><?php echo htmlspecialchars($member['team_name'] ?? 'No Team'); ?></td>
            <td>
              <div class="action-btns">
                <button class="btn-action btn-edit" data-id="<?php echo $member['id']; ?>">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn-action btn-delete" data-id="<?php echo $member['id']; ?>">
                  <i class="fas fa-trash"></i> Delete
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Teams Section - UPDATED -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center mb-2">
  <h3>Teams</h3>
  <button class="btn-add" id="addTeamBtn">
    <i class="fas fa-plus"></i> Add Team
  </button>
</div>
      <div class="team-grid" id="teamsGrid">
        <?php
        // Fetch teams with their members
        $conn = new mysqli($servername, $username, $password, $dbname);
        $teams_result = $conn->query("SELECT * FROM teams ORDER BY name");
        
        if ($teams_result->num_rows > 0) {
            while($team = $teams_result->fetch_assoc()) {
                $members_array = json_decode($team['members'] ?? '[]', true) ?: [];
                echo '<div class="team-card" data-id="' . $team['id'] . '" data-name="' . htmlspecialchars($team['name']) . '" data-leader="' . htmlspecialchars($team['leader']) . '" data-members=\'' . json_encode($members_array) . '\'>';
                echo '<h5>' . htmlspecialchars($team['name']) . '</h5>';
                echo '<div style="margin-top:6px"><strong>' . htmlspecialchars($team['leader']) . '</strong><div>Team Lead</div></div>';
                if (!empty($members_array)) {
                    echo '<div class="muted" style="margin-top:8px">Members: ' . htmlspecialchars(implode(', ', $members_array)) . '</div>';
                }
                echo '<div class="action-btns">';
                echo '<button class="btn-action btn-edit-team"><i class="fas fa-edit"></i> Edit</button>';
                echo '<button class="btn-action btn-delete-team"><i class="fas fa-trash"></i> Delete</button>';
                echo '</div>';
                echo '</div>';
            }
        }
        $conn->close();
        ?>
      </div>
    </div>

  </div>

  <!-- Member Details -->
  <aside class="right" id="memberPanel">
    <h4>Team Member Details</h4>
    <div class="muted">Click a member to see details</div>

    <div id="memberDetails" style="display:none; margin-top:12px; position:relative;">
      <button id="backBtn" class="btn btn-sm" 
              style="position:absolute; top:0; right:0; background:#0f6b67;color:#fff;border-radius:6px;">
        ← Back
      </button>

      <div style="margin-top:30px"><strong id="mName"></strong></div>
      <div class="muted" style="margin-top:8px"><strong>Contact Information</strong></div>
      <div style="margin-top:8px"><b>Role:</b> <span id="mRole"></span></div>
      <div style="margin-top:6px"><b>Skills:</b> <span id="mSkills"></span></div>
      <div style="margin-top:6px"><b>Workload:</b> 
        <div class="progress-bar" style="margin-top:6px"><span id="mWork" style="width:0%"></span></div>
      </div>
      <div style="margin-top:12px"><b>Assigned Projects:</b>
        <ul id="mProjects"></ul>
      </div>
    </div>
  </aside>
</div>

<!-- Add/Edit Member Modal -->
<div class="modal fade" id="memberModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Team Member</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="memberForm">
        <div class="modal-body">
          <input type="hidden" name="action" id="formAction" value="add">
          <input type="hidden" name="id" id="memberId">
          <div class="form-group">
            <label class="form-label" for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="role">Role</label>
            <input type="text" class="form-control" id="role" name="role" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="skills">Skills</label>
            <input type="text" class="form-control" id="skills" name="skills" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="availability">Availability</label>
            <select class="form-control" id="availability" name="availability" required>
              <option value="Available">Available</option>
              <option value="Busy">Busy</option>
              <option value="Part-time">Part-time</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="workload">Workload (%)</label>
            <input type="number" class="form-control" id="workload" name="workload" min="0" max="100" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="projects">Projects (comma-separated)</label>
            <input type="text" class="form-control" id="projects" name="projects" placeholder="Project 1, Project 2">
          </div>
          <div class="form-group">
            <label class="form-label" for="team_id">Team</label>
            <select class="form-control" id="team_id" name="team_id">
              <option value="">No Team</option>
              <?php foreach ($teams as $team): ?>
                <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="deleteForm">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
        <div class="modal-body">
          Are you sure you want to delete <strong id="deleteName"></strong>?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add/Edit Team Modal -->
<div class="modal fade" id="teamModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="teamModalTitle">Add Team</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="teamForm">
        <div class="modal-body">
          <input type="hidden" name="action" id="teamFormAction" value="add_team">
          <input type="hidden" name="team_id" id="teamId">
          <div class="form-group">
            <label class="form-label" for="team_name">Team Name</label>
            <input type="text" class="form-control" id="team_name" name="team_name" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="team_leader">Team Leader</label>
            <input type="text" class="form-control" id="team_leader" name="team_leader">
          </div>
          <div class="form-group">
            <label class="form-label" for="team_members">Members (comma-separated)</label>
            <input type="text" class="form-control" id="team_members" name="team_members" placeholder="Member1, Member2">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Team Modal -->
<div class="modal fade" id="deleteTeamModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="deleteTeamForm">
        <input type="hidden" name="action" value="delete_team">
        <input type="hidden" name="team_id" id="deleteTeamId">
        <div class="modal-body">
          Are you sure you want to delete <strong id="deleteTeamName"></strong>?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// DOM elements
const memberModal = new bootstrap.Modal(document.getElementById('memberModal'));
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
const teamModal = new bootstrap.Modal(document.getElementById('teamModal'));
const deleteTeamModal = new bootstrap.Modal(document.getElementById('deleteTeamModal'));
const modalTitle = document.getElementById('modalTitle');
const formAction = document.getElementById('formAction');
const addMemberBtn = document.getElementById('addMemberBtn');

// Add new member
addMemberBtn.addEventListener('click', () => {
  formAction.value = 'add';
  modalTitle.textContent = 'Add Team Member';
  document.getElementById('memberForm').reset();
  document.getElementById('memberId').value = '';
  memberModal.show();
});

// Edit existing member
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const row = btn.closest('.member-row');
    
    formAction.value = 'edit';
    modalTitle.textContent = 'Edit Team Member';
    document.getElementById('memberId').value = row.dataset.id;
    document.getElementById('name').value = row.dataset.name;
    document.getElementById('role').value = row.dataset.role;
    document.getElementById('skills').value = row.dataset.skills;
    document.getElementById('availability').value = row.dataset.availability;
    document.getElementById('workload').value = row.dataset.workload;
    document.getElementById('team_id').value = row.dataset.teamId || '';
    
    // Set projects
    try {
      const projects = JSON.parse(row.dataset.projects || '[]');
      document.getElementById('projects').value = projects.join(', ');
    } catch (e) {
      document.getElementById('projects').value = '';
    }
    
    memberModal.show();
  });
});

// Delete member
document.querySelectorAll('.btn-delete').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const row = btn.closest('.member-row');
    document.getElementById('deleteId').value = row.dataset.id;
    document.getElementById('deleteName').textContent = row.dataset.name;
    deleteModal.show();
  });
});

// Show member details
document.querySelectorAll('.member-row').forEach(row => {
  row.addEventListener('click', (e) => {
    // Don't trigger if clicking on action buttons
    if (e.target.closest('.action-btns')) return;
    
    const name = row.dataset.name;
    const role = row.dataset.role;
    const skills = row.dataset.skills;
    const workload = row.dataset.workload;
    
    let projects = [];
    try {
      projects = JSON.parse(row.dataset.projects || '[]');
    } catch (e) {
      projects = [];
    }

    document.getElementById('mName').textContent = name;
    document.getElementById('mRole').textContent = role;
    document.getElementById('mSkills').textContent = skills;
    document.getElementById('mWork').style.width = workload + '%';

    const ul = document.getElementById('mProjects');
    ul.innerHTML = '';
    projects.forEach(p => {
      const li = document.createElement('li');
      li.textContent = p;
      ul.appendChild(li);
    });

    document.querySelector('#memberPanel .muted').style.display = 'none';
    document.getElementById('memberDetails').style.display = 'block';
  });
});

// Back button logic
document.getElementById('backBtn').addEventListener('click', () => {
  document.getElementById('memberDetails').style.display = 'none';
  document.querySelector('#memberPanel .muted').style.display = 'block';
});

// Team functionality
document.getElementById('addTeamBtn').addEventListener('click', () => {
  document.getElementById('teamFormAction').value = 'add_team';
  document.getElementById('teamModalTitle').textContent = 'Add Team';
  document.getElementById('teamForm').reset();
  document.getElementById('teamId').value = '';
  teamModal.show();
});

document.querySelectorAll('.btn-edit-team').forEach(btn => {
  btn.addEventListener('click', e => {
    e.stopPropagation();
    const card = btn.closest('.team-card');
    document.getElementById('teamFormAction').value = 'edit_team';
    document.getElementById('teamModalTitle').textContent = 'Edit Team';
    document.getElementById('teamId').value = card.dataset.id;
    document.getElementById('team_name').value = card.dataset.name;
    document.getElementById('team_leader').value = card.dataset.leader;
    try {
      const members = JSON.parse(card.dataset.members || '[]');
      document.getElementById('team_members').value = members.join(', ');
    } catch (err) {
      document.getElementById('team_members').value = '';
    }
    teamModal.show();
  });
});

document.querySelectorAll('.btn-delete-team').forEach(btn => {
  btn.addEventListener('click', e => {
    e.stopPropagation();
    const card = btn.closest('.team-card');
    document.getElementById('deleteTeamId').value = card.dataset.id;
    document.getElementById('deleteTeamName').textContent = card.dataset.name;
    deleteTeamModal.show();
  });
});
</script>
</body>
</html>