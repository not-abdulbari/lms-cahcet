<?php
include 'db_connect.php';

// Collect form inputs
$branch = $conn->real_escape_string($_POST['branch']);
$year = $conn->real_escape_string($_POST['year']);
$year_roman = $conn->real_escape_string($_POST['year_roman']);
$section = $conn->real_escape_string($_POST['section']);
$semester = $conn->real_escape_string($_POST['semester']);
$subject = $conn->real_escape_string($_POST['subject']);
$exam = $conn->real_escape_string($_POST['exam']);
$faculty_code = $conn->real_escape_string($_POST['faculty_code']);
$exam_date = $conn->real_escape_string($_POST['exam_date']);

// Fetch faculty name
$facultyNameQuery = $conn->query("SELECT faculty_name FROM faculty WHERE faculty_code='$faculty_code'");
$facultyNameRow = $facultyNameQuery->fetch_assoc();
$facultyName = $facultyNameRow['faculty_name'] ?? "Unknown Faculty";

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

// Fetch subject name
$subjectNameQuery = $conn->query("SELECT subject_name FROM subjects WHERE subject_code='$subject' AND branch='$branch' AND semester='$semester'");
$subjectNameRow = $subjectNameQuery->fetch_assoc();
$subjectName = $subjectNameRow['subject_name'] ?? "Unknown Subject";

// Fetch students who scored less than 50
$failedStudentsQuery = $conn->query("
    SELECT roll_no FROM marks 
    WHERE branch='$branch' AND year='$year' 
    AND section='$section' AND semester='$semester'
    AND subject='$subject' AND exam='$exam'
    AND marks < 50
");
$failedStudents = [];
while ($row = $failedStudentsQuery->fetch_assoc()) {
    $failedStudents[] = $row['roll_no'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAPA Report</title>
    <style>
    @media print {
        body { margin: 12px; font-family: "Times New Roman", serif; font-size: 14px; }
        .no-print { display: none; }
        .container { width: 100%; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #000; padding: 2px; text-align: left; }
        .header{ display: flex; align-items: center; justify-content: center; }
        .header img{ height: 90px; width: 90px; margin-right: 10px; }
        h3, h4 { margin: 5px 0; }
        .signatures { margin-top: 50px; width: 75%; }
    }
    @page {
        size: A4 landscape;
    }

    body {
        font-family: "Times New Roman", serif;
        background-color: #fff;
        color: #000;
        padding: 20px;
    }

    .container {
        max-width: 1000px;
        margin: auto;
    }

    h2, h3, h4, p {
        margin-top: -5px;
        text-align: center;
    }


    table {
        width: 100%;
        border-collapse: collapse;
        margin: 2px 0;
    }

    th, td {
        border: 1px solid black;
        padding: 5px;
        text-align: left;
    }

    .signature {
        margin-top: 50px;
        display: flex;
        justify-content: space-between;
        text-align: right;
    }

    .signature div {
        text-align: right;
    }

    .header img{ height: 90px; width: 90px; }



</style>
    <script>
        function printMarksList() {
            window.print();
        }
    </script>
</head>
<body>
    
    <div class="no-print">
        <button class="print-btn" onclick="printMarksList()">Print CAPA Form</button>
    </div>
    

<div class="container">
    <div class="header">
        <img src="../assets/24349bb44aaa1a8c.jpg" alt="College Logo">
        <div>
            <h3>C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY</h3> 
            <h3>MELVISHARAM - 632509</h3> 
            <h3><?= htmlspecialchars($department) ?></h3> 
            <h3>Academic Year 2024 - 2025 (EVEN)</h3> 
        </div>
    </div>
<p style="text-align: center;">_________________________________________________________________________________________________________________________________________</p>

    <table>
        <tr>
            <th>Exam Type</th>
            <td colspan="3"><?= htmlspecialchars($exam) ?> Exam</td>
        </tr>
        <tr>
            <th>Subject Handler</th>
            <td><?= htmlspecialchars($facultyName) ?></td>
            <th>Year / Sem / Sec</th>
            <td><?= htmlspecialchars("$year_roman / $semester / $section") ?></td>
        </tr>
        <tr>
            <th>Subject Code and Name</th>
            <td><?= htmlspecialchars($subject) ?> - <?= htmlspecialchars($subjectName) ?></td>
            <th>Exam Date</th>
            <td><?= date('d/m/Y', strtotime(htmlspecialchars($exam_date))) ?></td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            <th>Issue Type</th>
            <td>Poor Performance ☐</td>
            <td>Irregularity ☐</td>
            <td>Others ☐</td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            <th>Student’s Details</th>
            <td colspan="3">
                <?= count($failedStudents) > 0 ? implode(', ', array_map('htmlspecialchars', $failedStudents)) : "No students scored less than 50." ?>
            </td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            <th>Corrective Measure</th>
            <td style="width: 21cm;">&nbsp;</td>
        </tr>
        <tr>
            <th>Root Causes</th>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <th>Corrective Action</th>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <th>Date of Action</th>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <th>Effectiveness</th>
            <td>&nbsp;</td>
        </tr>
    </table>

    <h4 style="margin-top: 5px;">Effectiveness Verified By:</h4>

    <table style="margin-top: 5px;">
        <tr>
            <th>Responsible Member</th>
            <th>Closed on</th>
            <th>Details Required to Close</th>
            <th>Report by Member: Signature with Date</th>
        </tr>
        <tr style="height: 75px;">
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
    </table>

    <div class="signatures">
        <div>
        <p>HOD</p>
    </div>
    </div>
</div>

</body>
</html>
