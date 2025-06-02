<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php';

if (isset($_GET['id'])) {
    $job_id = $_GET['id'];

    // Get current archive status
    $stmt = $conn->prepare("SELECT is_archived FROM Jobs WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($job) {
        $new_status = $job['is_archived'] ? 0 : 1;

        // Update archive status
        $update = $conn->prepare("UPDATE Jobs SET is_archived = ? WHERE job_id = ?");
        $update->execute([$new_status, $job_id]);
    }
}

header('Location: jobs.php');
exit();
