<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection

$response = ['success' => false, 'message' => ''];

// Get job_id from the URL
$job_id = isset($_GET['job_id']) ? filter_var($_GET['job_id'], FILTER_VALIDATE_INT) : 0;

if (isset($_GET['id'])) {
    $application_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($application_id === false || $application_id <= 0) {
        $response['message'] = 'Invalid application ID.';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE applications SET status = 'Maybe' WHERE application_id = :application_id");
            $stmt->bindParam(':application_id', $application_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Application status May be successfully.';
            } else {
                $response['message'] = 'No application found with the given ID.';
            }
        } catch (PDOException $e) {
            error_log("Error status May be application: " . $e->getMessage());
            $response['message'] = 'Failed to accept application. Please try again later.';
        }
    }
} else {
    $response['message'] = 'Application ID is required.';
}

// If this is an AJAX request, return JSON
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Otherwise, redirect with a message and preserve job_id
$_SESSION['message'] = $response['message'];
$redirect_url = 'candidates.php';
if ($job_id > 0) {
    $redirect_url .= '?job_id=' . $job_id;
}
header('Location: ' . $redirect_url);
exit();
?>
