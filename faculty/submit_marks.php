<?php
// Database connection
include 'db_connect.php';

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate required POST data
    if (!empty($_POST['semester']) && !empty($_POST['subject']) && !empty($_POST['exam']) && isset($_POST['marks'])) {
        $branch = htmlspecialchars($_POST['branch']);
        $year = htmlspecialchars($_POST['year']);
        $section = htmlspecialchars($_POST['section']);
        $semester = htmlspecialchars($_POST['semester']);
        $subject = htmlspecialchars($_POST['subject']);
        $exam = htmlspecialchars($_POST['exam']);
        $marks = $_POST['marks']; // Array of roll_no => marks

        // Prepare SQL statement
        $stmt = $conn->prepare("
            INSERT INTO marks (roll_no, subject, exam, marks, semester, branch, year, section)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                marks = VALUES(marks),
                semester = VALUES(semester),
                branch = VALUES(branch),
                year = VALUES(year),
                section = VALUES(section)
        ");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sssissss", $roll_no, $subject, $exam, $mark, $semester, $branch, $year, $section);

        // Iterate through the marks array and execute the statement
        foreach ($marks as $roll_no => $mark) {
            // Skip students with no marks entered
            if (trim($mark) === "") {
                continue;
            }

            $roll_no = htmlspecialchars($roll_no);
            $mark = (int)$mark;

            $stmt->execute();

            if ($stmt->error) {
                echo "Error for Roll No $roll_no: " . $stmt->error . "<br>";
            }
        }
        $stmt->close();
        echo "Marks entered successfully!";
    } else {
        echo "Missing required fields.";
    }
} else {
    echo "Invalid request method.";
}

$conn->close();
?>
