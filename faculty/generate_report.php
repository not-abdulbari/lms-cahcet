<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
include 'db_connect.php';

// Sanitize inputs
$branch = $conn->real_escape_string($_POST['branch']);
$year = $conn->real_escape_string($_POST['year']);
$year_roman = $conn->real_escape_string($_POST['year_roman']);
$section = $conn->real_escape_string($_POST['section']);
$semester = $conn->real_escape_string($_POST['semester']);
$subject = $conn->real_escape_string($_POST['subject']);
$exam = $conn->real_escape_string($_POST['exam']);
$faculty_code = $conn->real_escape_string($_POST['faculty_code']);
$exam_date = $conn->real_escape_string($_POST['exam_date']);

// Map branch names to department names
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

// Fetch subject name from subjects table
$subjectNameQuery = $conn->query("SELECT subject_name FROM subjects WHERE subject_code='$subject' AND branch='$branch' AND semester='$semester'");
$subjectNameRow = $subjectNameQuery->fetch_assoc();
$subjectName = $subjectNameRow['subject_name'] ?? "Unknown Subject";

// Fetch faculty name from faculty table
$facultyNameQuery = $conn->query("SELECT faculty_name FROM faculty WHERE faculty_code='$faculty_code'");
$facultyNameRow = $facultyNameQuery->fetch_assoc();
$facultyName = $facultyNameRow['faculty_name'] ?? "Unknown Faculty";

// Fetch total students
$totalStudentsQuery = $conn->query("
    SELECT COUNT(DISTINCT roll_no) FROM marks 
    WHERE branch='$branch' AND year='$year' 
    AND section='$section' AND semester='$semester'
");
$totalStudents = $totalStudentsQuery->fetch_row()[0] ?? 0;

// Fetch marks
$result = $conn->query("
    SELECT marks FROM marks 
    WHERE branch='$branch' AND year='$year' 
    AND section='$section' AND semester='$semester'
    AND subject='$subject' AND exam='$exam'
");

$absent = 0;
$passed = 0;
$ranges = array_fill(0, 6, 0); // 91-100, 81-90,..., <50

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mark = $row['marks'];

        if ($mark == '-1') {
            $absent++;
            continue;
        }

        $numericMark = (int)$mark;

        if ($numericMark >= 50) $passed++;

        if ($numericMark >= 91) $ranges[0]++;
        elseif ($numericMark >= 81) $ranges[1]++;
        elseif ($numericMark >= 71) $ranges[2]++;
        elseif ($numericMark >= 61) $ranges[3]++;
        elseif ($numericMark >= 50) $ranges[4]++;
        else $ranges[5]++;
    }
}

$appeared = $totalStudents - $absent;
$passPercentTotal = $totalStudents > 0 ? round(($passed / $totalStudents) * 100, 2) : 0;
$passPercentAppeared = $appeared > 0 ? round(($passed / $appeared) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Exam Result Analysis</title>
    <style>
        @media print {
            body { margin: 14px; font-family: Times new roman; font-size: 14px }
            .no-print { display: none; }
            table { width: 100%; border-collapse: collapse; margin-top: 14px; }
            th, td { border: 1px solid #000; padding: 8px; text-align:left; }
            .header { text-align: center; display: flex; align-items: center; justify-content: center; }
            h3{ margin-bottom: -10px;}
            .header img { margin-top:10px; height: 90px;}
            .exam-type { font-size: 18px; font-weight: bold; text-align: center; margin-top: 10px; }
            .info-container { display: flex; justify-content: space-between; margin-top: 20px; }
            .info-left, .info-right { width: 48%; }
            .signatures { margin-top: 150px; display: flex; justify-content: space-between; margin-right:20px;  }
        }
        @media screen {
           body { margin: 14px; font-family: Times new roman; font-size: 14px }
            .no-print { display: none; }
            table { width: 100%; border-collapse: collapse; margin-top: 14px; }
            th, td { border: 1px solid #000; padding: 8px; text-align:left; }
            .header { text-align: center; display: flex; align-items: center; justify-content: center; }
            h3{ margin-bottom: -10px;}
            .header img { margin-top:10px; height: 90px;}
            .exam-type { font-size: 18px; font-weight: bold; text-align: center; margin-top: 10px; }
            .info-container { display: flex; justify-content: space-between; margin-top: 20px; }
            .info-left, .info-right { width: 48%; }
            .signatures { margin-top: 150px; display: flex; justify-content: space-between; margin-right:20px;  }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="printMarksList()">Print Report Analysis</button>
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
    <div class="report-data">
        <h3 id="examTitle" style="text-align: center;">INTERNAL EXAM RESULT ANALYSIS</h3>
        <div class="exam-type"><?= htmlspecialchars($exam) ?></div> <!-- Exam Type Below Heading -->

        <div id="printContent">
            <div class="info-container">
                <div class="info-left">
                    <p><strong>Subject Handler:</strong> <?= htmlspecialchars($facultyName) ?></p>
                    <p><strong>Subject:</strong> <?= htmlspecialchars($subject) ?> - <?= htmlspecialchars($subjectName) ?></p>
                </div>
                <div class="info-right">
                    <p><strong>Year/Sem/Sec:</strong> <?= htmlspecialchars("$year_roman / $semester / $section") ?></p>
                    <p><strong>Exam Date:</strong> <?= date('d/m/Y', strtotime(htmlspecialchars($exam_date))) ?></p>
                </div>
            </div>

            <table>
                <tr><th>Total Number of Students</th><td><?= $totalStudents ?></td></tr>
                <tr><th>Number of Students Appeared</th><td><?= $appeared ?></td></tr>
                <tr><th>Number of Students Absent</th><td><?= $absent ?></td></tr>
                <tr><th>Number of Students Passed</th><td><?= $passed ?></td></tr>
                <tr><th>Number of Students Failed</th><td><?= $appeared - $passed ?></td></tr>
                <tr><th>Pass % Based on Total Students</th><td><?= $passPercentTotal ?>%</td></tr>
                <tr><th>Pass % Based on Appeared</th><td><?= $passPercentAppeared ?>%</td></tr>
            </table>

            <h4>Marks Distribution</h4>
            <table>
                <tr>
                    <th>Description</th>
                    <th>91-100</th>
                    <th>81-90</th>
                    <th>71-80</th>
                    <th>61-70</th>
                    <th>51-60</th>
                    <th>&lt;50</th>
                </tr>
                <tr>
                    <th>No. of Students</th>
                    <td><?= $ranges[0] ?></td>
                    <td><?= $ranges[1] ?></td>
                    <td><?= $ranges[2] ?></td>
                    <td><?= $ranges[3] ?></td>
                    <td><?= $ranges[4] ?></td>
                    <td><?= $ranges[5] ?></td>
                </tr>
            </table>
            <p><strong>Minimum Pass Marks: 50 Marks</strong></p>
            <div class="signatures">
                <div>Faculty In-Charge</div>
                <div>HOD</div>
            </div>
        </div>
    </div>

    <script>
    function printReport() {
        window.print();
    }

    document.addEventListener('DOMContentLoaded', function() {
        printReport();
    });
    </script>
</body>
</html>
