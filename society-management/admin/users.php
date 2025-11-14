<?php
include '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header('Location: ../auth/login.php'); exit; }

// Delete User (Safe check for $_GET['action'] and $_GET['id'])
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        // Start transaction for atomic deletion
        $pdo->beginTransaction();

        // Delete related records first to avoid foreign key constraints
        $deleteMessages = $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
        $deleteMessages->execute([$_GET['id'], $_GET['id']]);

        $deleteNotifications = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        $deleteNotifications->execute([$_GET['id']]);

        $deleteComplaints = $pdo->prepare("DELETE FROM complaints WHERE user_id = ?");
        $deleteComplaints->execute([$_GET['id']]);

        // Get flat_ids for this user to delete related bills and visitors
        $flatStmt = $pdo->prepare("SELECT flat_id FROM allotments WHERE user_id = ?");
        $flatStmt->execute([$_GET['id']]);
        $flatIds = $flatStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($flatIds)) {
            // Delete bills for user's flats
            $placeholders = str_repeat('?,', count($flatIds) - 1) . '?';
            $deleteBills = $pdo->prepare("DELETE FROM bills WHERE flat_id IN ($placeholders)");
            $deleteBills->execute($flatIds);

            // Delete visitors for user's flats
            $deleteVisitors = $pdo->prepare("DELETE FROM visitors WHERE flat_id IN ($placeholders)");
            $deleteVisitors->execute($flatIds);
        }

        $deleteAllotments = $pdo->prepare("DELETE FROM allotments WHERE user_id = ?");
        $deleteAllotments->execute([$_GET['id']]);

        // Delete profile picture if it exists
        $profilePicPath = "../images/users/{$_GET['id']}.jpg";
        if (file_exists($profilePicPath)) {
            unlink($profilePicPath);
        }

        // Finally delete the user
        $deleteUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($deleteUser->execute([$_GET['id']])) {
            $pdo->commit();
            // Reset AUTO_INCREMENT to 1 for the next registration to get ID 1 if available
            $pdo->exec("ALTER TABLE users AUTO_INCREMENT = 1");
            $success = "User and all associated records deleted successfully!";
        } else {
            $pdo->rollBack();
            $error = "Failed to delete user.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Database error: " . $e->getMessage();
    }
}

// Add Admin (Safe check for $_POST['add_admin'])
if (isset($_POST['add_admin']) && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->rowCount() > 0) {
        $error = "Duplicate Email: The email you entered is already in use. Please try another one.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, password, email, role) VALUES (?, ?, ?, 'admin')");
        if ($stmt->execute([$name, $password, $email])) {
            $success = "Admin added successfully!";
        } else {
            $error = "Failed to add admin.";
        }
    }
}

// Change Password (Safe check for $_POST['change_password'] and $_POST['user_id'])
if (isset($_POST['change_password']) && isset($_POST['user_id']) && isset($_POST['new_password'])) {
    try {
        $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $_POST['user_id']])) {
            $success = "Password changed successfully!";
        } else {
            $error = "Failed to change password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-users"></i> Users List</h1>
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAdminModal"><i class="fas fa-plus"></i> Add New Admin</button>
    </div>

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

    <?php if (isset($chat_success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $chat_success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Users</div>
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Search by user name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <?php if (isset($_GET['search'])): ?>
                            <a href="?action=list" class="btn btn-secondary ms-2">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            <div class="row">
                <?php
                $search = $_GET['search'] ?? '';
                $query = "SELECT * FROM users WHERE id != ?";
                if (!empty($search)) {
                    $query .= " AND name LIKE :search";
                }
                $query .= " ORDER BY created_at DESC";
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(1, $_SESSION['user_id']);
                if (!empty($search)) {
                    $stmt->bindValue(':search', '%' . $search . '%');
                }
                while ($user = $stmt->fetch()) {
                    $profile_pic = file_exists("../images/users/{$user['id']}.jpg") ? "../images/users/{$user['id']}.jpg?v=" . time() : "https://via.placeholder.com/150?text=User +{$user['id']}";
                    $badge_class = ($user['role'] == 'admin' ? 'danger' : 'primary');
                    echo <<<HTML
<div class='col-md-4 mb-3'>
    <div class='card h-100'>
        <img src='{$profile_pic}' class='card-img-top' alt='Profile' style='height: 200px; object-fit: cover;'>
        <div class='card-body'>
            <h5 class='card-title'>{$user['name']}</h5>
            <p class='card-text'>Email: {$user['email']}<br>Role: <span class='badge bg-{$badge_class}'>{$user['role']}</span><br>Joined: {$user['created_at']}</p>
            <div class='mt-2'>
                <button class='btn btn-primary btn-sm' data-bs-toggle='modal' data-bs-target='#userModal{$user['id']}'>View Details</button>
                <a href='?action=delete&id={$user['id']}' class='btn btn-danger btn-sm ms-1' onclick="return confirm('Are you sure you want to delete this?')">Delete</a>
            </div>
        </div>
    </div>
</div>
HTML;
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAdminModalLabel">Add New Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="add_admin" value="1">
                        <div class="mb-3">
                            <label for="admin_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="admin_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="admin_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="admin_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="admin_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="admin_password" name="password" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-success">Add Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- User Detail Modals -->
    <?php
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    while ($user = $stmt->fetch()) {
        echo <<<HTML
<div class="modal fade" id="userModal{$user['id']}" tabindex="-1" aria-labelledby="userModalLabel{$user['id']}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel{$user['id']}">User Details: {$user['name']}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Name:</strong> {$user['name']}</p>
                <p><strong>Email:</strong> {$user['email']}</p>
                <p><strong>Password:</strong> [Hidden for security]</p>
                <p><strong>Role:</strong> {$user['role']}</p>
                <p><strong>Joined:</strong> {$user['created_at']}</p>
                <hr>
                <h6>Change Password</h6>
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <input type="hidden" name="user_id" value="{$user['id']}">
                    <div class="mb-3">
                        <label for="new_password{$user['id']}" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password{$user['id']}" name="new_password" required>
                    </div>
                    <button type="submit" class="btn btn-warning">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
HTML;
    }
    ?>
    <?php if (isset($_GET['user'])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('userModal<?php echo intval($_GET['user']); ?>'));
            modal.show();
        });
        </script>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
