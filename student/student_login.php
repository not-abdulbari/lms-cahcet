<?php
session_start();
if (empty($_SESSION['student_logged_in']) || empty($_SESSION['roll_no'])) {
    // Debugging: Log session data to check why redirection happens
    error_log("Session data: " . print_r($_SESSION, true));
    header('Location: ../index.php');
    exit();
}
include '../faculty/db_connect.php';

$roll_number = $_SESSION['roll_no'];
$sql_student = "SELECT roll_no, reg_no, name, branch, year, section FROM students WHERE roll_no = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $roll_number);
$stmt->execute();
$result_student = $stmt->get_result();

if ($result_student->num_rows > 0) {
    $student_data = $result_student->fetch_assoc();
} else {
    echo "Student data not found.";
    exit();
}
$stmt->close();
// Remove $conn->close() here to avoid closing the connection prematurely.

// Handling form submission
$marks_data = [];
$attendance_data = [];
$grades_data = [];
$report_data = null;
$university_results_data = []; // General array for all university results
$student_data_error = null;
// $student_data = null; // Removed unnecessary initialization
$year_of_passing = null; // Initialize year of passing
$branch = null; // Initialize branch

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
        $reg_no = $student_data['reg_no']; // Get reg_no for university results
        $year_of_passing = $student_data['year']; // Get year of passing
        $branch = $student_data['branch']; // Get branch
    } else {
        $student_data_error = "Student with roll number $roll_number not found.";
    }
    $stmt->close();

    if (isset($student_data)) {
        // Fetch marks
    
        $sql_marks = "
        SELECT m.semester, m.subject AS subject_code, 
           (SELECT s1.subject_name 
            FROM subjects s1 
            WHERE s1.subject_code = m.subject 
            LIMIT 1) AS subject_name, -- Select the first occurrence of the subject name
            MAX(CASE WHEN m.exam = 'CAT1' THEN m.marks END) AS CAT1,
            MAX(CASE WHEN m.exam = 'CAT2' THEN m.marks END) AS CAT2,
            MAX(CASE WHEN m.exam = 'Model' THEN m.marks END) AS Model
        FROM marks m
        JOIN subjects s ON m.subject = s.subject_code
        WHERE m.roll_no = ?
        GROUP BY m.semester, m.subject
        ORDER BY m.semester ASC, m.subject ASC
        ";
        $stmt = $conn->prepare($sql_marks);
        $stmt->bind_param("s", $roll_number);
        $stmt->execute();
        $result_marks = $stmt->get_result();
        $marks_data = []; // Initialize here as well to be safe within the POST block
        while ($row = $result_marks->fetch_assoc()) {
            $marks_data[$row['semester']][] = $row;
        }
        $stmt->close();

        // Fetch attendance data
        $sql_attendance = "SELECT semester, attendance_entry, percentage FROM semester_attendance WHERE roll_no = ?";
        $stmt = $conn->prepare($sql_attendance);
        $stmt->bind_param("s", $roll_number);
        $stmt->execute();
        $result_attendance = $stmt->get_result();
        $attendance_data = []; // Initialize here
        while ($row = $result_attendance->fetch_assoc()) {
            $attendance_data[$row['semester']][] = $row;
        }
        $stmt->close();

        // Fetch grades
        $sql_grades = "SELECT display_semester, subject_code, grade, semester FROM university_grades WHERE roll_no = ?";
        $stmt = $conn->prepare($sql_grades);
        $stmt->bind_param("s", $roll_number);
        $stmt->execute();
        $result_grades = $stmt->get_result();
        $grades_data = []; // Initialize here
        while ($row = $result_grades->fetch_assoc()) {
            $grades_data[$row['display_semester']][] = $row;
        }
        $stmt->close();

        // Fetch report
        $sql_report = "SELECT display_semester, general_behaviour, inside_campus, report_1, report_2, report_3, report_4, disciplinary_committee, parent_discussion, remarks
                        FROM reports WHERE roll_no = ?";
        $stmt = $conn->prepare($sql_report);
        $stmt->bind_param("s", $roll_number);
        $stmt->execute();
        $result_report = $stmt->get_result();
        $report_data = []; // Initialize here
        while ($row = $result_report->fetch_assoc()) {
            $report_data[$row['display_semester']] = $row;
        }
        $stmt->close();

        // Fetch UNIVERSITY RESULTS
        $sql_university_results = "SELECT ur.semester, ur.subject_code, ur.grade, s.subject_name, ur.exam
                                    FROM university_results ur
                                    JOIN subjects s ON ur.subject_code = s.subject_code
                                    WHERE ur.reg_no = ?";
        $stmt = $conn->prepare($sql_university_results);
        $stmt->bind_param("s", $reg_no);
        $stmt->execute();
        $result_university_results = $stmt->get_result();
        $university_results_data = []; // Initialize here
        while ($row = $result_university_results->fetch_assoc()) {
            $semester = $row['semester'];
            $exam = strtoupper($row['exam']);
            $semester_to_display = $semester; // Default display semester

            // Logic for NOV/DEC-24 results based on year of passing
            if (strpos($exam, 'NOV/DEC-24') !== false) {
                if ($year_of_passing == 2026) {
                    $semester_to_display = 5;
                } elseif ($year_of_passing == 2027) {
                    $semester_to_display = 3;
                } elseif ($year_of_passing == 2025) {
                    $semester_to_display = 7;
                } elseif ($year_of_passing == 2028) {
                    $semester_to_display = 1;
                }
            }

            // Logic to display PG (MBA, MCA) results in Semester 3 and 1
            if (in_array(strtoupper($branch), ['MBA', 'MCA'])) {
                if ($year_of_passing == 2025) {
                    $semester_to_display = 3;
                } elseif ($year_of_passing == 2026) {
                    $semester_to_display = 1;
                }
            }

            $university_results_data[$semester_to_display][$row['subject_code']] = $row;
        }
        $stmt->close();
    }
}
$conn->close(); // Keep this at the very end of the script to close the connection after all operations.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent - View Student Marks, Attendance, Grades, Report & University Results</title>
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
        <?php
        if (isset($student_data_error)) { echo "<p class='error'>$student_data_error</p>"; }
        if (isset($student_data)) {
            echo "<div class='tabs'>
                          <button class='tab-link' onclick=\"openTab(event, 'profile')\">Profile</button>";
            for ($i = 1; $i <= 8; $i++) {
                $has_data = isset($all_semesters[$i]) && (!empty($all_semesters[$i]['marks']) || !empty($all_semesters[$i]['attendance']) || !empty($all_semesters[$i]['grades']) || isset($all_semesters[$i]['report']) || !empty($all_semesters[$i]['university_results']));
                $is_pg_sem3 = in_array(strtoupper($branch), ['MBA', 'MCA']) && $i == 3 && isset($all_semesters[3]['university_results']) && !empty($all_semesters[3]['university_results']);

                if ($has_data || $is_pg_sem3) {
                    echo "<button class='tab-link' onclick=\"openTab(event, 'semester-$i')\">Semester $i</button>";
                }
            }

            $all_semesters = [];
            foreach ($marks_data as $semester => $marks) {
                $all_semesters[$semester]['marks'] = $marks;
            }
            foreach ($attendance_data as $semester => $entries) {
                $all_semesters[$semester]['attendance'] = $entries;
            }
            foreach ($grades_data as $display_semester => $entries) {
                $all_semesters[$display_semester]['grades'] = $entries;
            }
            foreach ($report_data as $display_semester => $report) {
                if (!isset($all_semesters[$display_semester])) {
                    $all_semesters[$display_semester] = [];
                }
                $all_semesters[$display_semester]['report'] = $report;
            }
            foreach ($university_results_data as $semester => $results) {
                if (!isset($all_semesters[$semester])) {
                    $all_semesters[$semester] = [];
                }
                $all_semesters[$semester]['university_results'] = $results;
            }

            ksort($all_semesters);

            // Generate semester tabs only if there is data for that semester
            for ($i = 1; $i <= 8; $i++) {
                $has_data = isset($all_semesters[$i]) && (!empty($all_semesters[$i]['marks']) || !empty($all_semesters[$i]['attendance']) || !empty($all_semesters[$i]['grades']) || isset($all_semesters[$i]['report']) || !empty($all_semesters[$i]['university_results']));
                $is_pg_sem3 = in_array(strtoupper($branch), ['MBA', 'MCA']) && $i == 3 && isset($all_semesters[3]['university_results']) && !empty($all_semesters[3]['university_results']);

                if ($has_data || $is_pg_sem3) {
                    echo "<button class='tab-link' onclick=\"openTab(event, 'semester-$i')\">Semester $i</button>";
                }
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
                $is_pg_sem3_content = in_array(strtoupper($branch), ['MBA', 'MCA']) && $semester == 3 && isset($data['university_results']) && !empty($data['university_results']);
                $has_other_data = !empty($data['marks']) || !empty($data['attendance']) || !empty($data['grades']) || isset($data['report']) || (!empty($data['university_results']) && !(in_array(strtoupper($branch), ['MBA', 'MCA']) && $semester == 3));

                if ($has_other_data || $is_pg_sem3_content) {
                    echo "<div id='semester-$semester' class='tab-content'>
                                  <h3>Details for Semester $semester</h3>";

                    if (isset($data['marks']) && !empty($data['marks'])) {
                        echo "<h4>Internal Assessment Marks</h4>
                              <table class='marks-table' style='width: 100%;'>
                                  <tr><th>Subject Code</th><th>Subject Name</th><th>CAT-1</th><th>CAT-2</th><th>Model Exam</th></tr>";
                        foreach ($data['marks'] as $subject) {
                            echo "<tr>
                                      <td>" . htmlspecialchars($subject['subject_code']) . "</td>
                                      <td>" . htmlspecialchars($subject['subject_name']) . "</td>
                                      <td>" . htmlspecialchars($subject['CAT1']) . "</td>
                                      <td>" . htmlspecialchars($subject['CAT2']) . "</td>
                                      <td>" . htmlspecialchars($subject['Model']) . "</td>
                                  </tr>";
                        }
                        echo "</table>";
                    }

                    if (isset($data['attendance']) && !empty($data['attendance'])) {
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

 if (isset($data['grades']) && !empty($data['grades'])) {
  echo "<h4>Internal Grades</h4>
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

 if (isset($data['report'])) {
  echo "<h4>Report</h4>
    <div class='report'>
    <p><strong>General Behaviour:</strong> " . htmlspecialchars($data['report']['general_behaviour']) . "</p>
    <p><strong>Inside the Campus:</strong> " . htmlspecialchars($data['report']['inside_campus']) . "</p>
    <p><strong>Reports Sent to Parents:</strong></p>
    <ul>
    <li>" . htmlspecialchars($data['report']['report_1']) . "</li>
    <li>" . htmlspecialchars($data['report']['report_2']) . "</li>
    <li>" . htmlspecialchars($data['report']['report_3']) . "</li>
    <li>" . htmlspecialchars($data['report']['report_4']) . "</li>
    </ul>
    <p><strong>Reports Sent to Disciplinary Committee:</strong> " . htmlspecialchars($data['report']['disciplinary_committee']) . "</p>
    <p><strong>Discussion with Parents:</strong> " . htmlspecialchars($data['report']['parent_discussion']) . "</p>
    <p><strong>Remarks:</strong> " . htmlspecialchars($data['report']['remarks']) . "</p>
    </div>";
 }

 if (isset($data['university_results']) && !empty($data['university_results'])) {
  echo "<h4>University Exam Results</h4>
    <table class='university-results-table' style='width: 100%;'>
    <tr><th>Semester</th><th>Subject Code</th><th>Subject Name</th><th>Grade</th><th>Result</th></tr>";
  foreach ($data['university_results'] as $result) {
  $final_result = (in_array(strtoupper($result['grade']), ['U', 'UA'])) ? 'RA' : 'Pass';
  echo "<tr>
    <td>" . htmlspecialchars($result['semester']) . "</td>
    <td>" . htmlspecialchars($result['subject_code']) . "</td>
    <td>" . htmlspecialchars($result['subject_name']) . "</td>
    <td>" . htmlspecialchars($result['grade']) . "</td>
    <td>" . htmlspecialchars($final_result) . "</td>
    </tr>";
  }
  echo "</table>";
 }

 echo "</div>";
 }
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

 // Click the first tab by default only if student data is loaded
 document.addEventListener('DOMContentLoaded', function () {
 <?php if (isset($student_data)): ?>
 var firstTab = document.querySelector('.tab-link');
 if (firstTab) {
 firstTab.click();
 }
 <?php endif; ?>
 });
</script>
</body>
</html>
