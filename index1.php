<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>CAHCET - Student Management System</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: #333;
        }

        .header {
            width: 100%;
            height: 250px;
        }

        .header img {
            width: 100%;
            height: auto;
        }

        .banner {
            margin-top: 0;
            padding: 0;
            background-color: #003366;
            color: white;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .banner marquee {
            font-size: 16px;
            font-weight: bold;
        }

        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80vh;
        }

        .maintenance-message {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 20px;
            margin: 10px;
        }

        h2 {
            font-size: 22px;
            color: #6a11cb;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="assets/789asdfkl.webp" alt="LMS Portal Image">
    </div>
    <div class="banner">
        <marquee behavior="scroll" direction="left">
            <p>Server is down for maintenance. Please check back later.</p>
        </marquee>
    </div>
    <div class="main-container">
        <div class="maintenance-message">
            <h2>Server is down for maintenance</h2>
            <p>We are currently performing scheduled maintenance. We should be back online shortly.</p>
        </div>
    </div>
</body>

</html>
