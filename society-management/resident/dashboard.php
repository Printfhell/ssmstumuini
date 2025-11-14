<?php
include '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'resident') {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch allotment info
$stmt = $pdo->prepare("SELECT a.*, f.flat_number, f.floor, f.flat_type FROM allotments a JOIN flat f ON a.flat_id = f.id WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$allotment = $stmt->fetch();

// Fetch unpaid bills
$stmt = $pdo->prepare("SELECT * FROM bills b JOIN allotments a ON b.flat_id = a.flat_id WHERE a.user_id = ? AND (b.paid_amount < b.amount OR b.paid_amount IS NULL)");
$stmt->execute([$user_id]);
$bills = $stmt->fetchAll();

// Fetch complaints
$stmt = $pdo->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$complaints = $stmt->fetchAll();

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<script>
window.userId = <?php echo json_encode($_SESSION['user_id']); ?>;
window.userRole = <?php echo json_encode($_SESSION['role']); ?>;
</script>

<!-- Chat Modal -->
<div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chatModalLabel"><i class="fas fa-comments"></i> Society Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="chatFrame" src="../chat.php" width="100%" height="600" frameborder="0"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Floating Chat Button -->
<div class="chathead" onclick="openChat()">
    <i class="fas fa-comments"></i>
</div>

<script>
function openChat() {
    // Show Bootstrap modal
    const chatModal = new bootstrap.Modal(document.getElementById('chatModal'));
    chatModal.show();
}
</script>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-home"></i> Resident Dashboard</h1>
    <div class="row dashboard-row">
        <div class="col-12 col-md-6 mb-4">
            <div class="card shadow-sm h-100 dashboard-card allotment-card">
                <div class="card-header bg-primary text-white card-header-custom">
                    <h5 class="card-title mb-0"><i class="fas fa-building"></i> Allotment</h5>
                </div>
                <div class="card-body">
                    <?php if ($allotment): ?>
                        <p><strong>Flat Number:</strong> <?= htmlspecialchars($allotment['flat_number']) ?></p>
                        <p><strong>Floor:</strong> <?= htmlspecialchars($allotment['floor']) ?></p>
                        <p><strong>Type:</strong> <?= htmlspecialchars($allotment['flat_type']) ?></p>
                        <p><strong>Move In:</strong> <?= htmlspecialchars($allotment['move_in_date']) ?></p>
                        <p><strong>Move Out:</strong> <?= htmlspecialchars($allotment['move_out_date'] ?? 'N/A') ?></p>
                    <?php else: ?>
                        <p class="text-muted">No allotment assigned.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mb-4">
            <div class="card shadow-sm h-100 dashboard-card bills-card">
                <div class="card-header bg-warning text-dark card-header-custom">
                    <h5 class="card-title mb-0"><i class="fas fa-file-invoice-dollar"></i> Unpaid Bills</h5>
                </div>
                <div class="card-body">
                    <?php if ($bills): ?>
                        <ul class="list-unstyled">
                            <?php foreach ($bills as $bill): ?>
                                <li class="mb-2">
                                    <strong><?= htmlspecialchars($bill['bill_title']) ?></strong><br>
                                    Amount: <span class="text-danger">₱<?= htmlspecialchars($bill['amount']) ?></span><br>
                                    Month: <?= htmlspecialchars($bill['month']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-success">No unpaid bills.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mb-4">
            <div class="card shadow-sm h-100 dashboard-card complaints-card">
                <div class="card-header bg-info text-white card-header-custom">
                    <h5 class="card-title mb-0"><i class="fas fa-exclamation-triangle"></i> Recent Complaints</h5>
                </div>
                <div class="card-body">
                    <?php if ($complaints): ?>
                        <ul class="list-unstyled">
                            <?php foreach ($complaints as $complaint): ?>
                                <li class="mb-2">
                                    <?= htmlspecialchars(substr($complaint['description'], 0, 50)) ?>...<br>
                                    <small class="text-muted">Status: <span class="badge bg-secondary"><?= htmlspecialchars($complaint['status']) ?></span></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No complaints submitted.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mb-4">
            <div class="card shadow-sm h-100 dashboard-card notifications-card">
                <div class="card-header bg-success text-white card-header-custom">
                    <h5 class="card-title mb-0"><i class="fas fa-bell"></i> Notifications</h5>
                </div>
                <div class="card-body">
                    <?php if ($notifications): ?>
                        <ul class="list-unstyled">
                            <?php foreach ($notifications as $notification): ?>
                                <li class="mb-2">
                                    <?= htmlspecialchars(substr($notification['message'], 0, 50)) ?>...<br>
                                    <small class="text-muted">Type: <?= htmlspecialchars($notification['notification_type']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No notifications.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>



<?php include '../includes/footer.php'; ?>
