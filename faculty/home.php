<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <style>
        /* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body Styling */
body {
    font-family: 'Arial', sans-serif;
    margin: 0;
    background-color: #f4f6f9;
    color: #333;
}

/* Content Container */
.content-container {
    margin: 20px;
    padding: 20px;
    background-color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

/* College Info */
.college-info {
    background-color: #2c3e50;
    color: white;
    padding: 15px;
    text-align: center;
    margin-bottom: 20px;
    border-radius: 8px 8px 0 0;
}

.college-info h2 {
    margin: 0;
}

/* College Details */
.college-details {
    font-size: 14px;
    line-height: 1.6;
    text-align: justify;
}

.college-details h3 {
    color: #3498db;
    margin-top: 20px;
}

.college-details p {
    margin-bottom: 10px;
}

/* Responsive Layout */
@media screen and (max-width: 768px) {
    .college-details p {
        font-size: 13px;
    }
}

    </style>
</head>
<body>

    <?php
        session_start();

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../index.php');
        exit;
}
        include 'head.php'; // Including the header with the navigation and banner
    ?>

    <div class="content-container">
        <div class="college-info">
            <h2>C. Abdul Hakeem College of Engineering & Technology</h2>
            <p>Committed to Excellence in Education and Innovation</p>
        </div>

        <div class="college-details">
            <h3>About the College</h3>
            <p>C. Abdul Hakeem College of Engineering & Technology, established in 1994, is one of the premier institutions in the region, offering undergraduate and postgraduate degrees in various engineering disciplines. The college is affiliated with Anna University, Chennai, and has earned a reputation for academic excellence and innovative teaching methods.</p>

            <h3>Our Vision</h3>
            <p>Our vision is to nurture young minds to excel in the field of technology and contribute to the development of society through knowledge, innovation, and leadership.</p>

            <h3>Our Mission</h3>
            <p>To provide a world-class education that empowers students to become leaders in the field of engineering and technology. We aim to bridge the gap between industry and academia through collaborative research, skill development, and practical exposure.</p>

            <h3>Facilities</h3>
            <p>The college is equipped with state-of-the-art infrastructure, including modern classrooms, computer labs, and research facilities. We also provide opportunities for students to engage in extra-curricular activities, sports, and community service.</p>

            <h3>Accreditations</h3>
            <p>Our college is accredited by the National Board of Accreditation (NBA) and recognized by the All India Council for Technical Education (AICTE). We strive to maintain high standards of teaching and learning through continuous improvements in our academic programs and teaching methods.</p>
        </div>
    </div>

</body>
</html>
