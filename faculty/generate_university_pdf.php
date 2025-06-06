<?php 
ob_start(); // Add output buffering at the beginning 
ini_set('display_errors', 1); // Set to 1 during debugging
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL); // Enable error reporting during debugging

// Include the FPDF library 
require('../fpdf186/fpdf.php'); 

// Extend FPDF to handle multiline cells better
class PDF extends FPDF {
    // Custom function to draw a table with multiline support
    function ImprovedTable($header, $data, $widths, $heights=array()) {
        // Set default row height if not specified
        $defaultHeight = 9;
        
        // Column headers
        $this->SetFont('Times', 'B', 12);
        for($i=0; $i<count($header); $i++) {
            $this->Cell($widths[$i], 10, $header[$i], 1, 0, 'C');
        }
        $this->Ln();
        
        // Data rows
        $this->SetFont('Times', '', 12);
        
        // Process each data row
        foreach($data as $row) {
            // First calculate the height needed for this row
            $maxHeight = $defaultHeight;
            
            // Check if subject name (at index 2) needs more height
            $nb = $this->NbLines($widths[2], $row[2]);
            $h = $nb * $defaultHeight;
            $maxHeight = max($maxHeight, $h);
            
            // Draw the cells
            $this->Cell($widths[0], $maxHeight, $row[0], 'LR', 0, 'C');
            $this->Cell($widths[1], $maxHeight, $row[1], 'LR', 0, 'C');
            
            // Save position
            $x = $this->GetX();
            $y = $this->GetY();
            
            // Print subject name with multicell
            $this->MultiCell($widths[2], $defaultHeight, $row[2], 'LR', 'L');
            
            // Reset position for remaining cells
            $this->SetXY($x + $widths[2], $y);
            
            // Print remaining cells
            $this->Cell($widths[3], $maxHeight, $row[3], 'LR', 0, 'C');
            $this->Cell($widths[4], $maxHeight, $row[4], 'LR', 0, 'C');
            $this->Ln();
            
            // Draw the bottom line of each row
            $this->Cell(array_sum($widths), 0, '', 'T');
            $this->Ln();
        }
    }
    
    // Compute the number of lines a MultiCell will take
    function NbLines($w, $txt) {
        // Calculate the number of lines a MultiCell of width w will take
        $cw = &$this->CurrentFont['cw'];
        if($w==0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2*$this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb>0 && $s[$nb-1]=="\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i<$nb) {
            $c = $s[$i];
            if($c=="\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep = $i;
            $l += $cw[$c];
            if($l > $wmax) {
                if($sep==-1) {
                    if($i==$j)
                        $i++;
                } else
                    $i = $sep+1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }
}

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
    $pdf = new PDF(); 
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

    // Add a line 
    $pdf->SetY(38); 
    $pdf->Cell(0, 10, '___________________________________________________________________________________', 0, 1, 'C'); 

    // Add "Progress Report" heading 
    $pdf->SetFont('Times', 'B', 16); 
    $pdf->Cell(0, 10, 'UNIVERSITY RESULT', 0, 1, 'C'); 
  
    $pdf->SetFont('Times', 'B', 12); 
    $pdf->Cell(0, 17, 'RESULT FOR '.$exam.' EXAMINATION', 0, 1, 'C'); 

    $pdf->Ln(10); 

    // Add student info 
    $pdf->SetFont('Times', 'B', 12); 
    $pdf->Cell(25, 10, 'Name: ', 0, 0, 'L'); 
    $pdf->SetFont('Times', '', 12); 
    $pdf->Cell(70, 10, $student['name'], 0, 0, 'L'); 

    $pdf->SetFont('Times', 'B', 12); 
    $pdf->Cell(35, 10, 'Register No.: ', 0, 0, 'R'); // Changed to keep on same line
    $pdf->SetFont('Times', '', 12); 
    $pdf->Cell(50, 10, $student['reg_no'], 0, 1, 'L'); // Keep on same line

    $pdf->SetFont('Times', 'B', 12); 
    $pdf->Cell(25, 10, 'Year: ', 0, 0, 'L'); 
    $pdf->SetFont('Times', '', 12); 
    $pdf->Cell(70, 10, $year_roman, 0, 1, 'L'); 

    $pdf->Ln(5); 

    // Define column widths and headers for the table
    $widths = array(20, 40, 80, 25, 25);
    $headers = array('Sem', 'Subject Code', 'Subject Name', 'Grade', 'Result');
        
    // Calculate table width
    $tableWidth = array_sum($widths);

    // Calculate starting X-coordinate to center the table 
    $pageWidth = 210; 
    $startX = ($pageWidth - $tableWidth) / 2; 
    $pdf->SetX($startX);

    // Store the results in an array first and remove duplicates
    $marks_array = [];
    $unique_subjects = [];
    while ($mark = $marks_result->fetch_assoc()) {
        if (!in_array($mark['subject_code'], $unique_subjects)) {
            // Determine result based on grade (U, UA -> RA, otherwise PASS)
            $grade = strtoupper($mark['grade']);
            $result = (in_array($grade, ['U', 'UA'])) ? 'RA' : 'PASS';
            
            $marks_array[] = array(
                $mark['semester'],
                $mark['subject_code'],
                $mark['subject_name'] ?? 'Unknown Subject',
                $mark['grade'],
                $result
            );
            
            $unique_subjects[] = $mark['subject_code'];
        }
    }
    
    // Check if we have marks data
    if (count($marks_array) > 0) {
        // Draw the table with our custom function
        $pdf->ImprovedTable($headers, $marks_array, $widths);
    } else {
        $pdf->SetX($startX);
        $pdf->Cell($tableWidth, 10, 'No results found', 1, 1, 'C');
    }

    // Add a line break before the legends 
    $pdf->Ln(10); 

    // Add legends 
    $pdf->SetFont('Times', 'B', 12); 
    $pdf->Cell(20, 10, 'DEFINITIONS:', 0, 1, 'L'); 
    $pdf->SetFont('Times', '', 12); 

    $pdf->Cell(25, 10, '         RA : Re-Appearance (Failed Exam) ', 0, 1, 'L');
    $pdf->Cell(25, 10, '         UA : Absent ', 0, 1, 'L');

    // Output the PDF to the browser for download 
    $filename = "{$student['roll_no']}-{$exam}.pdf"; 
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
?>
