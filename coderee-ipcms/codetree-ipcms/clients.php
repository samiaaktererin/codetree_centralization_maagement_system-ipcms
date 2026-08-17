<?php
require_once 'config.php'; // $pdo connection

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO clients (name, company, email, phone, address, website, status) 
                               VALUES (:name, :company, :email, :phone, :address, :website, :status)");
        $stmt->execute([
            ':name' => $_POST['name'],
            ':company' => $_POST['company'],
            ':email' => $_POST['email'],
            ':phone' => $_POST['phone'],
            ':address' => $_POST['address'],
            ':website' => $_POST['website'],
            ':status' => $_POST['status']
        ]);
        header("Location: clients.php");
        exit();
    }

    if ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE clients SET name=:name, company=:company, email=:email, phone=:phone, address=:address, website=:website, status=:status WHERE id=:id");
        $stmt->execute([
            ':name' => $_POST['name'],
            ':company' => $_POST['company'],
            ':email' => $_POST['email'],
            ':phone' => $_POST['phone'],
            ':address' => $_POST['address'],
            ':website' => $_POST['website'],
            ':status' => $_POST['status'],
            ':id' => intval($_POST['id'])
        ]);
        header("Location: clients.php");
        exit();
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id=:id");
        $stmt->execute([':id' => intval($_POST['id'])]);
        header("Location: clients.php");
        exit();
    }
}

// Fetch clients
$searchQuery = $_GET['search'] ?? '';
if($searchQuery){
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE name LIKE :q OR company LIKE :q ORDER BY id DESC");
    $stmt->execute([':q' => "%$searchQuery%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM clients ORDER BY id DESC");
}
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Client Management - CODETREE</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{
  --sidebar-w:220px;
  --bg:#f0f3f7;
  --panel:#f1f4f5;
  --accent:#0f6b67;
  --muted:#9aa6a8;
}
html,body{height:100%;margin:0;font-family:Segoe UI,Arial;color:#1f2b2b;background:var(--bg);}
.main { margin-left: var(--sidebar-w); display:flex; height:100vh; align-items:stretch; }
.content { flex:1; padding:30px 40px; box-sizing:border-box; display:flex; gap:24px; }
.left { flex:2; }
.right { width:340px; background:var(--panel); padding:22px; border-left:1px solid rgba(0,0,0,0.06); box-sizing:border-box; }
.card-panel { background: #fff; border-radius:12px; padding:18px; margin-bottom:18px; box-shadow:0 2px 6px rgba(0,0,0,0.05); }
h2,h3{margin:0 0 16px 0; color:#213434; font-weight:700;}
.toolbar { display:flex; gap:12px; align-items:center; margin-bottom:12px; }
.search-form { flex:1; display:flex; gap:10px; }
.search-input { flex:1; padding:8px 12px; border-radius:6px; border:1px solid #ccc; }
.btn-new { 
  background:#014d44; 
  color:#fff; 
  border:none; padding:8px 12px; border-radius:6px; cursor:pointer; transition:0.3s; }
.btn-new:hover { background:#02675b; }
table { width:100%; border-collapse:collapse; }
th { text-align:left; color:var(--muted); font-weight:600; font-size:0.9rem; padding-bottom:8px; }
td { padding:10px 8px; vertical-align:middle; }
.status { padding:6px 10px; border-radius:8px; font-weight:600; font-size:0.8rem; display:inline-block }
.status.Active{ background:#e6f8f6; color:#0d8f7f; }
.status.Inactive{ background:#ffecec; color:#d25b5b; }
.services { display:flex; gap:20px; margin-top:22px; }
.service-box { background: #fff; border-radius:12px; padding:18px; width:160px; text-align:center; box-shadow:0 2px 6px rgba(0,0,0,0.05); transition:transform .2s; }
.service-box:hover { transform:translateY(-5px); }
.service-box i { font-size:2rem; color:#0f6b67; margin-bottom:10px; }
.details h4 { margin-bottom:8px; }
.details .muted { color:var(--muted); font-size:.9rem; margin-bottom:12px; }
.details .item { margin-bottom:12px; }
#btnBack {
  background: #014d44; 
  color:#fff; 
  border:none; 
  padding:6px 12px; 
  border-radius:6px; 
  font-size:0.85rem;
  cursor:pointer;
  transition:0.3s;
}
#btnBack:hover { background:#02675b; }
.btn-edit { background:#0f6b67; color:white; }
.btn-edit:hover { background:#0d5c59; transform:translateY(-1px); box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.btn-delete { background:#dc3545; color:white; }
.btn-delete:hover { background:#c82333; transform:translateY(-1px); box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.sidebar {
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
.sidebar .brand { text-align:center; margin-bottom:40px; }
.sidebar .brand img { width:120px; display:block; margin:0 auto; }
.sidebar ul.navlist{list-style:none; padding:0; flex-grow:1;}
.sidebar ul.navlist li{margin-bottom:18px;}
.sidebar ul.navlist a{
  color:#dfeff0;
  text-decoration:none;
  display:block;
  padding:10px 14px;
  border-radius:6px;
  transition:0.3s;
  font-size:16px;
}
.sidebar ul.navlist a:hover{background: rgba(255,255,255,0.1);}
@media (max-width:1000px){
  .main{flex-direction:column}
  .right{width:100%; border-left:none; border-top:1px solid rgba(0,0,0,0.06)}
  .content{flex-direction:column}
  .services{flex-wrap:wrap; justify-content:center}
}
</style>
</head>
<body>

<nav class="sidebar">
  <div class="brand"><img src="logo.png" alt="CODETREE" /></div>
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

<div class="main">
<div class="content">
<div class="left">
<h2>Client Management</h2>

<!-- Search & Add Client -->
<div class="card-panel">
  <div class="toolbar">
    <form method="get" class="search-form">
      <input type="text" name="search" class="search-input" placeholder="Search clients..." value="<?= htmlspecialchars($searchQuery) ?>">
      <button class="btn btn-secondary" type="submit">Search</button>
    </form>
    <button class="btn-new" id="btnNew">New Client</button>
  </div>

  <div style="overflow:auto">
    <table class="table table-borderless" id="clientsTable">
      <thead>
        <tr><th>Name</th><th>Company</th><th>Email</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach($clients as $c): ?>
        <tr class="client-row" data-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" data-company="<?= htmlspecialchars($c['company']) ?>" data-email="<?= htmlspecialchars($c['email']) ?>" data-phone="<?= htmlspecialchars($c['phone']) ?>" data-address="<?= htmlspecialchars($c['address']) ?>" data-website="<?= htmlspecialchars($c['website']) ?>" data-status="<?= $c['status'] ?>">
          <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
          <td><?= htmlspecialchars($c['company']) ?></td>
          <td><?= htmlspecialchars($c['email']) ?></td>
          <td><span class="status <?= $c['status'] ?>"><?= $c['status'] ?></span></td>
          <td>
            <button class="btn btn-sm btn-primary btn-edit">Edit</button>
            <button class="btn btn-sm btn-danger btn-delete">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<h3 style="margin-top:18px">Our Services</h3>
<div class="services">
  <div class="service-box"><i class="bi bi-laptop"></i><div>Web Development</div></div>
  <div class="service-box"><i class="bi bi-phone"></i><div>Mobile Application</div></div>
  <div class="service-box"><i class="bi bi-palette"></i><div>UI/UX Design</div></div>
  <div class="service-box"><i class="bi bi-cloud"></i><div>Cloud Solutions</div></div>
</div>
</div>

<!-- Right Details Panel -->
<aside class="right details" id="detailPanel">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
    <h4 style="margin:0">Client Details</h4>
    <button id="btnBack" style="display:none">← Back</button>
  </div>
  <div class="muted" id="noClientMsg">No client selected. Click a row to view details.</div>
  <div id="detailContent" style="display:none">
    <div class="item"><strong id="dName"></strong></div>
    <div class="item muted"><strong>Contact Information</strong></div>
    <div class="item"><b>Email:</b> <span id="dEmail"></span></div>
    <div class="item"><b>Phone:</b> <span id="dPhone"></span></div>
    <div class="item"><b>Address:</b> <span id="dAddress"></span></div>
    <div class="item"><b>Website:</b> <span id="dWebsite"></span></div>
    <div class="item"><b>Company:</b> <span id="dCompany"></span></div>
  </div>
</aside>
</div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="clientModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="clientForm" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Client</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="clientId">
          <input type="hidden" name="action" id="clientAction">
          <div class="mb-2"><label>Name</label><input type="text" class="form-control" name="name" id="clientName" required></div>
          <div class="mb-2"><label>Company</label><input type="text" class="form-control" name="company" id="clientCompany"></div>
          <div class="mb-2"><label>Email</label><input type="email" class="form-control" name="email" id="clientEmail"></div>
          <div class="mb-2"><label>Phone</label><input type="text" class="form-control" name="phone" id="clientPhone"></div>
          <div class="mb-2"><label>Address</label><textarea class="form-control" name="address" id="clientAddress"></textarea></div>
          <div class="mb-2"><label>Website</label><input type="text" class="form-control" name="website" id="clientWebsite"></div>
          <div class="mb-2"><label>Status</label>
            <select class="form-control" name="status" id="clientStatus">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Show client details
document.querySelectorAll('.client-row').forEach(row=>{
  row.addEventListener('click', ()=>{
    document.getElementById('dName').textContent = row.dataset.name;
    document.getElementById('dEmail').textContent = row.dataset.email;
    document.getElementById('dPhone').textContent = row.dataset.phone;
    document.getElementById('dAddress').textContent = row.dataset.address;
    document.getElementById('dWebsite').textContent = row.dataset.website;
    document.getElementById('dCompany').textContent = row.dataset.company;

    document.getElementById('noClientMsg').style.display = 'none';
    document.getElementById('detailContent').style.display = 'block';
    document.getElementById('btnBack').style.display = 'inline-block';
  });
});

// Back button
document.getElementById('btnBack').addEventListener('click', ()=>{
  document.getElementById('detailContent').style.display = 'none';
  document.getElementById('noClientMsg').style.display = 'block';
  document.getElementById('btnBack').style.display = 'none';
});

// Add/Edit Modal
let modal = new bootstrap.Modal(document.getElementById('clientModal'));
document.getElementById('btnNew').addEventListener('click', ()=>{
  document.getElementById('clientForm').reset();
  document.getElementById('clientAction').value = 'add';
  modal.show();
});

document.querySelectorAll('.btn-edit').forEach(btn=>{
  btn.addEventListener('click', e=>{
    e.stopPropagation();
    let row = btn.closest('tr');
    document.getElementById('clientId').value = row.dataset.id;
    document.getElementById('clientName').value = row.dataset.name;
    document.getElementById('clientCompany').value = row.dataset.company;
    document.getElementById('clientEmail').value = row.dataset.email;
    document.getElementById('clientPhone').value = row.dataset.phone;
    document.getElementById('clientAddress').value = row.dataset.address;
    document.getElementById('clientWebsite').value = row.dataset.website;
    document.getElementById('clientStatus').value = row.dataset.status;
    document.getElementById('clientAction').value = 'edit';
    modal.show();
  });
});

// Delete
document.querySelectorAll('.btn-delete').forEach(btn=>{
  btn.addEventListener('click', e=>{
    e.stopPropagation();
    if(confirm('Are you sure you want to delete this client?')){
      let row = btn.closest('tr');
      let form = document.createElement('form');
      form.method = 'post';
      form.innerHTML = `<input type="hidden" name="id" value="${row.dataset.id}">
                        <input type="hidden" name="action" value="delete">`;
      document.body.appendChild(form);
      form.submit();
    }
  });
});
</script>
</body>
</html>
