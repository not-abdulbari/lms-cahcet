<?php
session_start(); // Start the session to access session variables
include '../faculty/db_connect.php';

// Initialize all variables to avoid undefined variable warnings
$marks_data = [];
$attendance_data = [];
$grades_data = [];
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
    <title>View Student Marks, Attendance, Grades, Report & University Results</title>
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
                          <table style='width: 100%;'>
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
                    (!empty($data['university_results']) && !(in_array(strtoupper($branch), ['MBA', 'MCA']) && $semester == 3));

                if ($has_other_data || $is_pg_sem3_content) {
                    echo "<div id='semester-$semester' class='tab-content'>
                                  <h3>Details for Semester $semester</h3>";

                    // Marks
                    if (isset($data['marks']) && !empty($data['marks'])) {
                        echo "<h4>Internal Assessment Marks</h4>
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

                    // Attendance
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

                    // Grades
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

                    // University Results
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
