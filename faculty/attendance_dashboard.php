<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Database connection
include 'head.php';
include 'db_connect.php';
// Reusable function to fetch distinct values for dropdowns
function fetchDistinctValues($conn, $column, $table) {
    $stmt = $conn->prepare("SELECT DISTINCT $column FROM $table");
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

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Dashboard</title>
    <style>
/* Base styling for body */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f7f9fc; /* Light background */
    margin: 0;
    padding: 0;
    color: #333;
}

/* Header Styling */
h2 {
    text-align: center;
    color: #2c3e50;
    font-size: 28px;
    margin: 40px 0;
    font-weight: bold;
}


/* Form styling */
form {
    max-width: 900px;
    margin: 30px auto;
    padding: 40px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease-in-out;
    border-left: 5px solid #3498db;
}

/* Hover effect for form */
form:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

/* Form elements styling */
label {
    margin-top: 15px;
    display: block;
    font-weight: bold;
    color: #2c3e50;
    font-size: 16px;
}

select, button {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    font-size: 14px;
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

/* Button styling */
button {
    background-color: #3498db;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
}

button:hover {
    background-color: #2980b9;
    transform: scale(1.05);
}

button:active {
    transform: scale(1);
}

/* Responsive form layout */
.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

/* Form-group styles */
.form-row .form-group {
    flex: 1;
    min-width: 250px;
}

.form-row select {
    width: 100%;
}

/* Specific styling for form-group labels */
.form-row .form-group label {
    font-weight: normal;
    font-size: 14px;
}

/* Mobile responsive styling */
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

/* Smooth input animations */
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

/* Placeholder text color */
input::placeholder, select::placeholder {
    color: #bbb;
}

/* Background for the page */
body {
    background-color: #f7f9fc;
}
   </style>
</head>
<body>
    <h2>Attendance Entry</h2>
    <form action="attendance_entry.php" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label for="branch">Select Branch:</label>
                <select name="branch" id="branch" required>
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
                <select name="semester" id="semester" required>
                    <option value="">Select Semester</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?> Semester</option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="entry">Select Attendance Entry:</label>
                <select name="entry" id="entry" required>
                    <option value="">Select Entry</option>
                    <option value="Entry 1">Entry 1</option>
                    <option value="Entry 2">Entry 2</option>
                </select>
            </div>
        </div>

        <button type="submit">Go to Attendance Entry</button>
    </form>
</body>
</html>
