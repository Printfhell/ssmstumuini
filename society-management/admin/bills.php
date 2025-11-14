<?php 
include '../config/db.php'; 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { 
    header('Location: ../auth/login.php'); 
    exit; 
}

// Create Bill (Safe check for $_POST['action'])
if (isset($_POST['action']) && $_POST['action'] == 'create') {
    try {
        // Get flat_id from user_id
        $flat_stmt = $pdo->prepare("SELECT flat_id FROM allotments WHERE user_id = ? AND (move_out_date IS NULL OR move_out_date > CURDATE()) LIMIT 1");
        $flat_stmt->execute([$_POST['user_id']]);
        $flat = $flat_stmt->fetch();
        if ($flat) {
            $stmt = $pdo->prepare("INSERT INTO bills (flat_id, bill_title, amount, month, payment_method) VALUES (?, ?, ?, ?, 'cash')");
            if ($stmt->execute([$flat['flat_id'], $_POST['bill_title'], $_POST['amount'], $_POST['month']])) {
                $bill_id = $pdo->lastInsertId();
                $success = "Bill created successfully!";

                // Create notification for selected user
                $user_id = $_POST['user_id'];
                if ($user_id) {
                    $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, event_id, message, notification_type) VALUES (?, ?, ?, 'bill')");
                    $notif_stmt->execute([$user_id, $bill_id, "New bill: {$_POST['bill_title']} for ₱" . number_format($_POST['amount'], 2)]);
                }
            } else {
                $error = "Failed to create bill.";
            }
        } else {
            $error = "Selected resident does not have an assigned flat.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Mark Paid (Safe check for $_POST['action'] and $_POST['bill_id'])
if (isset($_POST['action']) && $_POST['action'] == 'pay' && isset($_POST['bill_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE bills SET paid_amount = ?, paid_date = ?, payment_method = 'cash', status = 'paid' WHERE id = ?");
        if ($stmt->execute([$_POST['paid_amount'], $_POST['paid_date'], $_POST['bill_id']])) {
            $success = "Bill marked as paid!";
        } else {
            $error = "Failed to update bill.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Reject Payment (Safe check for $_POST['action'] and $_POST['bill_id'])
if (isset($_POST['action']) && $_POST['action'] == 'reject' && isset($_POST['bill_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE bills SET status = 'unpaid', proof_file = NULL WHERE id = ?");
        if ($stmt->execute([$_POST['bill_id']])) {
            $success = "Payment rejected. Bill status reset to unpaid.";
        } else {
            $error = "Failed to reject payment.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Delete Bill (Safe check for $_GET['action'] and $_GET['id'])
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        // First, fetch the proof_file to delete it
        $fetch_stmt = $pdo->prepare("SELECT proof_file FROM bills WHERE id = ?");
        $fetch_stmt->execute([$_GET['id']]);
        $bill = $fetch_stmt->fetch();
        if ($bill && !empty($bill['proof_file'])) {
            $proof_path = __DIR__ . '/../uploads/' . $bill['proof_file'];
            if (file_exists($proof_path)) {
                unlink($proof_path);
            }
        }

        // Now delete the bill
        $stmt = $pdo->prepare("DELETE FROM bills WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            $success = "Bill deleted successfully!";
        } else {
            $error = "Failed to delete bill.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-file-invoice-dollar"></i> Manage Bills (Cash Only)</h1>

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

    <!-- Create Form -->
    <div class="card mb-4 dashboard-card">
        <div class="card-header bg-primary text-white card-header-custom">Add New Bill</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Select Resident</label>
                        <select name="user_id" id="user_id" class="form-control" required>
                            <option value="">Select Resident</option>
                            <?php
                            $users = $pdo->query("SELECT id, name FROM users WHERE role != 'admin' ORDER BY name");
                            while ($user = $users->fetch()) {
                                echo "<option value='{$user['id']}'>{$user['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bill Title</label>
                        <input type="text" name="bill_title" class="form-control" placeholder="e.g., Maintenance" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Amount (₱)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Month</label>
                        <input type="text" name="month" class="form-control" placeholder="e.g., Jan 2023" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Add Bill</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- List Bills -->
    <div class="card dashboard-card">
        <div class="card-header bg-success text-white card-header-custom">Bills List</div>
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
                        <th>Title</th>
                        <th>Amount (₱)</th>
                        <th>Paid Amount (₱)</th>
                        <th>Month</th>
                        <th>Status</th>
                        <th>Paid Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $search = $_GET['search'] ?? '';
                    $query = "SELECT b.*, f.flat_number, u.name as resident_name FROM bills b JOIN flat f ON b.flat_id = f.id JOIN allotments a ON a.flat_id = b.flat_id JOIN users u ON a.user_id = u.id";
                    if (!empty($search)) {
                        $query .= " WHERE u.name LIKE :search";
                    }
                    $query .= " ORDER BY b.created_at DESC";
                    $stmt = $pdo->prepare($query);
                    if (!empty($search)) {
                        $stmt->bindValue(':search', '%' . $search . '%');
                    }
                    $stmt->execute();
                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch()) {
                            $status_badge = '';
                            if ($row['status'] == 'paid') {
                                $status_badge = '<span class="badge bg-success">Paid</span>';
                            } elseif ($row['status'] == 'pending') {
                                $status_badge = '<span class="badge bg-warning">Pending Review</span>';
                            } else {
                                $status_badge = '<span class="badge bg-danger">Unpaid</span>';
                            }

                            // Handle optional paid_date without ?: (compatible with older PHP)
                            $paid_date_display = !empty($row['paid_date']) ? $row['paid_date'] : 'N/A';

                            $amount_formatted = number_format($row['amount'], 2);
                            $paid_amount_formatted = number_format($row['paid_amount'], 2);

                            echo "<tr>";
                            echo "<td>{$row['id']}</td>";
                            echo "<td>{$row['resident_name']}</td>";
                            echo "<td>{$row['bill_title']}</td>";
                            echo "<td>{$amount_formatted}</td>";
                            echo "<td>{$paid_amount_formatted}</td>";
                            echo "<td>{$row['month']}</td>";
                            echo "<td>{$status_badge}</td>";
                            echo "<td>{$paid_date_display}</td>";
                            echo "<td>";
                            echo "<div class='bill-action-group'>";
                            if ($row['status'] == 'pending') {
                                $proof_path = __DIR__ . '/../uploads/' . $row['proof_file'];
                                if (file_exists($proof_path)) {
                                    $file_ext = strtolower(pathinfo($row['proof_file'], PATHINFO_EXTENSION));
                                    echo "<button class='btn btn-info btn-sm' data-bs-toggle='modal' data-bs-target='#proofModal' data-file='../uploads/{$row['proof_file']}' data-type='{$file_ext}'>View Proof</button>";
                                } else {
                                    echo "<span class='text-muted'>Proof not available</span>";
                                }
                                echo "<form method='POST' class='d-inline'>";
                                echo "<input type='hidden' name='action' value='pay'>";
                                echo "<input type='hidden' name='bill_id' value='{$row['id']}'>";
                                echo "<input type='number' step='0.01' name='paid_amount' value='{$row['amount']}' class='form-control form-control-sm bill-input' placeholder='Paid Amt' required min='0'>";
                                echo "<input type='date' name='paid_date' class='form-control form-control-sm bill-input' required>";
                                echo "<button type='submit' class='btn btn-success btn-sm bill-btn'>Approve</button>";
                                echo "</form>";
                                echo "<form method='POST' class='d-inline'>";
                                echo "<input type='hidden' name='action' value='reject'>";
                                echo "<input type='hidden' name='bill_id' value='{$row['id']}'>";
                                echo "<button type='submit' class='btn btn-warning btn-sm' onclick='return confirm(\"Reject this payment?\")'>Reject</button>";
                                echo "</form>";
                            } else {
                                echo "<form method='POST' class='bill-action-form'>";
                                echo "<input type='hidden' name='action' value='pay'>";
                                echo "<input type='hidden' name='bill_id' value='{$row['id']}'>";
                                echo "<input type='number' step='0.01' name='paid_amount' value='{$row['amount']}' class='form-control form-control-sm bill-input' placeholder='Paid Amt' required min='0'>";
                                echo "<input type='date' name='paid_date' class='form-control form-control-sm bill-input' required>";
                                echo "<button type='submit' class='btn btn-success btn-sm bill-btn'>Paid</button>";
                                echo "</form>";
                            }
                            echo "<a href='?action=delete&id={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this?\")'>Delete</a>";
                            echo "</div>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9' class='text-center'>No bills found. Create one above.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Proof Modal -->
<div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proofModalLabel">Payment Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="proofContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var proofModal = document.getElementById('proofModal');
    proofModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var file = button.getAttribute('data-file');
        var type = button.getAttribute('data-type');
        var proofContent = document.getElementById('proofContent');
        proofContent.innerHTML = '';
        if (type === 'pdf') {
            var embed = document.createElement('embed');
            embed.src = file;
            embed.type = 'application/pdf';
            embed.width = '100%';
            embed.height = '600px';
            proofContent.appendChild(embed);
        } else {
            var img = document.createElement('img');
            img.src = file;
            img.alt = 'Payment Proof';
            img.className = 'img-fluid';
            proofContent.appendChild(img);
        }
    });
});
</script>


