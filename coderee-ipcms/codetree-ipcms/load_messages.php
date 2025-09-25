<?php
require_once 'config.php';

function ensure_db_connection() {
    global $conn, $db, $mysqli, $servername, $username, $password, $dbname, $db_host, $db_user, $db_pass, $db_name;
    if (isset($conn) && $conn instanceof mysqli) return;

    if (isset($db) && $db instanceof mysqli) { $conn = $db; return; }
    if (isset($mysqli) && $mysqli instanceof mysqli) { $conn = $mysqli; return; }

    $dbHost = defined('DB_HOST') ? DB_HOST : (isset($db_host) ? $db_host : (isset($servername) ? $servername : '127.0.0.1'));
    $dbUser = defined('DB_USER') ? DB_USER : (isset($db_user) ? $db_user : (isset($username) ? $username : 'root'));
    $dbPass = defined('DB_PASS') ? DB_PASS : (isset($db_pass) ? $db_pass : (isset($password) ? $password : ''));
    $dbName = defined('DB_NAME') ? DB_NAME : (isset($db_name) ? $db_name : (isset($dbname) ? $dbname : 'codetree'));

    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($conn->connect_error) {
        http_response_code(500);
        die("Database connection failed: " . $conn->connect_error);
    }
}

ensure_db_connection();
header('Content-Type: application/json; charset=utf-8');

$cid = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
if ($cid <= 0) {
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("SELECT m.*, u.username FROM messages m LEFT JOIN users u ON m.sender_id=u.id WHERE m.receiver_type='Client' AND m.receiver_id = ? ORDER BY m.sent_at ASC");
$stmt->bind_param("i", $cid);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
echo json_encode($messages);
