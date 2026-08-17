<?php
require_once 'config.php';
if(session_status()===PHP_SESSION_NONE){session_start();}
if(!isset($_SESSION['user_id'])){exit;}

$uid = $_SESSION['user_id'];

if(isset($_POST['id'])){
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
    $stmt->execute([$id, $uid]);
} elseif(isset($_POST['mark_all'])){
    if($_POST['mark_all']=='notifications'){
        $stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND title NOT LIKE '%Reminder%'");
        $stmt->execute([$uid]);
    } elseif($_POST['mark_all']=='reminders'){
        $stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND title LIKE '%Reminder%'");
        $stmt->execute([$uid]);
    }
}
?>
