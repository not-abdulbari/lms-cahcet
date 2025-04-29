<?php
require('../fpdf186/fpdf.php');

// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Prevent output buffering issues
ob_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

include 'db_connect.php'; // Include your database connection file

// Retrieve form data
$roll_no = $_POST['roll_no'] ?? null;
$branch = $_POST['branch'] ?? null;
$year = $_POST['year'] ?? null;
$year_roman = $_POST['year_roman'] ?? null;
$section = $_POST['section'] ?? null;
$batch = $_POST['batch'] ?? null;
$semester = $_POST['semester'] ?? null;
$exam_filter = $_POST['exam'] ?? null; // Updated for filtering by exam type
$faculty_code = $_POST['faculty_code'] ?? null;

// Validate required fields
if (!$roll_no || !$branch || !$year || !$section || !$semester || !$exam_filter) {
    die("All fields are required. Please go back and fill out the form completely.");
}

// Fetch student details
$sql = "SELECT name, reg_no FROM students WHERE roll_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $roll_no);
$stmt->execute();
$student_result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student_result) {
    die("Student details not found. Please check the provided Roll No.");
}

// Retrieve faculty name based on the provided faculty code
$facultyNameQuery = $conn->query("SELECT faculty_name FROM faculty WHERE faculty_code = '$faculty_code'");
$facultyNameRow = $facultyNameQuery->fetch_assoc();
$facultyName = $facultyNameRow['faculty_name'] ?? "Unknown Faculty";

// Fetch attendance data for all entries related to the roll number and semester
$attendance_query = "SELECT attendance_entry, percentage FROM semester_attendance WHERE roll_no = ? AND semester = ?";
$stmt = $conn->prepare($attendance_query);
if (!$stmt) {
    die("Error preparing attendance query: " . $conn->error);
}
$stmt->bind_param("si", $roll_no, $semester);
$stmt->execute();
$attendance_result = $stmt->get_result();

// Initialize default attendance data
$attendance_data = [
    'Entry 1' => '', // Default value if an entry is missing
    'Entry 2' => '',
    'Entry 3' => '',
    'Entry 4' => ''
];

// Process fetched attendance data
if ($attendance_result->num_rows > 0) {
    while ($row = $attendance_result->fetch_assoc()) {
        $attendance_entry = $row['attendance_entry'];
        $percentage = $row['percentage'];

        // Validate and format percentage
        if (!empty($percentage) || $percentage === '0') {
            $attendance_data[$attendance_entry] = $percentage . '%'; // Add "%" sign
        } else {
            $attendance_data[$attendance_entry] = 'Error'; // Mark as "Error" if percentage is missing
        }
    }
}
$stmt->close();

// Calculate and display the total percentage (if applicable)
$total_percentage = 0;
$valid_entries = 0;
foreach ($attendance_data as $entry => $percentage) {
    if ($percentage !== 'Error' && $percentage !== 'N/A') {
        $valid_entries++;
        $total_percentage += (float) str_replace('%', '', $percentage); // Remove "%" sign and convert to float
    }
}
$total_percentage = $valid_entries > 0 ? round($total_percentage / $valid_entries, 2) . '%' : 'Error';

// Fetch marks dynamically based on the selected exam type
$marks_query = "SELECT m.subject AS subject_code, m.exam, m.marks, s.subject_name
                FROM marks m
                LEFT JOIN subjects s ON m.subject = s.subject_code
                WHERE m.roll_no = ? AND m.branch = ? AND m.year = ? AND m.semester = ? AND m.section = ? AND m.exam IN (";

if ($exam_filter === "CAT1") {
    $marks_query .= "'CAT1')";
} elseif ($exam_filter === "CAT2") {
    $marks_query .= "'CAT1', 'CAT2')";
} elseif (strtolower($exam_filter) === "model") { // Case-insensitive comparison for "Model"
    $marks_query .= "'CAT1', 'CAT2', 'Model')";
} else {
    die("Invalid exam filter selected.");
}

$stmt = $conn->prepare($marks_query);
if (!$stmt) {
    die("Error preparing marks query: " . $conn->error);
}
$stmt->bind_param("sssis", $roll_no, $branch, $year, $semester, $section);
$stmt->execute();
$marks_result = $stmt->get_result();

// Check if marks are found
$marks_data = [];
if ($marks_result->num_rows > 0) {
    while ($row = $marks_result->fetch_assoc()) {
        $marks_data[] = $row;
    }
} else {
    die("No marks data found for the given criteria. Please ensure that the student and subject details are correct.");
}
$stmt->close();

// Group marks by subject code and exam type
$grouped_marks = [];
foreach ($marks_data as $mark) {
    $subject_code = $mark['subject_code'];
    $subject_name = $mark['subject_name'];
    $exam = strtoupper($mark['exam']); // Ensure consistent case for exam types
    $grouped_marks[$subject_code]['NAME'] = $subject_name; // Store subject name
    $grouped_marks[$subject_code][$exam] = $mark['marks'];
}

// Group marks by subject code and exam type
$grouped_marks = [];
foreach ($marks_data as $mark) {
    $subject_code = $mark['subject_code'];
    $subject_name = $mark['subject_name'];
    $exam = strtoupper($mark['exam']); // Ensure consistent case for exam types
    $grouped_marks[$subject_code]['NAME'] = $subject_name; // Store subject name
    $grouped_marks[$subject_code][$exam] = $mark['marks'];
}

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Times', 'B', 12);

// Add logo
$logo_path = '../assets/24349bb44aaa1a8c.jpg';
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 10, 10, 20);
}

// Add header
$pdf->Cell(0, 10, 'C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY', 0, 1, 'C');
$pdf->SetFont('Times', '', 10);
$pdf->Cell(0, 5, 'MELVISHARAM - 632509', 0, 1, 'C');

// Add department info
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(0, 5, 'DEPARTMENT OF ' . strtoupper($branch), 0, 1, 'C');

// Add form title
$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 10, 'COUNSELLING FORM', 0, 1, 'C');
$pdf->SetFont('Times', '', 10);

// Left Column
$pdf->SetY(50);
$pdf->SetX(10);
$pdf->Cell(60, 6, 'Name of the Counsellor', 0, 0);
$pdf->Cell(4, 6, ':', 0, 0);
$pdf->Cell(80, 6, $facultyName, 0, 1);

$pdf->Cell(60, 6, 'Name of the Student', 0, 0);
$pdf->Cell(4, 6, ':', 0, 0);
$pdf->Cell(80, 6, $student_result['name'], 0, 1);

$pdf->Cell(60, 6, 'Register Number', 0, 0);
$pdf->Cell(4, 6, ':', 0, 0);
$pdf->Cell(80, 6, $student_result['reg_no'], 0, 1);

// Right Column
$pdf->SetY(50);
$pdf->SetX(150);
$pdf->Cell(30, 6, 'Year / Sem / Sec', 0, 0);
$pdf->Cell(4, 6, ':', 0, 0);
$pdf->Cell(80, 6, $year_roman . ' / ' . $semester . ' / ' . $section, 0, 1);

$pdf->SetX(150);
$pdf->Cell(30, 6, 'CAY', 0, 0);
$pdf->Cell(4, 6, ':', 0, 0);
$pdf->Cell(80, 6, '2024-2025', 0, 1);//Change this in every acadmic year

$pdf->SetX(150);
$pdf->Cell(30, 6, 'Batch', 0, 0);
$pdf->Cell(4, 6, ':', 0, 0);
$pdf->Cell(80, 6, $batch, 0, 1);

// Reason for Counseling
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(0, 6, '1. Reason for Counseling:', 0, 1);
$pdf->SetFont('Times', '', 10);
$pdf->Cell(10, 6, '[ ]', 0, 0);
$pdf->Cell(0, 6, 'Lack of Attendance / Late Coming', 0, 1);
$pdf->Cell(10, 6, '[ ]', 0, 0);
$pdf->Cell(0, 6, 'Poor Performance in CAT / Model Examination / University Examination', 0, 1);
$pdf->Cell(10, 6, '[ ]', 0, 0);
$pdf->Cell(0, 6, 'Indiscipline', 0, 1);
$pdf->Cell(10, 6, '[ ]', 0, 0);
$pdf->Cell(0, 6, 'Others Specify', 0, 1);

// Academic Performance
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(0, 6, '2. Academic Performance:', 0, 1);
$pdf->SetFont('Times', '', 10);
$pdf->Cell(50, 6, 'Attendance Percentage:', 0, 1);

// Generate the attendance table dynamically
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(30, 6, 'Entry 1', 1, 0, 'C');
$pdf->Cell(30, 6, 'Entry 2', 1, 0, 'C');
$pdf->Cell(30, 6, 'Entry 3', 1, 0, 'C');
$pdf->Cell(30, 6, 'Entry 4', 1, 0, 'C');
$pdf->Cell(30, 6, 'Total', 1, 1, 'C');

$pdf->SetFont('Times', '', 10);
$pdf->Cell(30, 6, isset($attendance_data['Entry 1']) ? $attendance_data['Entry 1']  : 'N/A', 1, 0, 'C');
$pdf->Cell(30, 6, isset($attendance_data['Entry 2']) ? $attendance_data['Entry 2']  : 'N/A', 1, 0, 'C');
$pdf->Cell(30, 6, isset($attendance_data['Entry 3']) ? $attendance_data['Entry 3']  : 'N/A', 1, 0, 'C');
$pdf->Cell(30, 6, isset($attendance_data['Entry 4']) ? $attendance_data['Entry 4']  : 'N/A', 1, 0, 'C');
$pdf->Cell(30, 6, $total_percentage !== 'N/A' ? $total_percentage : 'N/A', 1, 1, 'C');

// Exam Marks Table
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(0, 6, '3. CAT / Model Marks / University Examination:', 0, 1);
$pdf->SetFont('Times', '', 10);
$pdf->Cell(10, 6, 'S.No', 1, 0, 'C');
$pdf->Cell(80, 6, 'Course code / Course Name', 1, 0, 'C');
$pdf->Cell(20, 6, 'CAT 1', 1, 0, 'C');
$pdf->Cell(20, 6, 'CAT 2', 1, 0, 'C');
$pdf->Cell(20, 6, 'Model', 1, 0, 'C');
$pdf->Cell(20, 6, 'University', 1, 1, 'C');

// Add each subject's marks to the PDF
$s_no = 1;
$pdf->SetFont('Times', '', 10);
foreach ($grouped_marks as $subject_code => $subject_data) {
    // Calculate maximum height for the row
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Render the course name MultiCell first to calculate its height
    $pdf->SetXY($x + 10, $y); // Skip the S.No column position
    $pdf->MultiCell(80, 6, $subject_code . ' / ' . $subject_data['NAME'], 1, 'L');
    $rowHeight = $pdf->GetY() - $y;

    // Reset X and Y for the remaining cells
    $pdf->SetXY($x, $y);

    // Render other cells with the calculated row height
    $pdf->Cell(10, $rowHeight, $s_no, 1, 0, 'C'); // S.No
    $pdf->SetXY($x + 90, $y); // Adjust X to skip over the MultiCell
    $pdf->Cell(20, $rowHeight, isset($subject_data['CAT1']) ? $subject_data['CAT1'] : '', 1, 0, 'C');
    $pdf->Cell(20, $rowHeight, isset($subject_data['CAT2']) ? $subject_data['CAT2'] : '', 1, 0, 'C');
    $pdf->Cell(20, $rowHeight, isset($subject_data['MODEL']) ? $subject_data['MODEL'] : '', 1, 0, 'C');
    $pdf->Cell(20, $rowHeight, isset($subject_data['UNIVERSITY']) ? $subject_data['UNIVERSITY'] : '', 1, 1, 'C');

    $s_no++;
}

// Total No. of Arrears
$pdf->SetFont('Times', '', 10);
$pdf->Cell(60, 6, 'Total No. of Arrears', 0, 0);
$pdf->Cell(10, 6, ':', 0, 0);
$pdf->Cell(120, 6, '', 0, 1);

// Specified Reason
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(0, 6, '4. Specified Reason:', 0, 1);
$pdf->SetFont('Times', '', 10);
$pdf->Cell(60, 6, 'Student', 0, 0);
$pdf->Cell(5, 6, ':', 0, 0); // Reduced width from 10 to 5
$pdf->Cell(125, 6, $student_result['name'], 0, 1); // Adjusted width to balance the total row width

$pdf->Cell(60, 6, 'Parents / Guardian Name', 0, 0);
$pdf->Cell(10, 6, ':', 0, 0);
$pdf->Cell(120, 6, '', 0, 1);

$pdf->Cell(60, 6, 'Parents / Guardian Occupation', 0, 0);
$pdf->Cell(10, 6, ':', 0, 0);
$pdf->Cell(120, 6, '', 0, 1);

$pdf->Cell(60, 6, 'Parents / Guardian Comment', 0, 0);
$pdf->Cell(10, 6, ':', 0, 0);
$pdf->Cell(120, 6, '', 0, 1);

// Comments Sections
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(0, 6, '5. Counsellor Comment:', 0, 1);

$pdf->Cell(0, 6, '6. Student Declaration:', 0, 1);

$pdf->Cell(0, 6, '7. Class Advisor Comment:', 0, 1);

// Contact Numbers & Signatures
$pdf->Cell(50, 6, '8. Contact Numbers:', 0, 1);
$pdf->Cell(60, 6, '1. Student', 0, 0);
$pdf->Cell(10, 6, ':', 0, 0);
$pdf->Cell(50, 6, '', 0, 0);
$pdf->Cell(40, 6, 'Signature', 0, 0);
$pdf->Cell(10, 6, ':', 0, 0);
$pdf->Cell(50, 6, '', 0, 1);

$pdf->Cell(60, 6, '2. Parents / Guardian', 0, 0);
$pdf->Cell(10, 6, ':', 0, 0);
$pdf->Cell(50, 6, '', 0, 0);
$pdf->Cell(40, 6, 'Signature', 0, 0);
$pdf->Cell(10, 6, ':', 0, 0);
$pdf->Cell(50, 6, '', 0, 1);
$pdf->Ln(25);

// Footer
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(60, 6, 'Counsellor', 0, 0, 'C');
$pdf->Cell(60, 6, 'Class Advisor', 0, 0, 'C');
$pdf->Cell(60, 6, 'HOD', 0, 1, 'C');

// Output the PDF to the browser for download
$filename = "{$roll_no}-Counseling_Form-{$semester}.pdf";
ob_clean(); // Clean output buffer to avoid invalid characters
$pdf->Output('D', $filename);
exit();
?>