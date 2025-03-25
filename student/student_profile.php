<?php
include '../faculty/db_connect.php';
// Handling form submission
$marks_data = [];
$attendance_data = [];
$grades_data = [];
$report_data = null;
$student_data_error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $roll_number = $_POST['roll_number'];

    // Fetch student biodata
    $sql_student = "SELECT roll_no, reg_no, name, branch, year, section
                    FROM students 
                    WHERE roll_no = ?";
    $stmt = $conn->prepare($sql_student);
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $result_student = $stmt->get_result();

    if ($result_student->num_rows > 0) {
        $student_data = $result_student->fetch_assoc();
    } else {
        $student_data_error = "Student with roll number $roll_number not found.";
    }
    $stmt->close();

    // Fetch marks
    $sql_marks = "
    SELECT m.semester, m.subject, s.subject_name,
           MAX(CASE WHEN m.exam = 'CAT1' THEN m.marks END) AS CAT1,
           MAX(CASE WHEN m.exam = 'CAT2' THEN m.marks END) AS CAT2,
           MAX(CASE WHEN m.exam = 'Model' THEN m.marks END) AS Model
    FROM marks m
    JOIN subjects s ON m.subject = s.subject_code
    WHERE m.roll_no = ?
    GROUP BY m.semester, m.subject, s.subject_name
    ORDER BY m.semester ASC, m.subject ASC
    ";

    $stmt = $conn->prepare($sql_marks);
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $result_marks = $stmt->get_result();

    if ($result_marks->num_rows > 0) {
        while ($row = $result_marks->fetch_assoc()) {
            $marks_data[] = $row;
        }
    } else if ($student_data) {
        $marks_data_error = "Marks for student with roll number $roll_number not found.";
    }
    $stmt->close();

    // Fetch attendance data
    $sql_attendance = "SELECT semester, attendance_entry, percentage FROM semester_attendance WHERE roll_no = ?";
    $stmt = $conn->prepare($sql_attendance);
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $result_attendance = $stmt->get_result();

    if ($result_attendance->num_rows > 0) {
        while ($row = $result_attendance->fetch_assoc()) {
            $attendance_data[$row['semester']][] = $row;
        }
    }
    $stmt->close();

    // Fetch grades
    $sql_grades = "SELECT display_semester, subject_code, grade, semester FROM university_grades WHERE roll_no = ?";
    $stmt = $conn->prepare($sql_grades);
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $result_grades = $stmt->get_result();

    if ($result_grades->num_rows > 0) {
        while ($row = $result_grades->fetch_assoc()) {
            $grades_data[$row['display_semester']][] = $row;
        }
    }
    $stmt->close();

    // Fetch report
    $sql_report = "SELECT display_semester, general_behaviour, inside_campus, report_1, report_2, report_3, report_4, disciplinary_committee, parent_discussion, remarks 
            FROM reports WHERE roll_no = ?";
    $stmt = $conn->prepare($sql_report);
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $result_report = $stmt->get_result();

    $report_data = [];
    if ($result_report->num_rows > 0) {
        while ($row = $result_report->fetch_assoc()) {
            $report_data[$row['display_semester']] = $row; // Store reports per semester
        }
    }
    $stmt->close();

}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent - View Student Marks, Attendance, Grades, and Report</title>
        <style>
        :root {
            --primary-color: #007BFF;
            --secondary-color: #6C757D;
            --success-color: #28A745;
            --danger-color: #DC3545;
            --warning-color: #FFC107;
            --info-color: #17A2B8;
            --light-color: #F8F9FA;
            --dark-color: #343A40;
            --white-color: #FFF;
            --font-family: Arial, sans-serif;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--light-color);
            color: var(--dark-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--white-color);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            color: var(--dark-color);
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid var(--secondary-color);
            border-radius: 4px;
        }

        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: var(--primary-color);
            color: var(--white-color);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: var(--dark-color);
        }

        .error {
            color: var(--danger-color);
            text-align: center;
            margin: 10px 0;
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .tabs button {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--secondary-color);
            background-color: var(--light-color);
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }

        .tabs button:hover,
        .tabs button.active {
            background-color: var(--primary-color);
            color: var(--white-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            padding: 10px;
            border: 1px solid var(--secondary-color);
            text-align: left;
        }

        table th {
            background-color: var(--secondary-color);
            color: var(--white-color);
        }

        .report ul {
            list-style-type: disc;
            padding-left: 20px;
        }

        .report ul li {
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            h1 {
                font-size: 24px;
            }

            input[type="text"] {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid var(--secondary-color);
            border-radius: 4px;
        }
            
            table th,
            table td {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>STUDENT DASHBOARD</h1>

        <form method="POST" action="student_profile.php">
            <label for="roll_number">Enter Roll Number:</label>
            <input type="text" name="roll_number" id="roll_number" required>
            <input type="submit" name="submit" value="View Details">
        </form>

        <?php
        if (isset($student_data_error)) {
            echo "<p class='error'>$student_data_error</p>";
        }
        if (isset($student_data)) {
            echo "<div class='tabs'>
                <button class='tab-link' onclick=\"openTab(event, 'profile')\">Profile</button>";
            
            // Collect all semesters
            $all_semesters = [];
            foreach ($marks_data as $marks) {
                $all_semesters[$marks['semester']]['marks'][] = $marks;
            }
            foreach ($attendance_data as $semester => $entries) {
                $all_semesters[$semester]['attendance'] = $entries;
            }
            foreach ($grades_data as $display_semester => $entries) {
                $all_semesters[$display_semester]['grades'] = $entries;
            }
            foreach (array_keys($all_semesters) as $semester) {
                echo "<button class='tab-link' onclick=\"openTab(event, 'semester-$semester')\">Semester $semester</button>";
            }
            echo "</div>";

            echo "<div id='profile' class='tab-content active'>
                <h3>Student Information</h3>
                <table style='width: 100%;'>
                    <tr><th>Name</th><td>" . htmlspecialchars($student_data['name']) . "</td></tr>
                    <tr><th>Roll Number</th><td>" . htmlspecialchars($student_data['roll_no']) . "</td></tr>
                    <tr><th>Register Number</th><td>" . htmlspecialchars($student_data['reg_no']) . "</td></tr>
                    <tr><th>Branch</th><td>" . htmlspecialchars($student_data['branch']) . "</td></tr>
                    <tr><th>Section</th><td>" . htmlspecialchars($student_data['section']) . "</td></tr>
                    <tr><th>Year</th><td>" . htmlspecialchars($student_data['year']) . "</td></tr>
                </table>
            </div>";

            foreach ($all_semesters as $semester => $data) {
                echo "<div id='semester-$semester' class='tab-content'>
                    <h3>Details for Semester $semester</h3>";

                if (!empty($data['marks'])) {
                    echo "<h4>Marks</h4>
                        <table class='marks-table' style='width: 100%;'>
                            <tr><th>Subject Code</th><th>Subject Name</th><th>CAT-1</th><th>CAT-2</th><th>Model Exam</th></tr>";
                    foreach ($data['marks'] as $subject) {
                        echo "<tr>
                            <td>" . htmlspecialchars($subject['subject']) . "</td>
                            <td>" . htmlspecialchars($subject['subject_name']) . "</td>
                            <td>" . htmlspecialchars($subject['CAT1']) . "</td>
                            <td>" . htmlspecialchars($subject['CAT2']) . "</td>
                            <td>" . htmlspecialchars($subject['Model']) . "</td>
                        </tr>";
                    }
                    echo "</table>";
                }

                if (!empty($data['attendance'])) {
                    echo "<h4>Attendance</h4>
                        <table class='attendance-table' style='width: 100%;'>
                            <tr><th>Entry Number</th><th>Percentage</th></tr>";
                    foreach ($data['attendance'] as $entry) {
                        echo "<tr>
                            <td>" . htmlspecialchars($entry['attendance_entry']) . "</td>
                            <td>" . htmlspecialchars($entry['percentage']) . "%</td>
                        </tr>";
                    }
                    echo "</table>";
                }

                if (!empty($data['grades'])) {
                    echo "<h4>Grades</h4>
                        <table class='grades-table' style='width: 100%;'>
                            <tr><th>Semester</th><th>Subject Code</th><th>Grade</th></tr>";
                    foreach ($data['grades'] as $entry) {
                        echo "<tr>
                            <td>" . htmlspecialchars($entry['semester']) . "</td>
                            <td>" . htmlspecialchars($entry['subject_code']) . "</td>
                            <td>" . htmlspecialchars($entry['grade']) . "</td>
                        </tr>";
                    }
                    echo "</table>";
                }

                if (isset($report_data[$semester])) {
                    echo "<h4>Report</h4>
                        <div class='report'>
                            <p><strong>General Behaviour:</strong> " . htmlspecialchars($report_data[$semester]['general_behaviour']) . "</p>
                            <p><strong>Inside the Campus:</strong> " . htmlspecialchars($report_data[$semester]['inside_campus']) . "</p>
                            <p><strong>Reports Sent to Parents:</strong></p>
                            <ul>
                                <li>" . htmlspecialchars($report_data[$semester]['report_1']) . "</li>
                                <li>" . htmlspecialchars($report_data[$semester]['report_2']) . "</li>
                                <li>" . htmlspecialchars($report_data[$semester]['report_3']) . "</li>
                                <li>" . htmlspecialchars($report_data[$semester]['report_4']) . "</li>
                            </ul>
                            <p><strong>Reports Sent to Disciplinary Committee:</strong> " . htmlspecialchars($report_data[$semester]['disciplinary_committee']) . "</p>
                            <p><strong>Discussion with Parents:</strong> " . htmlspecialchars($report_data[$semester]['parent_discussion']) . "</p>
                            <p><strong>Remarks:</strong> " . htmlspecialchars($report_data[$semester]['remarks']) . "</p>
                        </div>";
                }
                echo "</div>";
            }
        }
        ?>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tab-link");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        // Click the first tab by default
        document.addEventListener('DOMContentLoaded', function () {
            var firstTab = document.querySelector('.tab-link');
            if (firstTab) {
                firstTab.click();
            }
        });
    </script>
</body>

</html>
