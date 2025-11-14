<?php
$body_class = 'auth-page';
include '../config/db.php';

// Check for registration success message
if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

if ($_POST) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $success = "You're In! Redirecting you to your dashboard...";
        $redirect_url = '../' . ($user['role'] == 'admin' ? 'admin' : 'resident') . '/dashboard.php';
    } else {
        $error = "Invalid credentials";
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
                        <h3 class="auth-title text-center">Login</h3>
                        <?php if (isset($error)) echo "<div class='alert alert-danger text-center'>$error</div>"; ?>
                        <?php if (isset($success)) echo "<div class='alert alert-success text-center'>$success</div>"; ?>
                        <?php if (isset($redirect_url)): ?>
                            <script>
                                setTimeout(function() {
                                    window.location.href = '<?php echo $redirect_url; ?>';
                                }, 2000);
                            </script>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control auth-input" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control auth-input" required>
                            </div>
                            <button type="submit" class="btn auth-btn w-100"><i class="fas fa-sign-in-alt"></i> Login</button>
                        </form>
                        <p class="text-center mt-4">
                            <a href="forgot_password.php" class="auth-link"><i class="fas fa-key"></i> Forgot Password?</a>
                        </p>
                        <p class="text-center">Don't have an account? <a href="register.php" class="auth-link"><i class="fas fa-user-plus"></i> Register</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Login Success Modal -->
<div class="modal fade" id="loginSuccessModal" tabindex="-1" role="dialog" aria-labelledby="loginSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginSuccessModalLabel">You're In!</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Redirecting you to your dashboard...
            </div>
        </div>
    </div>
</div>

<script>
<?php if (isset($success)): ?>
    $(document).ready(function() {
        $('#loginSuccessModal').modal('show');
        setTimeout(function() {
            window.location.href = '<?php echo $redirect_url; ?>';
        }, 2000);
    });
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>
