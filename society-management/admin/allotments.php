<?php 
include '../config/db.php'; 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { 
    header('Location: ../auth/login.php'); 
    exit; 
}

// Create Allotment (Safe check for $_POST['action'])
if (isset($_POST['action']) && $_POST['action'] == 'create') {
    try {
        // Check if user already has an allotment
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM allotments WHERE user_id = ? AND (move_out_date IS NULL OR move_out_date > CURDATE())");
        $check_stmt->execute([$_POST['user_id']]);
        if ($check_stmt->fetchColumn() > 0) {
            $error = "User already has an active allotment.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO allotments (flat_id, user_id, move_in_date, move_out_date) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$_POST['flat_id'], $_POST['user_id'], $_POST['move_in_date'], $_POST['move_out_date'] ?? null])) {
                $success = "Allotment created successfully!";
            } else {
                $error = "Failed to create allotment.";
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Update Allotment (Safe check for $_POST['action'])
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    try {
        $stmt = $pdo->prepare("UPDATE allotments SET user_id = ?, move_in_date = ?, move_out_date = ? WHERE id = ?");
        if ($stmt->execute([$_POST['user_id'], $_POST['move_in_date'], $_POST['move_out_date'] ?? null, $_POST['allotment_id']])) {
            $success = "Allotment updated successfully!";
        } else {
            $error = "Failed to update allotment.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Delete Allotment (Safe check for $_GET['action'] and $_GET['id'])
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM allotments WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            $success = "Allotment deleted successfully!";
        } else {
            $error = "Failed to delete allotment.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-home"></i> Manage Allotments</h1>
    
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
    
    <!-- Assign Flat Form -->
    <div class="card mb-4">
        <div class="card-header">Assign Flat and Allot User </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Select Flat</label>
                        <select name="flat_id" class="form-control" required>
                            <option value="">Select Flat</option>
                            <?php
                            $flats = $pdo->query("SELECT id, flat_number FROM flat ORDER BY flat_number");
                            while ($flat = $flats->fetch()) {
                                echo "<option value='{$flat['id']}'>{$flat['flat_number']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Select Resident</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">Select User</option>
                            <?php
                            $users = $pdo->query("SELECT id, name FROM users WHERE role='resident' ORDER BY name");
                            while ($user = $users->fetch()) {
                                echo "<option value='{$user['id']}'>{$user['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Move In Date</label>
                        <input type="date" name="move_in_date" class="form-control" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Assign</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- List Allotments -->
    <div class="card">
        <div class="card-header">Allotments List</div>
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
                        <th>Flat</th>
                        <th>Move In</th>
                        <th>Move Out</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                            $search = $_GET['search'] ?? '';
                            $query = "SELECT a.*, u.name as user_name, f.flat_number FROM allotments a JOIN users u ON a.user_id = u.id JOIN flat f ON a.flat_id = f.id";
                            if (!empty($search)) {
                                $query .= " WHERE u.name LIKE :search";
                            }
                            $query .= " ORDER BY a.created_at DESC";
                            $stmt = $pdo->prepare($query);
                            if (!empty($search)) {
                                $stmt->bindValue(':search', '%' . $search . '%');
                            }
                            $stmt->execute();
                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch()) {
                            echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['user_name']}</td>
                                <td>{$row['flat_number']}</td>
                                <td>{$row['move_in_date']}</td>
                                <td>" . ($row['move_out_date'] ?: 'Ongoing') . "</td>
                                <td>{$row['created_at']}</td>
                                <td>
                                    <button class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#updateModal{$row['id']}'>Update</button>
                                    <a href='?action=delete&id={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this allotment?\")'>Delete</a>
                                </td>
                            </tr>";
                            
                            // Modal for Update
                            echo "
                            <div class='modal fade' id='updateModal{$row['id']}' tabindex='-1'>
                                <div class='modal-dialog'>
                                    <div class='modal-content'>
                                        <div class='modal-header'>
                                            <h5 class='modal-title'>Update Allotment #{$row['id']}</h5>
                                            <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                        </div>
                                        <form method='POST'>
                                            <div class='modal-body'>
                                                <input type='hidden' name='action' value='update'>
                                                <input type='hidden' name='allotment_id' value='{$row['id']}'>

                                                <div class='mb-3'>
                                                    <label class='form-label'>Select Resident</label>
                                                    <select name='user_id' class='form-control' required>
                                                        <option value=''>Select User</option>";
                                                        $users_modal = $pdo->query("SELECT u.id, u.name, f.flat_number FROM users u LEFT JOIN allotments a ON u.id = a.user_id AND (a.move_out_date IS NULL OR a.move_out_date > CURDATE()) LEFT JOIN flat f ON a.flat_id = f.id WHERE u.role='resident' ORDER BY u.name");
                                                        while ($user_modal = $users_modal->fetch()) {
                                                            $selected = ($user_modal['id'] == $row['user_id']) ? 'selected' : '';
                                                            $flat_display = $user_modal['flat_number'] ? " - {$user_modal['flat_number']}" : "";
                                                            echo "<option value='{$user_modal['id']}' {$selected}>{$user_modal['name']}{$flat_display}</option>";
                                                        }
                                                    echo "</select>
                                                </div>
                                                <div class='mb-3'>
                                                    <label class='form-label'>Move In Date</label>
                                                    <input type='date' name='move_in_date' class='form-control' value='{$row['move_in_date']}' required>
                                                </div>
                                                <div class='mb-3'>
                                                    <label class='form-label'>Move Out Date</label>
                                                    <input type='date' name='move_out_date' class='form-control' value='{$row['move_out_date']}'>
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
                        echo "<tr><td colspan='7' class='text-center'>No allotments found. Create one above.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
