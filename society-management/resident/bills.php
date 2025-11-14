<?php
include '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'resident') {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch bills for user's allotments
$stmt = $pdo->prepare("SELECT b.*, f.flat_number FROM bills b JOIN allotments a ON b.flat_id = a.flat_id JOIN flat f ON b.flat_id = f.id WHERE a.user_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$bills = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notify_payment'])) {
    $bill_id = $_POST['bill_id'];
    $paid_amount = $_POST['paid_amount'];
    $paid_date = $_POST['paid_date'];
    $payment_method = 'cash';

    // Handle file upload
    $proof_file = null;
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['proof_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = 'proof_' . $bill_id . '_' . time() . '.' . $ext;
            $upload_path = '../uploads/' . $new_filename;
            if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $upload_path)) {
                $proof_file = $new_filename;
            }
        }
    }

    $update = $pdo->prepare("UPDATE bills SET paid_amount = ?, paid_date = ?, payment_method = ?, status = 'pending', proof_file = ? WHERE id = ?");
    $update->execute([$paid_amount, $paid_date, $payment_method, $proof_file, $bill_id]);

    // Notify admin about payment
    $bill = $pdo->prepare("SELECT * FROM bills WHERE id = ?");
    $bill->execute([$bill_id]);
    $bill_data = $bill->fetch();

    $message = "User ID $user_id has submitted payment proof for bill '{$bill_data['bill_title']}' amounting to ₱$paid_amount.";
    $notify = $pdo->prepare("INSERT INTO notifications (user_id, message, notification_type, read_status) VALUES ((SELECT id FROM users WHERE role = 'admin' LIMIT 1), ?, 'payment', 'unread')");
    $notify->execute([$message]);

    $success = "Payment proof submitted successfully. Status changed to pending.";
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-file-invoice-dollar"></i> My Bills</h1>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Search by bill title or month..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <?php if (isset($_GET['search'])): ?>
                            <a href="?action=list" class="btn btn-secondary ms-2">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            <?php if ($bills): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Bill Title</th>
                            <th>Flat Number</th>
                            <th>Amount</th>
                            <th>Month</th>
                            <th>Paid Amount</th>
                            <th>Paid Date</th>
                            <th>Payment Method</th>
                            <th>Notify Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td><?= htmlspecialchars($bill['bill_title']) ?></td>
                                <td><?= htmlspecialchars($bill['flat_number']) ?></td>
                                <td><?= htmlspecialchars($bill['amount']) ?></td>
                                <td><?= htmlspecialchars($bill['month']) ?></td>
                                <td><?= htmlspecialchars($bill['paid_amount'] ?? '0') ?></td>
                                <td><?= htmlspecialchars($bill['paid_date'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($bill['payment_method'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($bill['status'] == 'unpaid'): ?>
                                    <form method="POST" enctype="multipart/form-data" class="d-flex flex-column">
                                        <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                                        <input type="number" name="paid_amount" class="form-control form-control-sm mb-1" placeholder="Amount" required min="1" max="<?= $bill['amount'] ?>">
                                        <input type="date" name="paid_date" class="form-control form-control-sm mb-1" required>
                                        <input type="file" name="proof_file" class="form-control form-control-sm mb-1" accept=".jpg,.jpeg,.png,.pdf" required>
                                        <button type="submit" name="notify_payment" class="btn btn-success btn-sm">Submit Proof</button>
                                    </form>
                                    <?php elseif ($bill['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Pending Review</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No bills found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
