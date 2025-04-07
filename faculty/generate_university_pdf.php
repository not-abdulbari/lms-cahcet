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
  $tableWidth = 20 + 40 + 80 + 25 + 25; // Adjusted widths for columns

  // Calculate starting X-coordinate to center the table 
  $pageWidth = 210; 
  $startX = ($pageWidth - $tableWidth) / 2; 

  // Set the X-coordinate for the table 
  $pdf->SetX($startX); 

  // Create the table header 
  $pdf->SetFont('Times', 'B', 12); 
  $pdf->Cell(20, 10, 'Sem', 1, 0, 'C'); 
  $pdf->Cell(40, 10, 'Subject Code', 1, 0, 'C'); 
  $pdf->Cell(80, 10, 'Subject Name', 1, 0, 'C'); 
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
      $pdf->Cell(20, 10, $mark['semester'], 1, 0, 'C'); 
      $pdf->Cell(40, 10, $mark['subject_code'], 1, 0, 'C'); 
      
      // Handle potentially long subject names by allowing multi-line cells
      $pdf->Cell(80, 10, $mark['subject_name'] ?? 'Unknown Subject', 1, 0, 'L'); 
      
      $pdf->Cell(25, 10, $mark['grade'], 1, 0, 'C'); 
      $pdf->Cell(25, 10, $result, 1, 1, 'C'); 
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
?>