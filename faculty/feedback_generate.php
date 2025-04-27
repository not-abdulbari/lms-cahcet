<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../fpdf186/fpdf.php';
include 'db_connect.php'; // Include your database connection file

// Start output buffering to suppress accidental output
ob_start();

$roll_no = $_GET['roll_no'];

// Fetch student details
$student_sql = "SELECT s.name AS student_name, si.* FROM students s 
                JOIN student_information si ON s.roll_no = si.roll_no 
                WHERE s.roll_no = ?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param('s', $roll_no);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die('Student not found.');
}

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Times', '', 12);

// Header
$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 10, 'C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY', 0, 1, 'C');
$pdf->Ln(10);

// Student Details
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 10, "Academic Year: ____________ / Semester: ____________", 0, 1);
$pdf->Cell(0, 10, "Name of the Student: " . $student['student_name'], 0, 1);
$pdf->Cell(0, 10, "Roll No: " . $roll_no, 0, 1);
$pdf->Cell(0, 10, "Branch & Batch of Study: " . $student['branch'] . " (" . $student['batch'] . ")", 0, 1);
$pdf->Cell(0, 10, "Name of the Parent: ______________", 0, 1);
$pdf->Cell(0, 10, "Address: ______________", 0, 1);
$pdf->Cell(0, 10, "Phone No: ______________", 0, 1);
$pdf->Cell(0, 10, "E-mail: ______________", 0, 1);
$pdf->Ln(10);

// Feedback Table
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(10, 10, 'S.No', 1);
$pdf->Cell(80, 10, 'Parameter', 1);
$pdf->Cell(25, 10, 'Excellent', 1);
$pdf->Cell(25, 10, 'Very Good', 1);
$pdf->Cell(25, 10, 'Good', 1);
$pdf->Cell(25, 10, 'Average', 1);
$pdf->Ln();

$parameters = [
    'Institutional Discipline and Culture',
    'Infrastructure Facilities',
    'Communication from College about Progress of Your Ward',
    'Career Guidance and Placement',
    'How do you rate our college?'
];

$pdf->SetFont('Times', '', 12);
foreach ($parameters as $index => $parameter) {
    $pdf->Cell(10, 10, $index + 1, 1);
    $pdf->Cell(80, 10, $parameter, 1);
    $pdf->Cell(25, 10, '', 1);
    $pdf->Cell(25, 10, '', 1);
    $pdf->Cell(25, 10, '', 1);
    $pdf->Cell(25, 10, '', 1);
    $pdf->Ln();
}

$pdf->Ln(10);
$pdf->Cell(0, 10, 'Suggestions if any: ______________', 0, 1);
$pdf->Ln(10);
$pdf->Cell(0, 10, 'Date: ______________', 0, 1);
$pdf->Cell(0, 10, 'Signature & Name of the Parent: ______________', 0, 1);

// Clean buffer and send PDF
ob_end_clean();
$pdf->Output();
?>
