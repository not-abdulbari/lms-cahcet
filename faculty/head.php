<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
if (!function_exists('getCurrentDateTime')) {
    function getCurrentDateTime() {
        return date('Y-m-d H:i:s');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to C. Abdul Hakeem College of Engineering & Technology</title>
<style>
    /* General Reset */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Body Styling */
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f4f6f9;
        color: #333;
        font-size: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Header Container */
    .header-container {
        text-align: center;
        padding: 10px;
        background-color: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        width: 100%;
    }

    /* Banner */
    .banner {
        background-color: #4caf50;
        color: #ffffff;
        padding: 15px;
        text-align: center;
        border-bottom: 3px solid #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Date-Time */
    .datetime {
        font-size: 14px;
        margin-top: 5px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* Navigation */
    nav {
        background-color: #3f51b5;
        text-align: center;
        width: 100%;
        position: relative;
    }

    nav ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
    }

    nav ul li {
        margin-right: 12px;
        position: relative;
    }

    nav ul li a {
        color: #ffffff;
        text-decoration: none;
        padding: 8px 12px;
        display: inline-block;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-radius: 4px;
        font-size: 12px;
    }

    nav ul li a:hover {
        background-color: #2c387e;
    }

    nav ul li a.active {
        background-color: #2c387e;
    }

    /* Dropdown Menu */
    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #3f51b5;
        min-width: 160px;
        z-index: 1;
        border-radius: 4px;
        overflow: hidden;
    }

    .dropdown-content a {
        color: #ffffff;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
    }

    .dropdown-content a:hover {
        background-color: #2c387e;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    /* Hamburger Button */
    .hamburger {
        display: none;
        cursor: pointer;
        color: #ffffff;
        font-size: 24px;
        padding: 8px 12px;
        position: absolute;
        top: 8px;
        left: 10px;
    }

    /* Mobile Navigation */
    .mobile-nav {
        display: none;
        flex-direction: column;
        align-items: center;
        background-color: #3f51b5;
        width: 100%;
        position: absolute;
        top: 50px;
        left: 0;
        z-index: 1000;
    }

    .mobile-nav.active {
        display: flex;
    }

    @media screen and (max-width: 768px) {
        .hamburger {
            display: block;
        }

        nav ul {
            display: none;
        }

        .mobile-nav ul {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .mobile-nav ul li {
            margin: 8px 0;
        }
    }
</style>
</head>
<body>
    <div class="banner">
        <h1>Welcome - C. Abdul Hakeem College of Engineering & Technology</h1>
        <div class="datetime" id="datetime"><?php echo getCurrentDateTime(); ?></div>
    </div>

    <nav>
        <div class="hamburger" onclick="toggleMenu()">☰</div>
        <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="faculty_dashboard.php">Marks</a></li>
            <li><a href="attendance_dashboard.php">Attendance</a></li>
            <li><a href="add_subject.php">Subject</a></li>
            <li><a href="revaluation.php">REVALUATION</a></li>
            <li class="dropdown">
                <a href="javascript:void(0)">Reports</a>
                <div class="dropdown-content">
                    <a href="report_selection.php">Subjectwise Result Analysis</a>
                    <a href="generate_marksheet.php">MarkList</a>
                    <a href="progress_prelims.php">Internal Exam Progress Report</a>
                    <a href="class_performance.php">Consolidated Exam Result Analysis</a>
                    <a href="capa_select.php">CAPA Form</a>
                    <a href="generate_namelist.php">Students Namelist</a>
                    <a href="consolidated_marklist.php">Consolidated Marklist</a>
                    <a href="university_results.php">University Progress Report</a>
                    <a href="parent_feedback.php">PARENT FEEDBACK</a>
                </div>
            </li>
            <li><a href="student_login.php">STUDENT LOGIN</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
        <div class="mobile-nav">
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="faculty_dashboard.php">Marks</a></li>
                <li><a href="attendance_dashboard.php">Attendance</a></li>
                <li><a href="add_subject.php">Subject</a></li>
                <li><a href="revaluation.php">REVALUATION</a></li>
                <li><a href="report_selection.php">Reports</a></li>
                <li><a href="student_login.php">STUDENT LOGIN</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <script>
        function toggleMenu() {
            const mobileNav = document.querySelector('.mobile-nav');
            mobileNav.classList.toggle('active');
        }

        function updateDateTime() {
            const datetimeElement = document.getElementById("datetime");
            const now = new Date();
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            datetimeElement.innerText = now.toLocaleString('en-US', options);
        }

        setInterval(updateDateTime, 1000);
        updateDateTime();

        const links = document.querySelectorAll('nav ul li a');
        links.forEach(link => {
            if (window.location.href.includes(link.getAttribute('href'))) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>
