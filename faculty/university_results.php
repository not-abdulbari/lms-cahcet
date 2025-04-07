<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
include 'head.php';
include 'db_connect.php'; // Include your database connection file

// Fetch branches
$branches_sql = "SELECT DISTINCT branch FROM students";
$branches_result = $conn->query($branches_sql);

// Fetch years
$years_sql = "SELECT DISTINCT year FROM students";
$years_result = $conn->query($years_sql);

// Fetch sections
$sections_sql = "SELECT DISTINCT section FROM students";
$sections_result = $conn->query($sections_sql);

// Fetch exam types from university_results table
$exam_types_sql = "SELECT DISTINCT exam FROM university_results";
$exam_types_result = $conn->query($exam_types_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>University Results Criteria</title>
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
</head>
<body>
    <div class="form-container">
        <h2>University Results Criteria</h2>
        <form action="university_results_list.php" method="post">
            <div class="form-group">
                <label for="branch">Branch:</label>
                <select id="branch" name="branch" required>
                    <option value="">Select Branch</option>
                    <?php while ($row = $branches_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['branch']) ?>"><?= htmlspecialchars($row['branch']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="year">Year of Passing:</label>
                <select id="year" name="year" required>
                    <option value="">Select Year of Passing</option>
                    <?php while ($row = $years_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['year']) ?>"><?= htmlspecialchars($row['year']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="dropdown-group">
                <label>Year (Roman):</label>
                <select name="year_roman" required>
                    <option value="">Select Year</option>
                    <option value="I">I</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                </select>
            </div>
            <div class="form-group">
                <label for="section">Section:</label>
                <select id="section" name="section" required>
                    <option value="">Select Section</option>
                    <?php while ($row = $sections_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['section']) ?>"><?= htmlspecialchars($row['section']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="exam">Exam:</label>
                <select id="exam" name="exam" required>
                    <option value="">Select Exam</option>
                    <?php while ($row = $exam_types_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['exam']) ?>"><?= htmlspecialchars($row['exam']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit">Submit</button>
            </div>
        </form>
    </div>
</body>
</html>