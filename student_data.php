<?php
// Include database connection
include 'faculty/db_connect.php';
$config = include('config.php');

$student_data = [];
$student_data_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fetch_student'])) {
    $roll_no = $_POST['roll_no'];

    // Fetch student details from the 'students' table
    $student_query = "SELECT * FROM students WHERE roll_no = '$roll_no'";
    $student_result = mysqli_query($conn, $student_query);

    if (mysqli_num_rows($student_result) > 0) {
        $student_data = mysqli_fetch_assoc($student_result);
    } else {
        $student_data_error = "No student found with the given roll number.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_student_info'])) {
    $roll_no = $_POST['roll_no'];
    $mail = $_POST['mail'];
    $dob = $_POST['dob'];
    $father_name = $_POST['father_name'];
    $occupation = $_POST['occupation'];
    $parent_phone = $_POST['parent_phone'];
    $student_phone = $_POST['student_phone'];
    $present_addr = $_POST['present_addr'];
    $permanent_addr = $_POST['permanent_addr'];
    $languages_known = $_POST['languages_known'];
    $school = $_POST['school'];
    $medium = $_POST['medium'];
    $math = $_POST['math'];
    $physic = $_POST['physic'];
    $chemis = $_POST['chemis'];
    $quota = $_POST['quota'];
    $cutoff = $math + $physic + $chemis;

    // Insert additional data into 'student_information' table
    $insert_query = "INSERT INTO student_information (roll_no, mail, dob, father_name, occupation, parent_phone, student_phone, present_addr, permanent_addr, languages_known, school, medium, math, physic, chemis, cutoff, quota) VALUES ('$roll_no', '$mail', '$dob', '$father_name', '$occupation', '$parent_phone', '$student_phone', '$present_addr', '$permanent_addr', '$languages_known', '$school', '$medium', '$math', '$physic', '$chemis', '$cutoff', '$quota')";
    
    if (mysqli_query($conn, $insert_query)) {
        echo "Student data successfully stored.";
    } else {
        echo "Error: " . $insert_query . "<br>" . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://hcaptcha.com/1/api.js" async defer></script>
    <title>Student Data Entry</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-sizing: border-box;
        }
        h2, h3 {
            color: #333;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="email"], input[type="number"], input[type="date"], select, textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #5cb85c;
            color: #fff;
            border: none;
            padding: 15px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }
        input[type="submit"]:hover {
            background-color: #4cae4c;
        }
        table {
            width: 100%;
            max-width: 700px;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .error {
            color: red;
            text-align: center;
        }
        .user-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            max-width: 700px;
            width: 100%;
        }
        .user-details .input-box {
            flex: 1;
            min-width: 45%;
        }
        @media (max-width: 768px) {
            .user-details .input-box {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <h2>Student Data Entry</h2>
    <form method="POST" action="">
        <label for="roll_no">Roll Number</label>
        <input type="text" name="roll_no" id="roll_no" required>
        <input type="submit" name="fetch_student" value="Fetch Student Details">
    </form>

    <?php if (!empty($student_data)): ?>
        <h3>Student Details</h3>
        <table>
            <tr><th>Roll Number</th><td><?php echo htmlspecialchars($student_data['roll_no']); ?></td></tr>
            <tr><th>Register Number</th><td><?php echo htmlspecialchars($student_data['reg_no']); ?></td></tr>
            <tr><th>Name</th><td><?php echo htmlspecialchars($student_data['name']); ?></td></tr>
            <tr><th>Branch</th><td><?php echo htmlspecialchars($student_data['branch']); ?></td></tr>
            <tr><th>Year</th><td><?php echo htmlspecialchars($student_data['year']); ?></td></tr>
            <tr><th>Section</th><td><?php echo htmlspecialchars($student_data['section']); ?></td></tr>
        </table>

        <h3>Additional Information</h3>
        <form method="POST" action="">
            <input type="hidden" name="roll_no" value="<?php echo htmlspecialchars($student_data['roll_no']); ?>">
            <div class="user-details">
                <div class="input-box">
                    <label for="mail">Mail</label>
                    <input type="email" name="mail" id="mail" required>
                </div>
                <div class="input-box">
                    <label for="dob">Date of Birth</label>
                    <input type="date" name="dob" id="dob" required>
                </div>
                <div class="input-box">
                    <label for="father_name">Father's Name</label>
                    <input type="text" name="father_name" id="father_name" required>
                </div>
                <div class="input-box">
                    <label for="occupation">Occupation</label>
                    <input type="text" name="occupation" id="occupation" required>
                </div>
                <div class="input-box">
                    <label for="parent_phone">Parent's Phone</label>
                    <input type="text" name="parent_phone" id="parent_phone" required>
                </div>
                <div class="input-box">
                    <label for="student_phone">Student's Phone</label>
                    <input type="text" name="student_phone" id="student_phone" required>
                </div>
                <div class="input-box">
                    <label for="present_addr">Present Address</label>
                    <textarea name="present_addr" id="present_addr" required></textarea>
                </div>
                <div class="input-box">
                    <label for="permanent_addr">Permanent Address</label>
                    <textarea name="permanent_addr" id="permanent_addr" required></textarea>
                </div>
                <div class="input-box">
                    <label for="languages_known">Languages Known</label>
                    <input type="text" name="languages_known" id="languages_known" required>
                </div>
                <div class="input-box">
                    <label for="school">School</label>
                    <input type="text" name="school" id="school" required>
                </div>
                <div class="input-box">
                    <label for="medium">Medium</label>
                    <input type="text" name="medium" id="medium" required>
                </div>
                <div class="input-box">
                    <label for="math">Math</label>
                    <input type="number" name="math" id="math" required>
                </div>
                <div class="input-box">
                    <label for="physic">Physics</label>
                    <input type="number" name="physic" id="physic" required>
                </div>
                <div class="input-box">
                    <label for="chemis">Chemistry</label>
                    <input type="number" name="chemis" id="chemis" required>
                </div>
                <div class="input-box">
                    <label for="quota">Quota</label>
                    <select name="quota" id="quota" required>
                        <option value="management">Management</option>
                        <option value="counselling">Counselling</option>
                    </select>
                </div>
            </div>
            <div class="h-captcha" data-sitekey="<?php echo $config['HCAPTCHA_SITE_KEY']; ?>"></div>
            <input type="submit" name="submit_student_info" value="Submit">
        </form>
    <?php elseif ($student_data_error): ?>
        <p class="error"><?php echo $student_data_error; ?></p>
    <?php endif; ?>
</body>
</html>
