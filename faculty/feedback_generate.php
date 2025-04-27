<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

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

    $department = isset($departmentNames[$student['branch']]) ? $departmentNames[$student['branch']] : "Department of " . $student['branch'];

    // Create a new PDF instance
    $pdf = new FPDF();
    $pdf->AddPage();

    // Add college logo
    $pdf->Image('../assets/24349bb44aaa1a8c.jpg', 10, 10, 30); // Logo positioned at (10, 10) with width 30

    // Add college name and academic year
    $pdf->SetFont('Times', 'B', 14);
    $pdf->SetXY(40, 15); // Start text after the logo
    $pdf->Cell(0, 10, 'C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY', 0, 1, 'C');
    $pdf->SetFont('Times', '', 12);
    $pdf->Cell(0, 10, "Academic Year 2024 - 2025 (EVEN)", 0, 1, 'C');
    $pdf->Cell(0, 10, "_____________________________________________________", 0, 1, 'C');

    // Add Parent Feedback Form title
    $pdf->SetFont('Times', 'B', 14);
    $pdf->Cell(0, 10, 'Parent Feedback Form', 0, 1, 'C');
    $pdf->Ln(10);

    // Student Details Section
    $pdf->SetFont('Times', '', 12);
    $pdf->Cell(0, 10, "Name of the Student          : " . $student['name'], 0, 1);
    $pdf->Cell(0, 10, "Roll No                      : " . $roll_no, 0, 1);
    $pdf->Cell(0, 10, "Branch                       : " . $department, 0, 1);
    $pdf->Cell(0, 10, "Name of the Parent           : " . $student['father_name'], 0, 1);
    $pdf->Cell(0, 10, "Address                      : " . $student['permanent_addr'], 0, 1);
    $pdf->Ln(5);

    // Phone Numbers Section (Student and Parent)
    $pdf->Cell(0, 10, "Student Phone No             : " . $student['student_phone'], 0, 0);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, "Parent Phone No              : " . $student['parent_phone'], 0, 1);
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
        "Communication from College about Progress of Your Ward",
        'Career Guidance and Placement',
        'How do you rate our college?'
    ];

    $pdf->SetFont('Times', '', 12);
    foreach ($parameters as $index => $parameter) {
        // Row Number Cell
        $pdf->Cell(10, 10, $index + 1, 1, 0, 'C');

        // Parameter Description (MultiCell to handle multiline)
        $x = $pdf->GetX(); // Save current X position
        $y = $pdf->GetY(); // Save current Y position
        $pdf->MultiCell(80, 10, $parameter, 1); // MultiCell for parameter description
        
        // Reset X and Y after MultiCell for subsequent cells
        $pdf->SetXY($x + 80, $y);

        // Empty Rating Cells
        $pdf->Cell(25, 10, '', 1, 0, 'C');
        $pdf->Cell(25, 10, '', 1, 0, 'C');
        $pdf->Cell(25, 10, '', 1, 0, 'C');
        $pdf->Cell(25, 10, '', 1, 0, 'C');
        $pdf->Ln();
    }

    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Suggestions if any:', 0, 1);
    $pdf->Ln(30);

    // Align Signature & Name of Parent to the right
    $pdf->Cell(140); // Move cursor to the right
    $pdf->Cell(0, 10, 'Signature & Name of the Parent: ', 0, 1, 'R');
    $pdf->Ln(10);

    // Clean output buffer and send PDF
    ob_end_clean();
    $pdf->Output();
}
?>
