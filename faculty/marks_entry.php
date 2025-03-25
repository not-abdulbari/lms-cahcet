<?php

include 'head.php';

// Database connection
include 'db_connect.php';

// Retrieve form data
$branch = $_POST['branch'] ?? '';
$year = $_POST['year'] ?? '';
$section = $_POST['section'] ?? '';
$semester = $_POST['semester'] ?? '';
$subject = $_POST['subject'] ?? '';
$exam = $_POST['exam'] ?? '';

// Fetch students based on branch, year, and section
$query = "SELECT roll_no, name FROM students WHERE branch = ? AND year = ? AND section = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $branch, $year, $section);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Entry</title>
    <style>
        /* General reset and font style */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #a8c0ff, #3f7cac); /* Soft pastel gradient */
    color: #333;
    padding: 40px;
}

h2 {
    text-align: center;
    color: #2e3b5e; /* Dark blue */
    font-size: 28px;
    margin-bottom: 15px;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
}

h3 {
    text-align: center;
    color: #5072a7; /* Soft blue */
    font-size: 20px;
    margin-bottom: 20px;
}

/* Form Container */
form {
    background-color: #fff; /* White background */
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    max-width: 800px; /* Slightly reduced width for better balance */
    margin: 0 auto;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
}

table th, table td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
    color: #555;
}

table th {
    background-color: #5072a7; /* Soft blue header */
    color: white;
    text-transform: uppercase;
}

table tr:nth-child(even) {
    background-color: #f9f9f9; /* Very light gray for even rows */
}

table tr:hover {
    background-color: #e0f3ff; /* Light blue hover effect */
}

/* Input Fields */
input[type="text"] {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 2px solid #5072a7; /* Soft blue border */
    margin: 8px 0;
    background-color: #fafafa;
    transition: border-color 0.3s ease, background-color 0.3s ease;
}

input[type="text"]:focus {
    border-color: #3f7cac; /* Darker blue on focus */
    outline: none;
    background-color: #fff;
}

/* Button Styling */
button {
    width: 100%;
    padding: 15px;
    background-color: #3f7cac; /* Soft teal button */
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

button:hover {
    background-color: #a8c0ff; /* Light blue hover effect */
    transform: scale(1.05); /* Slight zoom effect on hover */
}

/* Responsive Design */
@media (max-width: 768px) {
    h2 {
        font-size: 22px;
    }

    h3 {
        font-size: 18px;
    }

    table th, table td {
        font-size: 14px;
    }

    button {
        padding: 12px;
        font-size: 16px;
    }
}

/* Input Placeholder Text */
::placeholder {
    color: #aaa;
}

    </style>
</head>
<body>

    <h2>Mark Entry for <?= htmlspecialchars($branch) ?> - <?= htmlspecialchars($year) ?> Year - Section <?= htmlspecialchars($section) ?></h2>
    <h3>Subject: <?= htmlspecialchars($subject) ?> | Exam: <?= htmlspecialchars($exam) ?></h3>

    <form method="post" action="submit_marks.php">
        <!-- Hidden inputs to retain form details -->
        <input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
        <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
        <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
        <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>"> 
        <input type="hidden" name="subject" value="<?= htmlspecialchars($subject) ?>">
        <input type="hidden" name="exam" value="<?= htmlspecialchars($exam) ?>">

        <!-- Table to input marks -->
        <table>
            <thead>
                <tr>
                    <th>Roll Number</th>
                    <th>Name</th>
                    <th>Marks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['roll_no']) ?></td>
                        <td><?= htmlspecialchars($student['name']) ?></td>
                        <td>
                            <input 
                                type="text" 
                                name="marks[<?= htmlspecialchars($student['roll_no']) ?>]" 
                                placeholder="Enter marks" 
                                >
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit">Submit Marks</button>
    </form>
    
</body>
</html>
