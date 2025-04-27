<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

// Include required libraries
require_once(__DIR__ . '/../fpdf186/fpdf.php');

// Include database connection
require_once(__DIR__ . '/db_connect.php');

// Ensure database connection is established
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Validate GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['roll_no'])) {
    $roll_no = $_GET['roll_no'];

    // Fetch student details
    $sql = "SELECT s.name, si.father_name, si.permanent_addr, si.student_phone, si.parent_phone, s.branch 
            FROM students s 
            JOIN student_information si ON s.roll_no = si.roll_no 
            WHERE s.roll_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $roll_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
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

    $department = $departmentNames[$student['branch']] ?? "Department of " . $student['branch'];

    // Create PDF instance
    $pdf = new FPDF();
    $pdf->AddPage();

    // Add college logo
    $logoPath = __DIR__ . '/../assets/24349bb44aaa1a8c.jpg';
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 10, 30);
    } else {
        die("Logo file not found.");
    }

    // Header Section
    $pdf->SetFont('Times', 'B', 14);
    $pdf->Cell(14, 10, 'C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY', 0, 1, 'C');
    $pdf->SetFont('Times', '', 12);
    $pdf->Cell(0, 10, "Academic Year 2024 - 2025 (EVEN)", 0, 1, 'C');
    $pdf->Cell(0, 10, str_repeat("_", 50), 0, 1, 'C');

    // Parent Feedback Form Title
    $pdf->SetFont('Times', 'B', 14);
    $pdf->Cell(0, 10, 'Parent Feedback Form', 0, 1, 'C');
    $pdf->Ln(10);

    // Student Information
    $pdf->SetFont('Times', '', 12);
    $pdf->Cell(0, 10, "Name of the Student: " . $student['name'], 0, 1);
    $pdf->Cell(0, 10, "Roll No: " . $roll_no, 0, 1);
    $pdf->Cell(0, 10, "Branch: " . $department, 0, 1);
    $pdf->Cell(0, 10, "Name of the Parent: " . $student['father_name'], 0, 1);
    $pdf->Cell(0, 10, "Address: " . $student['permanent_addr'], 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, "Student Phone No: " . $student['student_phone'], 0, 1);
    $pdf->Cell(0, 10, "Parent Phone No: " . $student['parent_phone'], 0, 1);
    $pdf->Ln(10);

    // Feedback Table
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(10, 10, 'S.No', 1);
    $pdf->Cell(120, 10, 'Parameter', 1);
    $pdf->Cell(22, 10, 'Excellent', 1);
    $pdf->Cell(22, 10, 'Very Good', 1);
    $pdf->Cell(15, 10, 'Good', 1);
    $pdf->Cell(15, 10, 'Average', 1);
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
        $pdf->Cell(120, 10, $parameter, 1);
        $pdf->Cell(20, 10, '', 1);
        $pdf->Cell(25, 10, '', 1);
        $pdf->Cell(15, 10, '', 1);
        $pdf->Cell(15, 10, '', 1);
        $pdf->Ln();
    }

    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Suggestions if any:', 0, 1);
    $pdf->Ln(30);

    // Signature section
    $pdf->Cell(140);
    $pdf->Cell(0, 10, 'Signature & Name of the Parent: ', 0, 1, 'R');
    $pdf->Ln(10);

    // Output PDF
    ob_end_clean();
    $filename = $roll_no . "-PARENT-FEEDBACK.pdf";
    $pdf->Output('D', $filename);
} else {
    die("Invalid request.");
}
?>
