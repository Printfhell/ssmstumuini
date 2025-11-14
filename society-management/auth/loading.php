<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading - Smart Society Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="shortcut icon" href="../images/OIP.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: url('../images/Background.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.6), rgba(40, 167, 69, 0.6));
            z-index: 1;
        }

        .loading-container {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .title {
            font-family: 'Montserrat', sans-serif;
            font-size: 4rem;
            font-weight: 700;
            background: linear-gradient(45deg,rgb(18, 82, 134),rgb(69, 100, 77));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 50px;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
            to { text-shadow: 0 0 20px rgba(76, 69, 32, 0.8), 0 0 30px rgba(40, 167, 69, 0.8); }
        }

        .loading-bar-container {
            width: 400px;
            height: 20px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            margin: 0 auto;
        }

        .loading-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #FFD700, #28A745);
            border-radius: 10px;
            position: relative;
        }

        .loading-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, rgba(255,255,255,0.3), transparent);
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .loading-text {
            margin-top: 20px;
            font-size: 1.2rem;
            background: linear-gradient(45deg, #FFD700, #28A745);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .title {
                font-size: 2rem;
                margin-bottom: 30px;
            }
            .loading-bar-container {
                width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="title">Smart Society Management System</div>
        <div class="loading-bar-container">
            <div class="loading-bar" id="loadingBar"></div>
        </div>
        <div class="loading-text">Loading... 0%</div>
    </div>

    <script>
        const loadingBar = document.getElementById('loadingBar');
        const loadingText = document.querySelector('.loading-text');
        let progress = 0;

        const interval = setInterval(() => {
            progress += 1;
            loadingBar.style.width = progress + '%';
            loadingText.textContent = `Loading... ${progress}%`;

            if (progress >= 100) {
                clearInterval(interval);
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 500);
            }
        }, 50); // 100ms * 100 = 10 seconds
    </script>
</body>
</html>
