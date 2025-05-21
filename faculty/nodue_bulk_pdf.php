<?php
// Start output buffering at the very top of the file
ob_start();

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require('../fpdf186/fpdf.php'); 
include 'db_connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nodue_form.php');
    exit;
}

// Get the form data
$branch = $_POST['branch'] ?? '';
$year = $_POST['year'] ?? '';
$year_roman = $_POST['year_roman'] ?? '';
$section = $_POST['section'] ?? '';
$semester = $_POST['semester'] ?? '';
$selected_subjects = $_POST['selected_subjects'] ?? [];
$faculty = $_POST['faculty'] ?? [];
$subject_names = $_POST['subject_names'] ?? [];
$faculty_names = $_POST['faculty_names'] ?? [];

// Get selected students
$students = $_POST['students'] ?? [];

// Validate that required fields are present
if (empty($branch) || empty($year) || empty($year_roman) || empty($section) || 
    empty($semester) || empty($selected_subjects)) {
    echo '<div class="alert alert-danger">Missing required form data. Please go back and complete the form.</div>';
    exit;
}

// Check if any students are selected
if (empty($students)) {
    echo '<div class="alert alert-danger">No students selected. Please go back and select at least one student.</div>';
    exit;
}

// Create custom PDF class with better text wrapping functionality
class NODUEPDF extends FPDF {
    protected $widths;
    protected $aligns;
    
    function Header() {
        // Empty header for clean design
    }
    
    function Footer() {
        // Empty footer for clean design
    }
    
    // Set the array of column widths
    function SetWidths($w) {
        $this->widths = $w;
    }
    
    // Set the array of column alignments
    function SetAligns($a) {
        $this->aligns = $a;
    }
    
    // Improved Row function for table with word wrap
    function Row($data, $heights = 5) {
        // Calculate the height of the row
        $nb = 0;
        for($i=0; $i<count($data); $i++) {
            // Get max number of lines
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        // Reduce the total height by calculating with a smaller multiplier
        $h = $heights * max(1, $nb * 0.85);
        
        // Issue a page break first if needed
        $this->CheckPageBreak($h);
        
        // Draw the cells of the row
        for($i=0; $i<count($data); $i++) {
            $w = $this->widths[$i];
            
            // Save the current position
            $x = $this->GetX();
            $y = $this->GetY();
            
            // Set the alignment
            $align = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            
            // Draw the border
            $this->Rect($x, $y, $w, $h);
            
            // Print the text with reduced line height
            $this->MultiCell($w, $heights * 0.6, $data[$i], 0, $align);
            
            // Put the position to the right of the cell
            $this->SetXY($x + $w, $y);
        }
        
        // Go to the next line
        $this->Ln($h);
    }
    
    function CheckPageBreak($h) {
        // If the height h would cause an overflow, add a new page
        if($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }
    
    function NbLines($w, $txt) {
        // Compute the number of lines a MultiCell of width w will take
        if($w==0) return 1;
        
        $cw = &$this->CurrentFont['cw'];
        if($w==0)
            $w = $this->w - $this->rMargin - $this->x;
        
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        
        if($nb == 0)
            return 1;
            
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        
        while($i < $nb) {
            $c = $s[$i];
            if($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c == ' ')
                $sep = $i;
            
            $l += $cw[$c] ?? 0;
            
            if($l > $wmax) {
                if($sep == -1) {
                    if($i == $j)
                        $i++;
                }
                else
                    $i = $sep + 1;
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

// Initialize the PDF
$pdf = new NODUEPDF();

// For multiple students, we need to get their details
$placeholders = str_repeat('?,', count($students) - 1) . '?';
$stmt = $conn->prepare("SELECT roll_no, reg_no, name AS student_name FROM students WHERE roll_no IN ($placeholders) ORDER BY roll_no ASC");

// Bind parameters dynamically
$types = str_repeat('s', count($students));
$stmt->bind_param($types, ...$students);
$stmt->execute();
$result = $stmt->get_result();

// Process each student
$is_first_page = true;
while ($row = $result->fetch_assoc()) {
    $roll_no = $row['roll_no'];
    $reg_no = $row['reg_no'];
    $student_name = $row['student_name'];
    
    // Add a new page for each student (except the first one)
    if ($is_first_page) {
        $pdf->AddPage();
        $is_first_page = false;
    } else {
        $pdf->AddPage();
    }
    
    // Set margins
    $pdf->SetMargins(10, 10, 10);
    
    // Get attendance percentage for this student and semester
    $attendance_percentage = calculateAttendancePercentage($conn, $roll_no, $semester);
    
    // Sort subjects into theory and practical
    $theory_subjects = [];
    $practical_subjects = [];
    
    foreach ($selected_subjects as $subject_code) {
        // Extract the base subject code (remove _theory or _laboratory)
        $parts = explode('_', $subject_code);
        $base_code = $parts[0];
        $type = $parts[1] ?? '';
        
        // Get subject name from POST data if available
        $subject_name = $subject_names[$subject_code] ?? '';
        
        // If not in POST data, get from database
        if (empty($subject_name)) {
            $subject_sql = "SELECT subject_name FROM subjects WHERE subject_code = ?";
            $sub_stmt = $conn->prepare($subject_sql);
            $sub_stmt->bind_param("s", $base_code);
            $sub_stmt->execute();
            $subject_result = $sub_stmt->get_result();
            
            if ($subject_result->num_rows > 0) {
                $subject_data = $subject_result->fetch_assoc();
                $subject_name = $subject_data['subject_name'];
            }
            $sub_stmt->close();
        }
        
        // Get faculty name from POST data if available
        $faculty_name = $faculty_names[$subject_code] ?? '';
        
        // If not in POST data, get from database
        if (empty($faculty_name)) {
            $faculty_code = $faculty[$subject_code] ?? '';
            
            if (!empty($faculty_code)) {
                $faculty_sql = "SELECT faculty_name FROM faculty WHERE faculty_code = ?";
                $faculty_stmt = $conn->prepare($faculty_sql);
                $faculty_stmt->bind_param("s", $faculty_code);
                $faculty_stmt->execute();
                $faculty_result = $faculty_stmt->get_result();
                
                if ($faculty_result->num_rows > 0) {
                    $faculty_data = $faculty_result->fetch_assoc();
                    $faculty_name = $faculty_data['faculty_name'];
                }
                $faculty_stmt->close();
            }
        }
        
        $subject_info = [
            'code' => $base_code,
            'name' => $subject_name,
            'faculty' => $faculty_name
        ];
        
        if ($type == 'theory') {
            $theory_subjects[] = $subject_info;
        } else if ($type == 'laboratory') {
            $practical_subjects[] = $subject_info;
        }
    }
    
    // HEADER SECTION
    $pdf->SetFont('Times', 'B', 13);
    $pdf->Cell(0, 8, 'C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY, MELVISHARAM', 0, 1, 'C');
    $pdf->Cell(0, 6, 'DEPARTMENT OF ' . strtoupper($branch), 0, 1, 'C');

    // Determine if the semester is odd or even
    $semester_type = ($semester % 2 === 0) ? 'EVEN' : 'ODD';
    $pdf->Cell(0, 6, 'HALL TICKET NO-DUE FORM FOR ACADEMIC YEAR: 2024-2025 (' . $semester_type . ')', 0, 1, 'C');
    
    $pdf->Ln(5);
    
    // STUDENT INFO SECTION
    $pdf->SetFont('Times', 'B', 10);
    $pdf->Cell(40, 6, 'NAME', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(90, 6, $student_name, 0, 0);
    $pdf->Cell(30, 6, 'YEAR / SEM :', 0, 0);
    $pdf->Cell(30, 6, $year_roman . ' / ' . $semester . ' - "' . $section . '"', 0, 1);
    
    $pdf->Cell(40, 6, 'REGISTER NUMBER', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(90, 6, $reg_no, 0, 1);
    
    $pdf->Cell(40, 6, 'ROLL NUMBER', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(90, 6, $roll_no, 0, 0);
    $pdf->Cell(20, 6, 'BATCH', 0, 0);
    $pdf->Cell(5, 6, ':', 0, 0);
    $pdf->Cell(30, 6, $year, 0, 1);
    
    $pdf->Ln(5);
    
    // Row height for multi-line content
    $row_height = 7.2;
    
    // THEORY SUBJECTS TABLE
    if (!empty($theory_subjects)) {
        // Set column widths for theory table
        $pdf->SetWidths([10, 75, 35, 15, 15, 15, 25]);
        $pdf->SetAligns(['C', 'L', 'L', 'C', 'C', 'C', 'C']);
        
        // Theory subjects table header
        $pdf->SetFont('Times', 'B', 10);
        
        // Draw the header row with special handling for marks column
        $pdf->Cell(10, 9, 'S.No.', 1, 0, 'C');
        $pdf->Cell(75, 9, 'Theory Subjects', 1, 0, 'C');
        $pdf->Cell(35, 9, 'Staff In-charge', 1, 0, 'C');
        
        // Header for marks columns
        $pdf->Cell(45, 4.5, 'Marks', 1, 0, 'C');
        $pdf->Cell(25, 9, 'Signature', 1, 1, 'C');
        
        $pdf->SetXY(130, $pdf->GetY() - 4.5);
        $pdf->Cell(15, 4.5, 'CAT I', 1, 0, 'C');
        $pdf->Cell(15, 4.5, 'CAT II', 1, 0, 'C');
        $pdf->Cell(15, 4.5, 'Model', 1, 1, 'C');
        
        // Theory subjects data
        $pdf->SetFont('Times', '', 9);
        
        for ($i = 0; $i < count($theory_subjects); $i++) {
            $subject = $theory_subjects[$i];
            $theory_count = $i + 1;
            $code = $subject['code'];
            
            // Get marks for this subject and student
            $cat1 = getMarks($conn, $roll_no, $code, 'CAT1');
            $cat2 = getMarks($conn, $roll_no, $code, 'CAT2');
            $model = getMarks($conn, $roll_no, $code, 'MODEL');
            
            // Combine code and name
            $subject_text = $code . ' - ' . $subject['name'];
            
            // Use the improved Row function to handle multi-line content
            $pdf->Row([
                $theory_count,
                $subject_text,
                $subject['faculty'],
                $cat1,
                $cat2,
                $model,
                ''
            ], $row_height);
        }
    }
    
    // Add a small gap between tables
    $pdf->Ln(5);
    
    // PRACTICAL SUBJECTS TABLE
    if (!empty($practical_subjects)) {
        // Set column widths for practical table
        $pdf->SetWidths([10, 75, 45, 35, 25]);
        $pdf->SetAligns(['C', 'L', 'L', 'C', 'C']);
        
        // Practical Subjects table header
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(10, 9, 'S.No.', 1, 0, 'C');
        $pdf->Cell(75, 9, 'Practical Subjects', 1, 0, 'C');
        $pdf->Cell(45, 9, 'Staff In-charge', 1, 0, 'C');
        
        // Empty column in place of marks for practical subjects
        $pdf->Cell(35, 9, '', 1, 0, 'C');
        $pdf->Cell(25, 9, 'Signature', 1, 1, 'C');
        
        // Practical subjects data
        $pdf->SetFont('Times', '', 9);
        
        $practical_count = 0;
        
        for ($i = 0; $i < count($practical_subjects); $i++) {
            $subject = $practical_subjects[$i];
            $practical_count++;
            
            // Combine code and name
            $subject_text = $subject['code'] . ' - ' . $subject['name'];
            
            // Use the improved Row function to handle multi-line content
            $pdf->Row([
                $practical_count,
                $subject_text,
                $subject['faculty'],
                '',
                ''
            ], $row_height);
        }
    }
    
    // Calculate how much vertical space is left on the page
    $y_position = $pdf->GetY();
    $page_height = 297; // A4 height in mm
    $bottom_margin = 15; // Space needed for signature line at bottom
    $space_left = $page_height - $y_position - $bottom_margin;

    // If not enough space left for other departments, add a new page
    if ($space_left < 50) {
        $pdf->AddPage();
    } else {
        // Add a small gap between tables
        $pdf->Ln(5);
    }

    // Define consistent row height for other departments tables
    $other_depts_row_height = 6.8;

    // OTHER DEPARTMENTS TABLE
    $pdf->SetFont('Times', '', 10);
    
    // First section: Department signoffs
    $other_departments = [
        1 => ['Central Library', 'Mr.A. Fahim Shariff'],
        2 => ['Department Library', ''],
        3 => ['Attendance Percentage', 'Counselor'],
        4 => ['Physical Director', 'Dr.S.I.Aslam'],
        5 => ['Department', 'Head of the Department'],
        6 => ['Others', 'Mr.K.Md.Saleem (Reception)']
    ];
    
    // Save the current font size
    $regular_font_size = 10;
    $small_font_size = 8.5;
    
    // Set widths for department table
    $pdf->SetWidths([10, 75, 40, 40, 25]);
    $pdf->SetAligns(['C', 'L', 'L', 'C', 'C']);
    
    // Create the department table
    foreach ($other_departments as $sno => $dept) {
        // Use a smaller font size for the third column if it contains reception staff name
        if ($dept[1] == 'Mr.K.Md.Saleem (Reception)') {
            $pdf->SetFont('Times', '', $small_font_size);
        } else {
            $pdf->SetFont('Times', '', $regular_font_size);
        }
        
        // Add attendance percentage for the attendance row
        $attendance_value = ($dept[0] == 'Attendance Percentage') ? $attendance_percentage . '%' : '';
        
        // Use the improved Row function with the defined row height
        $pdf->Row([
            $sno,
            $dept[0],
            $dept[1],
            $attendance_value,
            ''
        ], $other_depts_row_height);
    }
    
    // Add a small gap between tables
    $pdf->Ln(5);
    
    // ADMINISTRATIVE SIGNOFFS TABLE
    
    // Set widths for admin table
    $pdf->SetWidths([85, 40, 65]);
    $pdf->SetAligns(['L', 'L', 'C']);
    
    // Fee and other details
    $admin_departments = [
        ['FEE-ARREARS (OFFICE)', 'Mr.K.Mohamed Irfan'],
        ['HOSTEL', 'Mr.K.Mohamed Irfan'],
        ['TRANSPORT', 'Mr.K.Md.Saleem (Reception)']
    ];
    
    foreach ($admin_departments as $dept) {
        // Use a smaller font size for the second column if it contains reception staff name
        if ($dept[1] == 'Mr.K.Md.Saleem (Reception)') {
            $pdf->SetFont('Times', '', $small_font_size);
        } else {
            $pdf->SetFont('Times', '', $regular_font_size);
        }
        
        // Use the improved Row function with the defined row height
        $pdf->Row([
            $dept[0],
            $dept[1],
            ''
        ], $other_depts_row_height);
    }
    
    // SIGNATURE LINES
    $pdf->Ln(15);
    $pdf->SetFont('Times', 'B', 10);
    $pdf->Cell(47.5, 10, 'HOD', 0, 0, 'C');
    $pdf->Cell(47.5, 10, 'ASSISTANT MANAGER', 0, 0, 'C');
    $pdf->Cell(47.5, 10, 'MANAGER', 0, 0, 'C');
    $pdf->Cell(47.5, 10, 'PRINCIPAL', 0, 1, 'C');
}

$stmt->close();

// Output the PDF
$filename = 'Nodue-' . $year_roman .'-'. $section .' .pdf';
ob_end_clean(); // Clear the buffer before sending PDF headers
$pdf->Output('D', $filename); // Download the file

// Close the database connection
$conn->close();
exit;

// HELPER FUNCTIONS

// Function to calculate average attendance percentage
function calculateAttendancePercentage($conn, $roll_no, $semester) {
    // Query to get attendance entries for this student and semester
    $attendance_sql = "SELECT percentage FROM semester_attendance WHERE roll_no = ? AND semester = ?";
    $stmt = $conn->prepare($attendance_sql);
    $stmt->bind_param("si", $roll_no, $semester);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // No attendance records found
        $stmt->close();
        return "N/A";
    }
    
    $total_percentage = 0;
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $total_percentage += $row['percentage'];
        $count++;
    }
    
    $stmt->close();
    
    if ($count == 0) {
        return "N/A";
    }
    
    // Calculate average and round to 2 decimal places
    $average_percentage = round($total_percentage / $count, 2);
    
    return $average_percentage;
}

// Function to get marks for a specific exam
function getMarks($conn, $roll_no, $subject_code, $exam_type) {
    $marks_sql = "SELECT marks FROM marks WHERE roll_no = ? AND subject = ? AND exam = ?";
    $marks_stmt = $conn->prepare($marks_sql);
    $marks_stmt->bind_param("sss", $roll_no, $subject_code, $exam_type);
    $marks_stmt->execute();
    $marks_result = $marks_stmt->get_result();
    
    $marks = '';
    if ($marks_result->num_rows > 0) {
        $marks_data = $marks_result->fetch_assoc();
        $marks = $marks_data['marks'] ?? '';
        // Check if marks is -1 and replace with AB
        if ($marks == '-1') {
            $marks = 'AB';
        }
    }
    
    $marks_stmt->close();
    return $marks;
}
?>