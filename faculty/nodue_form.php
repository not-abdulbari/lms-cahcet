<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
include 'head.php';
include 'db_connect.php'; // Include your database connection file

// Fetch branches
$branches_sql = "SELECT DISTINCT branch FROM students";
$branches_result = $conn->query($branches_sql);

// Fetch years
$years_sql = "SELECT DISTINCT year FROM students";
$years_result = $conn->query($years_sql);

// Fetch sections
$sections_sql = "SELECT DISTINCT section FROM students";
$sections_result = $conn->query($sections_sql);

// Semesters (assuming 8 semesters)
$semesters = range(1, 8);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>No-Due Form</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
/* Base styling for body */
/* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body Styling */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #f7f9fc, #e4f1fe); /* Light gradient background */
    margin: 0;
    padding: 0;
    color: #333;
}

/* Header Styling */
h2 {
    text-align: center;
    color: #2c3e50;
    font-size: 28px;
    margin: 40px 0;
    font-weight: bold;
}

/* Form Styling */
form {
    max-width: 800px;
    margin: 30px auto;
    padding: 40px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease-in-out;
    border-left: 5px solid #3498db;
}

form:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

/* Form Elements Styling */
label {
    margin-top: 15px;
    display: block;
    font-weight: bold;
    color: #2c3e50;
    font-size: 16px;
}

select, button, input {
    width: 100%;
    padding: 12px;
    margin-top: 10px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f5f5f5;
    transition: background-color 0.3s, border-color 0.3s;
}

select:focus, button:focus, input:focus {
    outline: none;
    border-color: #3498db;
}

select:hover, button:hover, input:hover {
    background-color: #eaf2f8;
}

/* Button Styling */
button {
    background-color: #3498db;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

button:hover {
    background-color: #2980b9;
    transform: scale(1.05);
}

button:active {
    transform: scale(1);
}

/* Responsive Form Layout */
.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row .form-group {
    flex: 1;
    min-width: 250px;
}

.form-row select {
    width: 100%;
}

/* Faculty Incharge Container */
.faculty-incharge-container {
    margin-top: 30px;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.faculty-incharge-container h3 {
    margin-bottom: 15px;
    color: #2c3e50;
    text-align: center;
    font-size: 18px;
    font-weight: bold;
}

/* Subject Faculty Container */
.subject-faculty-container {
    margin-top: 20px;
}

.subject-faculty-row {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 15px;
    padding: 15px;
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.subject-faculty-row:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.subject-info {
    flex: 1;
    min-width: 250px;
    padding-right: 20px;
}

.subject-info h4 {
    margin: 0;
    font-size: 16px;
    color: #2c3e50;
}

.faculty-select {
    flex: 1;
    min-width: 250px;
}

.faculty-dropdown {
    width: 100%;
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

/* Mobile Responsive Styling */
@media (max-width: 768px) {
    form {
        padding: 20px;
    }

    .form-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-row .form-group {
        width: 100%;
    }
    
    .subject-faculty-row {
        flex-direction: column;
    }
    
    .subject-info {
        margin-bottom: 10px;
        padding-right: 0;
    }
}

/* Smooth Input Animations */
form {
    animation: fadeIn 1s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Validation Message Styles */
.validation-message {
    color: #e74c3c;
    margin-top: 10px;
    font-size: 14px;
    display: none;
}

/* Loading Indicator */
.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.loading-spinner {
    border: 5px solid #f3f3f3;
    border-top: 5px solid #3498db;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
    </style>
    <script>
        $(document).ready(function() {
            // Function to update faculty incharge options based on selected criteria
            function updateFacultyIncharge() {
                const branch = $('#branch').val();
                const year = $('#year').val();
                const yearRoman = $('#year_roman').val();
                const section = $('#section').val();
                const semester = $('#semester').val();
                
                if (branch && year && yearRoman && section && semester) {
                    $('#faculty-incharge-container').html('<p class="text-center">Loading...</p>');
                    $('#faculty-incharge-container').show();
                    
                    $.ajax({
                        url: 'get_faculty_incharge.php',
                        method: 'POST',
                        data: {
                            branch: branch,
                            year: year,
                            year_roman: yearRoman,
                            section: section,
                            semester: semester
                        },
                        success: function(response) {
                            $('#faculty-incharge-container').html(response);
                            $('#faculty-incharge-container').show();
                        },
                        error: function() {
                            $('#faculty-incharge-container').html('<p>Error loading faculty data.</p>');
                            $('#faculty-incharge-container').show();
                        }
                    });
                } else {
                    $('#faculty-incharge-container').hide();
                }
            }
            
            $('#branch, #year, #year_roman, #section, #semester').change(function() {
                updateFacultyIncharge();
            });
            
            $('#faculty-incharge-container').hide();

            // Form validation before submission
            $('#noDueForm').submit(function(e) {
                // Check if any subject is selected
                let subjectSelected = false;
                $('.subject-checkbox:checked').each(function() {
                    subjectSelected = true;
                    return false; // Break the loop once one is found
                });

                if (!subjectSelected) {
                    e.preventDefault();
                    alert('Please select at least one subject');
                    return false;
                }

                // Verify faculty is selected for each checked subject
                let allValid = true;
                $('.subject-checkbox:checked').each(function() {
                    const subjectCode = $(this).val();
                    const facultyDropdown = $('.faculty-' + subjectCode);
                    
                    if (!facultyDropdown.val()) {
                        allValid = false;
                        facultyDropdown.css('border-color', '#e74c3c');
                        return false; // Break the loop
                    }
                });

                if (!allValid) {
                    e.preventDefault();
                    alert('Please select faculty for all checked subjects');
                    return false;
                }

                // Show loading indicator
                $('.loading-overlay').css('display', 'flex');
                
                // All checks passed, form can be submitted
                return true;
            });
        });

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
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="form-container">
        <h2>No-Due Form</h2>
        <form id="noDueForm" method="post" action="nodue_namelist.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="branch">Branch:</label>
                    <select id="branch" name="branch" required>
                        <option value="">Select Branch</option>
                        <?php while ($row = $branches_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['branch']) ?>"><?= htmlspecialchars($row['branch']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year">Year:</label>
                    <select id="year" name="year" required>
                        <option value="">Select Year</option>
                        <?php while ($row = $years_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['year']) ?>"><?= htmlspecialchars($row['year']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="year_roman">Year (Roman):</label>
                    <select id="year_roman" name="year_roman" required>
                        <option value="">Select Year (Roman)</option>
                        <option value="I">I</option>
                        <option value="II">II</option>
                        <option value="III">III</option>
                        <option value="IV">IV</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="section">Section:</label>
                    <select id="section" name="section" required>
                        <option value="">Select Section</option>
                        <?php while ($row = $sections_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['section']) ?>"><?= htmlspecialchars($row['section']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="semester">Semester:</label>
                    <select id="semester" name="semester" required>
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $semester): ?>
                            <option value="<?= $semester ?>"><?= $semester ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Faculty Incharge Container -->
            <div id="faculty-incharge-container" class="faculty-incharge-container"></div>
            
            <!-- Hidden fields to store main form values for summary -->
            <input type="hidden" id="branch_name" name="branch_name" value="">
            <input type="hidden" id="year_value" name="year_value" value="">
            <input type="hidden" id="year_roman_value" name="year_roman_value" value="">
            <input type="hidden" id="section_value" name="section_value" value="">
            <input type="hidden" id="semester_value" name="semester_value" value="">
            
            <div class="form-group">
                <button type="submit">Submit</button>
            </div>
        </form>
    </div>

    <script>
        // Update hidden fields with selected values for summary on next page
        $('#noDueForm').submit(function() {
            $('#branch_name').val($('#branch option:selected').text());
            $('#year_value').val($('#year option:selected').text());
            $('#year_roman_value').val($('#year_roman option:selected').text());
            $('#section_value').val($('#section option:selected').text());
            $('#semester_value').val($('#semester option:selected').text());
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>