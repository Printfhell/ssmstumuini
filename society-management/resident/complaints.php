<?php
include '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'resident') {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $flat_id = $_POST['flat_id'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO complaints (flat_id, user_id, description, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$flat_id, $user_id, $description]);

    // Notify admin about new complaint
    $message = "User ID $user_id has submitted a new complaint.";
    $notify = $pdo->prepare("INSERT INTO notifications (user_id, message, notification_type, read_status) VALUES ((SELECT id FROM users WHERE role = 'admin' LIMIT 1), ?, 'complaint', 'unread')");
    $notify->execute([$message]);

    $success = "Complaint submitted successfully.";
}

$stmt = $pdo->prepare("SELECT c.*, f.flat_number FROM complaints c JOIN flat f ON c.flat_id = f.id WHERE c.user_id = ? ORDER BY c.created_at DESC");
$stmt->execute([$user_id]);
$complaints = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, flat_number FROM flat ORDER BY flat_number");

$flats = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-exclamation-triangle"></i> My Complaints</h1>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <div class="card mb-3">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="flat_id" class="form-label">Select Flat</label>
                    <select name="flat_id" id="flat_id" class="form-control" required>
                        <option value="">Select Flat</option>
                        <?php foreach ($flats as $flat): ?>
                            <option value="<?= $flat['id'] ?>"><?= htmlspecialchars($flat['flat_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Complaint Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                </div>
                <button type="submit" name="submit_complaint" class="btn btn-primary">Submit Complaint</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Search by description or status..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <?php if (isset($_GET['search'])): ?>
                            <a href="?action=list" class="btn btn-secondary ms-2">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            <?php if ($complaints): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Flat Number</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Master Comment</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $complaint): ?>
                            <tr>
                                <td><?= htmlspecialchars($complaint['flat_number']) ?></td>
                                <td><?= htmlspecialchars($complaint['description']) ?></td>
                                <td><?= htmlspecialchars($complaint['status']) ?></td>
                                <td><?= htmlspecialchars($complaint['master_comment'] ?? '') ?></td>
                                <td><?= htmlspecialchars($complaint['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No complaints found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
