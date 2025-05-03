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

$showForm = true;
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch = $_POST['branch'];
    $year = $_POST['year'];
    $year_roman = $_POST['year_roman'];
    $section = $_POST['section'];
    $batch = $_POST['batch'];
    $semester = $_POST['semester'];
    $exam = $_POST['exam'];
    $faculty_code = $_POST['faculty_code'];

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
    <title>Counselling List</title>
    <style>
        /* Base styling for body */
/* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body Styling */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #f7f9fc, #e4f1fe); /* Light gradient background */
    margin: 0;
    padding: 0;
    color: #333;
}

/* Header Styling */
h2 {
    text-align: center;
    color: #2c3e50;
    font-size: 28px;
    margin: 40px 0;
    font-weight: bold;
}

/* Form Styling */
form {
    max-width: 800px;
    margin: 30px auto;
    padding: 40px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease-in-out;
    border-left: 5px solid #3498db;
}

form:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

/* Form Elements Styling */
label {
    margin-top: 15px;
    display: block;
    font-weight: bold;
    color: #2c3e50;
    font-size: 16px;
}

select, button {
    width: 100%;
    padding: 12px;
    margin-top: 10px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f5f5f5;
    transition: background-color 0.3s, border-color 0.3s;
}

select:focus, button:focus {
    outline: none;
    border-color: #3498db;
}

select:hover, button:hover {
    background-color: #eaf2f8;
}

/* Button Styling */
button {
    background-color: #3498db;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

button:hover {
    background-color: #2980b9;
    transform: scale(1.05);
}

button:active {
    transform: scale(1);
}

/* Responsive Form Layout */
.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row .form-group {
    flex: 1;
    min-width: 250px;
}

.form-row select {
    width: 100%;
}

/* Specific Styling for Form-Group Labels */
.form-row .form-group label {
    font-weight: normal;
    font-size: 16px;
}

/* Interactive Select Dropdowns */
select {
    background-color: #f9f9f9;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 5px;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

select option:hover {
    background-color: #f1f1f1;
}

/* Button Hover for Different States */
button:focus {
    outline: none;
    border-color: #3498db;
}

/* Mobile Responsive Styling */
@media (max-width: 768px) {
    form {
        padding: 20px;
    }

    .form-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-row .form-group {
        width: 100%;
    }
}

/* Smooth Input Animations */
form {
    animation: fadeIn 1s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Placeholder Text Color */
input::placeholder,
select::placeholder {
    color: #bbb;
}

/* Gradient Background for the Page */
body {
    background: linear-gradient(135deg, #f7f9fc, #e4f1fe);
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

            <div class="form-group">
                <label>
                    <input type="checkbox" name="nba_logo" value="1"> Is NBA logo needed?
                </label>
            </div>
            
            <button type="submit" class="btn">Get Students</button>
        </form>
    </div>
    <?php else: ?>
    <!-- Students Table -->
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
    <div style="text-align: center ; margin: 20px;">
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
