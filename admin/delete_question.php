<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php';

if (isset($_GET['id'])) {
    $question_id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM questions WHERE question_id = ?");
    $stmt->execute([$question_id]);
}

header('Location: questions.php');
exit();
?>
