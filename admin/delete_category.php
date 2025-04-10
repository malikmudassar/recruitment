<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php'; // Include database connection

if (isset($_GET['id'])) {
    $category_id = $_GET['id'];

    // Delete category from the database
    $stmt = $conn->prepare("DELETE FROM TestCategories WHERE category_id = :category_id");
    $stmt->bindParam(':category_id', $category_id);
    $stmt->execute();
}

header('Location: categories.php');
exit();
?>