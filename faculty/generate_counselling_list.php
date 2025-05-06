<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
include 'head.php'; // Ensure no output or whitespace exists in this file
include 'db_connect.php'; // Ensure no output or whitespace exists in this file

// Get form data from query parameters
$branch = $_GET['branch'];
$year = $_GET['year'];
$year_roman = $_GET['year_roman'];
$section = $_GET['section'];
$batch = $_GET['batch'];
$semester = $_GET['semester'];
$exam = $_GET['exam'];
$faculty_code = $_GET['faculty_code'];

// Fetch students based on criteria
$sql = "SELECT roll_no, name, reg_no FROM students WHERE branch = ? AND year = ? AND section = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $branch, $year, $section);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Counselling List</title>
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f7f9fc, #e4f1fe);
            color: #333;
        }

        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        table, th, td {
            border: 1px solid #ddd; 
        }

        th, td {
            padding: 12px;
            text-align: left;
            font-size: 16px;
            color: #2c3e50; 
        }

        th {
            background-color: #f7f9fc; 
            font-weight: bold;
        }

        .btn {
            padding: 10px 15px;
            background-color: #3498db; 
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn:hover {
            background-color: #2980b9; 
            transform: scale(1.05); 
        }

        .btn:active {
            transform: scale(1); 
        }

        h2 {
            text-align: center;
            margin: 20px 0;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <h2>Counselling List</h2>
    <table>
        <tr>
            <th>Roll No</th>
            <th>Name</th>
            <th>Reg No</th>
            <th>Action</th>
        </tr>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['roll_no']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['reg_no']) ?></td>
                <td>
                    <form action="generate_counselling_report.php" method="post" style="margin: 0;">
                        <input type="hidden" name="roll_no" value="<?= htmlspecialchars($row['roll_no']) ?>">
                        <input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
                        <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
                        <input type="hidden" name="year_roman" value="<?= htmlspecialchars($year_roman) ?>">
                        <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
                        <input type="hidden" name="batch" value="<?= htmlspecialchars($batch) ?>">
                        <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
                        <input type="hidden" name="exam" value="<?= htmlspecialchars($exam) ?>">
                        <input type="hidden" name="faculty_code" value="<?= htmlspecialchars($faculty_code) ?>">
                        <button type="submit" class="btn">Generate Counselling Report</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align: center;">No students found for the selected criteria.</td>
            </tr>
        <?php endif; ?>
    </table>
    <div style="text-align: center; margin: 20px;">
        <a href="counselling_report.php" class="btn">Back to Selection</a>
    </div>
</body>
</html>

<?php
if (isset($conn)) {
    $conn->close();
}
?>
