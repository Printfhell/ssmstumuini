<?php
$body_class = 'auth-page';
include '../config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_email'] = $email;
            $_SESSION['otp_time'] = time();

            $subject = "Password Reset OTP";
            $body = "Your OTP for password reset is: $otp\n\nThis OTP will expire in 10 minutes.";
            $headers = "From: noreply@yourdomain.com";

            // Send email and display OTP on screen
            $mail_sent = mail($email, $subject, $body, $headers);
            $message = "OTP sent to your email. Please check your inbox. (OTP: $otp)";
            if (!$mail_sent) {
                $message .= " Note: Email sending failed, but OTP is displayed here for testing.";
            }
        } else {
            $error = "Email not found.";
        }
    } elseif (isset($_POST['otp'])) {
        $otp = trim($_POST['otp']);
        if (isset($_SESSION['reset_otp']) && isset($_SESSION['reset_email']) && isset($_SESSION['otp_time'])) {
            if (time() - $_SESSION['otp_time'] > 600) { // 10 minutes
                $error = "OTP expired. Please request a new one.";
                unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['otp_time']);
            } elseif ($otp == $_SESSION['reset_otp']) {
                header('Location: reset_password.php');
                exit;
            } else {
                $error = "Invalid OTP.";
            }
        } else {
            $error = "Session expired. Please request OTP again.";
        }
    }
} elseif (isset($_GET['resend'])) {
    if (isset($_SESSION['reset_email'])) {
        $otp = rand(100000, 999999);
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_time'] = time();

        $email = $_SESSION['reset_email'];
        $subject = "Password Reset OTP";
        $body = "Your OTP for password reset is: $otp\n\nThis OTP will expire in 10 minutes.";
        $headers = "From: noreply@yourdomain.com";

        $mail_sent = mail($email, $subject, $body, $headers);
        $message = "OTP resent to your email. (OTP: $otp)";
        if (!$mail_sent) {
            $message .= " Note: Email sending failed, but OTP is displayed here for testing.";
        }
    } else {
        header('Location: forgot_password.php');
        exit;
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
                        <h3 class="auth-title text-center">Forgot Password</h3>
                        <?php if ($message) echo "<p class='text-success text-center'>$message</p>"; ?>
                        <?php if ($error) echo "<p class='text-danger text-center'>$error</p>"; ?>

                        <?php if (!isset($_SESSION['reset_otp'])): ?>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label">Enter your email</label>
                                <input type="email" name="email" class="form-control auth-input" required>
                            </div>
                            <button type="submit" class="btn auth-btn w-100"><i class="fas fa-paper-plane"></i> Send OTP</button>
                        </form>
                        <?php else: ?>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label">Enter OTP sent to <?php echo htmlspecialchars($_SESSION['reset_email']); ?></label>
                                <input type="text" name="otp" class="form-control auth-input" required maxlength="6">
                            </div>
                            <button type="submit" class="btn auth-btn w-100"><i class="fas fa-check"></i> Verify OTP</button>
                        </form>
                        <p class="text-center mt-3">
                            <a href="forgot_password.php?resend=1" class="auth-link">Resend OTP</a>
                        </p>
                        <?php endif; ?>

                        <p class="text-center mt-4"><a href="login.php" class="auth-link"><i class="fas fa-arrow-left"></i> Back to Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
