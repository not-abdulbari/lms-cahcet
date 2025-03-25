<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

include 'db_connect.php';

// Handle form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $subject_code = strtoupper(trim($_POST['subject_code']));
    $subject_name = trim($_POST['subject_name']);
    $branch = $_POST['branch'];
    $semester = $_POST['semester'];

    if (!empty($subject_code) && !empty($subject_name) && !empty($branch) && !empty($semester)) {
        try {
            // Insert into database
            $sql = "INSERT INTO subjects (subject_code, subject_name, branch, semester) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $subject_code, $subject_name, $branch, $semester);
            $stmt->execute();

            $message = "Subject added successfully!";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) { // Duplicate entry error
                $message = "Error: The subject code '$subject_code' already exists in the database.";
            } else {
                $message = "Database error: " . $e->getMessage();
            }
        }
    } else {
        $message = "All fields are required.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Subjects</title>
    <style>
        /* Basic Reset */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f7f9fc;
    color: #333;
    margin: 0;
    padding: 0;
}

/* Headings */
h1 {
    text-align: center;
    color: #2c3e50;
    font-size: 28px;
    margin-top: 50px;
    font-weight: bold;
}

/* Form */
form {
    max-width: 800px;
    margin: 20px auto;
    padding: 30px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
    border-left: 5px solid #3498db;
    transition: box-shadow 0.3s ease-in-out;
}

form:hover {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

/* Labels and Inputs */
label {
    margin-top: 15px;
    display: block;
    font-weight: bold;
    color: #2c3e50;
    font-size: 14px;
}

input, select, button {
    width: 100%;
    padding: 8px;
    margin-top: 8px;
    font-size: 14px;
    border: 1px solid #ddd;
    border-radius: 3px;
    background-color: #f5f5f5;
    transition: background-color 0.3s, border-color 0.3s;
}

input:focus, select:focus, button:focus {
    border-color: #3498db;
    outline: none;
}

input:hover, select:hover, button:hover {
    background-color: #eaf2f8;
}

/* Button */
button {
    background-color: #3498db;
    color: white;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

button:hover {
    background-color: #2980b9;
    transform: scale(1.05);
}

button:active {
    transform: scale(0.98);
}

/* Form Row */
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

/* Responsive Design */
@media (max-width: 768px) {
    form {
        padding: 20px;
    }

    .form-row {
        flex-direction: column;
    }

    .form-row .form-group {
        width: 100%;
    }
}

    </style>
</head>

<body>
    <header>
        <?php
            include 'head.php'; // This will include the header.php file into the index.php
        ?>
    </header>
    <div class="container">
        <h1>Add Subject</h1>
        <form method="POST" action="">
            <label for="subject_code">Subject Code</label>
            <input type="text" name="subject_code" id="subject_code" required pattern="[A-Z0-9]+" title="Only uppercase letters and numbers allowed">

            <label for="subject_name">Subject Name</label>
            <input type="text" name="subject_name" id="subject_name" required>

            <label for="branch">Branch</label>
            <select name="branch" id="branch" required>
                <option value="">Select Branch</option>
                <option value="CSE">CSE</option>
                <option value="MECH">MECH</option>
                <option value="IT">IT</option>
                <option value="CIVIL">CIVIL</option>
                <option value="EEE">EEE</option>
                <option value="ECE">ECE</option>
                <option value="AIDS">AIDS</option>
                <option value="MCA">MCA</option>
                <option value="MBA">MBA</option>
            </select>

            <label for="semester">Semester</label>
            <select name="semester" id="semester" required>
                <option value="">Select Semester</option>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?= sprintf('%02d', $i) ?>"><?= sprintf('%02d', $i) ?></option>
                <?php endfor; ?>
            </select>

            <button type="submit" name="add_subject">Add Subject</button>
        </form>

        <?php if (!empty($message)): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
    </div>
</body>

</html>
