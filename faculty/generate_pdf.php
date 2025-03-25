<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Include the FPDF library
require('../fpdf186/fpdf.php'); // Update the path to where you extracted FPDF

// Include your database connection file
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_no = $_POST['roll_no'];
    $branch = $_POST['branch'];
    $year = $_POST['year'];
    $year_roman = isset($_POST['year_roman']) ? $_POST['year_roman'] : ''; // Add this check
    $section = $_POST['section'];
    $semester = $_POST['semester'];
    $exam = $_POST['exam'];

    // Fetch student info
    $sql = "SELECT * FROM students WHERE roll_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $roll_no);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

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


    // Fetch student marks
    $marks_sql = "SELECT m.subject AS subject_code, sub.subject_name, m.marks 
                  FROM marks m
                  JOIN subjects sub ON m.subject = sub.subject_code
                  WHERE m.roll_no = ? AND m.semester = ? AND m.exam = ?";
    $marks_stmt = $conn->prepare($marks_sql);
    $marks_stmt->bind_param("sss", $roll_no, $semester, $exam);
    $marks_stmt->execute();
    $marks_result = $marks_stmt->get_result();

    // Create a new PDF instance
    $pdf = new FPDF();
    $pdf->AddPage();

    // Add college logo (replace 'college_logo.jpg' with the actual path to your logo)
    $pdf->Image('../assets/24349bb44aaa1a8c.jpg', 10, 10, 30); // Logo positioned at (10, 10) with width 30

    // Set font for the header
    $pdf->SetFont('Times', 'B', 14);
    $pdf->SetXY(40, 15); // Start text after the logo (X = 50, Y = 10)
    $pdf->Cell(0, 10, 'C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY', 0, 1, 'C');

    // Set font for the place
    $pdf->SetFont('Times', 'B', 14);
    $pdf->SetXY(40, 23); // Align with the college name
    $pdf->Cell(0, 10, 'MELVISHARAM - 632509', 0, 1, 'C');

    // Set font for the department
    $pdf->SetFont('Times', '', 12);
    $pdf->SetXY(40, 30); // Align with the college name
    $pdf->Cell(0, 10, $department, 0, 1, 'C');

    $pdf->SetFont('Times', '', 12);
    $pdf->SetXY(40, 38); // Align with the college name
    $pdf->Cell(0, 10, 'Academic Year 2024 - 2025 (EVEN)', 0, 1, 'C');

    // Add a line
    $pdf->SetY(40); // Adjust Y position to leave space for the logo and header
    $pdf->Cell(0, 10, '__________________________________________________________________________________________________', 0, 1, 'C');

    // Add "Progress Report" heading
    $pdf->SetFont('Times', 'B', 16);
    $pdf->Cell(0, 10, 'PROGRESS REPORT', 0, 1, 'C');

    // Add exam type (CAT1/CAT2/Model Exam)
    $pdf->SetFont('Times', 'B', 14);
    $pdf->Cell(0, 10, $exam . ' Exam', 0, 1, 'C');

    $pdf->Ln(10);

    // Add student info
// Set font for student info (labels in bold, values in regular)
$pdf->SetFont('Times', 'B', 12); // Bold for labels
$pdf->Cell(25, 10, 'Name: ', 0, 0, 'L'); // Label (reduced width)
$pdf->SetFont('Times', '', 12); // Regular for value
$pdf->Cell(70, 10, $student['name'], 0, 0, 'L'); // Value

$pdf->SetFont('Times', 'B', 12); // Bold for labels
$pdf->Cell(60, 10, 'Roll No.: ', 0, 0, 'R'); // Label (reduced width)
$pdf->SetFont('Times', '', 12); // Regular for value
$pdf->Cell(21, 10, $student['roll_no'], 0, 1, 'R'); // Value

$pdf->SetFont('Times', 'B', 12); // Bold for labels
$pdf->Cell(25, 10, 'Year: ', 0, 0, 'L'); // Label (reduced width)
$pdf->SetFont('Times', '', 12); // Regular for value
$pdf->Cell(70, 10, $year_roman, 0, 0, 'L'); // Value

// Set font for student info (labels in bold, values in regular)
$pdf->SetFont('Times', 'B', 12); // Bold for labels
$pdf->Cell(60, 10, 'Branch & Section: ', 0, 0, 'R'); // Label aligned to the right
$pdf->SetFont('Times', '', 12); // Regular for value
$pdf->Cell(21, 10, $branch . ' & ' . $section, 0, 1, 'R'); // Value aligned to the right

    // Add a line break
    $pdf->Ln(10);

   // Calculate table width
$tableWidth = 15 + 30 + 130 + 25; // Sum of column widths

// Calculate starting X-coordinate to center the table
$pageWidth = 210; // A4 page width in mm
$startX = ($pageWidth - $tableWidth) / 2;

// Set the X-coordinate for the table
$pdf->SetX($startX);

// Create the table header
$pdf->SetFont('Times', 'B', 12); // Times New Roman, bold, size 12
$pdf->Cell(15, 10, 'S.No.', 1, 0, 'C');
$pdf->Cell(30, 10, 'Subject Code', 1, 0, 'C');
$pdf->Cell(130, 10, 'Subject Name', 1, 0, 'C');
$pdf->Cell(25, 10, 'Marks', 1, 1, 'C');

// Fetch and add marks data
$pdf->SetFont('Times', '', 12); // Times New Roman, regular, size 12
$index = 1;
$printedSubjects = []; // Array to track printed subject codes

while ($mark = $marks_result->fetch_assoc()) {
    // Check if the subject code has already been printed
    if (!in_array($mark['subject_code'], $printedSubjects)) {
        $markDisplay = $mark['marks'] < 0 ? 'AB' : ($mark['marks'] < 50 ? $mark['marks'] . ' (U)' : $mark['marks']);
        
        // Set the X-coordinate for each row to keep the table centered
        $pdf->SetX($startX);
        
        $pdf->Cell(15, 10, $index, 1, 0, 'C');
        $pdf->Cell(30, 10, $mark['subject_code'], 1, 0, 'C');
        $pdf->Cell(130, 10, $mark['subject_name'], 1, 0, 'L');
        $pdf->Cell(25, 10, $markDisplay, 1, 1, 'C');

        // Add the subject code to the printed list
        $printedSubjects[] = $mark['subject_code'];
        $index++;
    }
}
    // Add a line break before the legends
$pdf->Ln(10);

// Add legends
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(20, 10, 'DEFINITIONS:', 0, 1, 'L');
$pdf->SetFont('Times', '', 12);
$pdf->Cell(25, 10, 'AB - Absent', 0, 1, 'L');
$pdf->Cell(25, 10, '(U) - Fail', 0, 1, 'L');

    // Output the PDF to the browser for download
    $filename = "{$student['roll_no']}-{$exam}-{$semester}.pdf";
    $pdf->Output($filename, 'D');
}

$conn->close();
?>
