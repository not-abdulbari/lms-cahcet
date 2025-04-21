<?php
include '../faculty/db_connect.php';
include 'head.php';

// Handling form submission
$university_results_data = [];
$student_data_error = null;
$student_data = null;

// Grades options for the dropdown
$grade_options = ['O', 'A+', 'A', 'B+', 'B', 'C', 'U', 'UA', 'WH1'];

// Fetch available exam types from university_results for the dropdown
$exam_types = [];
$sql_exam_types = "SELECT DISTINCT exam FROM university_results ORDER BY exam ASC";
$result_exam_types = $conn->query($sql_exam_types);
if ($result_exam_types->num_rows > 0) {
    while ($row = $result_exam_types->fetch_assoc()) {
        $exam_types[] = $row['exam'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fetch'])) {
    $reg_no = $_POST['reg_no'];
    $selected_exam = $_POST['exam'];

    // Fetch student biodata
    $sql_student = "SELECT reg_no, name, year
                    FROM students
                    WHERE reg_no = ?";
    $stmt = $conn->prepare($sql_student);
    $stmt->bind_param("s", $reg_no);
    $stmt->execute();
    $result_student = $stmt->get_result();

    if ($result_student->num_rows > 0) {
        $student_data = $result_student->fetch_assoc();
    } else {
        $student_data_error = "Student with register number $reg_no not found.";
    }
    $stmt->close();

    if (isset($student_data)) {
        // Fetch university results for the selected exam
        $sql_university_results = "SELECT DISTINCT ur.semester, ur.subject_code, ur.grade, s.subject_name, ur.exam
                                    FROM university_results ur
                                    JOIN subjects s ON ur.subject_code = s.subject_code
                                    WHERE ur.reg_no = ? AND ur.exam = ?
                                    ORDER BY ur.semester ASC, ur.subject_code ASC";
        $stmt = $conn->prepare($sql_university_results);
        $stmt->bind_param("ss", $reg_no, $selected_exam);
        $stmt->execute();
        $result_university_results = $stmt->get_result();
        $university_results_data = [];
        while ($row = $result_university_results->fetch_assoc()) {
            $university_results_data[$row['semester']][] = $row;
        }
        $stmt->close();
    }
}

// Handle grade updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_changes'])) {
    $reg_no = $_POST['reg_no']; // Ensure we use the specific student's register number

    foreach ($_POST['grade'] as $key => $new_grade) {
        // Parse the unique key (semester|subject_code|exam)
        list($semester, $subject_code, $exam) = explode('|', $key);
        
        // Update the grade in the database only for the specific student
        $sql_update_grade = "UPDATE university_results 
                             SET grade = ? 
                             WHERE reg_no = ? AND semester = ? AND subject_code = ? AND exam = ?";
        $stmt = $conn->prepare($sql_update_grade);
        $stmt->bind_param("ssiss", $new_grade, $reg_no, $semester, $subject_code, $exam);
        $stmt->execute();
        $stmt->close();
    }
    echo "<p class='success'>Grades updated successfully for Register Number: $reg_no!</p>";
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revaluation Dashboard</title>
    <style>
        /* General styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow: hidden;
        }

        h1 {
            font-size: 24px;
            text-align: center;
            color: #333333;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #555555;
        }

        input[type="text"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            border: 1px solid #cccccc;
            border-radius: 4px;
            background-color: #f9f9f9;
        }

        input[type="submit"] {
            display: block;
            width: 100%;
            padding: 10px;
            font-size: 16px;
            background-color: #4CAF50;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-transform: uppercase;
            font-weight: bold;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .success {
            color: #28a745;
            text-align: center;
            margin: 10px 0;
            font-size: 14px;
        }

        .error {
            color: #ff4444;
            text-align: center;
            margin: 10px 0;
            font-size: 14px;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-table th, 
        .results-table td {
            padding: 10px;
            border: 1px solid #dddddd;
            text-align: left;
            vertical-align: middle;
        }

        .results-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333333;
        }

        .results-table td {
            background-color: #ffffff;
        }

        .results-table tr:nth-child(even) td {
            background-color: #f9f9f9;
        }

        .edit-button, .cancel-button {
            cursor: pointer;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 14px;
        }

        .edit-button {
            background-color: #20c997;
        }

        .edit-button:hover {
            background-color: #17a589;
        }

        .cancel-button {
            background-color: #e74c3c;
        }

        .cancel-button:hover {
            background-color: #c0392b;
        }

        span.grade-text {
            display: inline-block;
            width: 100%;
            max-width: 200px;
        }

        select {
            width: 100%;
            max-width: 200px;
        }
    </style>
    <script>
        function toggleEdit(rowId) {
            const gradeText = document.getElementById(`grade-text-${rowId}`);
            const gradeSelect = document.getElementById(`grade-select-${rowId}`);
            const editButton = document.getElementById(`edit-button-${rowId}`);

            if (gradeText.style.display === "none") {
                gradeText.style.display = "inline-block";
                gradeSelect.style.display = "none";
                editButton.className = "edit-button";
                editButton.innerText = "Edit";
            } else {
                gradeText.style.display = "none";
                gradeSelect.style.display = "inline-block";
                editButton.className = "cancel-button";
                editButton.innerText = "Cancel";
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Revaluation Dashboard</h1>
        <form method="POST" action="revaluation.php">
            <label for="reg_no">Register Number</label>
            <input type="text" id="reg_no" name="reg_no" placeholder="Enter Register Number" required>

            <label for="exam">Select Exam</label>
            <select id="exam" name="exam" required>
                <option value="">-- Select Exam --</option>
                <?php foreach ($exam_types as $exam): ?>
                    <option value="<?php echo htmlspecialchars($exam); ?>"><?php echo htmlspecialchars($exam); ?></option>
                <?php endforeach; ?>
            </select>

            <input type="submit" name="fetch" value="Fetch Details">
        </form>

        <?php if ($student_data_error): ?>
            <p class="error"><?php echo $student_data_error; ?></p>
        <?php endif; ?>

        <?php if ($student_data): ?>
            <div class="student-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student_data['name']); ?></p>
                <p><strong>Register Number:</strong> <?php echo htmlspecialchars($student_data['reg_no']); ?></p>
                <p><strong>Year:</strong> <?php echo htmlspecialchars($student_data['year']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($university_results_data)): ?>
            <form method="POST" action="revaluation.php">
                <div class="results-table-container">
                    <h2>University Results: <?php echo htmlspecialchars($selected_exam); ?></h2>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Semester</th>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Grade</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($university_results_data as $semester => $results): ?>
                                <?php foreach ($results as $index => $result): ?>
                                    <?php $rowId = $semester . '-' . $index; ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['semester']); ?></td>
                                        <td><?php echo htmlspecialchars($result['subject_code']); ?></td>
                                        <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                        <td>
                                            <span id="grade-text-<?php echo $rowId; ?>" class="grade-text"><?php echo htmlspecialchars($result['grade']); ?></span>
                                            <select name="grade[<?php echo htmlspecialchars($result['semester'] . '|' . $result['subject_code'] . '|' . $result['exam']); ?>]" id="grade-select-<?php echo $rowId; ?>" style="display: none;">
                                                <?php foreach ($grade_options as $grade): ?>
                                                    <option value="<?php echo htmlspecialchars($grade); ?>" <?php echo $grade === $result['grade'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($grade); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="edit-button" id="edit-button-<?php echo $rowId; ?>" onclick="toggleEdit('<?php echo $rowId; ?>')">Edit</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <input type="hidden" name="reg_no" value="<?php echo htmlspecialchars($student_data['reg_no']); ?>">
                <input type="submit" name="save_changes" value="Save Changes">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
