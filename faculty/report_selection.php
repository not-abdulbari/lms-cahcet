<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
include 'db_connect.php';
include 'head.php';

// Fetch distinct values for dropdowns
$branches = $conn->query("SELECT DISTINCT branch FROM marks WHERE branch IS NOT NULL ORDER BY branch ASC");
$years = $conn->query("SELECT DISTINCT year FROM marks WHERE year IS NOT NULL ORDER BY year ASC");
$sections = $conn->query("SELECT DISTINCT section FROM marks WHERE section IS NOT NULL ORDER BY section ASC");
$semesters = $conn->query("SELECT DISTINCT semester FROM marks WHERE semester IS NOT NULL ORDER BY CAST(semester AS UNSIGNED) ASC");
$subjects = $conn->query("SELECT DISTINCT subject FROM marks WHERE subject IS NOT NULL ORDER BY subject ASC");
$exams = $conn->query("SELECT DISTINCT exam FROM marks WHERE exam IS NOT NULL ORDER BY exam ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Report Parameters</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    margin: 40px 0;
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

input[type="text"], input[type="date"], select, button {
    width: 100%;
    padding: 12px;
    margin-top: 10px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f5f5f5;
    transition: background-color 0.3s, border-color 0.3s;
}

input[type="text"]:focus, input[type="date"]:focus, select:focus, button:focus {
    outline: none;
    border-color: #3498db;
}

input[type="text"]:hover, input[type="date"]:hover, select:hover, button:hover {
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
        $(document).ready(function () {
            // Fetch subjects dynamically when branch & semester are selected
            $("#branch, #semester").change(function () {
                var branch = $("#branch").val();
                var semester = $("#semester").val();

                if (branch !== "" && semester !== "") {
                    $.ajax({
                        type: "POST",
                        url: "get_subject.php",
                        data: { branch: branch, semester: semester },
                        success: function (response) {
                            $("#subject").html(response);
                        }
                    });
                }
            });
        });
    </script>

</head>
<body>

    <div class="container">
        <h2>Internal Exam Result Analysis</h2>
        <form method="post" action="generate_report.php">
            <div class="dropdown-group">
                <label>Branch:</label>
                <select name="branch" id="branch" required>
                    <option value="">Select Branch</option>
                    <?php while ($row = $branches->fetch_assoc()) { ?>
                        <option value="<?= htmlspecialchars($row['branch']) ?>"><?= htmlspecialchars($row['branch']) ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="dropdown-group">
                <label>Year:</label>
                <select name="year" required>
                    <option value="">Select Year</option>
                    <?php while ($row = $years->fetch_assoc()) { ?>
                        <option value="<?= htmlspecialchars($row['year']) ?>"><?= htmlspecialchars($row['year']) ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="dropdown-group">
                <label>Section:</label>
                <select name="section" required>
                    <option value="">Select Section</option>
                    <?php while ($row = $sections->fetch_assoc()) { ?>
                        <option value="<?= htmlspecialchars($row['section']) ?>"><?= htmlspecialchars($row['section']) ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="dropdown-group">
                <label>Semester:</label>
                <select name="semester" id="semester" required>
                    <option value="">Select Semester</option>
                    <?php while ($row = $semesters->fetch_assoc()) { ?>
                        <option value="<?= htmlspecialchars($row['semester']) ?>"><?= htmlspecialchars($row['semester']) ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="dropdown-group">
                <label>Subject:</label>
                <select name="subject" id="subject" required>
                    <option value="">Select Semester & Branch First</option>
                </select>
            </div>

            <div class="dropdown-group">
                <label>Exam Type:</label>
                <select name="exam" required>
                    <option value="">Select Exam Type</option>
                    <?php while ($row = $exams->fetch_assoc()) { ?>
                        <option value="<?= htmlspecialchars($row['exam']) ?>"><?= htmlspecialchars($row['exam']) ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="dropdown-group">
                <label>Faculty Code:</label>
                <input type="text" name="faculty_code" required>
            </div>

            <div class="dropdown-group">
                <label>Date of Exam:</label>
                <input type="date" name="exam_date" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="btn-container">
                <a href="faculty_dashboard.php" class="btn btn-back">← Back</a>
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </div>
        </form>
    </div>

</body>
</html>
