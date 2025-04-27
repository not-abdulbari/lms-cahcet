<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

include 'db_connect.php'; // Include your database connection file

$branch = $_POST['branch'];
$year = $_POST['year'];
$section = $_POST['section'];

// Fetch students based on criteria
$students_sql = "SELECT roll_no, name FROM students WHERE branch = ? AND year = ? AND section = ?";
$stmt = $conn->prepare($students_sql);
$stmt->bind_param('sis', $branch, $year, $section);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student List</title>
   <style>
    /* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body Styling */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f7f9fc, #e4f1fe); /* Soft gradient background */
    color: #333;
}

/* Table Styling */
table {
    width: 80%;
    margin: 20px auto;
    border-collapse: collapse;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add subtle shadow */
    background: #ffffff;
    border-radius: 8px; /* Rounded corners */
    overflow: hidden;
}

table, th, td {
    border: 1px solid #ddd; /* Subtle border color */
}

th, td {
    padding: 12px;
    text-align: left;
    font-size: 16px;
    color: #2c3e50; /* Elegant text color */
}

th {
    background-color: #f7f9fc; /* Soft header background */
    font-weight: bold;
}

/* Button Styling */
.btn {
    padding: 10px 15px;
    background-color: #3498db; /* Vibrant blue */
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
}

.btn:hover {
    background-color: #2980b9; /* Darker blue */
    transform: scale(1.05); /* Subtle zoom on hover */
}

.btn:active {
    transform: scale(1); /* Return to normal */
}
    </style>
</head>
<body>
    <h2>Student List</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($student = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($student['name']) ?></td>
                    <td>
                        <a href="feedback_generate.php?student_id=<?= $student['roll_no'] ?>" target="_blank">Download Feedback</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
