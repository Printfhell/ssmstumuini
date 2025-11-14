<?php
$body_class = 'auth-page';
include '../config/db.php';

$message = '';
$error = '';

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $_SESSION['reset_email']]);

        unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['otp_time']);
        $message = "Password reset successfully. <a href='login.php'>Login now</a>";
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
                        <h3 class="auth-title text-center">Reset Password</h3>
                        <?php if ($message) echo "<p class='text-success text-center'>$message</p>"; ?>
                        <?php if ($error) echo "<p class='text-danger text-center'>$error</p>"; ?>

                        <?php if (!$message): ?>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control auth-input" required minlength="6">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control auth-input" required minlength="6">
                            </div>
                            <button type="submit" class="btn auth-btn w-100"><i class="fas fa-save"></i> Reset Password</button>
                        </form>
                        <?php endif; ?>

                        <p class="text-center mt-4"><a href="login.php" class="auth-link"><i class="fas fa-arrow-left"></i> Back to Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
