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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consolidated Marksheet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }

        .header {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px 0;
        }

        .header img {
            height: 100px;
            margin-bottom: 10px;
        }

        h3 {
            margin: 5px 0;
            font-size: 1.2em;
        }

        .container {
            margin: 20px auto;
            max-width: 1200px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #000;
            text-align: center;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        td {
            vertical-align: middle;
        }

        .info-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 1em;
        }

        .info-left {
            width: 48%;
        }

        .exam-type {
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }

        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            font-size: 1em;
            padding: 0 50px;
        }

        .no-print {
            margin: 20px 0;
            position: absolute;
            right: 2%;
        }

        .back-btn {
            position: absolute;
            left: 2%;
            top: 4%;
        }

        .back-btn a {
            color: white;
            cursor: pointer;
            text-decoration: none;
        }

        .print-btn {
            padding: 10px 20px;
            font-size: 1em;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .print-btn:hover {
            background-color: #2980b9;
        }

        /* Hide unnecessary elements in print view */
        @media print {
            .no-print, .back-btn {
                display: none;
            }

            body {
                margin: 0;
                font-size: 12pt;
                line-height: 1.4;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            th, td {
                border: 0.8px solid #000;
                text-align: center;
                padding: 6px;
            }

            th {
                background-color: #f1f1f1;
            }
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

<div class="back-btn">
    <button class="print-btn"><a href="consolidated_marklist.php">Back</a></button>
</div>

<div class="header">
    <img src="../assets/24349bb44aaa1a8c.jpg" alt="College Logo">
    <div>
        <h3>C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY</h3>
        <h3>MELVISHARAM - 632509</h3>
        <h3><?= htmlspecialchars($department) ?></h3> <!-- Dynamic Department Name -->
        <h3>Academic Year 2024 - 2025 (EVEN)</h3>
        <hr>
    </div>
    <?php if (isset($_POST['include_nba_logo']) && $_POST['include_nba_logo'] == '1') { ?>
        <img src="../assets/nba-logo.png" alt="NBA Logo" style="height: 100px; margin-right: 20px;">
    <?php } ?>
</div>
<div class="container">
    <h2 class="exam-type">Consolidated Marksheet</h2>
    <?php if (!empty($marks_by_student)) { ?>
        <div class="info-container">
            <div class="info-left">
                <p><strong>Year/Sem/Sec:</strong> <?= htmlspecialchars("$year_roman / $semester / $section") ?></p>
                <p><strong>Exam:</strong> <?= htmlspecialchars($exam) ?></p>
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
        <div class="signatures">
            <div>Faculty In-Charge</div>
            <div>HOD</div>
        </div>
    <?php } else { ?>
        <p style="text-align: center; color: red; font-size: 1.2em; font-weight: bold;">
            Marks are not entered for the selected details.
        </p>
    <?php } ?>
</div>

</body>
</html>
