<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$show_alert = false; // Flag to control alert display

// Handle Institution Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    include 'faculty/db_connect.php';

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
        $show_alert = true; // Set flag for invalid credentials
    }

    $stmt->close();
    $conn->close();
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
    <title>Login Page</title>
    <style>
        /* Existing styles remain unchanged */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: #333;
        }

        .header {
            width: 100%;
            height: 250px;
        }

        .header img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }

        .banner {
            margin-top: 40px;
            background-color: #003366;
            color: white;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .banner marquee {
            font-size: 16px;
            font-weight: bold;
        }

        .main-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding: 0 15px;
        }

        .container,
        .notice_board {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 20px;
            margin: 10px;
            width: 30%;
        }

        h2 {
            font-size: 22px;
            color: #6a11cb;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        input {
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 6px;
            background-color: #f4f4f4;
            font-size: 1em;
            color: #333;
        }

        button {
            background-color: #2575fc;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
        }

        .eye-icon {
            display: flex;
            width: 100%;
            position: relative;
            justify-content: center;
            align-items: center;
        }

        .eye-icon i {
            position: absolute;
            right: 75px;
            color: grey;
        }

        .notice_board p {
            color: red;
        }

        button:hover {
            background-color: #6a11cb;
        }

        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }


        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                align-items: center;
            }

            .header {
                width: 100%;
                height: 70px;
            }

            .container,
            .notice_board {
                width: 80%;
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="header">
        <img src="assets/logo.jpg" alt="Counsellor's Book Image">
    </div>
    <div class="banner">
        <marquee behavior="scroll" direction="left">
            <p>Welcome to the Counsellor's Book System</p>
        </marquee>
    </div>
    <div class="main-container">
        <div class="container">
            <h2>Institution Login</h2>
            <form id="loginForm" action="" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <div class="eye-icon">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <i class="fas fa-eye-slash icon"></i>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
        <!-- Rest of your existing HTML remains unchanged -->
        <div class="container">
            <h2>Student Login</h2>
            <form action="student/parent111.php" method="POST">
                <input type="text" name="roll_no" placeholder="Roll Number" required>
                <input type="text" name="dob" placeholder="Date of Birth (DD/MM/YYYY)">
                <button type="submit">Login</button>
            </form>
        </div>
        <div class="notice_board">
            <h2>Notice Board</h2>
            <marquee behavior="scroll" direction="left">
                <p>Important: Faculty and Student Login Details are available on the portal.</p>
                <p>Note: The system will be down for maintenance from 2:00 AM to 4:00 AM tomorrow.</p>
                <p>Reminder: Mark your attendance before the deadline to avoid penalties.</p>
            </marquee>
        </div>
    </div>

    <script>
        document.querySelector('.icon').addEventListener('click', function () {
            let passwordInput = document.getElementById('password');
            let icon = document.querySelector('.icon');

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            }
        });

        // AJAX form submission
        $(document).ready(function () {
            $('#loginForm').on('submit', function (e) {
                e.preventDefault(); // Prevent the form from submitting

                $.ajax({
                    url: '', // The same page
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        if (response.includes('Invalid username or password')) {
                            alert('Invalid username or password');
                        } else {
                            window.location.href = 'faculty/home.php';
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>
