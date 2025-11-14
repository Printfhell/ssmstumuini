<?php
include '../config/db.php'; 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { 
    header('Location: ../auth/login.php'); 
    exit; 
}

// Update Complaint (Safe check for $_POST['action'] and $_POST['complaint_id'])
if (isset($_POST['action']) && $_POST['action'] == 'update' && isset($_POST['complaint_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE complaints SET status = ?, master_comment = ? WHERE id = ?");
        if ($stmt->execute([$_POST['status'], $_POST['master_comment'], $_POST['complaint_id']])) {
            $success = "Complaint updated successfully!";
            
            // Create notification for resident
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, event_id, message, notification_type) VALUES (?, ?, ?, 'complaint')");
            $notif_stmt->execute([$_POST['user_id'], $_POST['complaint_id'], "Your complaint has been updated to: {$_POST['status']}"]);
        } else {
            $error = "Failed to update complaint.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Delete Complaint (Safe check for $_GET['action'] and $_GET['id'])
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM complaints WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            $success = "Complaint deleted successfully!";
        } else {
            $error = "Failed to delete complaint.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-exclamation-triangle"></i> Manage Complaints</h1>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show dashboard-card" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show dashboard-card" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- List Complaints -->
    <div class="card dashboard-card">
        <div class="card-header bg-info text-white card-header-custom">Complaints List</div>
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
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Resident</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Admin Comment</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $modals = '';
                    $search = $_GET['search'] ?? '';
                    $query = "SELECT c.*, u.name as user_name, f.flat_number FROM complaints c JOIN users u ON c.user_id = u.id JOIN flat f ON c.flat_id = f.id";
                    if (!empty($search)) {
                        $query .= " WHERE u.name LIKE :search";
                    }
                    $query .= " ORDER BY c.created_at DESC";
                    $stmt = $pdo->prepare($query);
                    if (!empty($search)) {
                        $stmt->bindValue(':search', '%' . $search . '%');
                    }
                    $stmt->execute();
                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch()) {
                            $status_badge = $row['status'] == 'pending' ? '<span class="badge bg-warning">Pending</span>' :
                                            ($row['status'] == 'resolved' ? '<span class="badge bg-success">Resolved</span>' :
                                            '<span class="badge bg-danger">Rejected</span>');
                            echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['user_name']}</td>
                                <td>" . substr($row['description'], 0, 50) . "...</td>
                                <td>{$status_badge}</td>
                                <td>" . ($row['master_comment'] ?: 'N/A') . "</td>
                                <td>{$row['created_at']}</td>
                                <td>
                                    <a href='?action=delete&id={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this complaint?\")'>Delete</a>
                                    <button class='btn btn-primary btn-sm' data-bs-toggle='modal' data-bs-target='#updateModal{$row['id']}'>Update</button>
                                </td>
                            </tr>";

                            // Collect Modal for Update
                            $modals .= "
                            <div class='modal fade' id='updateModal{$row['id']}' tabindex='-1' aria-labelledby='updateModalLabel{$row['id']}' aria-hidden='true'>
                                <div class='modal-dialog'>
                                    <div class='modal-content'>
                                        <div class='modal-header'>
                                            <h5 class='modal-title' id='updateModalLabel{$row['id']}'>Update Complaint #{$row['id']}</h5>
                                            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                        </div>
                                        <form method='POST'>
                                            <div class='modal-body'>
                                                <input type='hidden' name='action' value='update'>
                                                <input type='hidden' name='complaint_id' value='{$row['id']}'>
                                                <input type='hidden' name='user_id' value='{$row['user_id']}'>
                                                <div class='mb-3'>
                                                    <label class='form-label'>Status</label>
                                                    <select name='status' class='form-control' required>
                                                        <option value='pending' " . ($row['status'] == 'pending' ? 'selected' : '') . ">Pending</option>
                                                        <option value='resolved' " . ($row['status'] == 'resolved' ? 'selected' : '') . ">Resolved</option>
                                                        <option value='rejected' " . ($row['status'] == 'rejected' ? 'selected' : '') . ">Rejected</option>
                                                    </select>
                                                </div>
                                                <div class='mb-3'>
                                                    <label class='form-label'>Admin Comment</label>
                                                    <textarea name='master_comment' class='form-control' rows='3' placeholder='Your response...'>" . htmlspecialchars($row['master_comment']) . "</textarea>
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
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>No complaints found. Residents can submit them via their dashboard.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
// Output modals after the main content
echo $modals;
include '../includes/footer.php';
?>
