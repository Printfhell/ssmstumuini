<?php 
include '../config/db.php'; 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { 
    header('Location: ../auth/login.php'); 
    exit; 
}

// Create Flat (Safe check for $_POST['action'])
if (isset($_POST['action']) && $_POST['action'] == 'create') {
    try {
        $stmt = $pdo->prepare("INSERT INTO flat (floor, flat_type, block_number, flat_number) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$_POST['floor'], $_POST['flat_type'], $_POST['block_number'], $_POST['flat_number']])) {
            $success = "Flat created successfully!";
        } else {
            $error = "Failed to create flat.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}



// Update Flat (Safe check for $_POST['action'])
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    try {
        $stmt = $pdo->prepare("UPDATE flat SET floor = ?, flat_type = ?, block_number = ?, flat_number = ? WHERE id = ?");
        if ($stmt->execute([$_POST['floor'], $_POST['flat_type'], $_POST['block_number'], $_POST['flat_number'], $_POST['flat_id']])) {
            $success = "Flat updated successfully!";
        } else {
            $error = "Failed to update flat.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Delete Flat (Force delete with cascade)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $flat_id = $_GET['id'];
    try {
        // Delete dependent records first
        $pdo->beginTransaction();
        $delete_queries = [
            "DELETE FROM allotments WHERE flat_id = ?",
            "DELETE FROM bills WHERE flat_id = ?",
            "DELETE FROM complaints WHERE flat_id = ?",
            "DELETE FROM visitors WHERE flat_id = ?",
        ];
        foreach ($delete_queries as $query) {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$flat_id]);
        }
        // Now delete the flat
        $stmt = $pdo->prepare("DELETE FROM flat WHERE id = ?");
        if ($stmt->execute([$flat_id])) {
            $pdo->commit();
            $success = "Flat and all associated records deleted successfully!";
        } else {
            $pdo->rollBack();
            $error = "Failed to delete flat.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Database error: " . $e->getMessage();
    }
}

include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-building"></i> Manage Flats</h1>
    
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

    <!-- Create Flat Form -->
    <div class="card mb-4 dashboard-card">
        <div class="card-header bg-success text-white card-header-custom">Create New Flat</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Floor</label>
                        <input type="number" name="floor" class="form-control" placeholder="e.g., 1" required min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Flat Type</label>
                        <input type="text" name="flat_type" class="form-control" placeholder="e.g., 1BHK" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Block Number</label>
                        <input type="text" name="block_number" class="form-control" placeholder="e.g., A">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Flat Number</label>
                        <input type="text" name="flat_number" class="form-control" placeholder="e.g., 101" required>
                    </div>
                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn btn-success">Create Flat</button>
                    </div>
                </div>
            </form>
        </div>
    </div>



    <!-- List Flats -->
    <div class="card dashboard-card">
        <div class="card-header bg-warning text-dark card-header-custom">Flats List</div>
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Search by flat number..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
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
                        <th>Floor</th>
                        <th>Type</th>
                        <th>Number</th>
                        <th>Block</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $modals = '';
                    $search = $_GET['search'] ?? '';
                    $query = "SELECT * FROM flat";
                    if (!empty($search)) {
                        $query .= " WHERE flat_number LIKE :search";
                    }
                    $query .= " ORDER BY floor ASC, flat_number ASC";
                    $stmt = $pdo->prepare($query);
                    if (!empty($search)) {
                        $stmt->bindValue(':search', '%' . $search . '%');
                    }
                    $stmt->execute();
                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch()) {
                            // Handle optional block_number without ?: (compatible with older PHP)
                            $block_display = !empty($row['block_number']) ? $row['block_number'] : 'N/A';
                            echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['floor']}</td>
                                <td>{$row['flat_type']}</td>
                                <td>{$row['flat_number']}</td>
                                <td>{$block_display}</td>
                                <td>{$row['created_at']}</td>
                                <td>
                                    <button class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#updateModal{$row['id']}'>Update</button>
                                    <a href='?action=delete&id={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to force delete this flat? All associated records (allotments, bills, complaints, visitors) will be deleted.\")'>Delete</a>
                                </td>
                            </tr>";

                            // Collect Modal for Update
                            $modals .= "
                            <div class='modal fade' id='updateModal{$row['id']}' tabindex='-1' aria-labelledby='updateModalLabel{$row['id']}' aria-hidden='true'>
                                <div class='modal-dialog'>
                                    <div class='modal-content'>
                                        <div class='modal-header'>
                                            <h5 class='modal-title' id='updateModalLabel{$row['id']}'>Update Flat #{$row['id']}</h5>
                                            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                        </div>
                                        <form method='POST'>
                                            <div class='modal-body'>
                                                <input type='hidden' name='action' value='update'>
                                                <input type='hidden' name='flat_id' value='{$row['id']}'>
                                                <div class='mb-3'>
                                                    <label class='form-label'>Floor</label>
                                                    <input type='number' name='floor' class='form-control' value='{$row['floor']}' required min='0'>
                                                </div>
                                                <div class='mb-3'>
                                                    <label class='form-label'>Flat Type</label>
                                                    <input type='text' name='flat_type' class='form-control' value='{$row['flat_type']}' required>
                                                </div>
                                                <div class='mb-3'>
                                                    <label class='form-label'>Block Number</label>
                                                    <input type='text' name='block_number' class='form-control' value='{$row['block_number']}'>
                                                </div>
                                                <div class='mb-3'>
                                                    <label class='form-label'>Flat Number</label>
                                                    <input type='text' name='flat_number' class='form-control' value='{$row['flat_number']}' required>
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
                        echo "<tr><td colspan='7' class='text-center'>No flats found. Create one above.</td></tr>";
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
