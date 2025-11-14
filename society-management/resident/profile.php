<?php
include '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'resident') {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $errors = [];

    // Validate
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";

    // Check if email is already taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) $errors[] = "Email is already taken.";

    if (empty($errors)) {
        // Update user
        $update_fields = "name = ?, email = ?";
        $params = [$name, $email];

        if (!empty($password)) {
            $update_fields .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $params[] = $user_id;
        $stmt = $pdo->prepare("UPDATE users SET $update_fields WHERE id = ?");
        $stmt->execute($params);

        // Handle profile picture upload
        if (isset($_FILES['profile_pic'])) {
            $error_code = $_FILES['profile_pic']['error'];
            if ($error_code == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_pic']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $target = "../images/users/{$user_id}.jpg";
                    $dir = dirname($target);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    // Delete old profile picture if it exists
                    if (file_exists($target)) {
                        unlink($target);
                    }
                    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
                        $success = "Profile updated successfully!";
                    } else {
                        $error = "Failed to upload profile picture.";
                    }
                } else {
                    $error = "Invalid file type for profile picture.";
                }
            } elseif ($error_code == 4) {
                $success = "Profile updated successfully! No image selected.";
            } else {
                $error = "Upload error: " . $error_code;
            }
        } else {
            $success = "Profile updated successfully!";
        }

        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } else {
        $error = implode("<br>", $errors);
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid dashboard-container">
    <h1 class="mb-4 dashboard-title"><i class="fas fa-user"></i> Resident Profile</h1>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Update Profile</div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter new password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"></label>
                            <input type="file" name="profile_pic" class="form-control" accept="image/*" id="profile-pic-input" style="display: none;">
                            <button style="width: 100%;" type="button" class="btn btn-primary me-2" onclick="document.getElementById('profile-pic-input').click();"><i class="fas fa-upload"></i> Choose File</button><br>
                            <small class="form-text text-muted">Upload a new profile picture (JPG, PNG, GIF) or use the camera icon.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center position-relative">
                            <img src="<?php echo file_exists("../images/users/{$user['id']}.jpg") ? "../images/users/{$user['id']}.jpg?v=" . time() : "https://via.placeholder.com/150?text=Resident"; ?>" class="img-fluid rounded-circle" alt="Profile Picture" style="width: 400px; height: 400px; object-fit: cover;">
                            <button type="button" class="btn btn-light position-absolute" style="bottom: 10px; right: 10px; border-radius: 50%; padding: 10px;" onclick="openCamera()">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Camera Modal -->
    <div id="camera-modal" class="modal" style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Take Photo</h5>
                    <button type="button" class="btn-close" onclick="closeCameraModal()"></button>
                </div>
                <div class="modal-body text-center">
                    <video id="camera-video" autoplay playsinline style="width: 100%; max-width: 400px;"></video>
                    <canvas id="camera-canvas" style="display: none;"></canvas>
                    <br><br>
                    <button class="btn btn-primary" onclick="capturePhoto()">Capture Photo</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let stream = null;

async function openCamera() {
    const modal = document.getElementById('camera-modal');
    modal.style.display = 'block';

    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true });
        const video = document.getElementById('camera-video');
        video.srcObject = stream;
    } catch (error) {
        console.error('Error accessing camera:', error);
        alert('Unable to access camera. Please check permissions.');
        closeCameraModal();
    }
}

function closeCameraModal() {
    const modal = document.getElementById('camera-modal');
    modal.style.display = 'none';

    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
}

function capturePhoto() {
    const video = document.getElementById('camera-video');
    const canvas = document.getElementById('camera-canvas');
    const context = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob(function(blob) {
        const file = new File([blob], 'profile-photo.jpg', { type: 'image/jpeg' });
        const profileInput = document.getElementById('profile-pic-input');
        const dt = new DataTransfer();
        dt.items.add(file);
        profileInput.files = dt.files;

        closeCameraModal();
    }, 'image/jpeg');
}
</script>

<?php include '../includes/footer.php'; ?>
