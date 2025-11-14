<?php
include '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header('Location: ../auth/login.php'); exit; }

//  Read/Unread
if (isset($_GET['action']) && $_GET['action'] == 'toggle_read' && isset($_GET['id'])) {
    $new_status = $_GET['status'] == 'read' ? 'unread' : 'read';
    $stmt = $pdo->prepare("UPDATE notifications SET read_status = ? WHERE id = ?");
    $stmt->execute([$new_status, $_GET['id']]);
    header('Location: notifications.php');
    exit;
}

// Update Notification
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $stmt = $pdo->prepare("UPDATE notifications SET message = ? WHERE id = ?");
    if ($stmt->execute([$_POST['message'], $_POST['notification_id']])) {
        $success = "Notification updated successfully!";
    } else {
        $error = "Failed to update notification.";
    }
}

// Delete Notification
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    if ($stmt->execute([$_GET['id']])) {
        $success = "Notification deleted successfully!";
    } else {
        $error = "Failed to delete notification.";
    }
}

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-bell"></i> All Notifications</h1>

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
        <div class="card-header">Notifications List</div>
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Search by resident name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <?php if (isset($_GET['search'])): ?>
                            <a href="?action=list" class="btn btn-secondary ms-2">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Resident</th><th>Message</th><th>Type</th><th>Read Status</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php
                    $search = $_GET['search'] ?? '';
                    $query = "SELECT n.*, u.name FROM notifications n JOIN users u ON n.user_id = u.id";
                    if (!empty($search)) {
                        $query .= " WHERE u.name LIKE :search";
                    }
                    $query .= " ORDER BY n.created_at DESC";
                    $stmt = $pdo->prepare($query);
                    if (!empty($search)) {
                        $stmt->bindValue(':search', '%' . $search . '%');
                    }
                    $stmt->execute();
                    while ($row = $stmt->fetch()) {
                        $read_status = $row['read_status'] == 'unread' ? '<span class="badge bg-danger">Unread</span>' : '<span class="badge bg-secondary">Read</span>';
                        echo "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['name']}</td>
                            <td>" . substr($row['message'], 0, 50) . "...</td>
                            <td>{$row['notification_type']}</td>
                            <td>{$read_status}</td>
                            <td>{$row['created_at']}</td>
                            <td>
                                <a href='?action=toggle_read&id={$row['id']}&status={$row['read_status']}' class='btn btn-warning btn-sm'> " . ($row['read_status'] == 'read' ? 'Unread' : 'Read') . "</a>
                                <button class='btn btn-primary btn-sm' data-bs-toggle='modal' data-bs-target='#updateModal{$row['id']}'>Update</button>
                                <a href='?action=delete&id={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this notification?\")'>Delete</a>
                            </td>
                        </tr>";

                        // Modal for Update
                        echo "
                        <div class='modal fade' id='updateModal{$row['id']}' tabindex='-1'>
                            <div class='modal-dialog'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <h5 class='modal-title'>Update Notification #{$row['id']}</h5>
                                        <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                    </div>
                                    <form method='POST'>
                                        <div class='modal-body'>
                                            <input type='hidden' name='action' value='update'>
                                            <input type='hidden' name='notification_id' value='{$row['id']}'>
                                            <div class='mb-3'>
                                                <label class='form-label'>Message</label>
                                                <textarea name='message' class='form-control' rows='3' required>" . htmlspecialchars($row['message']) . "</textarea>
                                            </div>
                                        </div>
                                        <div class='modal-footer'>
                                            <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                                            <button type='submit' class='btn btn-primary'>Update</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>