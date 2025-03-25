<?php
    $host = '{{DB_HOST}}';
    $db = '{{DB_NAME}}';
    $user = '{{DB_USER}}';
    $pass = '{{DB_PASS}}';

    $conn = new mysqli($host, $user, $pass, $db);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>
