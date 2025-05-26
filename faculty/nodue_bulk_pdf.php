<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// Get extra dept rows if present
$extra_dept_rows = isset($_POST['extra_dept_rows']) ? intval($_POST['extra_dept_rows']) : 0;

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

// Custom PDF class
class NODUEPDF extends FPDF {
    protected $widths;
    protected $aligns;
    function Header() {}
    function Footer() {}
    function SetWidths($w) { $this->widths = $w; }
    function SetAligns($a) { $this->aligns = $a; }
    function Row($data, $heights = 5) {
        $nb = 0;
        for($i=0; $i<count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $h = $heights * max(1, $nb * 0.85);
        $this->CheckPageBreak($h);
        for($i=0; $i<count($data); $i++) {
            $w = $this->widths[$i];
            $x = $this->GetX();
            $y = $this->GetY();
            $align = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, $heights * 0.6, $data[$i], 0, $align);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }
    function CheckPageBreak($h) {
        if($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }
    function NbLines($w, $txt) {
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

// For multiple students, get their details
$placeholders = str_repeat('?,', count($students) - 1) . '?';
$stmt = $conn->prepare("SELECT roll_no, reg_no, name AS student_name FROM students WHERE roll_no IN ($placeholders) ORDER BY roll_no ASC");
$types = str_repeat('s', count($students));
$stmt->bind_param($types, ...$students);
$stmt->execute();
$result = $stmt->get_result();

$pdf = new NODUEPDF();

while ($row = $result->fetch_assoc()) {
    $roll_no = $row['roll_no'];
    $reg_no = $row['reg_no'];
    $student_name = $row['student_name'];
    
    $pdf->AddPage();
    $pdf->SetMargins(10, 10, 10);
    $attendance_percentage = calculateAttendancePercentage($conn, $roll_no, $semester);

    // Sort subjects into theory and practical
    $theory_subjects = [];
    $practical_subjects = [];
    foreach ($selected_subjects as $subject_code) {
        $parts = explode('_', $subject_code);
        $base_code = $parts[0];
        $type = $parts[1] ?? '';
        $subject_name = $subject_names[$subject_code] ?? '';
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
        $faculty_name = $faculty_names[$subject_code] ?? '';
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

    // HEADER
    $pdf->SetFont('Times', 'B', 13);
    $pdf->Cell(0, 8, 'C. ABDUL HAKEEM COLLEGE OF ENGINEERING & TECHNOLOGY, MELVISHARAM', 0, 1, 'C');
    $pdf->Cell(0, 6, 'DEPARTMENT OF ' . strtoupper($branch), 0, 1, 'C');
    $semester_type = ($semester % 2 === 0) ? 'EVEN' : 'ODD';
    $pdf->Cell(0, 6, 'HALL TICKET NO-DUE FORM FOR ACADEMIC YEAR: 2024-2025 (' . $semester_type . ')', 0, 1, 'C');
    $pdf->Ln(2);
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
    $pdf->Ln(2);

    $row_height = 7.2;

    // THEORY SUBJECTS TABLE
    if (!empty($theory_subjects)) {
        $pdf->SetWidths([10, 75, 35, 15, 15, 15, 25]);
        $pdf->SetAligns(['C', 'L', 'L', 'C', 'C', 'C', 'C']);
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(10, 9, 'S.No.', 1, 0, 'C');
        $pdf->Cell(75, 9, 'Theory Subjects', 1, 0, 'C');
        $pdf->Cell(35, 9, 'Staff In-charge', 1, 0, 'C');
        $pdf->Cell(45, 4.5, 'Marks', 1, 0, 'C');
        $pdf->Cell(25, 9, 'Signature', 1, 1, 'C');
        $pdf->SetXY(130, $pdf->GetY() - 4.5);
        $pdf->Cell(15, 4.5, 'CAT I', 1, 0, 'C');
        $pdf->Cell(15, 4.5, 'CAT II', 1, 0, 'C');
        $pdf->Cell(15, 4.5, 'Model', 1, 1, 'C');
        $pdf->SetFont('Times', '', 9);
        for ($i = 0; $i < count($theory_subjects); $i++) {
            $subject = $theory_subjects[$i];
            $theory_count = $i + 1;
            $code = $subject['code'];
            $cat1 = getMarks($conn, $roll_no, $code, 'CAT1');
            $cat2 = getMarks($conn, $roll_no, $code, 'CAT2');
            $model = getMarks($conn, $roll_no, $code, 'MODEL');
            $subject_text = $code . ' - ' . $subject['name'];
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
    $pdf->Ln(2);

    // PRACTICAL SUBJECTS TABLE
    if (!empty($practical_subjects)) {
        $pdf->SetWidths([10, 75, 45, 35, 25]);
        $pdf->SetAligns(['C', 'L', 'L', 'C', 'C']);
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(10, 9, 'S.No.', 1, 0, 'C');
        $pdf->Cell(75, 9, 'Practical Subjects', 1, 0, 'C');
        $pdf->Cell(45, 9, 'Staff In-charge', 1, 0, 'C');
        $pdf->Cell(35, 9, '', 1, 0, 'C');
        $pdf->Cell(25, 9, 'Signature', 1, 1, 'C');
        $pdf->SetFont('Times', '', 9);
        $practical_count = 0;
        for ($i = 0; $i < count($practical_subjects); $i++) {
            $subject = $practical_subjects[$i];
            $practical_count++;
            $subject_text = $subject['code'] . ' - ' . $subject['name'];
            $pdf->Row([
                $practical_count,
                $subject_text,
                $subject['faculty'],
                '',
                ''
            ], $row_height);
        }
    }

    // Department Table
    $y_position = $pdf->GetY();
    $page_height = 297;
    $bottom_margin = 15;
    $space_left = $page_height - $y_position - $bottom_margin;
    if ($space_left < 50) {
        $pdf->AddPage();
    } else {
        $pdf->Ln(2);
    }

    $other_depts_row_height = 6.8;
    $pdf->SetFont('Times', '', 10);

    $other_departments = [
        1 => ['Central Library', 'Mr.A. Fahim Shariff'],
        2 => ['Department Library', ''],
        3 => ['Attendance Percentage', 'Counselor'],
        4 => ['Physical Director', 'Dr.S.I.Aslam'],
        5 => ['Department', 'Head of the Department'],
        6 => ['Others', 'Mr.K.Md.Saleem (Reception)']
    ];

    $regular_font_size = 10;
    $small_font_size = 8.5;
    $pdf->SetWidths([10, 75, 40, 40, 25]);
    $pdf->SetAligns(['C', 'L', 'L', 'C', 'C']);

    foreach ($other_departments as $sno => $dept) {
        if ($dept[1] == 'Mr.K.Md.Saleem (Reception)') {
            $pdf->SetFont('Times', '', $small_font_size);
        } else {
            $pdf->SetFont('Times', '', $regular_font_size);
        }
        // Fix: Set $attendance_value only for Attendance Percentage row
        //$attendance_value = ($dept[0] == 'Attendance Percentage') ? ($attendance_percentage . '%') : '';
        $pdf->Row([
            $sno,
            $dept[0],
            $dept[1],
            $attendance_value,
            ''
        ], $other_depts_row_height);
    }

    // ADD EXTRA EMPTY ROWS if requested!
    if (!empty($extra_dept_rows) && intval($extra_dept_rows) > 0) {
        $pdf->SetFont('Times', '', $regular_font_size);
        for ($i = 0; $i < intval($extra_dept_rows); $i++) {
            $pdf->Row(['', '', '', '', ''], $other_depts_row_height);
        }
    }

    $pdf->Ln(2);

    // ADMIN DEPARTMENT TABLE
    $pdf->SetWidths([85, 40, 65]);
    $pdf->SetAligns(['L', 'L', 'C']);
    $admin_departments = [
        ['FEE-ARREARS (OFFICE)', 'Mr.K.Mohamed Irfan'],
        ['HOSTEL', 'Mr.K.Mohamed Irfan'],
        ['TRANSPORT', 'Mr.K.Md.Saleem (Reception)']
    ];
    foreach ($admin_departments as $dept) {
        if ($dept[1] == 'Mr.K.Md.Saleem (Reception)') {
            $pdf->SetFont('Times', '', $small_font_size);
        } else {
            $pdf->SetFont('Times', '', $regular_font_size);
        }
        $pdf->Row([
            $dept[0],
            $dept[1],
            ''
        ], $other_depts_row_height);
    }
    $pdf->Ln(8);
    $pdf->SetFont('Times', 'B', 10);
    $pdf->Cell(47.5, 10, 'CLASS INCHARGE', 0, 0, 'C');
    $pdf->Cell(47.5, 10, 'ASSISTANT MANAGER', 0, 0, 'C');
    $pdf->Cell(47.5, 10, 'MANAGER', 0, 0, 'C');
    $pdf->Cell(47.5, 10, 'HOD', 0, 1, 'C');

    // BELOW LINE: VICE PRINCIPAL, PRINCIPAL
    $pdf->Ln(4);
    $pdf->Cell(95, 10, 'VICE PRINCIPAL', 0, 0, 'C');
    $pdf->Cell(95, 10, 'PRINCIPAL', 0, 1, 'C');
}

$stmt->close();

// Output the PDF
$filename = 'Nodue-' .$branch. $year_roman .'-'. $section .'.pdf';
ob_end_clean();
$pdf->Output('D', $filename);
$conn->close();
exit;

// Helper functions

function calculateAttendancePercentage($conn, $roll_no, $semester) {
    $attendance_sql = "SELECT percentage FROM semester_attendance WHERE roll_no = ? AND semester = ?";
    $stmt = $conn->prepare($attendance_sql);
    $stmt->bind_param("si", $roll_no, $semester);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
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
    if ($count == 0) return "N/A";
    $average_percentage = round($total_percentage / $count, 2);
    return $average_percentage;
}
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
        if ($marks == '-1') $marks = 'AB';
    }
    $marks_stmt->close();
    return $marks;
}
?>
