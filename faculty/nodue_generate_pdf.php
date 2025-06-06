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
$students = $_POST['students'] ?? [];
$roll_no = $_POST['roll_no'] ?? '';
$reg_no = $_POST['reg_no'] ?? '';
$extra_dept_rows = isset($_POST['extra_dept_rows']) ? intval($_POST['extra_dept_rows']) : 0;

// Validate that required fields are present
if (empty($branch) || empty($year) || empty($year_roman) || empty($section) || 
    empty($semester) || empty($selected_subjects)) {
    echo '<div class="alert alert-danger">Missing required form data. Please go back and complete the form.</div>';
    exit;
}

// Process single student if roll_no is provided directly
if (!empty($roll_no)) {
    generatePDF($roll_no, $reg_no, $extra_dept_rows);
} 
// Process multiple students if students array is provided
else if (!empty($students)) {
    // For multiple students, we need to get their reg numbers
    $placeholders = str_repeat('?,', count($students) - 1) . '?';
    $stmt = $conn->prepare("SELECT roll_no, reg_no FROM students WHERE roll_no IN ($placeholders)");
    $types = str_repeat('s', count($students));
    $stmt->bind_param($types, ...$students);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        generatePDF($row['roll_no'], $row['reg_no'], $extra_dept_rows);
    }
    $stmt->close();
} else {
    echo '<div class="alert alert-danger">No students selected. Please go back and select at least one student.</div>';
    exit;
}

function generatePDF($roll_no, $reg_no, $extra_dept_rows = 0) {
    global $conn, $branch, $year, $year_roman, $section, $semester, $selected_subjects, $faculty;

    // Get student details
    $student_sql = "SELECT name AS student_name FROM students WHERE roll_no = ?";
    $stmt = $conn->prepare($student_sql);
    $stmt->bind_param("s", $roll_no);
    $stmt->execute();
    $student_result = $stmt->get_result();
    if ($student_result->num_rows == 0) {
        echo '<div class="alert alert-danger">Student not found: ' . $roll_no . '</div>';
        return;
    }
    $student = $student_result->fetch_assoc();
    $student_name = $student['student_name'];
    $stmt->close();

    // Get attendance percentage for this student and semester
    $attendance_percentage = calculateAttendancePercentage($roll_no, $semester);

    // Sort subjects into theory and practical
    $theory_subjects = [];
    $practical_subjects = [];
    foreach ($selected_subjects as $subject_code) {
        $parts = explode('_', $subject_code);
        $base_code = $parts[0];
        $type = $parts[1] ?? '';
        $subject_sql = "SELECT subject_code, subject_name FROM subjects WHERE subject_code = ?";
        $stmt = $conn->prepare($subject_sql);
        $stmt->bind_param("s", $base_code);
        $stmt->execute();
        $subject_result = $stmt->get_result();
        if ($subject_result->num_rows > 0) {
            $subject_data = $subject_result->fetch_assoc();
            $faculty_code = $faculty[$subject_code] ?? '';
            $faculty_name = '';
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
            $subject_info = [
                'code' => $subject_data['subject_code'],
                'name' => $subject_data['subject_name'],
                'faculty' => $faculty_name
            ];
            if ($type == 'theory') {
                $theory_subjects[] = $subject_info;
            } else if ($type == 'laboratory') {
                $practical_subjects[] = $subject_info;
            }
        }
        $stmt->close();
    }

    // PDF setup
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

    $pdf = new NODUEPDF();
    $pdf->AddPage();
    $pdf->SetMargins(10, 10, 10);
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
    $pdf->Ln(5);

    $row_height = 7.2;

    // TABLE 1: Theory subjects table
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
            // Get marks for this subject and student
            $cat1 = '';
            $cat2 = '';
            $model = '';
            // CAT1
            $marks_sql = "SELECT marks FROM marks WHERE roll_no = ? AND subject = ? AND exam = 'CAT1'";
            $marks_stmt = $conn->prepare($marks_sql);
            $marks_stmt->bind_param("ss", $roll_no, $code);
            $marks_stmt->execute();
            $marks_result = $marks_stmt->get_result();
            if ($marks_result->num_rows > 0) {
                $marks_data = $marks_result->fetch_assoc();
                $cat1 = $marks_data['marks'] ?? '';
                if ($cat1 == '-1') $cat1 = 'AB';
            }
            $marks_stmt->close();
            // CAT2
            $marks_sql = "SELECT marks FROM marks WHERE roll_no = ? AND subject = ? AND exam = 'CAT2'";
            $marks_stmt = $conn->prepare($marks_sql);
            $marks_stmt->bind_param("ss", $roll_no, $code);
            $marks_stmt->execute();
            $marks_result = $marks_stmt->get_result();
            if ($marks_result->num_rows > 0) {
                $marks_data = $marks_result->fetch_assoc();
                $cat2 = $marks_data['marks'] ?? '';
                if ($cat2 == '-1') $cat2 = 'AB';
            }
            $marks_stmt->close();
            // MODEL
            $marks_sql = "SELECT marks FROM marks WHERE roll_no = ? AND subject = ? AND exam = 'MODEL'";
            $marks_stmt = $conn->prepare($marks_sql);
            $marks_stmt->bind_param("ss", $roll_no, $code);
            $marks_stmt->execute();
            $marks_result = $marks_stmt->get_result();
            if ($marks_result->num_rows > 0) {
                $marks_data = $marks_result->fetch_assoc();
                $model = $marks_data['marks'] ?? '';
                if ($model == '-1') $model = 'AB';
            }
            $marks_stmt->close();
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

    $pdf->Ln(5);

    // TABLE 2: Practical subjects table
if (!empty($practical_subjects)) {
    $row_height = 6; // Reduced from default (e.g., 8 or 9) to make rows more compact
    $pdf->SetWidths([10, 75, 45, 35, 25]);
    $pdf->SetAligns(['C', 'L', 'L', 'C', 'C']);
    $pdf->SetFont('Times', 'B', 10);
    $pdf->Cell(10, $row_height, 'S.No.', 1, 0, 'C');
    $pdf->Cell(75, $row_height, 'Practical Subjects', 1, 0, 'C');
    $pdf->Cell(45, $row_height, 'Staff In-charge', 1, 0, 'C');
    $pdf->Cell(35, $row_height, '', 1, 0, 'C');
    $pdf->Cell(25, $row_height, 'Signature', 1, 1, 'C');
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
        ], $row_height); // Use reduced row height
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
    $pdf->Ln(2); // Reduce vertical padding after the table
}

$other_depts_row_height = 6; // Reduced from 6.8 to make it more compact

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
    //$attendance_value = ($dept[0] == 'Attendance Percentage') ? ($attendance_percentage . '%') : '';
    $pdf->Row([
        $sno,
        $dept[0],
        $dept[1],
        $attendance_value,
        ''
    ], $other_depts_row_height); // Use reduced row height
}

// ---- ADD EMPTY ROWS IF REQUESTED ----
if (!empty($extra_dept_rows) && intval($extra_dept_rows) > 0) {
    $pdf->SetFont('Times', '', $regular_font_size);
    for ($i = 0; $i < intval($extra_dept_rows); $i++) {
        $pdf->Row([
            '', '', '', '', ''
        ], $other_depts_row_height);
    }
}
    // Admin Table
    $pdf->Ln(2);
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

    // Signature line
    $pdf->Ln(15);
    $pdf->SetFont('Times', 'B', 10);
    $pdf->Cell(47.5, 10, 'HOD', 0, 0, 'C');
    $pdf->Cell(47.5, 10, 'ASSISTANT MANAGER', 0, 0, 'C');
    $pdf->Cell(47.5, 10, 'MANAGER', 0, 0, 'C');
    $pdf->Cell(47.5, 10, 'PRINCIPAL', 0, 1, 'C');

    $filename = 'Nodue_' . $roll_no . '.pdf';

    if (!is_dir('generated_pdfs')) {
        mkdir('generated_pdfs', 0755, true);
    }
    $pdf->Output('F', 'generated_pdfs/' . $filename);

    // Download for single, or collect for ZIP for bulk as per your logic
    if (empty($_POST['students'])) {
        ob_end_clean();
        $pdf->Output('D', $filename);
    }
}

// Function to calculate average attendance percentage
function calculateAttendancePercentage($roll_no, $semester) {
    global $conn;
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
    if ($count == 0) {
        return "N/A";
    }
    $average_percentage = round($total_percentage / $count, 2);
    return $average_percentage;
}

// If we've generated multiple PDFs, create a ZIP file
if (!empty($_POST['students'])) {
    $zip = new ZipArchive();
    $zipName = 'nodue_forms.zip';
    if ($zip->open('generated_pdfs/' . $zipName, ZipArchive::CREATE) === TRUE) {
        foreach ($_POST['students'] as $roll_no) {
            $pdfName = 'Nodue_' . $roll_no . '.pdf';
            if (file_exists('generated_pdfs/' . $pdfName)) {
                $zip->addFile('generated_pdfs/' . $pdfName, $pdfName);
            }
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize('generated_pdfs/' . $zipName));
        header('Pragma: no-cache');
        ob_end_clean();
        readfile('generated_pdfs/' . $zipName);
        exit;
    } else {
        echo 'Failed to create ZIP file.';
        exit;
    }
}
exit;
?>
