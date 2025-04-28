<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
include 'head.php';
include 'db_connect.php'; // Include your database connection file

$result = null; // Initialize $result to avoid undefined variable error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch = $_POST['branch'];
    $year = $_POST['year'];
    $year_roman = isset($_POST['year_roman']) ? $_POST['year_roman'] : '';
    $section = $_POST['section'];
    $semester = $_POST['semester'];
    $exam = $_POST['exam'];
    $nba_logo = isset($_POST['nba_logo']) ? $_POST['nba_logo'] : 0; // Capture the NBA logo checkbox value

    // Fetch students based on criteria
    $sql = "SELECT roll_no, name FROM students WHERE branch = ? AND year = ? AND section = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sss", $branch, $year, $section);
    if (!$stmt->execute()) {
        die("SQL execution failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    if (!$result) {
        die("Fetching result failed: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students List</title>
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
    }

    /* Table Styling */
    table {
        width: 1000px; /* Adjusted to make the table occupy full width */
        max-width: 1500px; /* Optional: Set a max width for large screens */
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
    </style>
</head>
<body>
    <h2 style="text-align: center; font-size: 30px;">Students List</h2>
<form id="bulk-download-form" action="generate_bulk_pdf.php" method="post">
    <div style="display: flex; justify-content: space-between; width: 80%; margin: 20px auto;">
        <div>
            <input type="checkbox" id="select-all">
            <label for="select-all" class="check-label">Select All</label>
        </div>
        <button type="submit" class="btn">Download Selected</button>
    </div>
    <table>
        <tr>
            <th>Select</th>
            <th>Roll No</th>
            <th>Name</th>
            <th>Action</th> <!-- Add Action column -->
        </tr>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td>
                    <input type="checkbox" class="student-checkbox" name="students[]" value="<?= htmlspecialchars($row['roll_no']) ?>">
                </td>
                <td><?= htmlspecialchars($row['roll_no']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>
                    <!-- Generate PDF button for each row -->
                    <button 
                        type="button" 
                        class="btn generate-pdf-btn" 
                        data-roll-no="<?= htmlspecialchars($row['roll_no']) ?>"
                        data-branch="<?= htmlspecialchars($branch) ?>"
                        data-year="<?= htmlspecialchars($year) ?>"
                        data-year-roman="<?= htmlspecialchars($year_roman) ?>"
                        data-section="<?= htmlspecialchars($section) ?>"
                        data-semester="<?= htmlspecialchars($semester) ?>"
                        data-exam="<?= htmlspecialchars($exam) ?>"
                        data-nba-logo="<?= htmlspecialchars($nba_logo) ?>"
                    >
                        Generate PDF
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align: center;">No students found for the given criteria.</td>
            </tr>
        <?php endif; ?>
    </table>
    <!-- Hidden Inputs to Pass Form Data -->
    <input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
    <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
    <input type="hidden" name="year_roman" value="<?= htmlspecialchars($year_roman) ?>">
    <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
    <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
    <input type="hidden" name="exam" value="<?= htmlspecialchars($exam) ?>">
    <input type="hidden" name="nba_logo" value="<?= htmlspecialchars($nba_logo) ?>">
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
            const branch = button.dataset.branch;
            const year = button.dataset.year;
            const yearRoman = button.dataset.yearRoman;
            const section = button.dataset.section;
            const semester = button.dataset.semester;
            const exam = button.dataset.exam;
            const nbaLogo = button.dataset.nbaLogo;

            // Create a form dynamically
            const form = document.createElement('form');
            form.action = 'generate_pdf.php';
            form.method = 'post';
            form.style.display = 'none';

            // Add form fields dynamically
            const fields = {
                roll_no: rollNo,
                branch: branch,
                year: year,
                year_roman: yearRoman,
                section: section,
                semester: semester,
                exam: exam,
                nba_logo: nbaLogo
            };

            for (const name in fields) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = fields[name];
                form.appendChild(input);
            }

            // Append form to body and submit it
            document.body.appendChild(form);
            form.submit();
        });
    });
</script>
</body>
</html>

<?php
$conn->close();
?>
