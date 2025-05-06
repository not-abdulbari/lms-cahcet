<?php
// Start output buffering to prevent "headers already sent" issues
ob_start();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

include 'head.php';
include 'db_connect.php'; // Include your database connection file

// Fetch branches
$branches_sql = "SELECT DISTINCT branch FROM students";
$branches_result = $conn->query($branches_sql);

// Fetch years
$years_sql = "SELECT DISTINCT year FROM students";
$years_result = $conn->query($years_sql);

// Fetch sections
$sections_sql = "SELECT DISTINCT section FROM students";
$sections_result = $conn->query($sections_sql);

// Fetch semesters
$semesters_sql = "SELECT DISTINCT semester FROM marks ORDER BY semester ASC";
$semesters_result = $conn->query($semesters_sql);

// Fetch exam types
$exam_types_sql = "SELECT DISTINCT exam FROM marks";
$exam_types_result = $conn->query($exam_types_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data
    $_SESSION['form_data'] = [
        'branch' => $_POST['branch'],
        'year' => $_POST['year'],
        'year_roman' => $_POST['year_roman'],
        'section' => $_POST['section'],
        'batch' => $_POST['batch'],
        'semester' => $_POST['semester'],
        'exam' => $_POST['exam'],
        'faculty_code' => $_POST['faculty_code']
    ];

    // Redirect to generate_counselling_list.php
    header("Location: generate_counselling_list.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Counselling Report</title>
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
    <div class="selection-form">
        <h2>Select Class Details</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="branch">Branch:</label>
                <select id="branch" name="branch" required>
                    <option value="">Select Branch</option>
                    <?php while ($row = $branches_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['branch']) ?>"><?= htmlspecialchars($row['branch']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="year">Year of Passing:</label>
                <select id="year" name="year" required>
                    <option value="">Select Year of Passing</option>
                    <?php while ($row = $years_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['year']) ?>"><?= htmlspecialchars($row['year']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="year_roman">Year (Roman):</label>
                <select id="year_roman" name="year_roman" required>
                    <option value="">Select Year</option>
                    <option value="I">I</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="section">Section:</label>
                <select id="section" name="section" required>
                    <option value="">Select Section</option>
                    <?php while ($row = $sections_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['section']) ?>"><?= htmlspecialchars($row['section']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="batch">Batch:</label>
                <select id="batch" name="batch" required>
                    <option value="">Select Batch</option>
                    <option value="I">I</option>
                    <option value="II">II</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="semester">Semester:</label>
                <select id="semester" name="semester" required>
                    <option value="">Select Semester</option>
                    <?php while ($row = $semesters_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['semester']) ?>"><?= htmlspecialchars($row['semester']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="exam">Exam:</label>
                <select id="exam" name="exam" required>
                    <option value="">Select Exam</option>
                    <?php while ($row = $exam_types_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['exam']) ?>"><?= htmlspecialchars($row['exam']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="faculty_code">Faculty Code:</label>
                <input type="text" id="faculty_code" name="faculty_code" placeholder="Enter Faculty Code" required>
            </div>
            
            <button type="submit" class="btn">Submit</button>
        </form>
    </div>
</body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}

// Flush output buffer
ob_end_flush();
?>
