<?php
require_once 'config.php';
// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get current user data from database with role check
try {
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ? AND role = 'User'");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // User not found or not a regular user, destroy session and redirect
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Update session with current user data
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    
    // Set login time if not already set
    if (!isset($_SESSION['login_time'])) {
        $_SESSION['login_time'] = time();
    }
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'error' => 'Unknown action'];
    
    switch ($action) {
        case 'send_message':
            $receiver_type = $_POST['receiver_type'] ?? '';
            $receiver_id = $_POST['receiver_id'] ?? 0;
            $message = $_POST['message'] ?? '';
            
            if (empty($receiver_type) || empty($message)) {
                $response['error'] = 'Missing required fields';
                break;
            }
            
            try {
                $sql = "INSERT INTO messages (sender_id, receiver_type, receiver_id, message) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['user_id'], $receiver_type, $receiver_id, $message]);
                $response['success'] = true;
            } catch (PDOException $e) {
                $response['error'] = 'Database error: ' . $e->getMessage();
            }
            break;
            
        case 'send_quick_message':
            $receiver_type = $_POST['receiver_type'] ?? 'All';
            $receiver_id = $_POST['receiver_id'] ?? 0;
            $message = $_POST['message'] ?? '';
            
            if (empty($message)) {
                $response['error'] = 'Message is required';
                break;
            }
            
            try {
                $sql = "INSERT INTO quick_messages (sender_id, receiver_type, receiver_id, message) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['user_id'], $receiver_type, $receiver_id, $message]);
                $response['success'] = true;
            } catch (PDOException $e) {
                $response['error'] = 'Database error: ' . $e->getMessage();
            }
            break;
            
        case 'update_message':
            $message_id = $_POST['message_id'] ?? 0;
            $message = $_POST['message'] ?? '';
            
            if (empty($message_id) || empty($message)) {
                $response['error'] = 'Missing required fields';
                break;
            }
            
            try {
                $sql = "SELECT id FROM messages WHERE id = ? AND sender_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$message_id, $_SESSION['user_id']]);
                
                if ($stmt->rowCount() === 0) {
                    $response['error'] = 'Message not found or access denied';
                    break;
                }
                
                $sql = "UPDATE messages SET message = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$message, $message_id]);
                $response['success'] = true;
            } catch (PDOException $e) {
                $response['error'] = 'Database error: ' . $e->getMessage();
            }
            break;
            
        case 'delete_message':
            $message_id = $_POST['message_id'] ?? 0;
            
            if (empty($message_id)) {
                $response['error'] = 'Message ID is required';
                break;
            }
            
            try {
                $sql = "SELECT id FROM messages WHERE id = ? AND sender_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$message_id, $_SESSION['user_id']]);
                
                if ($stmt->rowCount() === 0) {
                    $response['error'] = 'Message not found or access denied';
                    break;
                }
                
                $sql = "DELETE FROM messages WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$message_id]);
                $response['success'] = true;
            } catch (PDOException $e) {
                $response['error'] = 'Database error: ' . $e->getMessage();
            }
            break;
            
        case 'update_quick_message':
            $message_id = $_POST['message_id'] ?? 0;
            $message = $_POST['message'] ?? '';
            
            if (empty($message_id) || empty($message)) {
                $response['error'] = 'Missing required fields';
                break;
            }
            
            try {
                $sql = "SELECT id FROM quick_messages WHERE id = ? AND sender_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$message_id, $_SESSION['user_id']]);
                
                if ($stmt->rowCount() === 0) {
                    $response['error'] = 'Message not found or access denied';
                    break;
                }
                
                $sql = "UPDATE quick_messages SET message = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$message, $message_id]);
                $response['success'] = true;
            } catch (PDOException $e) {
                $response['error'] = 'Database error: ' . $e->getMessage();
            }
            break;
            
        case 'delete_quick_message':
            $message_id = $_POST['message_id'] ?? 0;
            
            if (empty($message_id)) {
                $response['error'] = 'Message ID is required';
                break;
            }
            
            try {
                $sql = "SELECT id FROM quick_messages WHERE id = ? AND sender_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$message_id, $_SESSION['user_id']]);
                
                if ($stmt->rowCount() === 0) {
                    $response['error'] = 'Message not found or access denied';
                    break;
                }
                
                $sql = "DELETE FROM quick_messages WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$message_id]);
                $response['success'] = true;
            } catch (PDOException $e) {
                $response['error'] = 'Database error: ' . $e->getMessage();
            }
            break;
            
        case 'get_messages':
            $type = $_POST['type'] ?? 'team';
            $receiver_type = ucfirst($type);
            $receiver_id = $_POST['receiver_id'] ?? 0;
            
            try {
                $sql = "SELECT m.*, 
                               CASE 
                                 WHEN m.receiver_type = 'Team' THEN t.name
                                 WHEN m.receiver_type = 'Employee' THEN u.username
                                 WHEN m.receiver_type = 'Client' THEN c.name
                               END as receiver_name
                        FROM messages m
                        LEFT JOIN teams t ON m.receiver_type = 'Team' AND m.receiver_id = t.id
                        LEFT JOIN users u ON m.receiver_type = 'Employee' AND m.receiver_id = u.id
                        LEFT JOIN clients c ON m.receiver_type = 'Client' AND m.receiver_id = c.id
                        WHERE m.sender_id = ? AND m.receiver_type = ?";
                
                $params = [$_SESSION['user_id'], $receiver_type];
                
                // Add receiver_id filter if provided
                if (!empty($receiver_id)) {
                    $sql .= " AND m.receiver_id = ?";
                    $params[] = $receiver_id;
                }
                
                $sql .= " ORDER BY m.sent_at DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response['success'] = true;
                $response['messages'] = $messages;
            } catch (PDOException $e) {
                $response['error'] = 'Database error: ' . $e->getMessage();
            }
            break;
            
        case 'get_quick_messages':
            $type = $_POST['type'] ?? 'all';
            $receiver_type_filter = $_POST['receiver_type'] ?? '';
            $receiver_id_filter = $_POST['receiver_id'] ?? 0;
            
            try {
                if ($type === 'all') {
                    $sql = "SELECT qm.*, 
                                   CASE 
                                     WHEN qm.receiver_type = 'Team' THEN t.name
                                     WHEN qm.receiver_type = 'Employee' THEN u.username
                                     WHEN qm.receiver_type = 'Client' THEN c.name
                                     ELSE 'All'
                                   END as receiver_name
                            FROM quick_messages qm
                            LEFT JOIN teams t ON qm.receiver_type = 'Team' AND qm.receiver_id = t.id
                            LEFT JOIN users u ON qm.receiver_type = 'Employee' AND qm.receiver_id = u.id
                            LEFT JOIN clients c ON qm.receiver_type = 'Client' AND qm.receiver_id = c.id
                            WHERE qm.sender_id = ?";
                    $params = [$_SESSION['user_id']];
                    
                    // Add receiver_type filter if provided
                    if (!empty($receiver_type_filter)) {
                        $sql .= " AND qm.receiver_type = ?";
                        $params[] = $receiver_type_filter;
                    }
                    
                    // Add receiver_id filter if provided
                    if (!empty($receiver_id_filter)) {
                        $sql .= " AND qm.receiver_id = ?";
                        $params[] = $receiver_id_filter;
                    }
                    
                    $sql .= " ORDER BY qm.sent_at DESC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                } else {
                    $receiver_type = ucfirst($type);
                    $sql = "SELECT qm.*, 
                                   CASE 
                                     WHEN qm.receiver_type = 'Team' THEN t.name
                                     WHEN qm.receiver_type = 'Employee' THEN u.username
                                     WHEN qm.receiver_type = 'Client' THEN c.name
                                     ELSE 'All'
                                   END as receiver_name
                            FROM quick_messages qm
                            LEFT JOIN teams t ON qm.receiver_type = 'Team' AND qm.receiver_id = t.id
                            LEFT JOIN users u ON qm.receiver_type = 'Employee' AND qm.receiver_id = u.id
                            LEFT JOIN clients c ON qm.receiver_type = 'Client' AND qm.receiver_id = c.id
                            WHERE qm.sender_id = ? AND qm.receiver_type = ?";
                    $params = [$_SESSION['user_id'], $receiver_type];
                    
                    // Add receiver_id filter if provided
                    if (!empty($receiver_id_filter)) {
                        $sql .= " AND qm.receiver_id = ?";
                        $params[] = $receiver_id_filter;
                    }
                    
                    $sql .= " ORDER BY qm.sent_at DESC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                }
                
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response['success'] = true;
                $response['messages'] = $messages;
            } catch (PDOException $e) {
                $response['error'] = 'Database error: ' . $e->getMessage();
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}

// Get initial data for the page
$teams = [];
$employees = [];
$clients = [];

try {
    $stmt = $pdo->prepare("SELECT id, name FROM teams");
    $stmt->execute();
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT id, username as name FROM users WHERE role = 'User' AND id != ?");
    $stmt->execute([$_SESSION['user_id']]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT id, name FROM clients WHERE status = 'Active'");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error silently for initial page load
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages - CODETREE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary-color: #004d4d;
    --secondary-color: #008080;
    --light-color: #e8f4f4;
    --dark-color: #1f2b2b;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --info-color: #17a2b8;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    display: flex;
    min-height: 100vh;
    color: var(--dark-color);
    overflow-x: hidden;
}

/* Sidebar */
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

.navlist {
    list-style: none;
    padding: 0;
    flex-grow: 1;
}

.navlist li {
    margin-bottom: 12px;
}

.navlist a {
    color: #dfeff0;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    transition: all 0.3s;
    font-size: 16px;
    font-weight: 500;
}

.navlist a:hover, .navlist a.active {
    background-color: var(--secondary-color);
    color: #fff;
    transform: translateX(5px);
}

.navlist a i {
    font-size: 18px;
    width: 24px;
    text-align: center;
}

/* Toggle button for mobile */
.toggle-btn {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    background: var(--primary-color);
    color: #fff;
    border: none;
    font-size: 22px;
    padding: 10px 14px;
    border-radius: 8px;
    z-index: 1001;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transition: all 0.3s;
}

.toggle-btn:hover {
    background: var(--secondary-color);
    transform: scale(1.05);
}

/* Content */
.content {
    flex: 1;
    padding: 30px;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

h1 {
    color: var(--primary-color);
    margin: 0;
    font-weight: 600;
    font-size: 32px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* Session Info */
.session-info {
    background: linear-gradient(135deg, var(--light-color) 0%, #d1e7e7 100%);
    padding: 16px 24px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border: 1px solid #cde0e0;
}

.session-details {
    display: flex;
    gap: 25px;
    align-items: center;
}

.session-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 500;
}

.session-item i {
    color: var(--primary-color);
    font-size: 20px;
}

.session-badge {
    background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Messages Container */
.messages-container {
    display: flex;
    gap: 25px;
    flex-wrap: wrap;
}

.card {
    background-color: #fff;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    flex: 1 1 500px;
    border: none;
    transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.card h3 {
    color: var(--primary-color);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    font-size: 22px;
    border-bottom: 2px solid var(--light-color);
    padding-bottom: 12px;
}

.card h3 i {
    font-size: 24px;
}

/* Tabs */
.tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    background: #f8f9fa;
    padding: 8px;
    border-radius: 10px;
}

.tab {
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
    background-color: #e9ecef;
    transition: all 0.3s;
    font-weight: 500;
    font-size: 14px;
    flex: 1;
    text-align: center;
}

.tab.active {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: #fff;
    box-shadow: 0 2px 8px rgba(0, 125, 125, 0.3);
}

.tab:hover:not(.active) {
    background-color: #dde1e6;
    transform: translateY(-2px);
}

/* Message Boxes */
.message-box {
    display: none;
}

.message-box.active {
    display: block;
}

.message-box select, .message-box textarea {
    width: 100%;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid #ced4da;
    margin-bottom: 15px;
    font-family: inherit;
    transition: all 0.3s;
    font-size: 15px;
}

.message-box select:focus, .message-box textarea:focus {
    outline: none;
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(0, 125, 125, 0.2);
}

.message-box textarea {
    height: 120px;
    resize: none;
}

.send-button {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    font-size: 15px;
    width: 100%;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.send-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 125, 125, 0.3);
}

/* History Button */
.history-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, var(--warning-color) 0%, #e0a800 100%);
    color: #212529;
    padding: 10px 18px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 15px;
    transition: all 0.3s;
    border: none;
    width: 100%;
    justify-content: center;
}

.history-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.history-button i {
    font-size: 16px;
}

/* History Box */
.history-box {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 15px;
    background: #fafbfc;
    display: none;
    margin-bottom: 15px;
}

.history-person {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: all 0.2s;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.history-person:last-child {
    border-bottom: none;
}

.history-person:hover {
    background-color: var(--light-color);
    transform: translateX(5px);
}

.history-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 18px;
}

.history-info {
    flex: 1;
}

.history-name {
    font-weight: 600;
    margin-bottom: 3px;
}

.history-type {
    font-size: 0.85rem;
    color: #6c757d;
}

/* Messages List - Initially Hidden */
.inbox-messages {
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    max-height: 350px;
    overflow-y: auto;
    padding: 15px;
    background: #fafbfc;
    display: none; /* Hidden by default */
}

.message-item {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    transition: all 0.2s;
    border-radius: 8px;
    margin-bottom: 10px;
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.message-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.message-item:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.message-content {
    flex: 1;
    padding-right: 15px;
}

.message-text {
    margin-bottom: 8px;
    line-height: 1.5;
}

.message-meta {
    font-size: 0.85rem;
    color: #6c757d;
    display: flex;
    justify-content: space-between;
}

.message-actions {
    display: flex;
    gap: 8px;
}

.message-actions button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    padding: 6px 10px;
    border-radius: 6px;
    transition: all 0.2s;
}

.edit-btn {
    color: var(--info-color);
}

.edit-btn:hover {
    background-color: #e1f5fe;
    transform: scale(1.1);
}

.delete-btn {
    color: var(--danger-color);
}

.delete-btn:hover {
    background-color: #fde8e8;
    transform: scale(1.1);
}

/* Quick Messages - Initially Hidden */
.quick-messages {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 15px;
    max-height: 350px;
    overflow-y: auto;
    padding: 5px;
    display: none; /* Hidden by default */
}

.quick-message-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 15px;
    border-left: 4px solid var(--secondary-color);
    box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
    position: relative;
}

.quick-message-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.12);
}

.quick-message-text {
    margin-bottom: 10px;
    line-height: 1.4;
}

.quick-message-meta {
    font-size: 0.8rem;
    color: #6c757d;
    display: flex;
    justify-content: space-between;
}

.quick-message-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: none;
    gap: 5px;
}

.quick-message-card:hover .quick-message-actions {
    display: flex;
}

.quick-message-actions button {
    background: rgba(255,255,255,0.8);
    border: none;
    border-radius: 4px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
}

.quick-edit-btn {
    color: var(--info-color);
}

.quick-edit-btn:hover {
    background: var(--info-color);
    color: white;
}

.quick-delete-btn {
    color: var(--danger-color);
}

.quick-delete-btn:hover {
    background: var(--danger-color);
    color: white;
}

/* Quick Send Button */
.quick-send-button {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    font-size: 15px;
    width: 100%;
    margin-top: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.quick-send-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 125, 125, 0.3);
}

/* Modal Enhancements */
.modal-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    border-bottom: none;
    border-radius: 12px 12px 0 0;
}

.modal-title {
    font-weight: 600;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    border-radius: 0 0 12px 12px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
    transform: translateY(-1px);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 30px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #dee2e6;
}

.empty-state h4 {
    margin-bottom: 10px;
    color: #6c757d;
}

/* Loading Animation */
.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(0, 125, 125, 0.3);
    border-radius: 50%;
    border-top-color: var(--secondary-color);
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 1200px) {
    .messages-container {
        flex-direction: column;
    }
    
    .card {
        flex: 1 1 auto;
    }
}

@media (max-width: 992px) {
    .sidebar {
        position: fixed;
        top: 0;
        left: -250px;
        height: 100%;
        z-index: 1000;
        width: 280px;
    }
    
    .sidebar.show {
        left: 0;
    }
    
    .toggle-btn {
        display: block;
    }
    
    .content {
        padding: 20px;
    }
    
    .session-info {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .session-details {
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }
}

@media (max-width: 768px) {
    h1 {
        font-size: 26px;
    }
    
    .card {
        padding: 20px;
    }
    
    .tabs {
        flex-direction: column;
    }
    
    .quick-messages {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    h1 {
        font-size: 22px;
    }
    
    .content {
        padding: 15px;
    }
    
    .card {
        padding: 15px;
    }
    
    .session-item {
        font-size: 14px;
    }
}

/* Scrollbar Styling */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
</head>
<body>

<!-- Sidebar Toggle Button -->
<button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
  <div class="logo">
    <img src="logo.png" alt="CODETREE Logo">
  </div>
  <ul class="navlist">
    <li><a href="uprofile.php">Profile</a></li>
    <li><a href="umessages.php">Messages</a></li>
    <li><a href="uprojects.php"> Projects</a></li>
    <li><a href="unotification.php">Notifications</a></li>
    <li><a href="logout.php"> Logout</a></li>
  </ul>
</nav>

<!-- Content -->
<div class="content">
  <div class="content-header">
    <h1>Messages Dashboard</h1>
  </div>

  <!-- Session Info -->
  <div class="session-info">
    <div class="session-details">
      <div class="session-item">
        <i class="bi bi-person-circle"></i>
        <span>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
      </div>
      <div class="session-item">
        <i class="bi bi-clock"></i>
        <span>Session Duration: <strong id="session-duration">00:00:00</strong></span>
      </div>
    </div>
    <div class="session-badge"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
  </div>

  <!-- Messages Container -->
  <div class="messages-container">
    <!-- Communication Section -->
    <div class="card">
      <h3><i class="bi bi-chat-dots"></i> Communication</h3>
      
      <div class="tabs">
        <div class="tab active" data-msg="team">Team</div>
        <div class="tab" data-msg="employee">Employee</div>
        <div class="tab" data-msg="client">Client</div>
      </div>

      <div class="message-box active" id="team">
        <select id="team-receiver" class="form-select">
          <option value="">Select a Team</option>
          <?php foreach ($teams as $team): ?>
            <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <textarea id="team-message" placeholder="Type your message to the team..."></textarea>
        <button class="send-button" onclick="sendMessage('team')">
          <i class="bi bi-send"></i> Send Message
        </button>
        
        <button class="history-button" onclick="toggleHistory('team')">
          <i class="bi bi-clock-history"></i> View Message History
        </button>
        
        <div class="history-box" id="team-history">
          <!-- Team history will be loaded here -->
        </div>
        
        <div class="inbox-messages" id="team-messages">
          <!-- Team messages will be loaded here when history is viewed -->
        </div>
      </div>

      <div class="message-box" id="employee">
        <select id="employee-receiver" class="form-select">
          <option value="">Select an Employee</option>
          <?php foreach ($employees as $employee): ?>
            <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <textarea id="employee-message" placeholder="Type your message to the employee..."></textarea>
        <button class="send-button" onclick="sendMessage('employee')">
          <i class="bi bi-send"></i> Send Message
        </button>
        
        <button class="history-button" onclick="toggleHistory('employee')">
          <i class="bi bi-clock-history"></i> View Message History
        </button>
        
        <div class="history-box" id="employee-history">
          <!-- Employee history will be loaded here -->
        </div>
        
        <div class="inbox-messages" id="employee-messages">
          <!-- Employee messages will be loaded here when history is viewed -->
        </div>
      </div>

      <div class="message-box" id="client">
        <select id="client-receiver" class="form-select">
          <option value="">Select a Client</option>
          <?php foreach ($clients as $client): ?>
            <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <textarea id="client-message" placeholder="Type your message to the client..."></textarea>
        <button class="send-button" onclick="sendMessage('client')">
          <i class="bi bi-send"></i> Send Message
        </button>
        
        <button class="history-button" onclick="toggleHistory('client')">
          <i class="bi bi-clock-history"></i> View Message History
        </button>
        
        <div class="history-box" id="client-history">
          <!-- Client history will be loaded here -->
        </div>
        
        <div class="inbox-messages" id="client-messages">
          <!-- Client messages will be loaded here when history is viewed -->
        </div>
      </div>
    </div>

    <!-- Quick Messages Section -->
    <div class="card">
      <h3><i class="bi bi-lightning"></i> Quick Messages</h3>
      
      <div class="tabs">
        <div class="tab active" data-quick="all">All</div>
        <div class="tab" data-quick="team">Team</div>
        <div class="tab" data-quick="employee">Employee</div>
        <div class="tab" data-quick="client">Client</div>
      </div>

      <textarea id="quick-message-text" placeholder="Type your quick message..."></textarea>
      <button class="quick-send-button" onclick="sendQuickMessage()">
        <i class="bi bi-send"></i> Send Quick Message
      </button>
      
      <button class="history-button" onclick="toggleQuickHistory()">
        <i class="bi bi-clock-history"></i> View Quick Messages History
      </button>
      
      <div class="history-box" id="quick-history">
        <!-- Quick messages history will be loaded here -->
      </div>

      <div class="quick-messages" id="quick-messages-all">
        <!-- Quick messages will be loaded here when history is viewed -->
      </div>
      
      <div class="quick-messages" id="quick-messages-team" style="display:none;">
        <!-- Team quick messages will be loaded here when history is viewed -->
      </div>
      
      <div class="quick-messages" id="quick-messages-employee" style="display:none;">
        <!-- Employee quick messages will be loaded here when history is viewed -->
      </div>
      
      <div class="quick-messages" id="quick-messages-client" style="display:none;">
        <!-- Client quick messages will be loaded here when history is viewed -->
      </div>
    </div>
  </div>
</div>

<!-- Edit Message Modal -->
<div class="modal fade" id="editMessageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Message</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <textarea id="edit-message-text" class="form-control" rows="4"></textarea>
        <input type="hidden" id="edit-message-id">
        <input type="hidden" id="edit-message-type">
        <input type="hidden" id="edit-message-is-quick">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="updateMessage()">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Global variables
let sessionStartTime = <?php echo $_SESSION['login_time']; ?>;
let currentMessageType = 'team';
let currentQuickType = 'all';

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeSingleQuotes(text) {
    return text.replace(/'/g, "\\'");
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
}

// Session timer
function updateSessionDuration() {
    const now = Math.floor(Date.now() / 1000);
    const duration = now - sessionStartTime;
    
    const hours = Math.floor(duration / 3600);
    const minutes = Math.floor((duration % 3600) / 60);
    const seconds = duration % 60;
    
    document.getElementById('session-duration').textContent = 
        `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

setInterval(updateSessionDuration, 1000);
updateSessionDuration(); // Initial call

// Tabs functionality
document.querySelectorAll('.tab[data-msg]').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab[data-msg]').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        
        document.querySelectorAll('.message-box').forEach(b => b.classList.remove('active'));
        document.getElementById(tab.dataset.msg).classList.add('active');
        
        currentMessageType = tab.dataset.msg;
        
        // Hide messages when switching tabs
        document.querySelectorAll('.inbox-messages').forEach(msg => {
            msg.style.display = 'none';
        });
        document.querySelectorAll('.history-box').forEach(box => {
            box.style.display = 'none';
        });
    });
});

document.querySelectorAll('.tab[data-quick]').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab[data-quick]').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        
        document.querySelectorAll('.quick-messages').forEach(c => c.style.display = 'none');
        
        currentQuickType = tab.dataset.quick;
        
        // If quick messages are currently shown, update them
        const quickMessagesContainer = document.getElementById(`quick-messages-${currentQuickType}`);
        if (quickMessagesContainer.style.display === 'grid') {
            loadQuickMessages(currentQuickType);
        }
    });
});

// Send message function
function sendMessage(type) {
    const messageText = document.getElementById(`${type}-message`).value.trim();
    const receiverSelect = document.getElementById(`${type}-receiver`);
    const receiverId = receiverSelect.value;
    
    if (!receiverId) {
        alert('Please select a receiver.');
        return;
    }
    
    if (!messageText) {
        alert('Please enter a message.');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('receiver_type', type.charAt(0).toUpperCase() + type.slice(1));
    formData.append('receiver_id', receiverId);
    formData.append('message', messageText);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`${type}-message`).value = '';
            alert('Message sent successfully!');
        } else {
            alert('Error sending message: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending message.');
    });
}

// Send quick message function
function sendQuickMessage() {
    const messageText = document.getElementById('quick-message-text').value.trim();
    
    if (!messageText) {
        alert('Please enter a message.');
        return;
    }
    
    const activeTab = document.querySelector('[data-quick].active');
    const receiverType = activeTab ? activeTab.dataset.quick : 'all';
    
    const formData = new FormData();
    formData.append('action', 'send_quick_message');
    formData.append('receiver_type', receiverType.charAt(0).toUpperCase() + receiverType.slice(1));
    formData.append('message', messageText);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('quick-message-text').value = '';
            alert('Quick message sent successfully!');
        } else {
            alert('Error sending quick message: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending quick message.');
    });
}

// Load messages function (only called when history is viewed) - FIXED
function loadMessages(type, receiverId = null) {
    const messageList = document.getElementById(`${type}-messages`);
    messageList.innerHTML = '<div class="text-center py-4"><span class="loading"></span> Loading messages...</div>';
    messageList.style.display = 'block';
    
    const formData = new FormData();
    formData.append('action', 'get_messages');
    formData.append('type', type);
    
    // Add receiver_id if provided
    if (receiverId) {
        formData.append('receiver_id', receiverId);
    }
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageList.innerHTML = '';
            
            if (data.messages.length === 0) {
                messageList.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-chat-square-text"></i>
                        <h4>No messages yet</h4>
                        <p>Send a message to get started</p>
                    </div>
                `;
                return;
            }
            
            data.messages.forEach(message => {
                const messageItem = document.createElement('div');
                messageItem.className = 'message-item';
                messageItem.innerHTML = `
                    <div class="message-content">
                        <div class="message-text">${escapeHtml(message.message)}</div>
                        <div class="message-meta">
                            <span>Sent to: ${escapeHtml(message.receiver_name)}</span>
                            <span>${formatDate(message.sent_at)}</span>
                        </div>
                    </div>
                    <div class="message-actions">
                        <button class="edit-btn" onclick="editMessage(${message.id}, '${type}', '${escapeSingleQuotes(message.message)}', false)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="delete-btn" onclick="deleteMessage(${message.id}, '${type}', false)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
                messageList.appendChild(messageItem);
            });
        } else {
            messageList.innerHTML = '<div class="text-center text-danger py-4">Error loading messages.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messageList.innerHTML = '<div class="text-center text-danger py-4">Error loading messages.</div>';
    });
}

// Load quick messages function (only called when history is viewed) - FIXED
function loadQuickMessages(type, receiverTypeFilter = '') {
    const container = document.getElementById(`quick-messages-${type}`);
    container.innerHTML = '<div class="text-center py-4"><span class="loading"></span> Loading quick messages...</div>';
    container.style.display = 'grid';
    
    const formData = new FormData();
    formData.append('action', 'get_quick_messages');
    formData.append('type', type);
    
    // Add receiver_type filter if provided
    if (receiverTypeFilter) {
        formData.append('receiver_type', receiverTypeFilter);
    }
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            container.innerHTML = '';
            
            if (data.messages.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-lightning"></i>
                        <h4>No quick messages yet</h4>
                        <p>Send a quick message to get started</p>
                    </div>
                `;
                return;
            }
            
            data.messages.forEach(message => {
                const messageCard = document.createElement('div');
                messageCard.className = 'quick-message-card';
                messageCard.innerHTML = `
                    <div class="quick-message-text">${escapeHtml(message.message)}</div>
                    <div class="quick-message-meta">
                        <span>To: ${escapeHtml(message.receiver_type)}</span>
                        <span>${formatDate(message.sent_at)}</span>
                    </div>
                    <div class="quick-message-actions">
                        <button class="quick-edit-btn" onclick="editMessage(${message.id}, '${type}', '${escapeSingleQuotes(message.message)}', true)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="quick-delete-btn" onclick="deleteMessage(${message.id}, '${type}', true)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
                container.appendChild(messageCard);
            });
        } else {
            container.innerHTML = '<div class="text-center text-danger py-4">Error loading quick messages.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        container.innerHTML = '<div class="text-center text-danger py-4">Error loading quick messages.</div>';
    });
}

// Toggle history function
function toggleHistory(type) {
    const historyBox = document.getElementById(`${type}-history`);
    const messageList = document.getElementById(`${type}-messages`);
    const isMessagesVisible = messageList.style.display === 'block';
    
    // Hide all history boxes and message lists first
    document.querySelectorAll('.history-box').forEach(box => {
        box.style.display = 'none';
    });
    document.querySelectorAll('.inbox-messages').forEach(msg => {
        msg.style.display = 'none';
    });
    document.querySelectorAll('.quick-messages').forEach(msg => {
        msg.style.display = 'none';
    });
    
    // Hide quick history if open
    document.getElementById('quick-history').style.display = 'none';
    
    if (!isMessagesVisible) {
        historyBox.style.display = 'block';
        loadHistory(type);
    }
}

function toggleQuickHistory() {
    const quickHistory = document.getElementById('quick-history');
    const quickMessages = document.getElementById(`quick-messages-${currentQuickType}`);
    const isMessagesVisible = quickMessages.style.display === 'grid';
    
    // Hide all history boxes and message lists first
    document.querySelectorAll('.history-box').forEach(box => {
        box.style.display = 'none';
    });
    document.querySelectorAll('.inbox-messages').forEach(msg => {
        msg.style.display = 'none';
    });
    document.querySelectorAll('.quick-messages').forEach(msg => {
        msg.style.display = 'none';
    });
    
    if (!isMessagesVisible) {
        quickHistory.style.display = 'block';
        loadQuickHistory();
    }
}

// Load history function
function loadHistory(type) {
    const historyBox = document.getElementById(`${type}-history`);
    historyBox.innerHTML = '<div class="text-center py-3"><span class="loading"></span> Loading history...</div>';
    
    const formData = new FormData();
    formData.append('action', 'get_messages');
    formData.append('type', type);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            historyBox.innerHTML = '';
            
            if (data.messages.length === 0) {
                historyBox.innerHTML = '<div class="text-center py-3">No message history found.</div>';
                return;
            }
            
            // Group messages by receiver
            const receivers = {};
            data.messages.forEach(message => {
                if (!receivers[message.receiver_id]) {
                    receivers[message.receiver_id] = {
                        name: message.receiver_name,
                        type: message.receiver_type,
                        id: message.receiver_id,
                        count: 0
                    };
                }
                receivers[message.receiver_id].count++;
            });
            
            // Create history items
            Object.values(receivers).forEach(receiver => {
                const historyItem = document.createElement('div');
                historyItem.className = 'history-person';
                historyItem.innerHTML = `
                    <div class="history-avatar">${receiver.name.charAt(0)}</div>
                    <div class="history-info">
                        <div class="history-name">${escapeHtml(receiver.name)}</div>
                        <div class="history-type">${receiver.type} • ${receiver.count} message(s)</div>
                    </div>
                `;
                historyItem.addEventListener('click', () => {
                    document.getElementById(`${type}-receiver`).value = receiver.id;
                    // FIXED: Pass the receiver ID to load only messages for that specific receiver
                    loadMessages(type, receiver.id);
                    historyBox.style.display = 'none';
                });
                historyBox.appendChild(historyItem);
            });
        } else {
            historyBox.innerHTML = '<div class="text-center text-danger py-3">Error loading history.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        historyBox.innerHTML = '<div class="text-center text-danger py-3">Error loading history.</div>';
    });
}

// Load quick history function
function loadQuickHistory() {
    const quickHistory = document.getElementById('quick-history');
    quickHistory.innerHTML = '<div class="text-center py-3"><span class="loading"></span> Loading quick messages history...</div>';
    
    const formData = new FormData();
    formData.append('action', 'get_quick_messages');
    formData.append('type', 'all');
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            quickHistory.innerHTML = '';
            
            if (data.messages.length === 0) {
                quickHistory.innerHTML = '<div class="text-center py-3">No quick messages history found.</div>';
                return;
            }
            
            // Group messages by receiver type
            const receivers = {};
            data.messages.forEach(message => {
                if (!receivers[message.receiver_type]) {
                    receivers[message.receiver_type] = {
                        type: message.receiver_type,
                        count: 0
                    };
                }
                receivers[message.receiver_type].count++;
            });
            
            // Create history items
            Object.values(receivers).forEach(receiver => {
                const historyItem = document.createElement('div');
                historyItem.className = 'history-person';
                historyItem.innerHTML = `
                    <div class="history-avatar">${receiver.type.charAt(0)}</div>
                    <div class="history-info">
                        <div class="history-name">${receiver.type} Messages</div>
                        <div class="history-type">${receiver.count} message(s)</div>
                    </div>
                `;
                historyItem.addEventListener('click', () => {
                    // Switch to the appropriate tab and show messages
                    document.querySelectorAll('.tab[data-quick]').forEach(tab => {
                        if (tab.dataset.quick.toLowerCase() === receiver.type.toLowerCase()) {
                            tab.click();
                        }
                    });
                    // FIXED: Pass the receiver type to load only messages for that specific type
                    loadQuickMessages(currentQuickType, receiver.type);
                    quickHistory.style.display = 'none';
                });
                quickHistory.appendChild(historyItem);
            });
        } else {
            quickHistory.innerHTML = '<div class="text-center text-danger py-3">Error loading quick messages history.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        quickHistory.innerHTML = '<div class="text-center text-danger py-3">Error loading quick messages history.</div>';
    });
}

// Edit message function
function editMessage(messageId, messageType, messageText, isQuick) {
    document.getElementById('edit-message-id').value = messageId;
    document.getElementById('edit-message-type').value = messageType;
    document.getElementById('edit-message-text').value = messageText;
    document.getElementById('edit-message-is-quick').value = isQuick ? '1' : '0';
    
    const modal = new bootstrap.Modal(document.getElementById('editMessageModal'));
    modal.show();
}

// Update message function
function updateMessage() {
    const messageId = document.getElementById('edit-message-id').value;
    const messageType = document.getElementById('edit-message-type').value;
    const messageText = document.getElementById('edit-message-text').value.trim();
    const isQuick = document.getElementById('edit-message-is-quick').value === '1';
    
    if (!messageText) {
        alert('Please enter a message.');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', isQuick ? 'update_quick_message' : 'update_message');
    formData.append('message_id', messageId);
    formData.append('message', messageText);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editMessageModal'));
            modal.hide();
            
            // Reload the current view
            if (isQuick) {
                const receiverType = document.querySelector('.tab[data-quick].active').dataset.quick;
                loadQuickMessages(receiverType);
            } else {
                const receiverSelect = document.getElementById(`${messageType}-receiver`);
                const receiverId = receiverSelect.value;
                loadMessages(messageType, receiverId);
            }
            
            alert('Message updated successfully!');
        } else {
            alert('Error updating message: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating message.');
    });
}

// Delete message function
function deleteMessage(messageId, messageType, isQuick) {
    if (!confirm('Are you sure you want to delete this message?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', isQuick ? 'delete_quick_message' : 'delete_message');
    formData.append('message_id', messageId);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the current view
            if (isQuick) {
                const receiverType = document.querySelector('.tab[data-quick].active').dataset.quick;
                loadQuickMessages(receiverType);
            } else {
                const receiverSelect = document.getElementById(`${messageType}-receiver`);
                const receiverId = receiverSelect.value;
                loadMessages(messageType, receiverId);
            }
            alert('Message deleted successfully!');
        } else {
            alert('Error deleting message: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting message.');
    });
}

// Initialize page - No messages loaded by default
document.addEventListener('DOMContentLoaded', function() {
    // No messages are loaded initially - they will load when history is viewed
});
</script>
</body>
</html>
