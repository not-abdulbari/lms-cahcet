<?php
session_start(); // Start the session to access session variables
include '../faculty/db_connect.php';

// Initialize all variables to avoid undefined variable warnings
$marks_data = [];
$attendance_data = [];
$grades_data = [];
$report_data = [];
$university_results_data = [];
$student_data_error = null;
$student_data = null;
$year_of_passing = null;
$branch = null;

// Fetch roll number from session
if (isset($_SESSION['student_roll_no'])) {
    $roll_number = $_SESSION['student_roll_no'];
} else {
    $student_data_error = "Roll number not provided in session.";
}

// Fetch student data if roll number is available
if (!empty($roll_number)) {
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
        $reg_no = $student_data['reg_no'];
        $year_of_passing = $student_data['year'];
        $branch = $student_data['branch'];
    } else {
        $student_data_error = "Student with roll number $roll_number not found.";
    }
    $stmt->close();

    if (isset($student_data)) {
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
        while ($row = $result_report->fetch_assoc()) {
            $report_data[$row['display_semester']] = $row;
        }
        $stmt->close();

        // Fetch university results
        $sql_university_results = "SELECT ur.semester, ur.subject_code, ur.grade, s.subject_name, ur.exam
                                    FROM university_results ur
                                    JOIN subjects s ON ur.subject_code = s.subject_code
                                    WHERE ur.reg_no = ?";
        $stmt = $conn->prepare($sql_university_results);
        $stmt->bind_param("s", $reg_no);
        $stmt->execute();
        $result_university_results = $stmt->get_result();
        while ($row = $result_university_results->fetch_assoc()) {
            $semester = $row['semester'];
            $exam = strtoupper($row['exam']);
            $semester_to_display = $semester;

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

            // Logic to display PG (MBA, MCA) results in Semester 3
            if (in_array(strtoupper($branch), ['MBA', 'MCA'])) {
                $semester_to_display = 3;
            }
            $university_results_data[$semester_to_display][$row['subject_code']] = $row;
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent - View Student Marks, Attendance, Grades, Report & University Results</title>
<style>
    :root {
        --primary-color: #0056b3; /* A deeper blue for professionalism */
        --secondary-color: #6c757d; /* Neutral gray for balance */
        --success-color: #28a745; /* Green for success messages */
        --danger-color: #dc3545; /* Red for error messages */
        --warning-color: #ffc107; /* Yellow for warnings */
        --info-color: #17a2b8; /* Light blue for info messages */
        --light-color: #f8f9fa; /* Light background */
        --dark-color: #343a40; /* Dark text for readability */
        --white-color: #ffffff; /* Clean white background */
        --font-family: 'Helvetica Neue', Arial, sans-serif; /* Modern font family */
    }

    body {
        font-family: var(--font-family);
        background-color: var(--light-color);
        color: var(--dark-color);
        margin: 0;
        padding: 20px;
        text-align: center; /* Center-align text content */
    }

    /* Header styling */
    h1 {
        color: var(--primary-color);
        margin-bottom: 20px;
        font-size: 2em; /* Larger font size for better visibility */
    }

    /* Form styling */
    form {
        display: inline-block; /* Center the form */
        text-align: left; /* Align form content to the left */
        margin-bottom: 20px;
        max-width: 400px; /* Restrict form width */
        width: 100%; /* Ensure responsiveness */
    }

    label {
        font-weight: bold;
        color: var(--dark-color);
        display: block; /* Ensures proper spacing between label and input */
        margin-bottom: 5px; /* Space below the label */
    }

    input[type="text"], 
    input[type="email"], 
    input[type="number"] {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid var(--secondary-color);
        border-radius: 4px;
        box-sizing: border-box; /* Ensures padding is included in width */
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
        margin: 10px 0;
    }

    /* Tabs Styling */
    .tabs {
        display: flex;
        justify-content: center; /* Center the tabs */
        border-bottom: 1px solid var(--secondary-color);
        margin-bottom: 20px;
    }

    .tabs button {
        flex: 1;
        padding: 10px;
        border: none;
        background-color: var(--light-color);
        cursor: pointer;
        transition: background-color 0.3s, color 0.3s;
    }

    .tabs button:hover,
    .tabs button.active {
        background-color: var(--primary-color);
        color: var(--white-color);
    }

    /* Tab content will show or hide according to the active tab */
    .tab-content {
        display: none;
        text-align: center; /* Center tab content */
    }

    .tab-content.active {
        display: block;
    }

    /* Table Styling */
    table {
        margin: 0 auto 20px; /* Center the table */
        width: 90%; /* Ensure table is responsive */
        max-width: 600px; /* Restrict maximum width */
        border-collapse: collapse;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Light shadow for sophistication */
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
        font-weight: bold; /* Bold headers for clarity */
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        body {
            padding: 10px; /* Reduced padding on smaller screens */
        }

        h1 {
            font-size: 1.5em; /* Adjusted font size for smaller screens */
        }

        form {
            max-width: 100%; /* Use full width for form on small screens */
        }

        table th,
        table td {
            padding: 8px;
            font-size: 14px; /* Smaller font size for readability */
        }
    }
</style>
</head>
<body>
    <div>
        <h1>STUDENT DASHBOARD</h1>
        <?php
        if (isset($student_data_error)) {
            echo "<p class='error'>$student_data_error</p>";
        }
        if (isset($student_data)) {
            echo "<div class='tabs'>
                          <button class='tab-link' onclick=\"openTab(event, 'profile')\">Profile</button>";
            $all_semesters = [];

            // Combine all data into a single structure
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

            // Sort semesters numerically
            ksort($all_semesters);

            // Generate tabs dynamically
            for ($i = 1; $i <= 8; $i++) {
                $has_data = isset($all_semesters[$i]) && (
                    !empty($all_semesters[$i]['marks']) ||
                    !empty($all_semesters[$i]['attendance']) ||
                    !empty($all_semesters[$i]['grades']) ||
                    isset($all_semesters[$i]['report']) ||
                    !empty($all_semesters[$i]['university_results'])
                );
                $is_pg_sem3 = in_array(strtoupper($branch), ['MBA', 'MCA']) && $i == 3 &&
                    isset($all_semesters[3]['university_results']) &&
                    !empty($all_semesters[3]['university_results']);

                if ($has_data || $is_pg_sem3) {
                    echo "<button class='tab-link' onclick=\"openTab(event, 'semester-$i')\">Semester $i</button>";
                }
            }
            echo "</div>";

            // Profile tab
            echo "<div id='profile' class='tab-content active'>
                          <h3>Student Information</h3>
                          <table style='width: 70%;'>
                              <tr><th>Name</th><td>" . htmlspecialchars($student_data['name']) . "</td></tr>
                              <tr><th>Roll Number</th><td>" . htmlspecialchars($student_data['roll_no']) . "</td></tr>
                              <tr><th>Register Number</th><td>" . htmlspecialchars($student_data['reg_no']) . "</td></tr>
                              <tr><th>Branch</th><td>" . htmlspecialchars($student_data['branch']) . "</td></tr>
                              <tr><th>Section</th><td>" . htmlspecialchars($student_data['section']) . "</td></tr>
                              <tr><th>Year</th><td>" . htmlspecialchars($student_data['year']) . "</td></tr>
                          </table>
                      </div>";

            // Semester tabs
            foreach ($all_semesters as $semester => $data) {
                $is_pg_sem3_content = in_array(strtoupper($branch), ['MBA', 'MCA']) && $semester == 3 &&
                    isset($data['university_results']) && !empty($data['university_results']);
                $has_other_data = !empty($data['marks']) ||
                    !empty($data['attendance']) ||
                    !empty($data['grades']) ||
                    isset($data['report']) ||
                    (!empty($data['university_results']) && !(in_array(strtoupper($branch), ['MBA', 'MCA']) && $semester == 3));

                if ($has_other_data || $is_pg_sem3_content) {
                    echo "<div id='semester-$semester' class='tab-content'>
                                  <h3>Details for Semester $semester</h3>";

                    // Marks
                    if (isset($data['marks']) && !empty($data['marks'])) {
                        echo "<h4>Internal Assessment Marks</h4>
                              <table class='marks-table' style='width: 70%;'>
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

                    // Attendance
                    if (isset($data['attendance']) && !empty($data['attendance'])) {
                        echo "<h4>Attendance</h4>
                              <table class='attendance-table' style='width: 70%;'>
                                  <tr><th>Entry Number</th><th>Percentage</th></tr>";
                        foreach ($data['attendance'] as $entry) {
                            echo "<tr>
                                      <td>" . htmlspecialchars($entry['attendance_entry']) . "</td>
                                      <td>" . htmlspecialchars($entry['percentage']) . "%</td>
                                  </tr>";
                        }
                        echo "</table>";
                    }

                    // Grades
                    if (isset($data['grades']) && !empty($data['grades'])) {
                        echo "<h4>Internal Grades</h4>
                              <table class='grades-table' style='width: 70%;'>
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

                    // Report
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

                    // University Results
                    if (isset($data['university_results']) && !empty($data['university_results'])) {
                        echo "<h4>University Exam Results</h4>
                              <table class='university-results-table' style='width: 70%;'>
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

        // Automatically open the first tab if student data is loaded
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
