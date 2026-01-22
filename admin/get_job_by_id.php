<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *'); // Allow CORS for testing; restrict in production
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require '../db.php';

if (!isset($_GET['job_id'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'job_id is required'
    ]);
    exit;
}

$jobId = (int) $_GET['job_id'];

try {
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
            j.reference,
            c.category_name
        FROM jobs j
        LEFT JOIN TestCategories c ON j.category_id = c.category_id
        WHERE j.job_id = :job_id AND j.is_archived = 0
        LIMIT 1
    ");

    $stmt->execute(['job_id' => $jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Job not found'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $job
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error'
    ]);
}
