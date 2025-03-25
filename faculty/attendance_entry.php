<?php

include 'head.php';

// Database connection
include 'db_connect.php';

// Fetch students based on selected criteria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['branch'], $_POST['year'], $_POST['section'], $_POST['semester'], $_POST['entry'])) {
    $branch = $_POST['branch'];
    $year = $_POST['year'];
    $section = $_POST['section'];
    $semester = $_POST['semester'];
    $entry = $_POST['entry']; // Entry 1 or Entry 2

    // Fetch students matching the criteria
    $stmt = $conn->prepare("SELECT roll_no, name FROM students WHERE branch = ? AND year = ? AND section = ?");
    $stmt->bind_param("sss", $branch, $year, $section);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    echo "Invalid access.";
    exit;
}

// Insert or update attendance in the database
if (isset($_POST['submit_attendance'])) {
    $attendance_data = $_POST['attendance'];
    foreach ($attendance_data as $roll_no => $percentage) {
        // Skip if attendance value is empty
        if (trim($percentage) === '') {
            continue;
        }

        // Insert or update attendance record, adding the new percentage to the existing value
        $stmt = $conn->prepare(
            "INSERT INTO semester_attendance (roll_no, semester, attendance_entry, percentage)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE percentage = VALUES(percentage)"
        );
        
        $stmt->bind_param("sisd", $roll_no, $semester, $entry, $percentage);
        $stmt->execute();
        $stmt->close();
    }
    echo "<p class='success-message'>Attendance updated successfully!</p>";
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Entry</title>
    <style>
        /* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body styling */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f3f7fa;
    color: #333;
    padding: 30px;
}

/* Header styling */
h2 {
    text-align: center;
    color: #4a90e2;
    margin-bottom: 20px;
    font-size: 28px;
    text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.1);
}

/* Form styling */
form {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease-in-out;
}

form:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

/* Table styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
    color: #555;
}

th {
    background-color: #4a90e2;
    color: white;
    text-transform: uppercase;
    font-size: 16px;
}

table tr:nth-child(even) {
    background-color: #f9f9f9;
}

table tr:hover {
    background-color: #e3f2fd;
}

/* Input styling */
input[type="text"] {
    width: 100%;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #ddd;
    background-color: #f7f7f7;
    font-size: 14px;
    margin-top: 5px;
    transition: background-color 0.3s, border-color 0.3s;
}

input[type="text"]:focus {
    outline: none;
    border-color: #4a90e2;
    background-color: white;
}

/* Button styling */
button {
    width: 100%;
    padding: 12px;
    background-color: #4a90e2;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s ease;
}

button:hover {
    background-color: #357ab7;
    transform: scale(1.05);
}

/* Success message styling */
.success-message {
    background-color: #28a745;
    color: white;
    padding: 10px;
    margin-top: 20px;
    text-align: center;
    border-radius: 5px;
    font-weight: bold;
}

/* Responsive Design */
@media (max-width: 768px) {
    h2 {
        font-size: 24px;
    }

    form {
        padding: 15px;
    }

    button {
        font-size: 14px;
    }

    table th, table td {
        font-size: 14px;
    }
}

    </style>
</head>
<body>
    <h2>Attendance Entry for <?= htmlspecialchars($branch) ?> - <?= htmlspecialchars($year) ?> - <?= htmlspecialchars($section) ?> (<?= htmlspecialchars($entry) ?>)</h2>
    <form action="attendance_entry.php" method="POST">
        <input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
        <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
        <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
        <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
        <input type="hidden" name="entry" value="<?= htmlspecialchars($entry) ?>">

        <table>
            <thead>
                <tr>
                    <th>Roll No</th>
                    <th>Student Name</th>
                    <th>Attendance Percentage (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['roll_no']) ?></td>
                        <td><?= htmlspecialchars($student['name']) ?></td>
                        <td>
                            <input type="text" name="attendance[<?= htmlspecialchars($student['roll_no']) ?>]" placeholder="Enter attendance" min="0" max="100">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" name="submit_attendance">Submit Attendance</button>
    </form>
</body>
</html>
