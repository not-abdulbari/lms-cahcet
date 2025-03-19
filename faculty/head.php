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
            font-size: 12px; /* Reduced font size for a more compact design */
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

        /* Header Image */
        .header-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        /* Banner */
        .banner {
            background-color: #4caf50; /* Lively green banner */
            color: #ffffff;
            padding: 15px; /* Reduced padding */
            text-align: center;
            border-bottom: 3px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
        }

        .banner:hover {
            background-color: #66bb6a; /* Lighter green on hover */
        }

        /* Date-Time */
        .datetime {
            font-size: 14px; /* Reduced font size */
            margin-top: 5px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        /* Navigation */
        nav {
            background-color: #3f51b5; /* Deep blue for navigation */
            padding: 8px 0; /* Reduced padding */
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
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
            margin-right: 12px; /* Reduced space between links */
            position: relative; /* Needed for dropdown */
        }

        nav ul li a {
            color: #ffffff;
            text-decoration: none;
            padding: 8px 12px; /* Reduced padding */
            display: inline-block;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 4px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-size: 12px; /* Reduced font size */
        }

        nav ul li a:hover {
            background-color: #2c387e; /* Darker blue for hover effect */
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        nav ul li a:active {
            transform: scale(0.98);
        }

        /* Active Links */
        nav ul li a.active {
            background-color: #2c387e; /* Active link color */
            transform: scale(1.05);
        }

        /* Dropdown Menu */
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #3f51b5;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 4px;
            overflow: hidden;
        }

        .dropdown-content a {
            color: #ffffff;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
        }

        .dropdown-content a:hover {
            background-color: #2c387e;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            nav ul {
                flex-direction: column;
                align-items: flex-start;
            }

            nav ul li {
                margin-bottom: 8px; /* Reduced bottom margin */
                margin-right: 0;
            }

            .datetime {
                font-size: 12px; /* Further reduced font size for smaller screens */
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- <div class="header-container">
        <img src="logo.jpg" alt="College Logo" class="header-image">
    </div>
     -->
    <div class="banner">
        <h1>Welcome - C. Abdul Hakeem College of Engineering & Technology</h1>
        <div class="datetime" id="datetime"><?php echo getCurrentDateTime(); ?></div>
    </div>

    <nav>
        <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="faculty_dashboard.php">Marks</a></li>
            <li><a href="attendance_dashboard.php">Attendance</a></li>
            <li><a href="add_grades.php">Grade</a></li>
            <li><a href="add_subject.php">Subject</a></li>
            <li><a href="student_report.php">Students Report</a></li>
            <li class="dropdown">
                <a href="javascript:void(0)">Reports</a>
                <div class="dropdown-content">
                    <a href="report_selection.php">Result Analysis</a>
                    <a href="generate_marksheet.php">Mark List</a>
                    <a href="progress_prelims.php">Progress Report</a>
                    <a href="class_performance.php">Consolidated Result Analysis</a>
                </div>
            </li>
            <li class="dropdown">
                <a href="javascript:void(0)">STUDENT</a>
                <div class="dropdown-content">
                    <a href="parent.php">STUDENT LOGIN</a>
                    <a href="student_profile.php">Under Development</a>
                </div>
            </li>
            <li><a href="logout.php" class="logout-link">Logout</a></li>
        </ul>
    </nav>

    <script>
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

        // Adding 'active' class to the current page link
        const links = document.querySelectorAll('nav ul li a');
        links.forEach(link => {
            if (window.location.href.includes(link.getAttribute('href'))) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>
