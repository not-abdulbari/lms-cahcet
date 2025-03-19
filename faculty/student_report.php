<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
// Database connection
include 'db_connect.php';

$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_no = trim($_POST['roll_no'] ?? '');
    $display_semester = trim($_POST['display_semester'] ?? '');
    $general_behaviour = trim($_POST['general_behaviour'] ?? '');
    $inside_campus = trim($_POST['inside_campus'] ?? '');
    $report_1 = trim($_POST['report_1'] ?? '');
    $report_2 = trim($_POST['report_2'] ?? '');
    $report_3 = trim($_POST['report_3'] ?? '');
    $report_4 = trim($_POST['report_4'] ?? '');
    $disciplinary_committee = trim($_POST['disciplinary_committee'] ?? '');
    $parent_discussion = trim($_POST['parent_discussion'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $arrears = trim($_POST['arrears'] ?? '');
    $note = trim($_POST['note'] ?? '');
    
    if (empty($roll_no)) {
        die("Roll number is required.");
    }
    
    $sql = "INSERT INTO reports (roll_no, display_semester, general_behaviour, inside_campus, report_1, report_2, report_3, report_4, disciplinary_committee, parent_discussion, remarks, arrears, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE general_behaviour=VALUES(general_behaviour), inside_campus=VALUES(inside_campus), report_1=VALUES(report_1), report_2=VALUES(report_2), 
            report_3=VALUES(report_3), report_4=VALUES(report_4), disciplinary_committee=VALUES(disciplinary_committee), parent_discussion=VALUES(parent_discussion), 
            remarks=VALUES(remarks), arrears=VALUES(arrears), note=VALUES(note)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssss", $roll_no, $display_semester, $general_behaviour, $inside_campus, $report_1, $report_2, $report_3, $report_4, $disciplinary_committee, $parent_discussion, $remarks, $arrears, $note);
    
    if ($stmt->execute()) {
        $success_message = "Record saved successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
}
$conn->close();

if (!empty($success_message)) {
    echo "<p class='success-message'>{$success_message}</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Report</title>
    <style>
       /* Base styling for body */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #f7f9fc, #e4f1fe); /* Light gradient background */
    margin: 0;
    padding: 0;
    color: #333;
    font-size: 14px; /* Reduced font size */
}

/* Center the h1 tag */
h1 {
    text-align: center;
    color: #2c3e50;
    font-size: 24px; /* Reduced font size */
    margin-top: 40px;
    text-transform: uppercase;
    font-weight: bold;
}

/* Form styling */
form {
    max-width: 900px;
    margin: 30px auto;
    padding: 40px;
    background: #ffffff;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease-in-out;
    border-left: 5px solid #3498db;
}

/* Form elements styling */
label {
    margin-top: 20px;
    display: block;
    font-weight: bold;
    color: #2c3e50;
    font-size: 16px; /* Reduced font size */
}

select,
button {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    font-size: 14px; /* Reduced font size */
    border: 2px solid #ddd;
    border-radius: 5px;
    background-color: #f5f5f5;
    transition: background-color 0.3s, border-color 0.3s;
}

select:focus,
button:focus {
    outline: none;
    border-color: #3498db;
}

/* Button styling */
button {
    background-color: #3498db;
    color: white;
    font-size: 16px; /* Reduced font size */
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

/* Form row styling */
.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-bottom: 20px;
}

.form-row .form-group {
    flex: 1;
    min-width: 250px;
}

.form-row select {
    width: 100%;
}

.form-row .form-group label {
    font-weight: normal;
    font-size: 14px; /* Reduced font size */
}

/* Select element styling */
select {
    background-color: #f9f9f9;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 5px;
    transition: background-color 0.3s ease, border 0.3s ease;
}

button:focus {
    outline: none;
    border-color: #3498db;
}

/* Mobile responsive styling */
@media screen and (max-width: 768px) {
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

/* Form group styling */
.form-group {
    margin-bottom: 20px;
}

/* Input and textarea styling */
input[type="text"], textarea {
    width: 100%;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #ddd;
    font-size: 14px; /* Reduced font size */
}

input[type="text"]:focus, textarea:focus {
    outline: none;
    border-color: #3498db;
}

/* Success message styling */
.success-message {
    text-align: center;
    color: green;
    font-size: 16px; /* Reduced font size */
    margin-top: 20px;
}


    </style>
</head>
<body>

    <?php
        include 'head.php'; // This will include the header.php file into the index.php
    ?>

    <div class="container">
        <h1>Student Report</h1>
        <form action="" method="post">
            <div class="form-group">
                <label for="roll_no">Enter Roll Number:</label>
                <input id="roll" type="text" name="roll_no" required>
            </div>
            <div class="form-group">
                <label for="display_semester">Semester:</label>
                <input type="text" name="display_semester">
            </div>
            <div class="form-group">
                <label for="general_behaviour">General Behaviour:</label>
                <textarea name="general_behaviour" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="inside_campus">Inside the Campus:</label>
                <textarea name="inside_campus" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Reports Sent to Parents:</label>
                <input type="text" name="report_1" placeholder="1)">
                <input type="text" name="report_2" placeholder="2)">
                <input type="text" name="report_3" placeholder="3)">
                <input type="text" name="report_4" placeholder="4)">
            </div>
            <div class="form-group">
                <label for="disciplinary_committee">Reports Sent to Disciplinary Committee:</label>
                <textarea name="disciplinary_committee" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="parent_discussion">Discussion with the Parents:</label>
                <textarea name="parent_discussion" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="remarks">Remarks:</label>
                <textarea name="remarks" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="arrears">Number of Arrears, If Any:</label>
                <input type="text" name="arrears">
            </div>
            <div class="form-group">
                <label for="note">Note:</label>
                <textarea name="note" rows="3"></textarea>
            </div>
            <button type="submit">Submit</button>
        </form>
    </div>
</body>
</html>
