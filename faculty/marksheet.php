<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
include 'db_connect.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if keys exist in the $_POST array and assign default values if they do not.
    $branch = isset($_POST['branch']) ? $_POST['branch'] : '';
    $year = isset($_POST['year']) ? $_POST['year'] : '';
    $year_roman = $conn->real_escape_string($_POST['year_roman']);
    $section = isset($_POST['section']) ? $_POST['section'] : '';
    $semester = isset($_POST['semester']) ? $_POST['semester'] : '';
    $subject = isset($_POST['subject']) ? $_POST['subject'] : '';
    $exam = isset($_POST['exam']) ? $_POST['exam'] : '';
    $exam_date = isset($_POST['exam_date']) ? $_POST['exam_date'] : '';
    $faculty_code = isset($_POST['faculty_code']) ? $_POST['faculty_code'] : '';

    // Fetch marks and student details if all parameters are set
    if (!empty($branch) && !empty($year) && !empty($section) && !empty($semester) && !empty($subject) && !empty($exam)) {
        $marks = $conn->query("SELECT m.roll_no, s.name, m.marks 
                               FROM marks m 
                               JOIN students s ON m.roll_no = s.roll_no 
                               WHERE m.branch = '$branch' 
                               AND m.year = '$year' 
                               AND m.section = '$section' 
                               AND m.semester = '$semester' 
                               AND m.subject = '$subject' 
                               AND m.exam = '$exam' 
                               ORDER BY m.roll_no ASC");
        // Debugging: Check if query executed successfully
        if (!$marks) {
            echo 'Error: ' . $conn->error;
        }
    } else {
        $marks = null;
    }

    // Fetch faculty details if faculty code is provided
    if (!empty($faculty_code)) {
        $faculty = $conn->query("SELECT faculty_name FROM faculty WHERE faculty_code = '$faculty_code'")->fetch_assoc();
        // Debugging: Check if query executed successfully
        if (!$faculty) {
            echo 'Error: ' . $conn->error;
        }
    } else {
        $faculty = null;
    }

    // Fetch subject details if subject code is provided
    $subject_details = $conn->query("SELECT subject_code, subject_name FROM subjects WHERE subject_code = '$subject'")->fetch_assoc();
    // Debugging: Check if query executed successfully
    if (!$subject_details) {
        echo 'Error: ' . $conn->error;
    }

    $subjectName = $subject_details['subject_name'] ?? 'N/A';

    $departmentNames = [
        "CSE" => "Department of Computer Science and Engineering",
    "ECE" => "Department of Electronics and Communication Engineering",
    "EEE" => "Department of Electrical and Electronics Engineering",
    "MECH" => "Department of Mechanical Engineering",
    "CIVIL" => "Department of Civil Engineering",
    "IT" => "Department of Information Technology",
    "AIDS" => "Department of Artificial Intelligence & Data Science",
    "MBA" => "Department of Master of Business Administration",
    "MCA" => "Department of Master of Computer Applications",
    ];

    $department = isset($departmentNames[$branch]) ? $departmentNames[$branch] : "Department of $branch";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marksheet</title>
    <style>
    @media print {
        body { margin: 14px; font-family: Times New Roman; font-size: 14px; }
        .no-print { display: none; }
        table { width: 50%; border-collapse: collapse; margin-top: 14px; margin-left: auto; margin-right: auto; }
        th, td { border: 1px solid #000; padding: 2px; text-align: left; font-size: 12px; } /* Reduced padding */
        .header { text-align: center; display: flex; align-items: center; justify-content: center; }
        h3 { margin-bottom: -10px; }
        .header img { margin-top: 10px; height: 90px; }
        .exam-type { font-size: 18px; font-weight: bold; text-align: center; margin-top: 10px; }
        .info-container { display: flex; justify-content: space-between; margin-top: 20px; }
        .info-left, .info-right { width: 48%; }
        .signatures { margin-top: 150px; display: flex; justify-content: space-between; margin-right: 20px; }
    
    }
    @media screen {
        body { margin: 14px; font-family: Times New Roman; font-size: 14px; }
        table { width: 50%; border-collapse: collapse; margin-top: 14px; margin-left: auto; margin-right: auto; }
        th, td { border: 1px solid #000; padding: 2px; text-align: left; font-size: 12px; } /* Reduced padding */
        .header { text-align: center; display: flex; align-items: center; justify-content: center; }
        h3 { margin-bottom: -10px; }
        .header img { margin-top: 10px; height: 90px; }
        .exam-type { font-size: 18px; font-weight: bold; text-align: center; margin-top: 10px; }
        .info-container { display: flex; justify-content: space-between; margin-top: 20px; }
        .info-left, .info-right { width: 48%; }
        .signatures { margin-top: 150px; display: flex; justify-content: space-between; margin-right: 20px; }
    }
    </style>
    <script>
        function printMarksList() {
            window.print();
        }
    </script>
</head>
<body>
<div class="no-print">
    <button class="print-btn" onclick="printMarksList()">Print Marks List</button>
</div>

<div class="header">
    <img src="../assets/24349bb44aaa1a8c.jpg" alt="College Logo">
    <div>
        <h3>C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY</h3>
        <h3>MELVISHARAM - 632509</h3>
        <h3><?= htmlspecialchars($department) ?></h3> <!-- Dynamic Department Name -->
        <h3>Academic Year 2024 - 2025 (EVEN)</h3>
    </div>
</div>
<p style="text-align: center;">______________________________________________________________________________________________</p>

<div class="container">
    <h2 style="text-align: center;"><?= htmlspecialchars($exam) ?> MARK SHEET</h2>
    <?php if (isset($marks) && $marks !== null && $marks->num_rows > 0) { ?>
        <div class="marks-list">
        <div id="printContent">
            <div class="info-container">
                <div class="info-left">
                    <p><strong>Subject Handler:</strong> <?= htmlspecialchars($faculty['faculty_name'] ?? 'N/A') ?></p>
                    <p><strong>Subject:</strong> <?= htmlspecialchars($subject) ?> - <?= htmlspecialchars($subjectName) ?></p>
                </div>
                <div class="info-right">
                    <p><strong>Year/Sem/Sec:</strong> <?= htmlspecialchars("$year_roman / $semester / $section") ?></p>
                    <p><strong>Exam Date:</strong> <?= date('d/m/Y', strtotime(htmlspecialchars($exam_date))) ?></p>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>S. No.</th>
                        <th>Roll No.</th>
                        <th>Name</th>
                        <th>Marks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = $marks->fetch_assoc()) { ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['roll_no']) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['marks']) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="signatures">
                <div>Faculty In-Charge</div>
                <div>HOD</div>
            </div>
            <div class="no-print">
                <button class="print-btn" onclick="printMarksList()">Print Marks List</button>
            </div>
        </div>
    <?php } ?>
</div>

</body>
</html>
