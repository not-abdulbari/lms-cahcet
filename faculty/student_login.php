<?php
ob_start();
include '../faculty/db_connect.php';
include 'head.php';
// Handling form submission
$marks_data = [];
$attendance_data = [];
$grades_data = [];
$report_data = null;
$university_results_data = []; // General array for all university results
$student_data_error = null;
$student_data = null; // Initialize student_data
$year_of_passing = null; // Initialize year of passing
$branch = null; // Initialize branch
$success_message = null; // For profile update success message

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $roll_number = $_POST['roll_no'];
    $reg_no = $_POST['reg_no'];
    
    // // Update students table
    // $sql_update_student = "UPDATE students SET 
    //                       name = ?,
    //                       branch = ?,
    //                       year = ?,
    //                       section = ?
    //                       WHERE roll_no = ?";
    // $stmt = $conn->prepare($sql_update_student);
    // $stmt->bind_param("sssss", 
    //                  $_POST['name'],
    //                  $_POST['branch'],
    //                  $_POST['year'],
    //                  $_POST['section'],
    //                  $roll_number);
    // $stmt->execute();
    // $stmt->close();
    
    // Update student_information table
    $sql_update_info = "UPDATE student_information SET 
                       mail = ?,
                       dob = ?,
                       father_name = ?,
                       occupation = ?,
                       parent_phone = ?,
                       student_phone = ?,
                       present_addr = ?,
                       permanent_addr = ?,
                       languages_known = ?,
                       school = ?,
                       medium = ?,
                       math = ?,
                       physic = ?,
                       chemis = ?,
                       cutoff = ?,
                       quota = ?
                       WHERE roll_no = ?";
    $stmt = $conn->prepare($sql_update_info);
    $stmt->bind_param("sssssssssssssssss", 
                     $_POST['mail'],
                     $_POST['dob'],
                     $_POST['father_name'],
                     $_POST['occupation'],
                     $_POST['parent_phone'],
                     $_POST['student_phone'],
                     $_POST['present_addr'],
                     $_POST['permanent_addr'],
                     $_POST['languages_known'],
                     $_POST['school'],
                     $_POST['medium'],
                     $_POST['math'],
                     $_POST['physic'],
                     $_POST['chemis'],
                     $_POST['cutoff'],
                     $_POST['quota'],
                     $roll_number);
    $stmt->execute();
    $stmt->close();
    
    $success_message = "Profile updated successfully!";
    
    // Redirect to refresh the page with the roll number
    header("Location: student_login.php?roll_number=" . $roll_number . "&success=1");
    exit();
}

// Process regular student lookup
if (($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) || 
    ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['roll_number']))) {
    
    $roll_number = isset($_POST['roll_number']) ? $_POST['roll_number'] : $_GET['roll_number'];
    
    // Fetch student biodata - expanded to include additional fields
    $sql_student = "SELECT s.roll_no, s.reg_no, s.name, s.branch, s.year, s.section, 
                    si.mail, si.dob, si.father_name, si.occupation, si.parent_phone, 
                    si.student_phone, si.present_addr, si.permanent_addr, 
                    si.languages_known, si.school, si.medium, si.math, 
                    si.physic, si.chemis, si.cutoff, si.quota
                    FROM students s
                    LEFT JOIN student_information si ON s.roll_no = si.roll_no
                    WHERE s.roll_no = ?";
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
    --primary-color: #3f51b5; /* Professional blue */
    --primary-light: #e8eaf6; /* Light blue for hover */
    --primary-dark: #303f9f; /* Darker blue for active */
    --secondary-color: #757575; /* Medium gray */
    --success-color: #4caf50; /* Green for success */
    --danger-color: #f44336; /* Red for errors */
    --warning-color: #ff9800; /* Orange for warnings */
    --info-color: #2196f3; /* Light blue for informational sections */
    --light-color: #f5f5f5; /* Light gray for backgrounds */
    --white-color: #ffffff; /* White */
    --dark-color: #000000; /* Black for text */
    --seagreen-color: #2e8b57; /* Sea green for view details button */
    --lawngreen-color: #7cfc00; /* Lawn green for edit button */
    --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    font-family: var(--font-family);
    background-color: var(--light-color);
    color: var(--dark-color); /* Black text */
    margin: 0;
    padding: 20px;
    line-height: 1.6;
}

.container {
    width: 70%;
    max-width: 1200px;
    margin: 0 auto;
    background-color: var(--white-color);
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

h1, h3, h4 {
    color: var(--dark-color); /* Black text */
    text-align: center;
    margin-bottom: 20px;
    font-weight: 600;
}

h1 {
    font-size: 2em;
    border-bottom: 2px solid var(--primary-light);
    padding-bottom: 10px;
}

h3 {
    font-size: 1.5em;
    margin-top: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

h4 {
    font-size: 1.2em;
    margin-top: 20px;
    padding-bottom: 5px;
    border-bottom: 1px solid var(--light-color);
}

form {
    margin-bottom: 20px;
}

label {
    font-weight: 500;
    display: block;
    margin-bottom: 10px;
    color: var(--dark-color); /* Black text */
}

input[type="text"], 
input[type="email"],
input[type="date"],
input[type="tel"],
textarea {
    width: 100%;
    padding: 12px;
    margin: 8px 0 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-sizing: border-box;
    transition: border-color 0.3s ease;
    font-size: 16px;
    color: var(--dark-color); /* Black text */
}

input[type="text"]:focus,
input[type="email"]:focus,
input[type="date"]:focus,
input[type="tel"]:focus,
textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 5px rgba(63, 81, 181, 0.2);
}

textarea {
    resize: none;
}

button, 
input[type="submit"] {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    text-transform: uppercase;
    transition: all 0.3s ease;
    color: var(--white-color);
    background-color: var(--primary-color);
}

button:hover, 
input[type="submit"]:hover {
    background-color: var(--primary-dark);
}

.primary-btn {
    background-color: var(--primary-color);
    color: var(--white-color);
}

.primary-btn:hover {
    background-color: var(--primary-dark);
}

.secondary-btn {
    background-color: var(--secondary-color);
    color: var(--white-color);
}

.secondary-btn:hover {
    background-color: #616161;
}

.green {
    background-color: var(--seagreen-color);
}

.green:hover {
    background-color: #1e6e3c;
}

.edit-btn {
    background-color: #5ba318;
    color: white;
}

.edit-btn:hover {
    background-color: #5ba318;
}

.cancel-btn {
    background-color: var(--danger-color);
    color: var(--white-color);
}

.cancel-btn:hover {
    background-color: #d32f2f;
}

.save-btn {
    background-color: var(--success-color);
    color: var(--white-color);
}

.save-btn:hover {
    background-color: #388e3c;
}

.error {
    color: var(--danger-color);
    text-align: center;
    margin: 15px 0;
    padding: 15px;
    background-color: #ffebee;
    border-radius: 5px;
    font-size: 16px;
}

.success {
    color: var(--success-color);
    text-align: center;
    margin: 15px 0;
    padding: 15px;
    background-color: #e8f5e9;
    border-radius: 5px;
    font-size: 16px;
}

.tabs {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--light-color);
}

.tabs button {
    padding: 12px 20px;
    margin-right: 5px;
    margin-bottom: -1px;
    background-color: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--secondary-color);
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.tabs button:hover {
    color: var(--primary-color);
    border-bottom-color: var(--primary-light);
}

.tabs button.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-content {
    display: none;
    padding: 15px 0;
}

.tab-content.active {
    display: block;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

table th,
table td {
    padding: 15px;
    border: 1px solid #e0e0e0;
    text-align: left;
    font-size: 14px;
    color: var(--dark-color); /* Black text */
}

table th {
    background-color: var(--primary-color);
    color: var(--white-color);
    font-weight: 600;
}

table tr:nth-child(even) {
    background-color: #f9f9f9;
}

table tr:hover {
    background-color: var(--primary-light);
}

.report ul {
    list-style-type: disc;
    padding-left: 20px;
}

.report ul li {
    margin-bottom: 8px;
    color: var(--dark-color); /* Black text */
}

.profile-section {
    margin-bottom: 30px;
}

.hidden {
    display: none;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 20px;
}

.form-group {
    flex: 1;
    min-width: 200px;
}

.readonly-field {
    background-color: #f0f0f0;
    color: var(--dark-color); /* Black text */
    cursor: not-allowed;
}

.styled-dropdown {
    width: 100%;
    padding: 12px;
    margin: 8px 0 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-sizing: border-box;
    transition: border-color 0.3s ease;
    font-size: 16px;
    color: var(--dark-color); /* Black text */
    background-color: var(--light-color);
    appearance: none; /* Remove default select arrow for consistent styling across browsers */
}

.styled-dropdown:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 5px rgba(63, 81, 181, 0.2);
}

.header-with-button {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

@media (max-width: 768px) {
    .container {
        width: 95%;
        padding: 15px;
    }

    .form-row {
        flex-direction: column;
    }
    
    .form-group {
        margin-bottom: 15px;
    }

    h1 {
        font-size: 24px;
    }

    .tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: 5px;
    }

    .tabs button {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    table th,
    table td {
        padding: 10px;
        font-size: 12px;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <h1>STUDENT DASHBOARD</h1>
        <form method="POST" action="student_login.php">
            <label for="roll_number">Enter Roll Number:</label>
            <input type="text" name="roll_number" id="roll_number" required value="<?php echo isset($student_data) ? htmlspecialchars($student_data['roll_no']) : ''; ?>">
            <input type="submit" name="submit" value="View Details" class="primary-btn green">
        </form>

        <?php if (isset($student_data_error)) { echo "<p class='error'>$student_data_error</p>"; } ?>

        <?php if (isset($student_data)) { ?>
            <div class="tabs">
                <button class="tab-link active" onclick="openTab(event, 'profile')">Profile</button>
                <?php
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
                ?>
            </div>

            <!-- Profile Tab Content -->
            <div id="profile" class="tab-content active">
                <h3>Student Information <button type="button" onclick="toggleEditMode()" class="edit-btn" id="edit-btn">Edit</button></h3>
                
                <!-- View Mode -->
                <div id="view-mode" class="profile-section">
                    <table>
                        <tr><th>Name</th><td><?php echo htmlspecialchars($student_data['name']); ?></td></tr>
                        <tr><th>Roll Number</th><td><?php echo htmlspecialchars($student_data['roll_no']); ?></td></tr>
                        <tr><th>Register Number</th><td><?php echo htmlspecialchars($student_data['reg_no']); ?></td></tr>
                        <tr><th>Branch</th><td><?php echo htmlspecialchars($student_data['branch']); ?></td></tr>
                        <tr><th>Section</th><td><?php echo htmlspecialchars($student_data['section']); ?></td></tr>
                        <tr><th>Year</th><td><?php echo htmlspecialchars($student_data['year']); ?></td></tr>
                        <tr><th>Email</th><td><?php echo htmlspecialchars($student_data['mail'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Date of Birth</th><td><?php echo htmlspecialchars($student_data['dob'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Student Phone</th><td><?php echo htmlspecialchars($student_data['student_phone'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Father's Name</th><td><?php echo htmlspecialchars($student_data['father_name'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Occupation</th><td><?php echo htmlspecialchars($student_data['occupation'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Parent's Phone</th><td><?php echo htmlspecialchars($student_data['parent_phone'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Languages Known</th><td><?php echo htmlspecialchars($student_data['languages_known'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Admission Quota</th><td><?php echo htmlspecialchars($student_data['quota'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Present Address</th><td><?php echo htmlspecialchars($student_data['present_addr'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Permanent Address</th><td><?php echo htmlspecialchars($student_data['permanent_addr'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>School</th><td><?php echo htmlspecialchars($student_data['school'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Medium of Instruction</th><td><?php echo htmlspecialchars($student_data['medium'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Mathematics</th><td><?php echo htmlspecialchars($student_data['math'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Physics</th><td><?php echo htmlspecialchars($student_data['physic'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Chemistry</th><td><?php echo htmlspecialchars($student_data['chemis'] ?? 'Not Available'); ?></td></tr>
                        <tr><th>Cutoff Score</th><td><?php echo htmlspecialchars($student_data['cutoff'] ?? 'Not Available'); ?></td></tr>
                    </table>
                </div>
                
                <!-- Edit Mode -->
                <div id="edit-mode" class="profile-section hidden">
                    <form method="POST" action="student_login.php">
                        <button type="button" onclick="cancelEdit()" class="cancel-btn">Cancel</button>
                        <button type="submit" name="update_profile" class="save-btn">Save Changes</button>
                        <div class="clearfix" style="clear:both;"></div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="roll_no">Roll Number:</label>
                                <input type="text" id="roll_no" name="roll_no" value="<?php echo htmlspecialchars($student_data['roll_no']); ?>" readonly class="readonly-field">
                            </div>
                            <div class="form-group">
                                <label for="reg_no">Register Number:</label>
                                <input type="text" id="reg_no" name="reg_no" value="<?php echo htmlspecialchars($student_data['reg_no']); ?>" readonly class="readonly-field">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Name:</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($student_data['name']); ?>" readonly class="readonly-field">
                            </div>
                            <div class="form-group">
                                <label for="branch">Branch:</label>
                                <input type="text" id="branch" name="branch" value="<?php echo htmlspecialchars($student_data['branch']); ?>" readonly class="readonly-field">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="year">Year:</label>
                                <input type="text" id="year" name="year" value="<?php echo htmlspecialchars($student_data['year']); ?>" readonly class="readonly-field">
                            </div>
                            <div class="form-group">
                                <label for="section">Section:</label>
                                <input type="text" id="section" name="section" value="<?php echo htmlspecialchars($student_data['section']); ?>" readonly class="readonly-field">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="mail">Email:</label>
                                <input type="email" id="mail" name="mail" value="<?php echo htmlspecialchars($student_data['mail'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="dob">Date of Birth:</label>
                                <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($student_data['dob'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="father_name">Father's Name:</label>
                                <input type="text" id="father_name" name="father_name" value="<?php echo htmlspecialchars($student_data['father_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="occupation">Occupation:</label>
                                <input type="text" id="occupation" name="occupation" value="<?php echo htmlspecialchars($student_data['occupation'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="student_phone">Student Phone:</label>
                                <input type="tel" id="student_phone" name="student_phone" value="<?php echo htmlspecialchars($student_data['student_phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="parent_phone">Parent's Phone:</label>
                                <input type="tel" id="parent_phone" name="parent_phone" value="<?php echo htmlspecialchars($student_data['parent_phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="languages_known">Languages Known:</label>
                                <input type="text" id="languages_known" name="languages_known" value="<?php echo htmlspecialchars($student_data['languages_known'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="quota">Admission Quota:</label>
                                <select id="quota" name="quota" class="styled-dropdown">
                                    <option value="management" <?php echo (isset($student_data['quota']) && $student_data['quota'] == 'Management') ? 'selected' : ''; ?>>management</option>
                                    <option value="counselling" <?php echo (isset($student_data['quota']) && $student_data['quota'] == 'Counselling') ? 'selected' : ''; ?>>counselling</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="present_addr">Present Address:</label>
                            <textarea id="present_addr" name="present_addr" rows="3"><?php echo htmlspecialchars($student_data['present_addr'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="permanent_addr">Permanent Address:</label>
                            <textarea id="permanent_addr" name="permanent_addr" rows="3"><?php echo htmlspecialchars($student_data['permanent_addr'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="school">School:</label>
                                <input type="text" id="school" name="school" value="<?php echo htmlspecialchars($student_data['school'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="medium">Medium of Instruction:</label>
                                <input type="text" id="medium" name="medium" value="<?php echo htmlspecialchars($student_data['medium'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="math">Mathematics:</label>
                                <input type="text" id="math" name="math" value="<?php echo htmlspecialchars($student_data['math'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="physic">Physics:</label>
                                <input type="text" id="physic" name="physic" value="<?php echo htmlspecialchars($student_data['physic'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="chemis">Chemistry:</label>
                                <input type="text" id="chemis" name="chemis" value="<?php echo htmlspecialchars($student_data['chemis'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="cutoff">Cutoff Score:</label>
                                <input type="text" id="cutoff" name="cutoff" value="<?php echo htmlspecialchars($student_data['cutoff'] ?? ''); ?>">
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            // Generate semester tabs content
            foreach ($all_semesters as $semester => $data) {
                $is_pg_sem3_content = in_array(strtoupper($branch), ['MBA', 'MCA']) && $semester == 3 && isset($data['university_results']) && !empty($data['university_results']);
                $has_other_data = !empty($data['marks']) || !empty($data['attendance']) || !empty($data['grades']) || isset($data['report']) || (!empty($data['university_results']) && !(in_array(strtoupper($branch), ['MBA', 'MCA']) && $semester == 3));

                if ($has_other_data || $is_pg_sem3_content) {
                    echo "<div id='semester-$semester' class='tab-content'>
                              <h3>Details for Semester $semester</h3>";

                    if (isset($data['marks']) && !empty($data['marks'])) {
                        echo "<h4>Internal Assessment Marks</h4>
                              <table class='marks-table'>
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
                              <table class='attendance-table'>
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
                            <table class='grades-table'>
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
                            <table class='university-results-table'>
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
            ?>
        <?php } ?>
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

        function toggleEditMode() {
            document.getElementById('view-mode').classList.add('hidden');
            document.getElementById('edit-mode').classList.remove('hidden');
            document.getElementById('edit-btn').classList.add('hidden');
        }

        function cancelEdit() {
            document.getElementById('view-mode').classList.remove('hidden');
            document.getElementById('edit-mode').classList.add('hidden');
            document.getElementById('edit-btn').classList.remove('hidden');
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

        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success') && urlParams.get('success') === '1') {
                alert("Profile updated successfully!");
            }
        });
    </script>
</body>
</html>
