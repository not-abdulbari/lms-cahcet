<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
            $sql_student = "SELECT * FROM students WHERE roll_no = ?";
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
/* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f7f7f7;
    color: #333;
    margin: 0;
    padding: 20px;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    background-color: #fff;
    padding: 30px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

h1 {
    text-align: center;
    color: #444;
    font-size: 28px;
    margin-bottom: 20px;
}

form {
    margin-bottom: 15px;
}

label {
    font-weight: 500;
    display: block;
    margin-bottom: 8px;
    color: #555;
}

input[type="text"],
input[type="submit"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

input[type="submit"] {
    background-color: #333;
    color: #fff;
    cursor: pointer;
}

input[type="submit"]:hover {
    background-color: #555;
}

/* Error Message */
.error {
    color: #e74c3c;
    text-align: center;
    margin-top: 15px;
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table th,
table td {
    padding: 10px;
    text-align: center;
    border: 1px solid #ddd;
}

table th {
    background-color: #444;
    color: #fff;
}

table tr:nth-child(even) {
    background-color: #f7f7f7;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 15px;
        max-width: 100%;
    }

    h1 {
        font-size: 24px;
    }

    input[type="submit"] {
        font-size: 14px;
    }

    table th,
    table td {
        font-size: 14px;
    }
}

            </style>
        </head>
        <body>
            <div class="container">
                <h1>Parent - View Student Marks, Attendance, Grades, and Report</h1>
        
                <form method="POST" action="parent111.php">
                    <label for="roll_number">Enter Roll Number:</label>
                    <input type="text" name="roll_number" id="roll_number" required>
                    <input type="submit" name="submit" value="View Details">
                </form>
        
                <?php
                if (isset($student_data_error)) {
                    echo "<p class='error'>$student_data_error</p>";
                }
                if (isset($student_data)) {
                    echo "<h3>Student Information</h3>";
                    echo "<table>";
                    echo "<tr><th>Name</th><td>" . htmlspecialchars($student_data['name']) . "</td></tr>";
                    echo "<tr><th>Roll Number</th><td>" . htmlspecialchars($student_data['roll_no']) . "</td></tr>";
                    echo "</table>";
                }
        
                if (!empty($marks_data) || !empty($attendance_data) || !empty($grades_data) || isset($report_data)) {
                    // Combine semesters from marks, attendance, grades, and report
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
        
                    // Display details semester-wise
                    foreach ($all_semesters as $semester => $data) {
                        echo "<h3>Details for Semester $semester</h3>";
                    
                        // Display Marks
                        if (!empty($data['marks'])) {
                            echo "<h4>Marks</h4>";
                            echo "<table class='marks-table'>";
                            echo "<tr><th>Subject Code</th><th>Subject Name</th><th>CAT-1</th><th>CAT-2</th><th>Model Exam</th></tr>";
                            foreach ($data['marks'] as $subject) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($subject['subject']?? '') . "</td>";
                                echo "<td>" . htmlspecialchars($subject['subject_name']?? '') . "</td>";
                                echo "<td>" . htmlspecialchars($subject['CAT1']?? ''). "</td>";
                                echo "<td>" . htmlspecialchars($subject['CAT2']?? '') . "</td>";
                                echo "<td>" . htmlspecialchars($subject['Model'] ?? '') . "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        }
                    
                        // Display Attendance
                        if (!empty($data['attendance'])) {
                            echo "<h4>Attendance</h4>";
                            echo "<table class='attendance-table'>";
                            echo "<tr><th>Entry Number</th><th>Percentage</th></tr>";
                            foreach ($data['attendance'] as $entry) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($entry['attendance_entry']) . "</td>";
                                echo "<td>" . htmlspecialchars($entry['percentage']) . "%</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        }
                    // Display Grades
                        if (!empty($data['grades'])) {
                            echo "<h4>Grades</h4>";
                            echo "<table class='grades-table'>";
                            echo "<tr><th>Semester</th><th>Subject Code</th><th>Grade</th></tr>";
                            foreach ($data['grades'] as $entry) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($entry['semester']) . "</td>";
                                echo "<td>" . htmlspecialchars($entry['subject_code']) . "</td>";
                                echo "<td>" . htmlspecialchars($entry['grade']) . "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        }
                    
                        // Display Reports for the Semester
                        if (isset($report_data[$semester])) {
                            echo "<h4>Report</h4>";
                            echo "<div class='report'>";
                            echo "<p><strong>General Behaviour:</strong> " . htmlspecialchars($report_data[$semester]['general_behaviour']) . "</p>";
                            echo "<p><strong>Inside the Campus:</strong> " . htmlspecialchars($report_data[$semester]['inside_campus']) . "</p>";
                            echo "<p><strong>Reports Sent to Parents:</strong></p>";
                            echo "<ul>";
                            echo "<li>" . htmlspecialchars($report_data[$semester]['report_1']) . "</li>";
                            echo "<li>" . htmlspecialchars($report_data[$semester]['report_2']) . "</li>";
                            echo "<li>" . htmlspecialchars($report_data[$semester]['report_3']) . "</li>";
                            echo "<li>" . htmlspecialchars($report_data[$semester]['report_4']) . "</li>";
                            echo "</ul>";
                            echo "<p><strong>Reports Sent to Disciplinary Committee:</strong> " . htmlspecialchars($report_data[$semester]['disciplinary_committee']) . "</p>";
                            echo "<p><strong>Discussion with Parents:</strong> " . htmlspecialchars($report_data[$semester]['parent_discussion']) . "</p>";
                            echo "<p><strong>Remarks:</strong> " . htmlspecialchars($report_data[$semester]['remarks']) . "</p>";
                            echo "</div>";
                        }
                    }
                }            
                ?>
            </div>
        </body>
        </html>
