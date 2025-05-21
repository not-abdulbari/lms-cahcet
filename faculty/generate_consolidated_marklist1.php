<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

include 'db_connect.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $branch = isset($_POST['branch']) ? $_POST['branch'] : '';
    $year = isset($_POST['year']) ? $_POST['year'] : '';
    $year_roman = isset($_POST['year_roman']) ? $_POST['year_roman'] : '';
    $section = isset($_POST['section']) ? $_POST['section'] : '';
    $semester = isset($_POST['semester']) ? $_POST['semester'] : '';
    $exam = isset($_POST['exam']) ? $_POST['exam'] : '';
    $isNbaLogoNeeded = isset($_POST['include_nba_logo']) && $_POST['include_nba_logo'] == '1';

    // Fetch subjects for the selected parameters
    $subjects = $conn->query("SELECT DISTINCT subject FROM marks WHERE branch='$branch' AND year='$year' AND section='$section' AND semester='$semester' ORDER BY subject ASC");

    // Fetch students and their marks for the selected parameters
    $students = $conn->query("SELECT s.roll_no, s.name, m.subject, m.marks 
                               FROM students s 
                               JOIN marks m ON s.roll_no = m.roll_no 
                               WHERE m.branch='$branch' AND m.year='$year' AND m.section='$section' AND m.semester='$semester' AND m.exam='$exam' 
                               ORDER BY s.roll_no ASC, m.subject ASC");

    // Organize marks by student
    $marks_by_student = [];
    while ($row = $students->fetch_assoc()) {
        $marks_by_student[$row['roll_no']]['name'] = $row['name'];
        $marks_by_student[$row['roll_no']]['marks'][$row['subject']] = $row['marks'];
    }

    // Fetch department name based on the branch
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
    
    // ADDED: Calculate analysis data for each subject
    $reportData = [];
    $studentFailures = [];
    $allStudents = [];
    
    // First get total number of students in class
    $totalStudentsQuery = $conn->query("SELECT COUNT(DISTINCT roll_no) as total_students FROM students 
                                        WHERE branch='$branch' AND year='$year' AND section='$section'");
    $totalStudentsRow = $totalStudentsQuery->fetch_assoc();
    $totalStudents = $totalStudentsRow['total_students'];
    
    // Reset subjects pointer for reuse
    $subjects->data_seek(0);
    
    // Iterate over each subject to fetch and calculate data
    while ($subject = $subjects->fetch_assoc()) {
        $subjectCode = $subject['subject'];
        
        // Fetch subject name from subjects table
        $subjectNameQuery = $conn->query("SELECT subject_name FROM subjects WHERE subject_code='$subjectCode' AND branch='$branch' AND semester='$semester'");
        $subjectNameRow = $subjectNameQuery->fetch_assoc();
        $subjectName = $subjectNameRow['subject_name'] ?? "Unknown Subject";
        
        // Fetch marks for this subject
        $result = $conn->query("
            SELECT roll_no, marks FROM marks 
            WHERE branch='$branch' AND year='$year' 
            AND section='$section' AND semester='$semester'
            AND subject='$subjectCode' AND exam='$exam'
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
        
        // Calculate percentages in the format shown in the image
        $passPercentOfTotal = $totalStudents > 0 ? round(($passed / $totalStudents) * 100, 2) : 0;
        $passPercentOfAppeared = $appeared > 0 ? round(($passed / $appeared) * 100, 2) : 0;
        
        $reportData[] = [
            'subject' => $subjectCode,
            'subjectName' => $subjectName,
            'totalStudents' => $totalStudents,
            'appeared' => $appeared,
            'passed' => $passed,
            'failed' => $failed,
            'absent' => $absent,
            'passPercentOfTotal' => $passPercentOfTotal,
            'passPercentOfAppeared' => $passPercentOfAppeared,
        ];
    }
    
    // Calculate student failure statistics
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consolidated Marksheet</title>
    <style>
       @media print {
        body { margin: 14px; font-family: "Times New Roman"; font-size: 14px; }
        .no-print { display: none; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #000; padding: 2px; text-align: center; font-size: 12px; } /* Changed to center alignment */
        .header { text-align: center; display: flex; align-items: center; justify-content: center; }
        h3 { margin-bottom: -10px; }
        .header img { margin-top: 10px; height: 90px; }
        .exam-type { font-size: 18px; font-weight: bold; text-align: center; margin-top: 10px; }
        .info-container { display: flex; justify-content: space-between; margin-top: 20px; }
        .info-left, .info-right { width: 48%; }
        .signatures { margin-top: 80px; display: flex; justify-content: space-between; }
        .page-break { page-break-before: always; }
        .center-text { text-align: center; }
        .report-data { margin-top: 20px; }
    }
    @media screen {
        body { margin: 14px; font-family: "Times New Roman"; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #000; padding: 2px; text-align: center; font-size: 12px; } /* Changed to center alignment */
        .header { text-align: center; display: flex; align-items: center; justify-content: center; }
        h3 { margin-bottom: -10px; }
        .header img { margin-top: 10px; height: 90px; }
        .exam-type { font-size: 18px; font-weight: bold; text-align: center; margin-top: 10px; }
        .info-container { display: flex; justify-content: space-between; margin-top: 20px; }
        .info-left, .info-right { width: 48%; }
        .signatures { margin-top: 80px; display: flex; justify-content: space-between; }
        .page-break { margin-top: 50px; }
        .center-text { text-align: center; }
        .report-data { margin-top: 20px; }
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
    <button class="print-btn" onclick="printMarksList()">Print Marksheet</button>
</div>

<div class="header">
    <img src="../assets/24349bb44aaa1a8c.jpg" alt="College Logo">
    <div>
        <h3>C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY</h3>
        <h3>MELVISHARAM - 632509</h3>
        <h3><?= htmlspecialchars($department) ?></h3> <!-- Dynamic Department Name -->
        <h3>Academic Year 2024 - 2025 (EVEN)</h3>
    </div>
    <?php if (isset($_POST['include_nba_logo']) && $_POST['include_nba_logo'] == '1') { ?>
        <img src="../assets/nba-logo.svg" alt="NBA Logo" style="height: 100px; margin-right: 20px;">
    <?php } ?>
    
</div>
<p style="text-align:center;" >_________________________________________________________________________________________</p>

<div class="container">
    <h2 class="exam-type">Consolidated Marksheet</h2>
    <h3 style="text-align:center;"><strong> <?= htmlspecialchars($exam) ?> Exam</strong></h3>
    <?php if (!empty($marks_by_student)) { ?>
        <div class="info-container">
            <div class="info-left">
                <p><strong>Year / Sem / Sec :</strong> <?= htmlspecialchars("$year_roman / $semester / $section") ?></p>
            </div>
        </div>
        
        <!-- Student Marks Table -->
        <table>
            <thead>
                <tr>
                    <th>S. No.</th>
                    <th>Roll No.</th>
                    <th>Name</th>
                    <?php 
                    $subjects->data_seek(0); // Reset pointer to the beginning
                    while ($subject = $subjects->fetch_assoc()) { ?>
                        <th><?= htmlspecialchars($subject['subject']) ?></th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                foreach ($marks_by_student as $roll_no => $student) {
                ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($roll_no) ?></td>
                        <td><?= htmlspecialchars($student['name']) ?></td>
                        <?php
                        $subjects->data_seek(0); // Reset pointer to the beginning
                        while ($subject = $subjects->fetch_assoc()) {
                            $subject_code = $subject['subject'];
                            $marks = isset($student['marks'][$subject_code]) ? htmlspecialchars($student['marks'][$subject_code]) : 'N/A';
                            echo "<td>$marks</td>";
                        }
                        ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <!-- UPDATED: Subject-wise Analysis Table -->
        <div class="report-data">
            <h3 style="text-align:center;">Subject-wise Analysis</h3>
            
            <!-- Subject-wise Analysis Table matching the uploaded image - vertical format -->
            <table>
                <tr>
                    <th>Total No. of Students</th>
                    <?php foreach ($reportData as $data) : ?>
                        <td><?= htmlspecialchars($data['totalStudents']) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>No. of Students Appeared</th>
                    <?php foreach ($reportData as $data) : ?>
                        <td><?= htmlspecialchars($data['appeared']) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>No. of Students Pass</th>
                    <?php foreach ($reportData as $data) : ?>
                        <td><?= htmlspecialchars($data['passed']) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>No. of Students Fail</th>
                    <?php foreach ($reportData as $data) : ?>
                        <td><?= htmlspecialchars($data['failed']) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>No. of Students Absent</th>
                    <?php foreach ($reportData as $data) : ?>
                        <td><?= htmlspecialchars($data['absent']) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>Percentage of Pass: (Based on Total)</th>
                    <?php foreach ($reportData as $data) : ?>
                        <td><?= htmlspecialchars($data['passPercentOfTotal']) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>(Based on Appeared)</th>
                    <?php foreach ($reportData as $data) : ?>
                        <td><?= htmlspecialchars($data['passPercentOfAppeared']) ?></td>
                    <?php endforeach; ?>
                </tr>
            </table>

            <!-- Overall Student Performance Table -->
            <h3 style="text-align:center;">Overall Student Performance</h3>
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

            <!-- Overall Pass Percentage -->
            <p class="center-text">Overall Pass Percentage</p>
            <p class="center-text" style="font-size: 14px; font-weight: bold;"><?= htmlspecialchars($overallPassPercent) ?>%</p>

            <!-- Signatures for the analysis section -->
            <div class="signatures">
                <div><b>Test Coordinator</b></div>
                <div><b>HOD</b></div>
                <div><b>Vice Principal</b></div>
                <div><b>Principal</b></div>
            </div>
        </div>
        
    <?php } else { ?>
        <p style="text-align: center; color: red; font-size: 1.2em; font-weight: bold;">
            Marks are not entered for the selected details.
        </p>
    <?php } ?>
</div>

</body>
</html>
