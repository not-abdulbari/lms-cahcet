<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
include 'head.php';
include 'db_connect.php'; // Include your database connection file

// Check if we need to show the selection form
$showForm = true;
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch = $_POST['branch'];
    $year = $_POST['year'];
    $section = $_POST['section'];
    $semester = isset($_POST['semester']) ? $_POST['semester'] : '';
    $exam = isset($_POST['exam']) ? $_POST['exam'] : 'University';

    // Convert year to Roman numeral
    $romanNumerals = ['I', 'II', 'III', 'IV', 'V'];
    $year_roman = isset($romanNumerals[$year-1]) ? $romanNumerals[$year-1] : $year;

    // Fetch students based on criteria
    $sql = "SELECT roll_no, name, reg_no FROM students WHERE branch = ? AND year = ? AND section = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $branch, $year, $section);
    $stmt->execute();
    $result = $stmt->get_result();
    $showForm = false;
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

        /* Form Styling */
        .selection-form {
            width: 80%;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
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

        /* Heading styles */
        h2 {
            text-align: center;
            margin: 20px 0;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <?php if ($showForm): ?>
    <!-- Selection Form -->
    <div class="selection-form">
        <h2>Select Class Details</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="branch">Branch:</label>
                <select id="branch" name="branch" required>
                    <option value="">Select Branch</option>
                    <option value="CSE">Computer Science and Engineering</option>
                    <option value="ECE">Electronics and Communication Engineering</option>
                    <option value="EEE">Electrical and Electronics Engineering</option>
                    <option value="MECH">Mechanical Engineering</option>
                    <option value="CIVIL">Civil Engineering</option>
                    <option value="IT">Information Technology</option>
                    <option value="AIDS">Artificial Intelligence & Data Science</option>
                    <option value="MBA">Master of Business Administration</option>
                    <option value="MCA">Master of Computer Applications</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="year">Year:</label>
                <select id="year" name="year" required>
                    <option value="">Select Year</option>
                    <option value="1">I</option>
                    <option value="2">II</option>
                    <option value="3">III</option>
                    <option value="4">IV</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="section">Section:</label>
                <select id="section" name="section" required>
                    <option value="">Select Section</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="semester">Semester:</label>
                <select id="semester" name="semester">
                    <option value="">Select Semester</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="exam">Exam Type:</label>
                <select id="exam" name="exam">
                    <option value="University">University</option>
                    <option value="CAT1">CAT1</option>
                    <option value="CAT2">CAT2</option>
                    <option value="Model">Model</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Get Students</button>
        </form>
    </div>
    <?php else: ?>
    <!-- Students Table -->
    <h2>Students List</h2>
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
                    <form action="generate_university_pdf.php" method="post" style="margin: 0;">
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
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align: center;">No students found for the selected criteria.</td>
            </tr>
        <?php endif; ?>
    </table>
    <div style="text-align: center; margin: 20px;">
        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn">Back to Selection</a>
    </div>
    <?php endif; ?>
</body>
</html>

<?php
if (isset($conn)) {
    $conn->close();
}
?>