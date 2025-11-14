<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header('Location: ' . ($role == 'admin' ? 'admin' : 'resident') . '/dashboard.php');
} else {
    header('Location: auth/loading.php');
}
?>
