<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the FPDF library
require('../fpdf186/fpdf.php');

// Include your database connection file
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $roll_no = $_GET['roll_no'];

    // Fetch student info and additional details
    $sql = "SELECT s.name, si.father_name, si.permanent_addr, si.student_phone, si.parent_phone, s.branch 
            FROM students s 
            JOIN student_information si ON s.roll_no = si.roll_no 
            WHERE s.roll_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $roll_no);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        die("Student not found.");
    }

    // Create a new PDF instance
    $pdf = new FPDF();
    $pdf->AddPage();

    // Add college logo
    $pdf->Image('../assets/24349bb44aaa1a8c.jpg', 10, 10, 30); // Logo positioned at (10, 10) with width 30

    // Set font for the header
    $pdf->SetFont('Times', 'B', 14);

    // Add college name
    $pdf->Cell(0, 10, 'C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY', 0, 1, 'C');
    $pdf->Ln(5);

    // Add Parent Feedback Form title
    $pdf->SetFont('Times', '', 12);
    $pdf->Cell(0, 10, 'Parent Feedback Form', 0, 1, 'C');
    $pdf->Ln(10);

    // Student Details Section
    $pdf->SetFont('Times', '', 12);
    $pdf->Cell(0, 10, "Academic Year: ____________ / Semester: ____________", 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, "Name of the Student: " . $student['name'], 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, "Roll No: " . $roll_no, 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, "Branch: " . $student['branch'], 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, "Name of the Parent: " . $student['father_name'], 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, "Address: " . $student['permanent_addr'], 0, 1);
    $pdf->Ln(5);

    // Phone Numbers Section (Student and Parent)
    $pdf->Cell(50, 10, "Student Phone No: " . $student['student_phone'], 0, 0);
    $pdf->Cell(50, 10, "Parent Phone No: " . $student['parent_phone'], 0, 1);
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
        "Communication from College about Progress of Your Ward\n(continued line for clarity)",
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

    // Align Signature & Name of Parent to the right
    $pdf->Cell(140); // Move cursor to the right
    $pdf->Cell(0, 10, 'Signature & Name of the Parent: ______________', 0, 1, 'R');
    $pdf->Ln(10);

    // Output PDF
    $pdf->Output();
}
?>
