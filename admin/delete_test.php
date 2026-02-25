<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection

if (isset($_GET['id'])) {
    $test_id = $_GET['id'];

    // Delete test from the database
    $stmt = $conn->prepare("DELETE FROM tests WHERE test_id = :test_id");
    $stmt->bindParam(':test_id', $test_id);
    $stmt->execute();
}

header('Location: tests.php');
exit();
?>
