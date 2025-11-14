<?php
$body_class = 'auth-page';
include '../config/db.php';
if ($_POST && $_POST['role'] == 'resident') {  // Only residents can register
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->rowCount() > 0) {
        $error = "Duplicate Email: The email you entered is already in use. Please try another one.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, password, email, role) VALUES (?, ?, ?, 'resident')");
        if ($stmt->execute([$name, $password, $email])) {
            $_SESSION['registration_success'] = "Registration Complete! You're now part of our community. Enjoy!";
            header("Location: login.php");
            exit();
        } else {
            $error = "Registration failed";
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<div class="auth-overlay">
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="row justify-content-center w-100">
            <div class="col-md-6 col-lg-4">
                <div class="auth-card">
                    <div class="auth-card-body">
                        <img src="../images/OIP.png" class="img-fluid d-block mx-auto mb-3" alt="" style="max-width: 200px;">
                        <h3 class="auth-title text-center">Register (Resident)</h3>
                        <?php if (isset($error)) echo "<div class='alert alert-danger text-center'>$error</div>"; ?>
                        <form method="POST" onsubmit="validateForm('regForm')">
                            <input type="hidden" name="role" value="resident" id="regForm">
                            <div class="mb-4">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control auth-input" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control auth-input" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control auth-input" required minlength="6">
                            </div>
                            <button type="submit" class="btn auth-btn w-100"><i class="fas fa-user-plus"></i> Register</button>
                        </form>
                        <p class="text-center mt-4">Already have account? <a href="login.php" class="auth-link"><i class="fas fa-sign-in-alt"></i> Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Duplicate Email Modal -->
<div class="modal fade" id="duplicateEmailModal" tabindex="-1" role="dialog" aria-labelledby="duplicateEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateEmailModalLabel">Duplicate Email</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                The email you entered is already in use. Please try another one.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>



<script>
<?php if (isset($error) && strpos($error, 'Duplicate Email') !== false): ?>
    $(document).ready(function() {
        $('#duplicateEmailModal').modal('show');
    });
<?php endif; ?>


</script>

<?php include '../includes/footer.php'; ?>
