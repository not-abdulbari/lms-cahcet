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
    $section = isset($_POST['section']) ? $_POST['section'] : '';

    // Fetch student details if all parameters are set
    if (!empty($branch) && !empty($year) && !empty($section)) {
        $students = $conn->query("SELECT roll_no, name, reg_no FROM students WHERE branch = '$branch' AND year = '$year' AND section = '$section' ORDER BY roll_no ASC");
        // Debugging: Check if query executed successfully
        if (!$students) {
            echo 'Error: ' . $conn->error;
        }
    } else {
        $students = null;
    }

    $departmentNames = [
        "CSE" => "Department of Computer Science and Engineering",
        "ECE" => "Department of Electronics and Communication Engineering",
        "EEE" => "Department of Electrical and Electronics Engineering",
        "MECH" => "Department of Mechanical Engineering",
        "CIVIL" => "Department of Civil Engineering",
        "IT" => "Department of Information Technology",
        "AIDS" => "Department of Artificial Intelligence & Data Science",
        "MBA" => "School of Management",
        "MCA" => "Department of Computer Applications",
    ];

    $department = isset($departmentNames[$branch]) ? $departmentNames[$branch] : "Department of $branch";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Name List</title>
    <style>
        @media print {
            body { margin: 14px; font-family: Times New Roman; font-size: 14px; }
            .no-print { display: none; }
            table { width: 100%; border-collapse: collapse; margin-top: 14px; }
            th, td { border: 1px solid #000; padding: 3px; text-align: left; font-size: 12px; }
            .header { text-align: center; display: flex; align-items: center; justify-content: center; }
            h3 { margin-bottom: -10px; }
            .header img { margin-top: 10px; height: 90px; }
        }
        @media screen {
            body { margin: 14px; font-family: Times New Roman; font-size: 14px; }
            .no-print { display: none; }
            table { width: 100%; border-collapse: collapse; margin-top: 14px; }
            th, td { border: 1px solid #000; padding: 3px; text-align: left; font-size: 12px; }
            .header { text-align: center; display: flex; align-items: center; justify-content: center; }
            h3 { margin-bottom: -10px; }
            .header img { margin-top: 10px; height: 90px; }
        }
    </style>
    <script>
        function printNameList() {
            window.print();
        }
    </script>
</head>
<body>
<div class="no-print">
    <button class="print-btn" onclick="printNameList()">Print Name List</button>
</div>

<div class="header">
    <img src="../assets/24349bb44aaa1a8c.jpg" alt="College Logo">
    <div>
        <h3>C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY</h3>
        <h3>MELVISHARAM - 632509</h3>
        <h3><?= htmlspecialchars($department) ?></h3> </div>
</div>
<p style="text-align: center;">______________________________________________________________________________________________</p>

<div class="container">
    <h2 style="text-align: center;">Student Name List</h2>
    <?php if (isset($students) && $students !== null && $students->num_rows > 0) { ?>
        <div class="name-list">
            <div id="printContent">
                <table>
                    <thead>
                        <tr>
                            <th>S. No.</th>
                            <th>Roll No.</th>
                            <th>Register Number</th>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = $students->fetch_assoc()) { ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['roll_no']) ?></td>
                                <td><?= htmlspecialchars($row['reg_no']) ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="no-print">
                    <button class="print-btn" onclick="printNameList()">Print Name List</button>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

</body>
</html>