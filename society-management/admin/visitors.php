<?php 
include '../config/db.php'; 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { 
    header('Location: ../auth/login.php'); 
    exit; 
}

    // Create Visitor (Safe check for $_POST['action'])
if (isset($_POST['action']) && $_POST['action'] == 'create') {
    try {
        $stmt = $pdo->prepare("INSERT INTO visitors (name, address, person_to_meet, phone, reason, in_datetime) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$_POST['name'], $_POST['address'], $_POST['person_to_meet'], $_POST['phone'], $_POST['reason'], $_POST['in_datetime']])) {
            $success = "Visitor registered successfully!";
        } else {
            $error = "Failed to register visitor.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Checkout Visitor (Safe check for $_POST['action'] and $_POST['visitor_id'])
if (isset($_POST['action']) && $_POST['action'] == 'checkout' && isset($_POST['visitor_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE visitors SET is_in_out = 'out', out_datetime = ?, out_remark = ? WHERE id = ?");
        if ($stmt->execute([$_POST['out_datetime'], $_POST['out_remark'], $_POST['visitor_id']])) {
            $success = "Visitor checked out successfully!";
        } else {
            $error = "Failed to checkout visitor.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Update Visitor (Safe check for $_POST['action'])
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    try {
        $stmt = $pdo->prepare("UPDATE visitors SET name = ?, address = ?, person_to_meet = ?, phone = ?, reason = ? WHERE id = ?");
        if ($stmt->execute([$_POST['name'], $_POST['address'], $_POST['person_to_meet'], $_POST['phone'], $_POST['reason'], $_POST['visitor_id']])) {
            $success = "Visitor updated successfully!";
        } else {
            $error = "Failed to update visitor.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Delete Visitor (Safe check for $_GET['action'] and $_GET['id'])
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM visitors WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            $success = "Visitor record deleted successfully!";
        } else {
            $error = "Failed to delete visitor.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-user-friends"></i> Manage Visitors</h1>
    
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
    
    <!-- Create Form -->
    <div class="card mb-4">
        <div class="card-header">Register New Visitor</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Visitor's name" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Visitor's address" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Person to Meet</label>
                        <input type="text" name="person_to_meet" class="form-control" placeholder="Resident's name" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" placeholder="e.g., 09123456789">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g., Meeting" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">In Time</label>
                        <input type="datetime-local" name="in_datetime" class="form-control" required>
                    </div>
                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn btn-primary">Register Visitor</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- List Visitors -->
    <div class="card">
        <div class="card-header">Visitors List</div>
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Search by visitor name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
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
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Person to Meet</th>
                        <th>Reason</th>
                        <th>In Time</th>
                        <th>Status</th>
                        <th>Out Time</th>
                        <th>Out Remark</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $search = $_GET['search'] ?? '';
                    $query = "SELECT v.* FROM visitors v";
                    if (!empty($search)) {
                        $query .= " WHERE v.name LIKE :search";
                    }
                    $query .= " ORDER BY v.in_datetime DESC";
                    $stmt = $pdo->prepare($query);
                    if (!empty($search)) {
                        $stmt->bindValue(':search', '%' . $search . '%');
                    }
                    $stmt->execute();
                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch()) {
                            $status = $row['is_in_out'] == 'in' ? '<span class="badge bg-warning">In</span>' : '<span class="badge bg-success">Out</span>';

                            // Handle optional fields without ?: (compatible with older PHP)
                            $phone_display = !empty($row['phone']) ? $row['phone'] : 'N/A';
                            $out_datetime_display = !empty($row['out_datetime']) ? $row['out_datetime'] : 'N/A';
                            $out_remark_display = !empty($row['out_remark']) ? $row['out_remark'] : 'N/A';

                            $checkout_button = ($row['is_in_out'] == 'in') ? "<button class='btn btn-success btn-sm' data-bs-toggle='modal' data-bs-target='#checkoutModal{$row['id']}'>Checkout</button>" : '';

                            echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['name']}</td>
                                <td>{$phone_display}</td>
                                <td>{$row['person_to_meet']}</td>
                                <td>" . substr($row['reason'], 0, 20) . "...</td>
                                <td>{$row['in_datetime']}</td>
                                <td>{$status}</td>
                                <td>{$out_datetime_display}</td>
                                <td>{$out_remark_display}</td>
                                <td>
                                    {$checkout_button}
                                    <button class='btn btn-warning btn-sm ms-1' data-bs-toggle='modal' data-bs-target='#updateModal{$row['id']}'>Update</button>
                                    <a href='?action=delete&id={$row['id']}' class='btn btn-danger btn-sm ms-1' onclick='return confirm(\"Are you sure you want to delete this visitor record?\")'>Delete</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='10' class='text-center'>No visitors found. Register one above.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modals for Checkout and Update -->
    <?php
    $stmt = $pdo->query("SELECT v.* FROM visitors v ORDER BY v.in_datetime DESC");
    while ($row = $stmt->fetch()) {
        if ($row['is_in_out'] == 'in') {
            echo "
            <div class='modal fade' id='checkoutModal{$row['id']}' tabindex='-1'>
                <div class='modal-dialog'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title'>Checkout Visitor #{$row['id']}</h5>
                            <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                        </div>
                        <form method='POST'>
                            <div class='modal-body'>
                                <input type='hidden' name='action' value='checkout'>
                                <input type='hidden' name='visitor_id' value='{$row['id']}'>
                                <div class='mb-3'>
                                    <label class='form-label'>Out DateTime</label>
                                    <input type='datetime-local' name='out_datetime' class='form-control' required>
                                </div>
                                <div class='mb-3'>
                                    <label class='form-label'>Out Remark</label>
                                    <textarea name='out_remark' class='form-control' rows='3' placeholder='Remark...' required></textarea>
                                </div>
                            </div>
                            <div class='modal-footer'>
                                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                                <button type='submit' class='btn btn-success'>Checkout</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>";
        }

        echo "
        <div class='modal fade' id='updateModal{$row['id']}' tabindex='-1'>
            <div class='modal-dialog'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title'>Update Visitor #{$row['id']}</h5>
                        <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                    </div>
                    <form method='POST'>
                        <div class='modal-body'>
                            <input type='hidden' name='action' value='update'>
                            <input type='hidden' name='visitor_id' value='{$row['id']}'>

                            <div class='mb-3'>
                                <label class='form-label'>Name</label>
                                <input type='text' name='name' class='form-control' value='" . htmlspecialchars($row['name']) . "' required>
                            </div>
                            <div class='mb-3'>
                                <label class='form-label'>Address</label>
                                <input type='text' name='address' class='form-control' value='" . htmlspecialchars($row['address']) . "' required>
                            </div>
                            <div class='mb-3'>
                                <label class='form-label'>Person to Meet</label>
                                <input type='text' name='person_to_meet' class='form-control' value='" . htmlspecialchars($row['person_to_meet']) . "' required>
                            </div>
                            <div class='mb-3'>
                                <label class='form-label'>Phone</label>
                                <input type='tel' name='phone' class='form-control' value='" . htmlspecialchars($row['phone']) . "'>
                            </div>
                            <div class='mb-3'>
                                <label class='form-label'>Reason</label>
                                <input type='text' name='reason' class='form-control' value='" . htmlspecialchars($row['reason']) . "' required>
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
</div>
<?php include '../includes/footer.php'; ?>
