<?php
// billing.php
session_start();

// Require login (adjust if your app uses a different check)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* ---------- DB Connection (PDO) ---------- */
$host = "127.0.0.1";
$db   = "codetree";
$user = "root";
$pass = "";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

/* ---------- POST handlers: add / edit / delete ---------- */
$message = '';
$message_type = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op = $_POST['operation'] ?? '';

    if ($op === 'add') {
        $project_id   = (int)($_POST['project_id'] ?? 0);
        $invoice_no   = trim($_POST['invoice_number'] ?? '');
        $amount       = (float)($_POST['amount'] ?? 0);
        $issue_date   = $_POST['issue_date'] ?? null;
        $due_date     = $_POST['due_date'] ?? null;
        $status       = $_POST['status'] ?? 'Pending';
        $paid_amount  = (float)($_POST['paid_amount'] ?? 0);

        // Validate inputs
        if (empty($invoice_no) || $project_id <= 0 || $amount <= 0) {
            $message = 'Please fill all required fields correctly.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO invoices (project_id, invoice_number, amount, issue_date, due_date, status, paid_amount) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$project_id, $invoice_no, $amount, $issue_date, $due_date, $status, $paid_amount]);
                
                $message = 'Invoice created successfully!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Error creating invoice: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }

    if ($op === 'edit') {
        $id           = (int)($_POST['id'] ?? 0);
        $project_id   = (int)($_POST['project_id'] ?? 0);
        $invoice_no   = trim($_POST['invoice_number'] ?? '');
        $amount       = (float)($_POST['amount'] ?? 0);
        $issue_date   = $_POST['issue_date'] ?? null;
        $due_date     = $_POST['due_date'] ?? null;
        $status       = $_POST['status'] ?? 'Pending';
        $paid_amount  = (float)($_POST['paid_amount'] ?? 0);

        // Validate inputs
        if (empty($invoice_no) || $project_id <= 0 || $amount <= 0 || $id <= 0) {
            $message = 'Please fill all required fields correctly.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE invoices SET project_id=?, invoice_number=?, amount=?, issue_date=?, due_date=?, status=?, paid_amount=? WHERE id=?");
                $stmt->execute([$project_id, $invoice_no, $amount, $issue_date, $due_date, $status, $paid_amount, $id]);
                
                $message = 'Invoice updated successfully!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Error updating invoice: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }

    if ($op === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = 'Invoice deleted successfully!';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error deleting invoice: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

/* ---------- Fetch data: invoices, projects, teams ---------- */
/* invoices joined with projects (to get project title, client, team_lead) */
$invoicesStmt = $pdo->prepare("
    SELECT i.*, p.title AS project_title, p.client AS client_name, p.team_lead AS project_team_lead
    FROM invoices i
    LEFT JOIN projects p ON i.project_id = p.id
    ORDER BY i.id DESC
");
$invoicesStmt->execute();
$invoices = $invoicesStmt->fetchAll();

/* projects list for the modal dropdown */
$projectsStmt = $pdo->prepare("SELECT * FROM projects ORDER BY title");
$projectsStmt->execute();
$projects = $projectsStmt->fetchAll();

/* teams list */
$teamsStmt = $pdo->prepare("SELECT * FROM teams ORDER BY name");
$teamsStmt->execute();
$teams = $teamsStmt->fetchAll();

/* prepare statement to fetch team invoices by matching projects.team_lead = teams.leader */
$teamInvoicesStmt = $pdo->prepare("
    SELECT i.*, p.client AS client_name, p.title AS project_title
    FROM invoices i
    LEFT JOIN projects p ON i.project_id = p.id
    WHERE p.team_lead = ?
    ORDER BY i.id DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Billing & Finance - CODETREE</title>
<style>
  * {margin:0; padding:0; box-sizing:border-box;}
  body {font-family: Arial, sans-serif; background:#f0f3f7; display:flex; color:#222;}

  /* Sidebar */
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
  .logo{text-align:center;margin-bottom:40px;}
  .logo img{width:120px;display:block;margin:0 auto;}
  .sidebar ul.navlist{list-style:none;padding:0;flex-grow:1;}
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

  /* Content Area */
  .content {margin-left:240px; padding:30px; width:calc(100% - 240px);}
  h1 {font-size:22px; font-weight:bold; margin-bottom:20px;}

  /* Header Bar */
  .header-bar {display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap: wrap; gap: 15px;}

  /* Buttons (keeps previous .btn look) */
  .btn {
    margin-left:8px;
    padding:8px 12px;
    border-radius:6px;
    border:none;
    cursor:pointer;
    background:#014d44; /* base */
    color:#fff;
    transition:0.18s;
    font-size:14px;
  }
  .btn:hover { transform:translateY(-1px); background:#02675b; }
  .btn-edit { background:#02766c; } /* edit */
  .btn-edit:hover { background:#05907f; }
  .btn-delete { background:#c0392b; } /* delete */
  .btn-delete:hover { background:#e74c3c; }
  .btn-search { background: #919fa8e3; } /* search button */
  .btn-search:hover { background: #5a6266ff; }

  /* Layout Grid */
  .main-grid {display:grid; grid-template-columns:2fr 1fr; gap:20px;}
  .card {background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.05); margin-bottom:20px;}

  /* Invoice Table */
  /* .table-header {display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;} */
  .table-actions {display: flex; gap: 10px; align-items: center;}
  .table-actions input {
    padding:8px 12px; border:1px solid #ccc; border-radius:6px; outline:none;
    width:400px;
  }
  table {width:100%; border-collapse:collapse;}
  th,td {padding:12px; font-size:14px; text-align:left; border-bottom:1px solid #eee;}
  tr[data-id] { cursor:pointer; } /* rows clickable for details */
  th {color:#666;}
  .amount-red {color:#c0392b; font-weight:bold;}
  .amount-green {color:#27ae60; font-weight:bold;}
  .badge {padding:2px 6px; font-size:11px; border-radius:6px; background:#ddd; color:#333;}

  /* Invoice Details */
  .invoice-header {display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;}
  .back-btn {background:#01332d; color:#fff; border:none; padding:6px 10px; border-radius:8px; font-size:13px; cursor:pointer;}
  .bar {width:100%; background:#e4e4e4; border-radius:6px; height:8px; margin-top:8px;}
  .bar-fill {background:#02766c; height:100%; border-radius:6px; width:80%;}

  /* Teams */
  .teams-grid {display:grid; grid-template-columns:1fr 1fr; gap:20px;}
  .team-box {background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.05);}
  .team-box h4 {margin-bottom:8px; font-size:15px;}
  .team-box p {font-size:14px; line-height:1.5;}
  .team-invoices {margin-top:10px;}
  .team-invoices table {width:100%; font-size:13px;}
  .team-invoices th, .team-invoices td {padding:6px; border-bottom:1px solid #eee;}

  /* Financial Summary */
  .summary {text-align:left; margin-top:10px;}
  .summary h3 {margin-bottom:10px;}
  .summary p {margin:6px 0; font-size:14px;}

  /* Modal */
  .modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); justify-content:center; align-items:center; z-index:999; }
  .modal .modal-box { background:#fff; border-radius:10px; padding:18px; width:420px; max-width:95%; box-shadow:0 10px 30px rgba(0,0,0,0.2); position:relative; }
  .modal .modal-box h3 { margin-bottom:12px; }
  .modal .modal-box label { display:block; margin-top:8px; font-size:13px; color:#333; }
  .modal .modal-box input[type="text"], .modal .modal-box input[type="number"], .modal .modal-box input[type="date"], .modal .modal-box select {
      width:100%; padding:8px 10px; border-radius:6px; border:1px solid #d0d6da; margin-top:6px;
  }
  .modal .modal-close { position:absolute; top:10px; right:10px; background:#c0392b; color:#fff; border:none; padding:6px 8px; border-radius:6px; cursor:pointer; }

  /* Message styles */
  .message {
    padding: 10px 15px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-weight: bold;
  }
  .message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }
  .message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }

  /* small helpers */
  .actions-cell { white-space:nowrap; }
</style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
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
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </div>

  <!-- Content -->
  <div class="content">
    <div class="header-bar">
      <h1>Billing & Finance</h1>
    </div>

    <?php if (!empty($message)): ?>
      <div class="message <?= $message_type ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <div class="main-grid">

      <!-- Left: Invoice Table -->
      <div>
        <div class="card">
          <div class="table-header">
        
            <div class="table-actions">
              <input type="text" id="searchInput" placeholder="Search Invoice (invoice #, client, project)">
              <button class="btn btn-search" id="searchBtn">Search</button>
              <button class="btn" id="createInvoiceBtn">Create New Invoice</button>
            </div>
          </div>
          <table id="invoiceTable">
            <thead>
              <tr><th>Invoice</th><th>Client</th><th>Project</th><th>Team</th><th>Amount</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php foreach ($invoices as $inv): 
                  // safe outputs
                  $id = (int)$inv['id'];
                  $invoice_no = htmlspecialchars($inv['invoice_number'] ?? '');
                  $client_name = htmlspecialchars($inv['client_name'] ?? '—');
                  $project_title = htmlspecialchars($inv['project_title'] ?? '—');
                  $team_lead = htmlspecialchars($inv['project_team_lead'] ?? '—');
                  $amount = number_format((float)($inv['amount'] ?? 0), 2);
                  $status = htmlspecialchars($inv['status'] ?? 'Pending');
                  $paid = number_format((float)($inv['paid_amount'] ?? 0), 2);
                  $issue_date = htmlspecialchars($inv['issue_date'] ?? '');
                  $due_date = htmlspecialchars($inv['due_date'] ?? '');
                  $project_id = (int)($inv['project_id'] ?? 0);
                  
                  // Determine status badge color
                  $status_class = '';
                  if ($status === 'Paid') $status_class = 'amount-green';
                  elseif ($status === 'Partial') $status_class = 'amount-red';
                  else $status_class = '';
              ?>
              <tr data-id="<?= $id ?>"
                  data-invoice="<?= $invoice_no ?>"
                  data-client="<?= $client_name ?>"
                  data-project="<?= $project_title ?>"
                  data-team="<?= $team_lead ?>"
                  data-project-id="<?= $project_id ?>"
                  data-amount="<?= $amount ?>"
                  data-date="<?= $issue_date ?>"
                  data-due="<?= $due_date ?>"
                  data-status="<?= $status ?>"
                  data-payment="<?= $paid ?>"
                  data-team-lead="<?= $team_lead ?>">
                <td><?= $invoice_no ?></td>
                <td><?= $client_name ?></td>
                <td><?= $project_title ?></td>
                <td><?= $team_lead ?></td>
                <td class="<?= $status === 'Paid' ? 'amount-green' : 'amount-red' ?>">$<?= $amount ?></td>
                <td><span class="<?= $status_class ?>"><?= $status ?></span></td>
                <td class="actions-cell">
                  <button type="button" class="btn btn-edit edit-btn">Edit</button>
                  <form method="post" style="display:inline;margin-left:8px;">
                    <input type="hidden" name="operation" value="delete">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this invoice?')">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Teams -->
        <h3 style="margin:20px 0;">Teams & Their Invoices</h3>
        <div class="teams-grid">
          <?php foreach ($teams as $team): 
              $teamName = $team['name'];
              $teamLeader = $team['leader'];
              // fetch invoices for this team by matching projects.team_lead = teams.leader
              $teamInvoicesStmt->execute([$teamLeader]);
              $teamInvoices = $teamInvoicesStmt->fetchAll();
              
              // Calculate team totals
              $teamTotal = 0;
              $teamPaid = 0;
              foreach ($teamInvoices as $ti) {
                  $teamTotal += (float)($ti['amount'] ?? 0);
                  if (($ti['status'] ?? '') === 'Paid') {
                      $teamPaid += (float)($ti['paid_amount'] ?? 0);
                  } elseif (($ti['status'] ?? '') === 'Partial') {
                      $teamPaid += (float)($ti['paid_amount'] ?? 0);
                  }
              }
          ?>
            <div class="team-box">
              <h4><?= htmlspecialchars($teamName) ?></h4>
              <p><strong>Leader:</strong> <?= htmlspecialchars($teamLeader) ?></p>
              <p><strong>Total Invoiced:</strong> $<?= number_format($teamTotal, 2) ?></p>
              <p><strong>Amount Collected:</strong> $<?= number_format($teamPaid, 2) ?></p>

              <div class="team-invoices">
                <table>
                  <thead><tr><th>Invoice</th><th>Project</th><th>Client</th><th>Amount</th><th>Status</th></tr></thead>
                  <tbody>
                    <?php if (count($teamInvoices) === 0): ?>
                      <tr><td colspan="5" style="color:#888; text-align:center;">No invoices for this team.</td></tr>
                    <?php else: ?>
                      <?php foreach ($teamInvoices as $ti):
                          $t_invoice = htmlspecialchars($ti['invoice_number'] ?? '');
                          $t_project = htmlspecialchars($ti['project_title'] ?? '—');
                          $t_client  = htmlspecialchars($ti['client_name'] ?? '—');
                          $t_amount  = number_format((float)($ti['amount'] ?? 0), 2);
                          $t_status  = htmlspecialchars($ti['status'] ?? 'Pending');
                          
                          // Determine status color
                          $t_status_class = '';
                          if ($t_status === 'Paid') $t_status_class = 'amount-green';
                          elseif ($t_status === 'Partial') $t_status_class = 'amount-red';
                      ?>
                        <tr>
                          <td><?= $t_invoice ?></td>
                          <td><?= $t_project ?></td>
                          <td><?= $t_client ?></td>
                          <td>$<?= $t_amount ?></td>
                          <td><span class="<?= $t_status_class ?>"><?= $t_status ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Right: Invoice Details & Summary -->
      <div>
        <div class="card" id="invoiceDetails" style="display:none;">
          <div class="invoice-header">
            <h3>Invoice Details</h3>
            <button class="back-btn" id="backBtn">Back</button>
          </div>
          <div id="detailsContent"></div>
        </div>

        <div class="card summary">
          <h3>Financial Summary</h3>
          <?php
            $total = 0.0; $paid = 0.0; $partial = 0.0;
            foreach ($invoices as $inv) {
                $amt = (float)($inv['amount'] ?? 0);
                $total += $amt;
                if (($inv['status'] ?? '') === 'Paid') $paid += (float)($inv['paid_amount'] ?? 0);
                if (($inv['status'] ?? '') === 'Partial') $partial += (float)($inv['paid_amount'] ?? 0);
            }
            $outstanding = $total - ($paid + $partial);
          ?>
          <p>Total Invoices: <b>$<?= number_format($total,2) ?></b></p>
          <p>Paid: <b>$<?= number_format($paid,2) ?></b></p>
          <p>Partial: <b>$<?= number_format($partial,2) ?></b></p>
          <p>Outstanding: <b>$<?= number_format($outstanding,2) ?></b></p>
        </div>
        
        <div class="card summary">
          <h3>Team Performance</h3>
          <?php
          $teamPerformanceStmt = $pdo->prepare("
            SELECT p.team_lead, 
                   COUNT(i.id) as invoice_count,
                   SUM(i.amount) as total_amount,
                   SUM(CASE WHEN i.status = 'Paid' THEN i.paid_amount ELSE 0 END) as paid_amount,
                   SUM(CASE WHEN i.status = 'Partial' THEN i.paid_amount ELSE 0 END) as partial_amount
            FROM invoices i
            LEFT JOIN projects p ON i.project_id = p.id
            GROUP BY p.team_lead
            ORDER BY total_amount DESC
          ");
          $teamPerformanceStmt->execute();
          $teamPerformance = $teamPerformanceStmt->fetchAll();
          
          foreach ($teamPerformance as $team):
            $teamName = htmlspecialchars($team['team_lead'] ?? 'Unassigned');
            $invoiceCount = (int)$team['invoice_count'];
            $totalAmount = number_format((float)$team['total_amount'], 2);
            $paidAmount = number_format((float)$team['paid_amount'] + (float)$team['partial_amount'], 2);
            $completionRate = $team['total_amount'] > 0 ? 
                round((((float)$team['paid_amount'] + (float)$team['partial_amount']) / (float)$team['total_amount']) * 100, 1) : 0;
          ?>
          <div style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
            <p><strong><?= $teamName ?></strong></p>
            <p>Invoices: <?= $invoiceCount ?></p>
            <p>Total: $<?= $totalAmount ?></p>
            <p>Collected: $<?= $paidAmount ?></p>
            <p>Completion: <?= $completionRate ?>%</p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Add / Edit Invoice -->
  <div class="modal" id="invoiceModal" aria-hidden="true">
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <button class="modal-close" id="modalClose" title="Close">X</button>
      <h3 id="modalTitle">Create Invoice</h3>

      <form id="invoiceForm" method="post">
        <input type="hidden" name="operation" id="operation" value="add">
        <input type="hidden" name="id" id="form_id" value="">

        <label for="invoice_number">Invoice Number</label>
        <input type="text" name="invoice_number" id="invoice_number" required>

        <label for="project_id">Project (choose project & team will auto-assign)</label>
        <select name="project_id" id="project_id" required>
          <option value="">-- Select Project --</option>
          <?php foreach ($projects as $p): 
            $team_lead = htmlspecialchars($p['team_lead'] ?? 'Unassigned');
          ?>
            <option value="<?= (int)$p['id'] ?>" data-team="<?= $team_lead ?>">
              <?= htmlspecialchars($p['title']) ?> (<?= htmlspecialchars($p['client']) ?>) - Team: <?= $team_lead ?>
            </option>
          <?php endforeach; ?>
        </select>
        
        <div id="team-info" style="margin-top: 5px; padding: 8px; background: #f0f3f7; border-radius: 4px; display: none;">
          <small>This invoice will be assigned to: <strong id="selected-team">None</strong></small>
        </div>

        <label for="amount">Amount</label>
        <input type="number" name="amount" id="amount" step="0.01" min="0" required>

        <label for="issue_date">Issue Date</label>
        <input type="date" name="issue_date" id="issue_date" required>

        <label for="due_date">Due Date</label>
        <input type="date" name="due_date" id="due_date" required>

        <label for="status">Status</label>
        <select name="status" id="status" required>
          <option value="Pending">Pending</option>
          <option value="Partial">Partial</option>
          <option value="Paid">Paid</option>
        </select>

        <label for="paid_amount">Paid Amount</label>
        <input type="number" name="paid_amount" id="paid_amount" step="0.01" min="0" required>

        <div style="margin-top:12px; display:flex; gap:8px;">
          <button type="submit" class="btn" id="saveBtn">Save</button>
          <button type="button" class="btn btn-delete" id="cancelBtn">Cancel</button>
        </div>
      </form>
    </div>
  </div>

<script>
/* ---------- Utility ---------- */
const $ = (sel, root=document) => root.querySelector(sel);
const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

/* ---------- Elements ---------- */
const invoiceTable = $('#invoiceTable');
const invoiceRows = $$('#invoiceTable tbody tr[data-id]');
const invoiceDetails = $('#invoiceDetails');
const detailsContent = $('#detailsContent');
const backBtn = $('#backBtn');
const searchInput = $('#searchInput');
const searchBtn = $('#searchBtn');

const invoiceModal = $('#invoiceModal');
const createInvoiceBtn = $('#createInvoiceBtn');
const modalClose = $('#modalClose');
const cancelBtn = $('#cancelBtn');
const invoiceForm = $('#invoiceForm');
const operationInput = $('#operation');
const formId = $('#form_id');
const modalTitle = $('#modalTitle');
const saveBtn = $('#saveBtn');
const projectSelect = $('#project_id');
const teamInfo = $('#team-info');
const selectedTeam = $('#selected-team');

/* ---------- Show invoice details on row click ---------- */
$$('#invoiceTable tbody tr[data-id]').forEach(row => {
  row.addEventListener('click', function(e){
    // If clicked target is Edit/Delete button, ignore (we stopPropagation there)
    if (e.target.closest('button')) return;

    const invoice = this.dataset.invoice || '';
    const client = this.dataset.client || '';
    const project = this.dataset.project || '';
    const team = this.dataset.team || '';
    const amount = this.dataset.amount || '';
    const date = this.dataset.date || '';
    const due = this.dataset.due || '';
    const status = this.dataset.status || '';
    const payment = this.dataset.payment || '';

    detailsContent.innerHTML = `
      <p><b>Invoice #${invoice}</b></p>
      <p><b>Client:</b> ${client}</p>
      <p><b>Project:</b> ${project}</p>
      <p><b>Team:</b> ${team}</p>
      <p><b>Amount:</b> $${amount}</p>
      <p><b>Issue Date:</b> ${date}</p>
      <p><b>Due Date:</b> ${due}</p>
      <h4 style="margin-top:12px;">Payment</h4>
      <p>Paid: <b>$${payment}</b></p>
      <p>Status: <b>${status}</b></p>
      <div class="bar"><div class="bar-fill" style="width:${status==='Paid'?100:(status==='Partial'?40:0)}%"></div></div>
    `;
    invoiceDetails.style.display = 'block';
    invoiceDetails.scrollIntoView({behavior:'smooth'});
  });
});

/* hide details */
backBtn.addEventListener('click', ()=> {
  invoiceDetails.style.display = 'none';
});

/* ---------- Search ---------- */
function performSearch() {
  const filter = searchInput.value.trim().toLowerCase();
  $$('#invoiceTable tbody tr[data-id]').forEach(row => {
    const invoice = (row.dataset.invoice || '').toLowerCase();
    const client = (row.dataset.client || '').toLowerCase();
    const project = (row.dataset.project || '').toLowerCase();
    const team = (row.dataset.team || '').toLowerCase();
    const match = invoice.includes(filter) || client.includes(filter) || project.includes(filter) || team.includes(filter);
    row.style.display = match ? '' : 'none';
  });
}

searchInput.addEventListener('input', performSearch);
searchBtn.addEventListener('click', performSearch);

/* ---------- Show team info when project is selected ---------- */
projectSelect.addEventListener('change', function() {
  const selectedOption = this.options[this.selectedIndex];
  if (selectedOption.value) {
    const teamName = selectedOption.getAttribute('data-team') || 'Unassigned';
    selectedTeam.textContent = teamName;
    teamInfo.style.display = 'block';
  } else {
    teamInfo.style.display = 'none';
  }
});

/* ---------- Modal open/close ---------- */
function openModal(mode='add', row=null) {
  invoiceForm.reset();
  teamInfo.style.display = 'none';
  
  if (mode === 'add') {
    operationInput.value = 'add';
    formId.value = '';
    modalTitle.textContent = 'Create Invoice';
    saveBtn.textContent = 'Save';
    
    // Set today's date as default for issue date
    const today = new Date().toISOString().split('T')[0];
    $('#issue_date').value = today;
    
    // Set due date to 30 days from now
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 30);
    $('#due_date').value = dueDate.toISOString().split('T')[0];
  } else {
    operationInput.value = 'edit';
    modalTitle.textContent = 'Edit Invoice';
    saveBtn.textContent = 'Update';
    if (row) {
      // populate fields from data-* attributes
      formId.value = row.dataset.id || '';
      $('#invoice_number').value = row.dataset.invoice || '';
      $('#project_id').value = row.dataset.projectId || '';
      
      // Show team info for edit mode
      const selectedOption = projectSelect.options[projectSelect.selectedIndex];
      if (selectedOption) {
        const teamName = selectedOption.getAttribute('data-team') || row.dataset.teamLead || 'Unassigned';
        selectedTeam.textContent = teamName;
        teamInfo.style.display = 'block';
      }
      
      $('#amount').value = row.dataset.amount ? parseFloat(row.dataset.amount) : '';
      $('#issue_date').value = row.dataset.date || '';
      $('#due_date').value = row.dataset.due || '';
      $('#status').value = row.dataset.status || 'Pending';
      $('#paid_amount').value = row.dataset.payment ? parseFloat(row.dataset.payment) : '';
    }
  }
  invoiceModal.style.display = 'flex';
  invoiceModal.setAttribute('aria-hidden', 'false');
}

function closeModal() {
  invoiceModal.style.display = 'none';
  invoiceModal.setAttribute('aria-hidden', 'true');
}

/* create invoice */
createInvoiceBtn.addEventListener('click', function(){
  openModal('add', null);
});

/* modal close/cancel */
modalClose.addEventListener('click', closeModal);
cancelBtn.addEventListener('click', closeModal);
window.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeModal(); });

/* ---------- Edit button listeners ---------- */
$$('.edit-btn').forEach(btn => {
  btn.addEventListener('click', function(e){
    e.stopPropagation(); // prevent row click showing details
    const row = this.closest('tr[data-id]');
    openModal('edit', row);
  });
});

/* ---------- Form validation ---------- */
invoiceForm.addEventListener('submit', function(e) {
  const amount = parseFloat($('#amount').value);
  const paidAmount = parseFloat($('#paid_amount').value);
  const status = $('#status').value;
  
  // Validate that paid amount doesn't exceed total amount
  if (paidAmount > amount) {
    e.preventDefault();
    alert('Paid amount cannot exceed the total invoice amount.');
    return;
  }
  
  // Validate status consistency
  if (status === 'Paid' && paidAmount < amount) {
    e.preventDefault();
    alert('For "Paid" status, the paid amount must equal the total amount.');
    return;
  }
  
  if (status === 'Pending' && paidAmount > 0) {
    e.preventDefault();
    alert('For "Pending" status, the paid amount must be 0.');
    return;
  }
});

/* ---------- Ensure clicking inside modal doesn't close it accidentally ---------- */
invoiceModal.addEventListener('click', function(e){
  if (e.target === invoiceModal) closeModal();
});
</script>
</body>
</html>