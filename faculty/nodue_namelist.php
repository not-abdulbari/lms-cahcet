<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
include 'head.php';
include 'db_connect.php'; // Include your database connection file

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: no_due_form.php');
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

// Validate that required fields are present
if (empty($branch) || empty($year) || empty($year_roman) || empty($section) || empty($semester) || empty($selected_subjects)) {
    echo '<div class="alert alert-danger">Missing required form data. Please go back and complete the form.</div>';
    exit;
}

// Fetch students based on the selected criteria
$students_sql = "SELECT roll_no, name AS student_name, reg_no 
                FROM students 
                WHERE branch = ? AND year = ? AND section = ?
                ORDER BY roll_no ASC";
$stmt = $conn->prepare($students_sql);
$stmt->bind_param("sss", $branch, $year, $section);
$stmt->execute();
$students_result = $stmt->get_result();

if ($students_result->num_rows == 0) {
    echo '<div class="alert alert-warning">No students found for the selected criteria.</div>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>No-Due Name List</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body Styling */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f7f9fc, #e4f1fe);
            color: #333;
            padding: 10px;
        }

        /* Table Styling */
        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
            font-size: 16px;
            color: #2c3e50;
        }

        th {
            background-color: #f7f9fc;
            font-weight: bold;
        }

        /* Checkbox Styling */
        input[type="checkbox"] {
            accent-color: green;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .check-label {
            font-size: 18px;
            padding-left: 10px;
            text-align: center;
            font-weight: bold;
        }

        /* Button Styling */
        .btn {
            padding: 10px 15px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn:hover {
            background-color: #2980b9;
            transform: scale(1.05);
        }

        .btn:active {
            transform: scale(1);
        }

        /* Responsive Styling */
        @media (max-width: 768px) {
            th, td {
                font-size: 14px;
                padding: 10px;
            }

            .check-label {
                font-size: 16px;
            }

            .btn {
                padding: 8px 12px;
                font-size: 14px;
            }

            table {
                width: 100%;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            th, td {
                font-size: 12px;
                padding: 8px;
            }

            .check-label {
                font-size: 14px;
            }

            .btn {
                padding: 6px 10px;
                font-size: 12px;
            }
        }

        /* Header styling */
        h2 {
            text-align: center;
            font-size: 30px;
            margin: 20px 0;
        }

        /* Summary Section */
        .summary-section {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
            border-left: 5px solid #3498db;
        }

        .summary-title {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .summary-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .summary-item {
            flex: 1;
            min-width: 200px;
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
        }

        .summary-item strong {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <h2>No-Due Name List</h2>
    
    <form id="no-due-form" action="nodue_bulk_pdf.php" method="post">
        <!-- Hidden fields to pass all required criteria to the next page -->
        <input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
        <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
        <input type="hidden" name="year_roman" value="<?= htmlspecialchars($year_roman) ?>">
        <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
        <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
        
        <?php foreach ($selected_subjects as $subject_code): ?>
            <input type="hidden" name="selected_subjects[]" value="<?= htmlspecialchars($subject_code) ?>">
            <?php if (isset($subject_names[$subject_code])): ?>
                <input type="hidden" name="subject_names[<?= htmlspecialchars($subject_code) ?>]" value="<?= htmlspecialchars($subject_names[$subject_code]) ?>">
            <?php endif; ?>
            <?php if (isset($faculty[$subject_code])): ?>
                <input type="hidden" name="faculty[<?= htmlspecialchars($subject_code) ?>]" value="<?= htmlspecialchars($faculty[$subject_code]) ?>">
            <?php endif; ?>
            <?php if (isset($faculty_names[$subject_code])): ?>
                <input type="hidden" name="faculty_names[<?= htmlspecialchars($subject_code) ?>]" value="<?= htmlspecialchars($faculty_names[$subject_code]) ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        
        <div style="display: flex; justify-content: space-between; width: 80%; margin: 20px auto;">
            <div>
                <input type="checkbox" id="select-all">
                <label for="select-all" class="check-label">Select All</label>
            </div>
            <button type="submit" class="btn">Generate Selected</button>
        </div>
        
        <table>
            <tr>
                <th>Select</th>
                <th>Roll No</th>
                <th>Student Name</th>
                <th>Reg No</th>
                <th>Action</th>
            </tr>
            <?php if ($students_result->num_rows > 0): ?>
                <?php while ($student = $students_result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="student-checkbox" name="students[]" value="<?= htmlspecialchars($student['roll_no']) ?>">
                    </td>
                    <td><?= htmlspecialchars($student['roll_no']) ?></td>
                    <td><?= htmlspecialchars($student['student_name']) ?></td>
                    <td><?= htmlspecialchars($student['reg_no']) ?></td>
                    <td>
                        <button 
                            type="button" 
                            class="btn generate-single-btn" 
                            data-roll-no="<?= htmlspecialchars($student['roll_no']) ?>"
                            data-reg-no="<?= htmlspecialchars($student['reg_no']) ?>"
                        >
                            Generate PDF
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No students found for the given criteria.</td>
                </tr>
            <?php endif; ?>
        </table>
    </form>
    
    <script>
        // Handle "Select All" functionality
        const selectAllCheckbox = document.getElementById('select-all');
        const studentCheckboxes = document.querySelectorAll('.student-checkbox');

        selectAllCheckbox.addEventListener('change', () => {
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });

        // Handle Generate for an individual student
        const generateSingleButtons = document.querySelectorAll('.generate-single-btn');
        generateSingleButtons.forEach(button => {
            button.addEventListener('click', () => {
                const rollNo = button.dataset.rollNo;
                const regNo = button.dataset.regNo;
                
                // Create a form dynamically
                const form = document.createElement('form');
                form.action = 'nodue_generate_pdf.php';
                form.method = 'post';
                form.style.display = 'none';

                // Add all the hidden inputs from the main form
                const mainForm = document.getElementById('no-due-form');
                const hiddenInputs = mainForm.querySelectorAll('input[type="hidden"]');
                
                hiddenInputs.forEach(input => {
                    const newInput = document.createElement('input');
                    newInput.type = 'hidden';
                    newInput.name = input.name;
                    newInput.value = input.value;
                    form.appendChild(newInput);
                });

                // Add the specific student info
                const rollInput = document.createElement('input');
                rollInput.type = 'hidden';
                rollInput.name = 'roll_no';
                rollInput.value = rollNo;
                form.appendChild(rollInput);

                const regInput = document.createElement('input');
                regInput.type = 'hidden';
                regInput.name = 'reg_no';
                regInput.value = regNo;
                form.appendChild(regInput);

                // Append form to body and submit it
                document.body.appendChild(form);
                form.submit();
            });
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>