<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    include 'db_connect.php';

    $input_username = $_POST['username'];
    $input_password = $_POST['password'];
    $input_hashed_password = hash('sha256', $input_password);

    // Fetch hashed password from the database
    $sql = "SELECT hashed_password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $input_username);
    $stmt->execute();
    $stmt->bind_result($stored_hashed_password);
    $stmt->fetch();
    $stmt->close();

    if ($input_hashed_password === $stored_hashed_password) {
        $_SESSION['logged_in'] = true;
        header('Location: home.php');  // Redirect to dashboard after login
    } else {
        echo "<div class='error'>Invalid username or password.</div>";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Login</title>
    <style>
        /* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body Styling */
body {
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
    color: #fff;
}

/* Form Styling */
form {
    background-color: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
    text-align: center;
    color: #333;
    animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Header Styling */
h1 {
    font-size: 1.8em;
    margin-bottom: 20px;
    color: #6a11cb;
    font-weight: 600;
}

/* Input Styling */
input {
    width: 100%;
    padding: 12px;
    margin: 15px 0;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f4f4f4;
    font-size: 1em;
    color: #333;
    transition: border-color 0.3s, background-color 0.3s;
}

input:focus {
    border-color: #2575fc;
    outline: none;
    background-color: #fff;
}

/* Button Styling */
button {
    background-color: #2575fc;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    width: 100%;
    font-size: 1em;
    transition: background-color 0.3s, transform 0.3s;
}

button:hover {
    background-color: #6a11cb;
    transform: translateY(-2px);
}

button:active {
    transform: translateY(1px);
}

/* Error Message Styling */
.error {
    color: red;
    font-weight: bold;
    margin-top: 15px;
}

/* Forgot Password Link Styling */
.forgot-password {
    color: #2575fc;
    text-decoration: none;
    font-size: 0.9em;
    margin-top: 10px;
    display: inline-block;
}

.forgot-password:hover {
    text-decoration: underline;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    form {
        padding: 20px;
    }

    h1 {
        font-size: 1.5em;
    }

    input, button {
        padding: 10px;
    }
}

    </style>
</head>
<body>
    <form action="faculty_login.php" method="POST">
        <h1>Institution login</h1>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <a href="#" class="forgot-password">Forgot Password?</a>
    </form>
</body>
</html>
