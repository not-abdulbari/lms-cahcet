<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

// Include the FPDF library
require('../fpdf186/fpdf.php');

// Include your database connection file
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_students = $_POST['students']; // Array of selected roll numbers
    $branch = $_POST['branch'];
    $year = $_POST['year'];
    $year_roman = isset($_POST['year_roman']) ? $_POST['year_roman'] : ''; // Add this check
    $section = $_POST['section'];
    $semester = $_POST['semester'];
    $exam = $_POST['exam'];
    $nba_logo = isset($_POST['nba_logo']) && $_POST['nba_logo'] == "1";

    // Define department names
    $departmentNames = [
        "CSE" => "Department of Computer Science and Engineering",
        "ECE" => "Department of Electronics and Communication Engineering",
        "EEE" => "Department of Electrical and Electronics Engineering",
        "MECH" => "Department of Mechanical Engineering",
        "CIVIL" => "Department of Civil Engineering",
        "IT" => "Department of Information Technology",
        "AIDS" => "Department of Artificial Intelligence & Data Science",
        "MBA" => "Department of Master of Business Administration",
        "MCA" => "Department of Master of Computer Applications",
    ];

    $department = isset($departmentNames[$branch]) ? $departmentNames[$branch] : "Department of $branch";

    // Create a new PDF instance
    $pdf = new FPDF();
    
    foreach ($selected_students as $roll_no) {
        // Fetch student info
        $sql = "SELECT * FROM students WHERE roll_no = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $roll_no);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Fetch student marks
        $marks_sql = "SELECT m.subject AS subject_code, sub.subject_name, m.marks 
                      FROM marks m
                      JOIN subjects sub ON m.subject = sub.subject_code
                      WHERE m.roll_no = ? AND m.semester = ? AND m.exam = ?";
        $marks_stmt = $conn->prepare($marks_sql);
        $marks_stmt->bind_param("sss", $roll_no, $semester, $exam);
        $marks_stmt->execute();
        $marks_result = $marks_stmt->get_result();

        // Fetch average attendance
        $attendance_sql = "SELECT AVG(percentage) AS average_attendance FROM semester_attendance WHERE roll_no = ? AND semester = ?";
        $attendance_stmt = $conn->prepare($attendance_sql);
        $attendance_stmt->bind_param("ss", $roll_no, $semester);
        $attendance_stmt->execute();
        $attendance_result = $attendance_stmt->get_result()->fetch_assoc();
        $average_attendance = isset($attendance_result['average_attendance']) ? $attendance_result['average_attendance'] : 0; // Default to 0 if null
        $attendance_stmt->close();

        // Add a new page for each student
        $pdf->AddPage();

        // Add college logo
        $pdf->Image('../assets/24349bb44aaa1a8c.jpg', 10, 10, 30);

        // Header details
        $pdf->SetFont('Times', 'B', 14);
        $pdf->SetXY(40, 15);
        $pdf->Cell(0, 10, 'C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY', 0, 1, 'C');
        $pdf->SetXY(40, 23);
        $pdf->Cell(0, 10, 'MELVISHARAM - 632509', 0, 1, 'C');
        $pdf->SetFont('Times', '', 12);
        $pdf->SetXY(40, 30);
        $pdf->Cell(0, 10, $department, 0, 1, 'C');

        if ($nba_logo) {
            $pdf->Image('../assets/nba-logo.png', 170, 23, 30);
        }

        $pdf->SetXY(40, 38);
        $pdf->Cell(0, 10, 'Academic Year 2024 - 2025 (EVEN)', 0, 1, 'C');
        $pdf->Cell(0, 10, '_______________________________________________________________', 0, 1, 'C');
        $pdf->SetFont('Times', 'B', 16);
        $pdf->Cell(0, 10, 'PROGRESS REPORT', 0, 1, 'C');
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 10, $exam . ' Exam', 0, 1, 'C');
        $pdf->Ln(10);

        // Student details
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(25, 10, 'Name: ', 0, 0, 'L');
        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(70, 10, $student['name'], 0, 0, 'L');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(60, 10, 'Roll No.: ', 0, 0, 'R');
        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(21, 10, $student['roll_no'], 0, 1, 'R');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(25, 10, 'Year: ', 0, 0, 'L');
        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(70, 10, $year_roman, 0, 0, 'L');
        $pdf->Ln(10);

        // Table header
        $tableWidth = 15 + 30 + 130 + 25;
        $startX = (210 - $tableWidth) / 2;
        $pdf->SetX($startX);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(15, 10, 'S.No.', 1, 0, 'C');
        $pdf->Cell(30, 10, 'Subject Code', 1, 0, 'C');
        $pdf->Cell(130, 10, 'Subject Name', 1, 0, 'C');
        $pdf->Cell(25, 10, 'Marks', 1, 1, 'C');

        // Table data
        $pdf->SetFont('Times', '', 12);
        $index = 1;
        $printedSubjects = []; // Track printed subject codes
        while ($mark = $marks_result->fetch_assoc()) {
            if (!in_array($mark['subject_code'], $printedSubjects)) {
                $markDisplay = $mark['marks'] < 0 ? 'AB' : ($mark['marks'] < 50 ? $mark['marks'] . ' (U)' : $mark['marks']);
                $pdf->SetX($startX);
                $pdf->Cell(15, 10, $index, 1, 0, 'C');
                $pdf->Cell(30, 10, $mark['subject_code'], 1, 0, 'C');
                $pdf->Cell(130, 10, $mark['subject_name'], 1, 0, 'L');
                $pdf->Cell(25, 10, $markDisplay, 1, 1, 'C');
                $printedSubjects[] = $mark['subject_code']; // Add to printed list
                $index++;
            }
        }

        // Attendance
        $pdf->Ln(10);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(50, 10, 'Average Attendance:', 0, 0, 'L');
        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(0, 10, round($average_attendance, 2) . '%', 0, 1, 'L');
    }

    // Output the PDF
    $filename = "{$branch}-{$year}-{$exam}-{$semester}.pdf";
    ob_end_clean();
    $pdf->Output($filename, 'D');
}

$conn->close();
?>
