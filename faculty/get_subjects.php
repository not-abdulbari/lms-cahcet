<?php
include 'db_connect.php';

// Get the semester and branch from the GET request
$semester = isset($_GET['semester']) ? trim($_GET['semester']) : '';
$branch = isset($_GET['branch']) ? trim($_GET['branch']) : '';

// Initialize an empty array to hold the subjects
$subjects = [];

// Validate that both semester and branch are provided
if (!empty($semester) && !empty($branch)) {
    // SQL query to fetch subjects for the selected semester and branch
    $query = "SELECT subject_code, subject_name FROM subjects WHERE semester = ? AND branch = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $semester, $branch);

    // Execute the query
    if ($stmt->execute()) {
        $result = $stmt->get_result();

        // Fetch subjects into the $subjects array
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }

        // Return the subjects as a JSON response
        echo json_encode($subjects);
    } else {
        // Handle query execution errors
        echo json_encode(["error" => "Failed to fetch subjects."]);
    }

    // Close the statement
    $stmt->close();
} else {
    // If required parameters are missing, return an error
    echo json_encode(["error" => "Semester and branch are required."]);
}

// Close the connection
$conn->close();
?>
