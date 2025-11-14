<?php
include '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'resident') {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT a.*, f.flat_number, f.floor, f.flat_type FROM allotments a JOIN flat f ON a.flat_id = f.id WHERE a.user_id = ? ORDER BY a.created_at DESC");
$stmt->execute([$user_id]);
$allotments = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-building"></i> My Allotments</h1>
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Search by flat number or type..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <?php if (isset($_GET['search'])): ?>
                            <a href="?action=list" class="btn btn-secondary ms-2">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            <?php if ($allotments): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Flat Number</th>
                            <th>Floor</th>
                            <th>Type</th>
                            <th>Move In Date</th>
                            <th>Move Out Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allotments as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['flat_number']) ?></td>
                                <td><?= htmlspecialchars($a['floor']) ?></td>
                                <td><?= htmlspecialchars($a['flat_type']) ?></td>
                                <td><?= htmlspecialchars($a['move_in_date']) ?></td>
                                <td><?= htmlspecialchars($a['move_out_date'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No allotments found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
