<?php
include 'db_connect.php'; // Include your database connection file

// Get the POST parameters
$branch = $_POST['branch'] ?? '';
$year = $_POST['year'] ?? '';
$year_roman = $_POST['year_roman'] ?? '';
$section = $_POST['section'] ?? '';
$semester = $_POST['semester'] ?? '';

// Validate inputs
if (empty($branch) || empty($year) || empty($year_roman) || empty($section) || empty($semester)) {
    echo '<p>Please select all required fields.</p>';
    exit;
}

// Query to fetch distinct subjects from the subjects table based on the selected criteria
$subjects_sql = "SELECT subject_code, subject_name FROM subjects WHERE branch = ? AND semester = ?";
$stmt = $conn->prepare($subjects_sql);
$stmt->bind_param("si", $branch, $semester);
$stmt->execute();
$subjects_result = $stmt->get_result();

if ($subjects_result->num_rows == 0) {
    echo '<h3>No subjects found for the selected criteria</h3>';
    exit;
}

// Query to fetch all faculty members - do this only once
$faculty_sql = "SELECT faculty_code, faculty_name FROM faculty ORDER BY faculty_name";
$faculty_stmt = $conn->prepare($faculty_sql);
$faculty_stmt->execute();
$faculty_result = $faculty_stmt->get_result();

// Store all faculty in an array for reuse
$faculty_list = [];
while ($faculty = $faculty_result->fetch_assoc()) {
    $faculty_list[] = $faculty;
}

echo '<h3>Theory & Laboratory Faculty Incharge for ' . htmlspecialchars($branch) . ' - ' . htmlspecialchars($year_roman) . ' Year, Section ' . htmlspecialchars($section) . ', Semester ' . htmlspecialchars($semester) . '</h3>';

// Add the note tab
echo '<div class="note-tab">';
echo '<strong>Note:</strong> Select the required subjects for your related semester.';
echo '</div>';

echo '<div class="subject-faculty-container">';

while ($subject = $subjects_result->fetch_assoc()) {
    $subject_code = $subject['subject_code'];
    $subject_name = $subject['subject_name'];
    
    echo '<div class="subject-faculty-row">';
    
    // Theory Section
    echo '<div class="subject-section">';
    echo '<div class="checkbox-label-group">';
    echo '<input type="checkbox" name="selected_subjects[]" value="' . htmlspecialchars($subject_code) . '_theory" class="subject-checkbox" id="subject_' . htmlspecialchars($subject_code) . '_theory" onchange="toggleRequired(this, \'' . htmlspecialchars($subject_code) . '_theory\')">';
    echo '<label for="subject_' . htmlspecialchars($subject_code) . '_theory">' . htmlspecialchars($subject_name) . ' (Theory)</label>';
    echo '</div>';
    echo '<select name="faculty[' . htmlspecialchars($subject_code) . '_theory]" class="faculty-dropdown faculty-' . htmlspecialchars($subject_code) . '_theory" disabled>';
    echo '<option value="">Select Faculty (Theory)</option>';
    foreach ($faculty_list as $faculty) {
        echo '<option value="' . htmlspecialchars($faculty['faculty_code']) . '">' . htmlspecialchars($faculty['faculty_name']) . '</option>';
    }
    echo '</select>';
    echo '</div>';
    
    // Laboratory Section
    echo '<div class="subject-section">';
    echo '<div class="checkbox-label-group">';
    echo '<input type="checkbox" name="selected_subjects[]" value="' . htmlspecialchars($subject_code) . '_laboratory" class="subject-checkbox" id="subject_' . htmlspecialchars($subject_code) . '_laboratory" onchange="toggleRequired(this, \'' . htmlspecialchars($subject_code) . '_laboratory\')">';
    echo '<label for="subject_' . htmlspecialchars($subject_code) . '_laboratory">' . htmlspecialchars($subject_name) . ' (Laboratory)</label>';
    echo '</div>';
    echo '<select name="faculty[' . htmlspecialchars($subject_code) . '_laboratory]" class="faculty-dropdown faculty-' . htmlspecialchars($subject_code) . '_laboratory" disabled>';
    echo '<option value="">Select Faculty (Laboratory)</option>';
    foreach ($faculty_list as $faculty) {
        echo '<option value="' . htmlspecialchars($faculty['faculty_code']) . '">' . htmlspecialchars($faculty['faculty_name']) . '</option>';
    }
    echo '</select>';
    echo '</div>';
    
    echo '</div>'; // End of subject-faculty-row
}

echo '</div>';

$stmt->close();
$faculty_stmt->close();
$conn->close();
?>
<script>
    // Function to toggle the required attribute and enable/disable dropdown
    function toggleRequired(checkbox, subjectCode) {
        const dropdown = document.querySelector('.faculty-' + subjectCode);
        if (checkbox.checked) {
            dropdown.disabled = false;
            dropdown.setAttribute('required', 'required');
        } else {
            dropdown.disabled = true;
            dropdown.removeAttribute('required');
            dropdown.value = ''; // Reset dropdown value
        }
    }
</script>
<style>
/* General Styling for the Subject-Faculty Selection */
.subject-faculty-container {
    margin-top: 20px;
}

/* Note Tab Styling */
.note-tab {
    margin: 15px 0;
    padding: 10px;
    background-color: #ffe6e6; /* Light red background */
    color: #b30000; /* Dark red text */
    border: 1px solid #ff0000; /* Red border */
    border-radius: 5px;
    font-size: 14px;
}

/* Rows for Subjects */
.subject-faculty-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    padding: 15px;
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease;
}

.subject-faculty-row:hover {
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

/* Individual Subject Section (Theory or Laboratory) */
.subject-section {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    flex: 1; /* Equal width for Theory and Laboratory sections */
    gap: 10px;
}

/* Flex Alignment for Checkbox and Label */
.checkbox-label-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Styling for Checkboxes */
.subject-checkbox {
    position: relative;
    cursor: pointer;
    appearance: none;
    width: 20px;
    height: 20px;
    border: 2px solid #3498db;
    border-radius: 4px;
    outline: none;
    transition: background-color 0.3s, border-color 0.3s;
}

.subject-checkbox:checked {
    background-color: #2ecc71; /* Green color when checked */
    border-color: #27ae60;
}

.subject-checkbox:checked::after {
    content: '\2713'; /* Checkmark symbol */
    color: white;
    font-size: 14px;
    display: flex;
    justify-content: center;
    align-items: center;
    position: absolute;
    top: 0;
    left: 0;
    width: 20px;
    height: 20px;
}

/* Styling for Labels */
label {
    font-size: 16px;
    color: #2c3e50;
    cursor: pointer;
}

/* Faculty Dropdown Styling */
.faculty-dropdown {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
    transition: border-color 0.3s, background-color 0.3s;
}

.faculty-dropdown:disabled {
    background-color: #f0f0f0;
    border-color: #dcdcdc;
}

.faculty-dropdown:focus {
    border-color: #3498db;
    background-color: #ffffff;
}

/* Hover Effects for Faculty Dropdowns */
.faculty-dropdown:hover {
    border-color: #2980b9;
}
</style>