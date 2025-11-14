<?php
include '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'resident') {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch visitor records with optional search by name
$query = "SELECT v.*, f.flat_number FROM visitors v LEFT JOIN flat f ON v.flat_id = f.id";
if ($search) {
    $query .= " WHERE v.name LIKE :search";
}
$query .= " ORDER BY v.in_datetime DESC";

$stmt = $pdo->prepare($query);
if ($search) {
    $stmt->bindValue(':search', '%' . $search . '%');
}
$stmt->execute();
$visitors = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-users"></i> Visitors</h1>
    <!-- Search Form -->
    <form method="GET" class="mb-3">
        <div class="row">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search by visitor name..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if ($search): ?>
                    <a href="?action=list" class="btn btn-secondary ms-2">Clear</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
    <div class="card">
        <div class="card-body">
            <?php if ($visitors): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Person to Meet</th>
                            <th>Phone</th>
                            <th>Reason</th>
                            <th>In Time</th>
                            <th>Status</th>
                            <th>Out Time</th>
                            <th>Out Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitors as $visitor): ?>
                            <tr>
                                <td><?= htmlspecialchars($visitor['name']) ?></td>
                                <td><?= htmlspecialchars($visitor['address']) ?></td>
                                <td><?= htmlspecialchars($visitor['person_to_meet']) ?></td>
                                <td><?= htmlspecialchars($visitor['phone']) ?></td>
                                <td><?= htmlspecialchars($visitor['reason']) ?></td>
                                <td><?= htmlspecialchars($visitor['in_datetime']) ?></td>
                                <td><?= htmlspecialchars($visitor['is_in_out']) ?></td>
                                <td><?= htmlspecialchars($visitor['out_datetime'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($visitor['out_remark'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No visitors found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
