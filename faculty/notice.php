<?php
session_start();

// Only allow access if logged in as faculty/staff (institution login)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Not logged in, redirect to login page
    header('Location: ../index.php');
    exit();
}

// Path to the notice file (you can change the storage as needed)
$noticeFile = __DIR__ . '../notice/notice_board.txt';

// Handle notice update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notice'])) {
    $notice = trim($_POST['notice']);
    // Optionally sanitize or validate input
    file_put_contents($noticeFile, $notice);
    $message = "Notice board updated successfully.";
}

// Load current notice
$currentNotice = '';
if (file_exists($noticeFile)) {
    $currentNotice = file_get_contents($noticeFile);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Notice Board</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; padding: 40px; }
        .container {
            background: #fff;
            max-width: 600px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 30px;
        }
        h2 { color: #2575fc; }
        textarea {
            width: 100%;
            height: 150px;
            border-radius: 6px;
            border: 1.5px solid #ccc;
            font-size: 1em;
            padding: 10px;
            resize: vertical;
            background: #fafbfc;
        }
        button {
            background: #2575fc;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 24px;
            margin-top: 18px;
            font-size: 1em;
            cursor: pointer;
        }
        .msg { color: green; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Notice Board</h2>
        <?php if (isset($message)): ?>
            <div class="msg"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="notice"><strong>Notice Content:</strong></label><br>
            <textarea name="notice" id="notice" required><?= htmlspecialchars($currentNotice) ?></textarea><br>
            <button type="submit">Update Notice</button>
        </form>
        <p style="margin-top:30px;"><a href="../index.php">⬅️ Go back to Home</a></p>
    </div>
</body>
</html>
