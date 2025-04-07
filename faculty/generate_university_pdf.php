<?php 
ob_start(); // Add output buffering at the beginning 
ini_set('display_errors', 1); // Set to 1 during debugging
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL); // Enable error reporting during debugging

// Include the FPDF library 
require('../fpdf186/fpdf.php'); 

// Include your database connection file 
include 'db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $roll_no = $_POST['roll_no']; 
    $branch = $_POST['branch']; 
    $year = $_POST['year']; 
    $section = $_POST['section']; 
    
    // These might be missing in your form submission
    $semester = isset($_POST['semester']) ? $_POST['semester'] : '';
    $year_roman = isset($_POST['year_roman']) ? $_POST['year_roman'] : convertToRoman($year);
    $exam = isset($_POST['exam']) ? $_POST['exam'] : 'University'; // Default to University if not specified

    // Fetch student info 
    $sql = "SELECT * FROM students WHERE roll_no = ?"; 
    $stmt = $conn->prepare($sql); 
    $stmt->bind_param("s", $roll_no); 
    $stmt->execute(); 
    $student = $stmt->get_result()->fetch_assoc(); 
    $stmt->close(); 

    if (!$student) {
        die("Student not found with roll number: $roll_no");
    }

    // Get semester if not provided
    if (empty($semester)) {
        // You might need to adjust this query based on your database structure
        $sem_sql = "SELECT semester FROM university_results WHERE reg_no = ? ORDER BY semester DESC LIMIT 1";
        $sem_stmt = $conn->prepare($sem_sql);
        $sem_stmt->bind_param("s", $student['reg_no']);
        $sem_stmt->execute();
        $sem_result = $sem_stmt->get_result()->fetch_assoc();
        $semester = $sem_result ? $sem_result['semester'] : '';
        $sem_stmt->close();
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

    $department = isset($departmentNames[$branch]) ? $departmentNames[$branch] : "Department of $branch"; 

    // Fetch student marks from university_results based on reg_no
    // Remove the exam condition if you want to show all results regardless of exam type
    $marks_sql = "SELECT ur.semester, ur.subject_code, ur.grade, s.subject_name 
            FROM university_results ur
            LEFT JOIN subjects s ON ur.subject_code = s.subject_code 
            WHERE ur.reg_no = ?";
    $marks_stmt = $conn->prepare($marks_sql); 
    $marks_stmt->bind_param("s", $student['reg_no']); 
    $marks_stmt->execute(); 
    $marks_result = $marks_stmt->get_result(); 
    
    // Check if marks data exists
    if ($marks_result->num_rows == 0) {
        echo "No marks data found for student with registration number: " . $student['reg_no'];
        exit;
    }

    // Fetch average attendance 
    $attendance_sql = "SELECT AVG(percentage) AS average_attendance FROM semester_attendance WHERE roll_no = ? AND semester = ?"; 
    $attendance_stmt = $conn->prepare($attendance_sql); 
    $attendance_stmt->bind_param("ss", $roll_no, $semester); 
    $attendance_stmt->execute(); 
    $attendance_result = $attendance_stmt->get_result()->fetch_assoc(); 
    $average_attendance = isset($attendance_result['average_attendance']) ? $attendance_result['average_attendance'] : 0; 
    $attendance_stmt->close(); 

    // Create a new PDF instance 
    $pdf = new FPDF(); 
    $pdf->AddPage(); 

    // Add college logo 
    if (file_exists('../assets/24349bb44aaa1a8c.jpg')) {
        $pdf->Image('../assets/24349bb44aaa1a8c.jpg', 10, 10, 30);
    }

    // Set font for the header 
    $pdf->SetFont('Times', 'B', 14); 
    $pdf->SetXY(40, 15); 
    $pdf->Cell(0, 10, 'C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY', 0, 1, 'C'); 

    // Set font for the place 
    $pdf->SetFont('Times', 'B', 14); 
    $pdf->SetXY(40, 23); 
    $pdf->Cell(0, 10, 'MELVISHARAM - 632509', 0, 1, 'C'); 

    // Set font for the department 
    $pdf->SetFont('Times', '', 12); 
    $pdf->SetXY(40, 30); 
    $pdf->Cell(0, 10, $department, 0, 1, 'C'); 

    $pdf->SetFont('Times', '', 12); 
    $pdf->SetXY(40, 38); 

    // Add a line 
    $pdf->SetY(40); 
    $pdf->Cell(0, 10, '_____________________________________________________', 0, 1, 'C'); 

    // Add "Progress Report" heading 
    $pdf->SetFont('Times', 'B', 16); 
    $pdf->Cell(0, 10, 'RESULT FOR', 0, 1, 'C'); 

    // Add exam type (CAT1/CAT2/Model Exam) 
    $pdf->SetFont('Times', 'B', 14); 
    $pdf->Cell(0, 10, $exam . ' Exam', 0, 1, 'C'); 

    $pdf->Ln(10); 

    // Add student info 
    $pdf->SetFont('Times', 'B', 12); 
    $pdf->Cell(25, 10, 'Name: ', 0, 0, 'L'); 
    $pdf->SetFont('Times', '', 12); 
    $pdf->Cell(70, 10, $student['name'], 0, 0, 'L'); 

    $pdf->SetFont('Times', 'B', 12); 
    $pdf->Cell(30, 10, 'Reg No.: ', 0, 0, 'R'); // Changed to keep on same line
    $pdf->SetFont('Times', '', 12); 
    $pdf->Cell(50, 10, $student['reg_no'], 0, 1, 'L'); // Keep on same line

    $pdf->SetFont('Times', 'B', 12); 
    $pdf->Cell(25, 10, 'Year: ', 0, 0, 'L'); 
    $pdf->SetFont('Times', '', 12); 
    $pdf->Cell(70, 10, $year_roman, 0, 1, 'L'); 

    $pdf->Ln(5); 

    // Calculate table width (adjusted for new columns)
    $tableWidth = 20 + 45 + 75 + 25 + 25; // Adjusted widths for columns

    // Calculate starting X-coordinate to center the table 
    $pageWidth = 210; 
    $startX = ($pageWidth - $tableWidth) / 2; 

    // Set the X-coordinate for the table 
    $pdf->SetX($startX); 

    // Create the table header 
    $pdf->SetFont('Times', 'B', 12); 
    $pdf->Cell(20, 10, 'Sem', 1, 0, 'C'); 
    $pdf->Cell(45, 10, 'Subject Code', 1, 0, 'C'); 
    $pdf->Cell(75, 10, 'Subject Name', 1, 0, 'C'); 
    $pdf->Cell(25, 10, 'Grade', 1, 0, 'C'); 
    $pdf->Cell(25, 10, 'Result', 1, 1, 'C'); 

    // Fetch and add marks data 
    $pdf->SetFont('Times', '', 12); 

    // Store the results in an array first and remove duplicates
    $marks_array = [];
    $unique_subjects = [];
    while ($mark = $marks_result->fetch_assoc()) {
        if (!in_array($mark['subject_code'], $unique_subjects)) {
            $marks_array[] = $mark;
            $unique_subjects[] = $mark['subject_code'];
        }
    }
    
    // Check if we have marks data
    if (count($marks_array) > 0) {
        foreach ($marks_array as $mark) { 
            // Determine result based on grade (U, UA -> RA, otherwise PASS)
            $grade = strtoupper($mark['grade']);
            $result = (in_array($grade, ['U', 'UA'])) ? 'RA' : 'PASS';

            $pdf->SetX($startX);

            // Temporarily store current X (needed for proper placement later)
            $x = $pdf->GetX();

            // Calculate the number of lines needed for the subject name
            $nb = $pdf->NbLines(75, $mark['subject_name'] ?? 'Unknown Subject');

            // Calculate the maximum cell height
            $cellHeight = max(10, 10 * $nb);

            // 1. Draw Sem cell
            $pdf->MultiCell(20, $cellHeight / $nb, $mark['semester'], 1, 'C');

            // Reset Y to top of this row, move X to next cell
            $pdf->SetXY($x + 20, $pdf->GetY() - $cellHeight);

            // 2. Draw Subject Code cell
            $pdf->MultiCell(45, $cellHeight / $nb, $mark['subject_code'], 1, 'C');

            // Reset Y again
            $pdf->SetXY($x + 65, $pdf->GetY() - $cellHeight);

            // 3. Subject Name
            $pdf->MultiCell(75, $cellHeight / $nb, $mark['subject_name'] ?? 'Unknown Subject', 1, 'L');

            // Reset Y again
            $pdf->SetXY($x + 140, $pdf->GetY() - $cellHeight);

            // 4. Grade
            $pdf->Cell(25, $cellHeight, $mark['grade'], 1, 0, 'C');

            // 5. Result
            $pdf->Cell(25, $cellHeight, $result, 1, 1, 'C');
        }
    } else {
        $pdf->SetX($startX);
        $pdf->Cell(190, 10, 'No results found', 1, 1, 'C');
    }

    // Add a line break before the legends 
    $pdf->Ln(10); 

    // Add legends 
    $pdf->SetFont('Times', 'B', 12); 
    $pdf->Cell(20, 10, 'DEFINITIONS:', 0, 1, 'L'); 
    $pdf->SetFont('Times', '', 12); 
    $pdf->Cell(25, 10, 'AB - Absent', 0, 1, 'L'); 
    $pdf->Cell(25, 10, '(U) - Fail', 0, 1, 'L'); 
    $pdf->Cell(25, 10, 'RA - Reappear', 0, 1, 'L');
    $pdf->Cell(25, 10, 'UA - Unauthorized Absent', 0, 1, 'L');

    // Set the font for the label 
    $pdf->SetFont('Times', 'B', 12); 
    $pdf->Cell(50, 10, 'Average Attendance:', 0, 0, 'L'); 

    // Set the font for the value 
    $pdf->SetFont('Times', '', 12); 
    $pdf->Cell(0, 10, round($average_attendance, 2) . '%', 0, 1, 'L'); 

    // Output the PDF to the browser for download 
    $filename = "{$student['roll_no']}-{$exam}-{$semester}.pdf"; 
    ob_end_clean(); // Clean the output buffer
    $pdf->Output($filename, 'D'); 
} else {
    echo "Direct access not allowed.";
}

// Function to convert numeric year to Roman numerals
function convertToRoman($year) {
    $roman = ['I', 'II', 'III', 'IV', 'V'];
    $numeric = (int)$year;
    return isset($roman[$numeric-1]) ? $roman[$numeric-1] : $year;
}

$conn->close();

class PDF extends FPDF {
    function NbLines($w, $txt) {
        // Calculates the number of lines a MultiCell of width w will take
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }
}

// Use the custom PDF class
$pdf = new PDF();
?>
