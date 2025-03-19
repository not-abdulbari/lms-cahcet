<?php
session_start();

include 'head.php';

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: faculty_login.php');
    exit;
}

// Database connection
include 'db_connect.php';

// Reusable function to fetch distinct values for dropdowns
function fetchDistinctValues($conn, $column, $table) {
    $stmt = $conn->prepare("SELECT DISTINCT $column FROM $table ORDER BY $column ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $values[] = htmlspecialchars($row[$column]);
    }
    $stmt->close();
    return $values;
}

// Fetch dropdown values
$branches = fetchDistinctValues($conn, 'branch', 'students');
$years = fetchDistinctValues($conn, 'year', 'students');
$sections = fetchDistinctValues($conn, 'section', 'students');
$semesters = fetchDistinctValues($conn, 'semester', 'subjects');

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <style>
/* Base styling for body */
/* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body Styling */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #f7f9fc, #e4f1fe); /* Light gradient background */
    margin: 0;
    padding: 0;
    color: #333;
}

/* Header Styling */
h2 {
    text-align: center;
    color: #2c3e50;
    font-size: 28px;
    margin-bottom: 40px;
    text-transform: uppercase;
    font-weight: bold;
}

/* Form Styling */
form {
    max-width: 800px;
    margin: 30px auto;
    padding: 40px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease-in-out;
    border-left: 5px solid #3498db;
}

form:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

/* Form Elements Styling */
label {
    margin-top: 15px;
    display: block;
    font-weight: bold;
    color: #2c3e50;
    font-size: 16px;
}

select, button {
    width: 100%;
    padding: 12px;
    margin-top: 10px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f5f5f5;
    transition: background-color 0.3s, border-color 0.3s;
}

select:focus, button:focus {
    outline: none;
    border-color: #3498db;
}

select:hover, button:hover {
    background-color: #eaf2f8;
}

/* Button Styling */
button {
    background-color: #3498db;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

button:hover {
    background-color: #2980b9;
    transform: scale(1.05);
}

button:active {
    transform: scale(1);
}

/* Responsive Form Layout */
.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row .form-group {
    flex: 1;
    min-width: 250px;
}

.form-row select {
    width: 100%;
}

/* Specific Styling for Form-Group Labels */
.form-row .form-group label {
    font-weight: normal;
    font-size: 16px;
}

/* Interactive Select Dropdowns */
select {
    background-color: #f9f9f9;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 5px;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

select option:hover {
    background-color: #f1f1f1;
}

/* Button Hover for Different States */
button:focus {
    outline: none;
    border-color: #3498db;
}

/* Mobile Responsive Styling */
@media (max-width: 768px) {
    form {
        padding: 20px;
    }

    .form-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-row .form-group {
        width: 100%;
    }
}

/* Smooth Input Animations */
form {
    animation: fadeIn 1s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Placeholder Text Color */
input::placeholder,
select::placeholder {
    color: #bbb;
}

/* Gradient Background for the Page */
body {
    background: linear-gradient(135deg, #f7f9fc, #e4f1fe);
}


    </style>
    <script>
        function fetchSubjects() {
    const branch = document.getElementById("branch").value;
    const semester = document.getElementById("semester").value;
    const subjectSelect = document.getElementById("subject");

    // Reset subject dropdown
    subjectSelect.innerHTML = '<option value="">Select Subject</option>';

    if (branch && semester) {
        fetch(`get_subjects.php?branch=${encodeURIComponent(branch)}&semester=${encodeURIComponent(semester)}`)
            .then(response => response.json())
            .then(subjects => {
                if (subjects.error) {
                    console.error(subjects.error);
                    alert("Error fetching subjects: " + subjects.error);
                } else {
                    subjects.forEach(subject => {
                        const option = document.createElement("option");
                        option.value = subject.subject_code;
                        option.textContent = `${subject.subject_code} - ${subject.subject_name}`;
                        subjectSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error("Error fetching subjects:", error));
    }
}
    </script>
</head>
<body>
    <h2>Marks Entry</h2>
    <form action="marks_entry1.php" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label for="branch">Select Branch:</label>
                <select name="branch" id="branch" onchange="fetchSubjects()" required>
                    <option value="">Select Branch</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= $branch ?>"><?= $branch ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="year">Select Year of Passing:</label>
                <select name="year" id="year" required>
                    <option value="">Select Year of Passing</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= $year ?>"><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="section">Select Section:</label>
                <select name="section" id="section" required>
                    <option value="">Select Section</option>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?= $section ?>"><?= $section ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="semester">Select Semester:</label>
                <select name="semester" id="semester" onchange="fetchSubjects()" required>
                    <option value="">Select Semester</option>
                    <?php foreach ($semesters as $semester): ?>
                        <option value="<?= $semester ?>"><?= $semester ?> Semester</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
        <div class="form-group">
            <label for="subject">Select Subject:</label>
            <select name="subject" id="subject" required>
                <option value="">Select Subject</option>
            </select>
        </div>
    </div>

            <div class="form-group">
                <label for="exam">Select Exam Type:</label>
                <select name="exam" id="exam" required>
                    <option value="">Select Exam</option>
                    <option value="CAT1">CAT1</option>
                    <option value="CAT2">CAT2</option>
                    <option value="Model">Model</option>
                </select>
            </div>
        </div>

        <button type="submit">Go to Mark Entry</button>
    </form>
</body>
</html>
