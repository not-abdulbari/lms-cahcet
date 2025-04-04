<?php
include 'db_connect.php';

// Get the parameters from the POST request
$branch = isset($_POST['branch']) ? trim($_POST['branch']) : '';
$year = isset($_POST['year']) ? trim($_POST['year']) : '';
$section = isset($_POST['section']) ? trim($_POST['section']) : '';
$semester = isset($_POST['semester']) ? trim($_POST['semester']) : '';

// Initialize an empty array to hold the subjects
$subjects = [];

// Validate that all required parameters are provided
if (!empty($branch) && !empty($year) && !empty($section) && !empty($semester)) {
    // SQL query to fetch subjects for the selected parameters
    $query = "SELECT DISTINCT subject FROM marks WHERE branch = ? AND year = ? AND section = ? AND semester = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $branch, $year, $section, $semester);

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
    echo json_encode(["error" => "Branch, year, section, and semester are required."]);
}

// Close the connection
$conn->close();
?>
