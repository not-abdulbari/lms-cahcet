<?php
echo 'HCAPTCHA_SITE_KEY: ' . htmlspecialchars(getenv('HCAPTCHA_SITE_KEY'));
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'faculty/db_connect.php';

$show_alert = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    // Verify hCaptcha
    $hcaptcha_response = $_POST['h-captcha-response'] ?? '';
    $hcaptcha_secret = getenv('HCAPTCHA_SECRET_KEY'); // GitHub Secret
    $hcaptcha_site_key = getenv('HCAPTCHA_SITE_KEY'); // GitHub Secret

    if (empty($hcaptcha_response)) {
        $show_alert = true;
        echo "<script>alert('Please complete the hCaptcha verification.');</script>";
    } else {
        $verify_url = "https://hcaptcha.com/siteverify";
        $data = [
            'secret' => $hcaptcha_secret,
            'response' => $hcaptcha_response
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $verify_response = file_get_contents($verify_url, false, $context);
        $response_data = json_decode($verify_response);

        if (!$response_data->success) {
            $show_alert = true;
            echo "<script>alert('hCaptcha verification failed. Please try again.');</script>";
        } else {
            // Proceed with login logic
            $input_username = $_POST['username'];
            $input_password = $_POST['password'];
            $input_hashed_password = hash('sha256', $input_password);

            $sql = "SELECT hashed_password FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $input_username);
            $stmt->execute();
            $stmt->bind_result($stored_hashed_password);
            $stmt->fetch();

            if ($input_hashed_password === $stored_hashed_password) {
                $_SESSION['logged_in'] = true;
                $stmt->close();
                $conn->close();
                header('Location: faculty/home.php');
                exit();
            } else {
                $show_alert = true;
                echo "<script>alert('Invalid username or password');</script>";
            }

            $stmt->close();
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>CAHCET - Student Management System</title>
    <style>
        /* Styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: #333;
        }

        .header img {
            width: 100%;
            height: auto;
        }

        .banner {
            background-color: #003366;
            color: white;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .main-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin: 20px;
        }

        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 30%;
            text-align: center;
            margin: 10px;
        }

        h2 {
            font-size: 22px;
            color: #6a11cb;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        input, button {
            width: 80%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
            font-size: 1em;
        }

        button {
            background: #2575fc;
            color: white;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #6a11cb;
        }

        .eye-icon {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .eye-icon i {
            position: absolute;
            right: 10%;
            cursor: pointer;
        }

        .notice_board {
            color: red;
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                align-items: center;
            }
            .container {
                width: 80%;
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://hcaptcha.com/1/api.js" async defer></script>
</head>

<body>
    <div class="header">
        <img src="assets/789asdfkl.webp" alt="LMS Portal Image">
    </div>

    <div class="banner">
        <marquee behavior="scroll" direction="left">
            <p>Welcome to the Learning Management System Portal.</p>
        </marquee>
    </div>

    <div class="main-container">
        <!-- Faculty Login -->
        <div class="container">
            <h2>Faculty Login</h2>
            <form action="" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <div class="eye-icon">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <i class="fas fa-eye-slash" id="togglePassword"></i>
                </div>
                <div class="h-captcha" data-sitekey="<?= htmlspecialchars(getenv('HCAPTCHA_SITE_KEY')) ?>"></div>
                <button type="submit">Login</button>
            </form>
        </div>

        <!-- Student Login -->
        <div class="container">
            <h2>Student Login</h2>
            <form action="student/student_profile.php" method="POST">
                <input type="text" name="roll_no" placeholder="Roll Number" required>
                <input type="text" name="dob" placeholder="Date of Birth (DD/MM/YYYY)" required>
                <div class="h-captcha" data-sitekey="<?= htmlspecialchars(getenv('HCAPTCHA_SITE_KEY')) ?>"></div>
                <button type="submit">Login</button>
            </form>
        </div>

        <!-- Notice Board -->
        <div class="container notice_board">
            <h2>Notice Board</h2>
            <marquee behavior="scroll" direction="left">
                <p>Important: Faculty and Student Login Details are available on the portal.</p>
                <p>System maintenance from 2:00 AM to 4:00 AM tomorrow.</p>
                <p>Mark your attendance before the deadline to avoid penalties.</p>
            </marquee>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', () => {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            togglePassword.classList.toggle('fa-eye-slash');
            togglePassword.classList.toggle('fa-eye');
        });
    </script>
</body>
</html>
