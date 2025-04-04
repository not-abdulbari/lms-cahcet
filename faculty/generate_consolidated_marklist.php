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

    // Fetch subjects for the selected parameters
    $subjects = $conn->query("SELECT DISTINCT subject FROM marks WHERE branch='$branch' AND year='$year' AND section='$section' AND semester='$semester' ORDER BY subject ASC");

    // Fetch students and their marks for the selected parameters
    $students = $conn->query("SELECT s.roll_no, s.name, m.subject, m.marks 
                               FROM students s 
                               JOIN marks m ON s.roll_no = m.roll_no 
                               WHERE m.branch='$branch' AND m.year='$year' AND m.section='$section' AND m.semester='$semester' 
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
        body { margin: 14px; font-family: Times New Roman; font-size: 14px; }
        .no-print { display: none; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #000; padding: 2px; text-align: left; font-size: 12px; } /* Reduced padding */
        .header { text-align: center; display: flex; align-items: center; justify-content: center; }
        h3 { margin-bottom: -10px; }
        .header img { margin-top: 10px; height: 90px; }
        .exam-type { font-size: 18px; font-weight: bold; text-align: center; margin-top: 10px; }
        .info-container { display: flex; justify-content: space-between; margin-top: 20px; }
        .info-left, .info-right { width: 48%; }
        .signatures { margin-top: 150px; display: flex; justify-content: space-between; }
    }
    @media screen {
        body { margin: 14px; font-family: Times New Roman; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #000; padding: 2px; text-align: left; font-size: 12px; } /* Reduced padding */
        .header { text-align: center; display: flex; align-items: center; justify-content: center; }
        h3 { margin-bottom: -10px; }
        .header img { margin-top: 10px; height: 90px; }
        .exam-type { font-size: 18px; font-weight: bold; text-align: center; margin-top: 10px; }
        .info-container { display: flex; justify-content: space-between; margin-top: 20px; }
        .info-left, .info-right { width: 48%; }
        .signatures { margin-top: 150px; display: flex; justify-content: space-between; }
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
</div>
<p style="text-align: center;">______________________________________________________________________________________________</p>

<div class="container">
    <h2 style="text-align: center;">Consolidated Marksheet</h2>
    <?php if (isset($marks_by_student) && !empty($marks_by_student)) { ?>
        <div class="marks-list">
            <div id="printContent">
                <div class="info-container">
                    <div class="info-left">
                        <p><strong>Department:</strong> <?= htmlspecialchars($department) ?></p>
                        <p><strong>Year/Sem/Sec:</strong> <?= htmlspecialchars("$year_roman / $semester / $section") ?></p>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>S. No.</th>
                            <th>Roll No.</th>
                            <th>Name</th>
                            <?php while ($subject = $subjects->fetch_assoc()) { ?>
                                <th><?= htmlspecialchars($subject['subject']) ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($marks_by_student as $roll_no => $student) { ?>
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
                <div class="signatures">
                    <div>Faculty In-Charge</div>
                    <div>HOD</div>
                </div>
                <div class="no-print">
                    <button class="print-btn" onclick="printMarksList()">Print Marksheet</button>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

</body>
</html>
