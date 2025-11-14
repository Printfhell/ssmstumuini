<?php
include 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'get_messages':
        $chat_id = $_POST['chat_id'] ?? '';
        $chat_type = $_POST['chat_type'] ?? '';

        if ($chat_type === 'group') {
            // Get group messages (messages with no specific receiver)
            $stmt = $pdo->prepare("
                SELECT m.*, u.name as sender_name, u.role as sender_role, u.id as sender_id
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.receiver_id IS NULL
                ORDER BY m.created_at ASC
            ");
            $stmt->execute();
        } else {
            // Get private messages between current user and selected user
            $stmt = $pdo->prepare("
                SELECT m.*, u.name as sender_name, u.role as sender_role, u.id as sender_id
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$user_id, $chat_id, $chat_id, $user_id]);
        }

        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add profile picture URLs to messages
        foreach ($messages as &$message) {
            $profile_pic_path = "../images/users/{$message['sender_id']}.jpg";
            $message['profile_pic'] = file_exists($profile_pic_path) ? $profile_pic_path : "data:image/svg+xml;base64," . base64_encode('<svg width="30" height="30" xmlns="http://www.w3.org/2000/svg"><circle cx="15" cy="15" r="15" fill="#007bff"/><text x="15" y="20" font-family="Arial" font-size="12" fill="white" text-anchor="middle">' . substr($message['sender_name'], 0, 1) . '</text></svg>');
        }
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    case 'send_message':
        $chat_id = $_POST['chat_id'] ?? '';
        $chat_type = $_POST['chat_type'] ?? '';
        $message = trim($_POST['message'] ?? '');

        // Allow empty message if files are attached
        $hasFiles = isset($_FILES['files']) && !empty($_FILES['files']['name'][0]);

        if (empty($message) && !$hasFiles) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
            exit;
        }

        if (strlen($message) > 500) {
            echo json_encode(['success' => false, 'message' => 'Message too long (max 500 characters)']);
            exit;
        }

        $filePaths = [];
        if ($hasFiles) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach ($_FILES['files']['name'] as $key => $fileName) {
                $fileTmp = $_FILES['files']['tmp_name'][$key];
                $fileSize = $_FILES['files']['size'][$key];
                $fileError = $_FILES['files']['error'][$key];

                // Validate file
                if ($fileError !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'message' => 'File upload error']);
                    exit;
                }

                if ($fileSize > 10 * 1024 * 1024) { // 10MB limit
                    echo json_encode(['success' => false, 'message' => 'File too large (max 10MB)']);
                    exit;
                }

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm', 'video/ogg', 'audio/mp3', 'audio/wav', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $fileType = mime_content_type($fileTmp);
                if (!in_array($fileType, $allowedTypes)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
                    exit;
                }

                // Generate unique filename
                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueName = uniqid() . '_' . time() . '.' . $fileExt;
                $filePath = $uploadDir . $uniqueName;

                if (move_uploaded_file($fileTmp, $filePath)) {
                    $filePaths[] = $filePath;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
                    exit;
                }
            }
        }

        try {
            if ($chat_type === 'group') {
                // Group message - no receiver_id
                if ($hasFiles) {
                    foreach ($filePaths as $filePath) {
                        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, message, file_path, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$user_id, $message, $filePath]);
                        $message = ''; // Only attach file to first message
                    }
                } else {
                    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, message, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$user_id, $message]);
                }
            } else {
                // Private message
                if ($hasFiles) {
                    foreach ($filePaths as $filePath) {
                        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, file_path, created_at) VALUES (?, ?, ?, ?, NOW())");
                        $stmt->execute([$user_id, $chat_id, $message, $filePath]);
                        $message = ''; // Only attach file to first message
                    }
                } else {
                    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $chat_id, $message]);
                }
            }

        echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'delete_message':
        $message_id = $_POST['message_id'] ?? '';

        try {
            // First, get the file_path if it exists
            $stmt = $pdo->prepare("SELECT file_path FROM messages WHERE id = ? AND sender_id = ?");
            $stmt->execute([$message_id, $user_id]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);

            // Delete the message
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
            $stmt->execute([$message_id, $user_id]);

            if ($stmt->rowCount() > 0) {
                // Delete the associated file if it exists
                if ($message && $message['file_path'] && file_exists($message['file_path'])) {
                    unlink($message['file_path']);
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Message not found or not authorized to delete']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'delete_history':
        $chat_id = $_POST['chat_id'] ?? '';
        $chat_type = $_POST['chat_type'] ?? '';

        try {
            // First, get all file_paths for messages to be deleted
            if ($chat_type === 'group') {
                $stmt = $pdo->prepare("SELECT file_path FROM messages WHERE sender_id = ? AND receiver_id IS NULL AND file_path IS NOT NULL");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $pdo->prepare("SELECT file_path FROM messages WHERE sender_id = ? AND receiver_id = ? AND file_path IS NOT NULL");
                $stmt->execute([$user_id, $chat_id]);
            }
            $filesToDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Delete the messages
            if ($chat_type === 'group') {
                $stmt = $pdo->prepare("DELETE FROM messages WHERE sender_id = ? AND receiver_id IS NULL");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM messages WHERE sender_id = ? AND receiver_id = ?");
                $stmt->execute([$user_id, $chat_id]);
            }

            // Delete associated files
            foreach ($filesToDelete as $filePath) {
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;



    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
