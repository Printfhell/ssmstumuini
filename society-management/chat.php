<?php
include 'config/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'] ?? 'User';

// Get all users for chat list
$query = "SELECT u.id, u.name, u.role FROM users u WHERE u.id != ? ORDER BY u.name";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$users = $stmt->fetchAll();

// Filter users based on role: non-admins only see admin accounts
if ($user_role !== 'admin') {
    $users = array_filter($users, function($user) {
        return $user['role'] === 'admin';
    });
}

// Add profile pictures to users
foreach ($users as &$user) {
    $profile_pic_path = "images/users/{$user['id']}.jpg";
    $user['profile_pic'] = file_exists($profile_pic_path) ? $profile_pic_path . '?v=' . time() : "data:image/svg+xml;base64," . base64_encode('<svg width="40" height="40" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="20" fill="#007bff"/><text x="20" y="25" font-family="Arial" font-size="16" fill="white" text-anchor="middle">' . substr($user['name'], 0, 1) . '</text></svg>');
}

// Get recent messages for current user
$stmt = $pdo->prepare("
    SELECT m.*, u.name as sender_name, u.role as sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = ? OR m.receiver_id = ? OR m.receiver_id IS NULL)
    ORDER BY m.created_at DESC LIMIT 50
");
$stmt->execute([$user_id, $user_id]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Society Chat - <?php echo htmlspecialchars($user_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .chat-container {
            height: 80vh;
            display: flex;
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
        }
        .users-list {
            width: 300px;
            background: #f8f9fa;
            border-right: 1px solid #ddd;
            padding: 0;
        }
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #fff;
        }
        .chat-input-area {
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #ddd;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 10px;
            max-width: 70%;
        }
        .message.sent {
            background: #007bff;
            color: white;
            margin-left: auto;
            text-align: right;
        }
        .message.received {
            background: #e9ecef;
            color: #333;
        }
        .user-item {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
            transition: background 0.3s;
        }
        .user-item:hover {
            background: #e9ecef;
        }
        .user-item.active {
            background: #007bff;
            color: white;
        }
        .message-time {
            font-size: 0.8em;
            opacity: 0.7;
            margin-top: 5px;
        }
        .group-chat-btn {
            background: linear-gradient(135deg, #FFD700, #28A745);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            width: 100%;
        }
        .group-chat-btn:hover {
            background: linear-gradient(135deg, #FFA500, #FFD700);
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="fas fa-comments"></i> Society Chat
                    <small class="text-muted">Welcome, <?php echo htmlspecialchars($user_name); ?> (<?php echo ucfirst($user_role); ?>)</small>
                    <button id="delete-history-btn" class="btn btn-danger btn-sm float-end" style="display: none;" onclick="deleteChatHistory()">
                        <i class="fas fa-trash"></i> Delete History
                    </button>
                </h2>

                <div class="chat-container">
                    <!-- Users List -->
                    <div class="users-list">
                        <button class="group-chat-btn" onclick="selectChat('group')">
                            <i class="fas fa-users"></i> Group Chat (All)
                        </button>

                        <div class="list-group">
                            <?php foreach ($users as $user): ?>
                                <?php if ($user['id'] == $user_id) continue; ?>
                                <div class="user-item" id="user-<?php echo $user['id']; ?>" onclick="selectChat(<?php echo $user['id']; ?>)">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo ucfirst($user['role']); ?></small>
                                        </div>
                                        <i class="fas fa-circle text-success" style="font-size: 8px;"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>

                    <!-- Chat Area -->
                    <div class="chat-area">
                        <div id="chat-messages" class="chat-messages">
                            <div class="text-center text-muted mt-5">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Select a user or group to start chatting</p>
                            </div>
                        </div>

                        <div class="chat-input-area" id="chat-input-area" style="display: none;">
                            <div class="input-group">
                                <input type="text" id="message-input" class="form-control" placeholder="Type your message..." maxlength="500">
                                <input type="file" id="file-input" accept="image/*,video/*,audio/*,application/*" style="display: none;" multiple>
                                <button class="btn btn-outline-secondary" onclick="document.getElementById('file-input').click()">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                                <input type="file" id="camera-input" accept="image/*" capture="camera" style="display: none;">
                                <button class="btn btn-outline-secondary" onclick="openCamera()">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <button class="btn btn-primary" onclick="sendMessage()">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </div>
                            <div id="file-preview" class="mt-2" style="display: none;"></div>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentChatId = null;
        let currentChatType = null; // 'user' or 'group'
        let userId = <?php echo json_encode($user_id); ?>;
        let userName = <?php echo json_encode($user_name); ?>;
        let pollInterval;

        function selectChat(chatId) {
            currentChatId = chatId;
            currentChatType = (chatId === 'group') ? 'group' : 'user';

            // Update UI
            document.querySelectorAll('.user-item').forEach(item => item.classList.remove('active'));
            if (chatId !== 'group') {
                event.currentTarget.classList.add('active');
            }

            document.getElementById('chat-input-area').style.display = 'block';
            document.getElementById('delete-history-btn').style.display = 'none';
            document.getElementById('chat-messages').innerHTML = '<div class="text-center text-muted mt-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading messages...</p></div>';

            loadMessages();

            // Start polling for new messages
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(loadMessages, 3000);
        }

        function loadMessages() {
            if (!currentChatId) return;

            fetch('chat_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_messages&chat_id=${currentChatId}&chat_type=${currentChatType}&user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMessages(data.messages);
                }
            })
            .catch(error => console.error('Error loading messages:', error));
        }

        function displayMessages(messages) {
            const container = document.getElementById('chat-messages');
            const deleteBtn = document.getElementById('delete-history-btn');
            if (messages.length === 0) {
                container.innerHTML = '<div class="text-center text-muted mt-5"><i class="fas fa-comments fa-3x mb-3"></i><p>No messages yet. Start the conversation!</p></div>';
                deleteBtn.style.display = 'none';
                return;
            }

            deleteBtn.style.display = 'block';

            container.innerHTML = '';
            messages.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${message.sender_id == userId ? 'sent' : 'received'}`;

                const time = new Date(message.created_at).toLocaleString();
                const profilePic = message.profile_pic || 'data:image/svg+xml;base64,' + btoa('<svg width="30" height="30" xmlns="http://www.w3.org/2000/svg"><circle cx="15" cy="15" r="15" fill="#007bff"/><text x="15" y="20" font-family="Arial" font-size="12" fill="white" text-anchor="middle">' + message.sender_name.charAt(0) + '</text></svg>');

                let messageContent = message.message;
                if (message.file_path) {
                    const fileUrl = message.file_path;
                    const fileName = fileUrl.split('/').pop();
                    const fileExt = fileName.split('.').pop().toLowerCase();
                    if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                        messageContent += `<br><img src="${fileUrl}" alt="${fileName}" class="img-fluid rounded" style="max-width: 200px; max-height: 200px;">`;
                    } else if (['mp4', 'webm', 'ogg'].includes(fileExt)) {
                        messageContent += `<br><video controls style="max-width: 200px;"><source src="${fileUrl}" type="video/${fileExt}"></video>`;
                    } else if (['mp3', 'wav'].includes(fileExt)) {
                        messageContent += `<br><audio controls><source src="${fileUrl}" type="audio/${fileExt}"></audio>`;
                    } else {
                        messageContent += `<br><a href="${fileUrl}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> ${fileName}</a>`;
                    }
                }

                if (message.sender_id == userId) {
                    // Sent message
                    messageDiv.innerHTML = `
                        <div class="d-flex align-items-start justify-content-end">
                            <div class="flex-grow-1 text-end">
                                <div>${messageContent}</div>
                                <div class="message-time">${time} <button class="btn btn-sm btn-outline-danger ms-2" onclick="deleteMessage(${message.id})"><i class="fas fa-trash"></i></button></div>
                            </div>
                            <img src="${profilePic}" alt="Profile" class="rounded-circle ms-2" style="width: 30px; height: 30px; object-fit: cover;">
                        </div>
                    `;
                } else {
                    // Received message
                    messageDiv.innerHTML = `
                        <div class="d-flex align-items-start">
                            <img src="${profilePic}" alt="Profile" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <div>${messageContent}</div>
                                <div class="message-time">${message.sender_name} • ${time}</div>
                            </div>
                        </div>
                    `;
                }

                container.appendChild(messageDiv);
            });

            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
        }

        function sendMessage() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            const fileInput = document.getElementById('file-input');
            const cameraInput = document.getElementById('camera-input');
            const files = fileInput.files.length > 0 ? fileInput.files : cameraInput.files;

            if (!message && files.length === 0) return;
            if (!currentChatId) return;

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('chat_id', currentChatId);
            formData.append('chat_type', currentChatType);
            formData.append('message', message);
            formData.append('user_id', userId);

            if (files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                }
            }

            fetch('chat_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    fileInput.value = '';
                    cameraInput.value = '';
                    document.getElementById('file-preview').style.display = 'none';
                    loadMessages();
                } else {
                    alert('Error sending message: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Error sending message');
            });
        }

        // Handle file selection for preview
        document.getElementById('file-input').addEventListener('change', function(e) {
            previewFiles(e.target.files);
        });

        document.getElementById('camera-input').addEventListener('change', function(e) {
            previewFiles(e.target.files);
        });

        function previewFiles(files) {
            const preview = document.getElementById('file-preview');
            preview.innerHTML = '';
            if (files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const fileItem = document.createElement('div');
                    fileItem.className = 'd-inline-block me-2 mb-2';
                    fileItem.innerHTML = `<small class="badge bg-secondary">${file.name}</small>`;
                    preview.appendChild(fileItem);
                }
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }

        // Camera functions
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
                const file = new File([blob], 'camera-photo.jpg', { type: 'image/jpeg' });
                const cameraInput = document.getElementById('camera-input');
                const dt = new DataTransfer();
                dt.items.add(file);
                cameraInput.files = dt.files;

                previewFiles([file]);
                closeCameraModal();
            }, 'image/jpeg');
        }

        // Send message on Enter key
        document.getElementById('message-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        function deleteMessage(messageId) {
            if (!confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
                return;
            }

            fetch('chat_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_message&message_id=${messageId}&user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadMessages();
                } else {
                    alert('Error deleting message: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting message:', error);
                alert('Error deleting message.');
            });
        }

        function deleteChatHistory() {
            if (!currentChatId) return;

            if (!confirm('Are you sure you want to delete all chat history for this conversation? This action cannot be undone.')) {
                return;
            }

            fetch('chat_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_history&chat_id=${currentChatId}&chat_type=${currentChatType}&user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadMessages();
                    alert('Chat history deleted successfully.');
                } else {
                    alert('Error deleting chat history: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting chat history:', error);
                alert('Error deleting chat history.');
            });
        }



        // Load initial messages if any
        <?php if (isset($_GET['user']) && is_numeric($_GET['user'])): ?>
            selectChat(<?php echo intval($_GET['user']); ?>);
        <?php elseif (!empty($messages)): ?>
            selectChat('group');
        <?php endif; ?>
    </script>
</body>
</html>
