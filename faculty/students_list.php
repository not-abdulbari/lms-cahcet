<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
include 'head.php';
include 'db_connect.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch = $_POST['branch'];
    $year = $_POST['year'];
    $year_roman = isset($_POST['year_roman']) ? $_POST['year_roman'] : '';
    $section = $_POST['section'];
    $semester = $_POST['semester'];
    $exam = $_POST['exam'];


    // Fetch students based on criteria
    $sql = "SELECT roll_no, name FROM students WHERE branch = ? AND year = ? AND section = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $branch, $year, $section);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students List</title>
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
    <h2 style="text-align: center;">Students List</h2>
    <table>
        <tr>
            <th>Roll No</th>
            <th>Name</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['roll_no']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td>
                <form action="generate_pdf.php" method="post" style="margin: 0;">
                    <input type="hidden" name="roll_no" value="<?= htmlspecialchars($row['roll_no']) ?>">
                    <input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
                    <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
                    <input type="hidden" name="year_roman" value="<?= htmlspecialchars($year_roman) ?>">
                    <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
                    <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
                    <input type="hidden" name="exam" value="<?= htmlspecialchars($exam) ?>">
                    <button type="submit" class="btn">Generate PDF</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php
$conn->close();
?>
