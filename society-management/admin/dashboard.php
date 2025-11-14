<?php 
include '../config/db.php'; 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { 
    header('Location: ../auth/login.php'); 
    exit; 
} 
// Fetch stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_flats = $pdo->query("SELECT COUNT(*) FROM flat")->fetchColumn();
$unpaid_bills = $pdo->query("SELECT COUNT(*) FROM bills WHERE paid_amount < amount")->fetchColumn();
$total_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'pending'")->fetchColumn();
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
    <h1 class="mb-4 dashboard-title"><i class="fas fa-tachometer-alt"></i> Admin Dashboard - Barangay Balug Tumauini Isabela</h1>
    <div class="row dashboard-row">
        <div class="col-12 col-md-6 mb-4">
            <div class="card shadow-sm h-100 dashboard-card users-card">
                <div class="card-header bg-success text-white card-header-custom">
                    <h5 class="card-title mb-0"><i class="fas fa-users"></i> Total Users</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-center fs-1"><?php echo $total_users; ?></p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mb-4">
            <div class="card shadow-sm h-100 dashboard-card flats-card">
                <div class="card-header bg-warning text-dark card-header-custom">
                    <h5 class="card-title mb-0"><i class="fas fa-building"></i> Total Flats</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-center fs-1"><?php echo $total_flats; ?></p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mb-4">
            <div class="card shadow-sm h-100 dashboard-card bills-card">
                <div class="card-header bg-danger text-white card-header-custom">
                    <h5 class="card-title mb-0"><i class="fas fa-file-invoice-dollar"></i> Unpaid Bills</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-center fs-1"><?php echo $unpaid_bills; ?></p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mb-4">
            <div class="card shadow-sm h-100 dashboard-card complaints-card">
                <div class="card-header bg-info text-white card-header-custom">
                    <h5 class="card-title mb-0"><i class="fas fa-exclamation-triangle"></i> Pending Complaints</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-center fs-1"><?php echo $total_complaints; ?></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Recent Visitors Table -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-header bg-primary text-white card-header-custom">Recent Visitors</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead><tr><th>Name</th><th>Address</th><th>Phone</th><th>Time In</th><th>Time Out</th></tr></thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT name, address, phone, in_datetime, out_datetime FROM visitors ORDER BY in_datetime DESC LIMIT 5");
                            while ($row = $stmt->fetch()) {
                                $out_time = $row['out_datetime'] ?: 'N/A';
                                echo "<tr><td>{$row['name']}</td><td>{$row['address']}</td><td>{$row['phone']}</td><td>{$row['in_datetime']}</td><td>{$out_time}</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>



<?php include '../includes/footer.php'; ?>
