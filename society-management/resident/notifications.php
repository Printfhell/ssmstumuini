<?php
include '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'resident') {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Delete notification
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$_GET['id'], $user_id])) {
            $success = "Notification deleted successfully!";
        } else {
            $error = "Failed to delete notification.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-bell"></i> Notifications</h1>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Search by message or type..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <?php if (isset($_GET['search'])): ?>
                            <a href="?action=list" class="btn btn-secondary ms-2">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            <?php if ($notifications): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Message</th>
                            <th>Type</th>
                            <th>Notified By</th>
                            <th>Read Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notification): ?>
                            <tr>
                                <td><?= htmlspecialchars($notification['message']) ?></td>
                                <td><?= htmlspecialchars($notification['notification_type']) ?></td>
                                <td>Admin</td>
                                <td><?= htmlspecialchars($notification['read_status']) ?></td>
                                <td><?= htmlspecialchars($notification['created_at']) ?></td>
                                <td>
                                    <a href='?action=delete&id=<?= $notification['id'] ?>' class='btn btn-danger btn-sm' onclick='return confirm("Are you sure you want to delete this notification?")'>Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No notifications found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
