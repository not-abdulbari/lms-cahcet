<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

include 'head.php';
include 'db_connect.php'; // Include your database connection file

$branch = $_POST['branch'];
$year = $_POST['year'];
$section = $_POST['section'];
$nba_logo = isset($_POST['nba_logo']) ? $_POST['nba_logo'] : 0; // Capture the NBA logo checkbox value

// Fetch students based on criteria
$students_sql = "SELECT roll_no, name FROM students WHERE branch = ? AND year = ? AND section = ?";
$stmt = $conn->prepare($students_sql);
$stmt->bind_param('sis', $branch, $year, $section);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student List</title>
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
            background: linear-gradient(135deg, #f7f9fc, #e4f1fe); /* Soft gradient background */
            color: #333;
            padding: 10px; /* Added padding for smaller screens */
        }

        /* Table Styling */
        table {
            width: 900px;
            margin: 20px auto;
            border-collapse: collapse;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add subtle shadow */
            background: #ffffff;
            border-radius: 8px; /* Rounded corners */
            overflow: hidden;
        }

        table, th, td {
            border: 1px solid #ddd; /* Subtle border color */
        }

        th, td {
            padding: 12px;
            text-align: left;
            font-size: 16px;
            color: #2c3e50; /* Elegant text color */
        }

        th {
            background-color: #f7f9fc; /* Soft header background */
            font-weight: bold;
        }

        /* Checkbox Styling */
        input[type="checkbox"] {
            accent-color: green; /* Set checkbox color to green */
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
            background-color: #3498db; /* Vibrant blue */
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn:hover {
            background-color: #2980b9; /* Darker blue */
            transform: scale(1.05); /* Subtle zoom on hover */
        }

        .btn:active {
            transform: scale(1); /* Return to normal */
        }
        
        /* Responsive Styling */
        @media (max-width: 768px) {
            th, td {
                font-size: 14px; /* Smaller text for smaller screens */
                padding: 10px;
            }

            .check-label {
                font-size: 16px; /* Adjust label font size */
            }

            .btn {
                padding: 8px 12px; /* Smaller buttons */
                font-size: 14px;
            }

            table {
                width: 100%;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            th, td {
                font-size: 12px; /* Smaller text for very small screens */
                padding: 8px;
            }

            .check-label {
                font-size: 14px; /* Adjust label font size */
            }

            .btn {
                padding: 6px 10px; /* Smaller buttons */
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <h2 style="text-align: center; font-size: 30px;">Student List</h2>
    <form id="bulk-download-form" action="feedback_generate_bulk.php" method="post">
        <!-- Hidden fields to pass all required criteria to the bulk PDF generation -->
        <input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
        <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
        <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
        <input type="hidden" name="nba_logo" value="<?= htmlspecialchars($nba_logo) ?>">
        
        <div style="display: flex; justify-content: space-between; width: 80%; margin: 20px auto;">
            <div>
                <input type="checkbox" id="select-all">
                <label for="select-all" class="check-label">Select All</label>
            </div>
            <button type="submit" class="btn">Download Selected</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Select</th>
                    <th>Roll No</th>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($student = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="student-checkbox" name="students[]" value="<?= htmlspecialchars($student['roll_no']) ?>">
                            </td>
                            <td><?= htmlspecialchars($student['roll_no']) ?></td>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td>
                                <button 
                                    type="button" 
                                    class="btn generate-pdf-btn" 
                                    data-roll-no="<?= htmlspecialchars($student['roll_no']) ?>"
                                    data-nba-logo="<?= htmlspecialchars($nba_logo) ?>"
                                >
                                    Download Feedback
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No students found for the given criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
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

        // Handle Generate PDF for an individual student
        const generatePdfButtons = document.querySelectorAll('.generate-pdf-btn');
        generatePdfButtons.forEach(button => {
            button.addEventListener('click', () => {
                const rollNo = button.dataset.rollNo;
                const nbaLogo = button.dataset.nbaLogo;

                // Create a form dynamically
                const form = document.createElement('form');
                form.action = 'feedback_generate.php';
                form.method = 'get';
                form.style.display = 'none';

                // Add form fields dynamically
                const rollNoInput = document.createElement('input');
                rollNoInput.type = 'hidden';
                rollNoInput.name = 'roll_no';
                rollNoInput.value = rollNo;
                form.appendChild(rollNoInput);

                const nbaLogoInput = document.createElement('input');
                nbaLogoInput.type = 'hidden';
                nbaLogoInput.name = 'nba_logo';
                nbaLogoInput.value = nbaLogo;
                form.appendChild(nbaLogoInput);

                // Append form to body and submit it
                document.body.appendChild(form);
                form.submit();
            });
        });
    </script>
</body>
</html>
