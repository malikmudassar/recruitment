<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection

if (isset($_GET['id'])) {
    $job_id = $_GET['id'];

    // Delete job from the database
    $stmt = $conn->prepare("DELETE FROM Jobs WHERE job_id = :job_id");
    $stmt->bindParam(':job_id', $job_id);
    $stmt->execute();
}

header('Location: jobs.php');
exit();
?>