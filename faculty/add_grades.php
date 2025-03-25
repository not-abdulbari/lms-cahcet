<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $roll_no = $_POST['roll_no'];
    $display_semester = $_POST['semester'];
    $semesters = $_POST['semester_row'];
    $subjects = $_POST['subject_code'];
    $grades = $_POST['grade'];

    // Database connection
    include 'db_connect.php';

    $stmt = $conn->prepare("INSERT INTO university_grades (roll_no, display_semester, semester, subject_code, grade) 
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE grade = VALUES(grade)");

    // Loop through subjects and grades
    foreach ($subjects as $key => $subject_code) {
        if (!empty($subject_code) && !empty($grades[$key]) && !empty($semesters[$key])) {
            $uppercase_subject_code = strtoupper(trim($subject_code)); 
            $grade = strtoupper(trim($grades[$key]));
            $semester_row = $semesters[$key]; 
            $stmt->bind_param("siiss", $roll_no, $display_semester, $semester_row, $uppercase_subject_code, $grade);
            $stmt->execute();
        }
    }
    
    // Close connection
    $stmt->close();
    $conn->close();

    echo "<p>University grades added successfully!</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add University Grades</title>
    <style>
        /* Basic Reset */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f7f7f7;
    color: #333;
    margin: 0;
    padding: 0;
}

/* Headings */
h1 {
    text-align: center;
    color: #2c3e50;
    font-size: 28px;
    margin: 40px 0;
    font-weight: bold;
}

/* Form */
form {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 5px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-left: 5px solid #3498db;
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Labels and Inputs */
label {
    display: block;
    font-weight: bold;
    color: #2c3e50;
    font-size: 14px;
    margin: 10px 0 5px;
}

input, select, button {
    width: 100%;
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ddd;
    border-radius: 3px;
    margin-bottom: 10px;
    background-color: #f5f5f5;
    transition: border-color 0.3s ease, transform 0.3s ease;
}

input:focus {
    border-color: #3498db;
    outline: none;
}

button {
    background-color: #3498db;
    color: #fff;
    font-size: 16px;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.3s ease;
    padding: 10px;
}

button:hover {
    background-color: #2980b9;
}

button:active {
    transform: scale(0.98);
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table th, table td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: center;
}

table tbody tr:hover {
    background-color: #f1f1f1;
}

/* Button Container */
.btn-container {
    text-align: center;
    margin-top: 20px;
}

/* Additional Button */
.add-row {
    background-color: #eaf2f8;
    color: #3498db;
    padding: 10px 15px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

.add-row:hover {
    background-color: #d1e7f3;
}

/* Responsive Design */
@media (max-width: 768px) {
    form {
        padding: 15px;
    }
}

    </style>
    <script>
        function addRow() {
            const table = document.getElementById("gradesTable").getElementsByTagName('tbody')[0];
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="semester_row[]"></td>
                <td><input type="text" name="subject_code[]"></td>
                <td><input type="text" name="grade[]"></td>
            `;
            table.appendChild(row);

            setTimeout(() => {
                document.querySelector('.add-row').style.transform = 'rotate(0deg)';
            }, 300);
        }
    </script>
</head>
<body>
    <header>
        <?php include 'head.php'; ?>
    </header>
    <div class="container">
        <h1>Add University Grades</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="roll_no">Roll Number:</label>
                <input type="text" id="roll_no" name="roll_no" required>
            </div>
            <div>
                <label for="semester">Display Semester:</label>
                <select id="semester" name="semester" required>
                    <option value="">-- Select Semester --</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>">Semester <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <h3>Enter Grades</h3>
            <table id="gradesTable">
                <thead>
                    <tr>
                        <th>Semester</th>
                        <th>Subject Code</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="semester_row[]"></td>
                        <td><input type="text" name="subject_code[]"></td>
                        <td><input type="text" name="grade[]"></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="add-row" onclick="addRow()">Add More Subjects</button>
            <div class="btn-container">
                <button type="submit">Submit</button>
            </div>
        </form>
    </div>
</body>
</html>
