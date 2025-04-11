<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
include 'db_connect.php';

// Sanitize inputs
$branch = isset($_POST['branch']) ? $conn->real_escape_string($_POST['branch']) : '';
$year = isset($_POST['year']) ? $conn->real_escape_string($_POST['year']) : '';
$year_roman = $conn->real_escape_string($_POST['year_roman']);
$section = isset($_POST['section']) ? $conn->real_escape_string($_POST['section']) : '';
$semester = isset($_POST['semester']) ? $conn->real_escape_string($_POST['semester']) : '';
$exam = isset($_POST['exam']) ? $conn->real_escape_string($_POST['exam']) : '';
$isNbaLogoNeeded = isset($_POST['nba_logo']) && $_POST['nba_logo'] == "1";


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

// Fetch all subjects for the given branch and semester
$subjectsQuery = $conn->query("SELECT DISTINCT subject FROM marks WHERE branch='$branch' AND semester='$semester'");
$subjects = [];
while ($row = $subjectsQuery->fetch_assoc()) {
    $subjects[] = $row['subject'];
}

$reportData = [];
$studentFailures = [];
$allStudents = [];

// Iterate over each subject to fetch and calculate data
foreach ($subjects as $subject) {
    // Fetch subject name from subjects table
    $subjectNameQuery = $conn->query("SELECT subject_name FROM subjects WHERE subject_code='$subject' AND branch='$branch' AND semester='$semester'");
    $subjectNameRow = $subjectNameQuery->fetch_assoc();
    $subjectName = $subjectNameRow['subject_name'] ?? "Unknown Subject";

    // Fetch marks
    $result = $conn->query("
        SELECT roll_no, marks FROM marks 
        WHERE branch='$branch' AND year='$year' 
        AND section='$section' AND semester='$semester'
        AND subject='$subject' AND exam='$exam'
    ");

    $absent = 0;
    $passed = 0;
    $failed = 0;
    $appeared = 0;

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $studentId = $row['roll_no'];
            $mark = $row['marks'];

            if (!isset($allStudents[$studentId])) {
                $allStudents[$studentId] = 0;
            }

            if ($mark == '-1') {
                $absent++;
                continue;
            }

            $numericMark = (int)$mark;
            $appeared++;

            if ($numericMark >= 50) {
                $passed++;
            } else {
                $failed++;
                $studentFailures[$studentId] = ($studentFailures[$studentId] ?? 0) + 1;
            }
        }
    }

    $passPercent = $appeared > 0 ? round(($passed / $appeared) * 100, 2) : 0;

    if ($appeared > 0) {
        $reportData[] = [
            'subject' => $subject,
            'subjectName' => $subjectName,
            'totalPresent' => $appeared,
            'totalAppear' => $appeared + $absent,
            'passed' => $passed,
            'failed' => $failed,
            'passPercent' => $passPercent,
        ];
    }
}

// Calculate student failure statistics only for the provided year, semester, section, and exam
$allCleared = 0;
$failedOne = 0;
$failedTwo = 0;
$failedThree = 0;
$failedMoreThanThree = 0;
$totalAppeared = 0;

foreach ($allStudents as $studentId => $count) {
    $failCount = $studentFailures[$studentId] ?? 0;

    if ($failCount == 0) {
        $allCleared++;
    } elseif ($failCount == 1) {
        $failedOne++;
    } elseif ($failCount == 2) {
        $failedTwo++;
    } elseif ($failCount == 3) {
        $failedThree++;
    } else {
        $failedMoreThanThree++;
    }

    if ($failCount >= 0) {
        $totalAppeared++;
    }
}

$overallPassPercent = $totalAppeared > 0 ? round(($allCleared / $totalAppeared) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Consolidated Exam Result Analysis</title>
    <style>
        @media print {
            body { margin: 14px; font-family: Times new roman; font-size: 14px }
            .no-print { display: none; }
            table { width: 100%; border-collapse: collapse; margin-top: 14px; }
            th, td { border: 1px solid #000; padding: 4px; text-align:left; }
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
    <div class="header">
        <img src="../assets/24349bb44aaa1a8c.jpg" alt="College Logo">
        <div>
            <h3>C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY</h3>
            <h3>MELVISHARAM - 632509</h3>
            <h3><?= htmlspecialchars($department) ?></h3> <!-- Dynamic Department Name -->
            <h3>Academic Year 2024 - 2025 (EVEN)</h3>
        </div>
        <?php if ($isNbaLogoNeeded) { ?>
            <img src="../assets/nba-logo.svg" alt="NBA Logo" style="height: 90px;">
        <?php } ?>
    </div>
    <p style="text-align: center;">______________________________________________________________________________________________</p>
    <div class="report-data">
        <h3 id="examTitle" style="text-align: center;">CONSOLIDATED EXAM RESULT ANALYSIS</h3>
        <div class="exam-type"><?= htmlspecialchars($exam) ?> Exam</div> <!-- Exam Type Below Heading -->

        <div id="printContent">
            <p style="text-align: center;"><strong>Year/Sem/Sec:</strong> <?= htmlspecialchars("$year_roman / $semester / $section") ?></p>
            <table>
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Total Students</th>
                        <th>Total Appear</th>
                        <th>Pass</th>
                        <th>Fail</th>
                        <th>Pass %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $data) : ?>
                        <tr>
                            <td><?= htmlspecialchars($data['subject']) ?></td>
                            <td><?= htmlspecialchars($data['subjectName']) ?></td>
                            <td><?= htmlspecialchars($data['totalAppear']) ?></td>
                            <td><?= htmlspecialchars($data['totalPresent']) ?></td>
                            <td><?= htmlspecialchars($data['passed']) ?></td>
                            <td><?= htmlspecialchars($data['failed']) ?></td>
                            <td><?= htmlspecialchars($data['passPercent']) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p style="text-align: center;">Overall Student Performance</p>
            <table>
                <thead>
                    <tr>
                        <th>Performance Category</th>
                        <th>Number of Students</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Cleared All Subjects</td>
                        <td><?= htmlspecialchars($allCleared) ?></td>
                    </tr>
                    <tr>
                        <td>Failed in One Subject</td>
                        <td><?= htmlspecialchars($failedOne) ?></td>
                    </tr>
                    <tr>
                        <td>Failed in Two Subjects</td>
                        <td><?= htmlspecialchars($failedTwo) ?></td>
                    </tr>
                    <tr>
                        <td>Failed in Three Subjects</td>
                        <td><?= htmlspecialchars($failedThree) ?></td>
                    </tr>
                    <tr>
                        <td>Failed in More Than Three Subjects</td>
                        <td><?= htmlspecialchars($failedMoreThanThree) ?></td>
                    </tr>
                </tbody>
            </table>

            <p style="text-align: center;">Overall Pass Percentage</p>
            <p style="text-align: center; font-size: 14px; font-weight: bold;"><?= htmlspecialchars($overallPassPercent) ?>%</p>

            <div class="signatures">
                <div>Test Coordinator</div>
                <div>HOD</div>
                <div>Vice Principal</div>
                <div>Principal</div>
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
