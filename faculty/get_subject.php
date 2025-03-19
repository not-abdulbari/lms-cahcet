<?php
include 'db_connect.php';

if (isset($_POST['branch']) && isset($_POST['semester'])) {
    $branch = $_POST['branch'];
    $semester = $_POST['semester'];

    // Fetch subjects sorted alphabetically
    $stmt = $conn->prepare("SELECT DISTINCT subject FROM marks WHERE branch = ? AND semester = ? ORDER BY subject ASC");
    
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("ss", $branch, $semester);
    
    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<option value="">Select Subject</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($row['subject']) . '">' . htmlspecialchars($row['subject']) . '</option>';
        }
    } else {
        echo '<option value="">No subjects found</option>';
    }

    $stmt->close();
} else {
    echo '<option value="">Invalid request</option>';
}
?>
