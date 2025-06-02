<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *'); // Allow CORS for testing; restrict in production
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require '../db.php';

try {
    // Prepare and execute query to fetch jobs with category names and screening questions
    $stmt = $conn->prepare("
        SELECT 
            j.job_id,
            j.title,
            j.description,
            j.requirements,
            j.salary_package,
            j.perks,
            j.location,
            j.created_at,
            j.screening_questions,
            j.is_archived,
            j.reference,
            c.category_name
        FROM jobs j
        LEFT JOIN TestCategories c ON j.category_id = c.category_id
          WHERE j.is_archived = 0
        ORDER BY j.created_at DESC
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response
    $response = [
        'status' => 'success',
        'data' => $jobs,
        'message' => 'Jobs retrieved successfully'
    ];

    // Output JSON
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
} catch (PDOException $e) {
    // Handle database errors
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'data' => [],
        'message' => 'Database error: Unable to fetch jobs: ' . $e->getMessage()
    ]);
    error_log("Database error: " . $e->getMessage());
} catch (JsonException $e) {
    // Handle JSON encoding errors
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'data' => [],
        'message' => 'Error encoding response: ' . $e->getMessage()
    ]);
    error_log("JSON error: " . $e->getMessage());
}
?>