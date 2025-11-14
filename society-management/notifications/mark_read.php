<?php
include '../config/db.php';
if ($_POST['id'] && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET read_status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['id'], $_SESSION['user_id']]);
    echo 'success';
} else {
    echo 'error';
}
?>