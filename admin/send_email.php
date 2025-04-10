<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection

if (isset($_GET['id'])) {
    $candidate_id = $_GET['id'];

    // Fetch candidate details
    $stmt = $conn->prepare("SELECT * FROM Candidates WHERE candidate_id = :candidate_id");
    $stmt->bindParam(':candidate_id', $candidate_id);
    $stmt->execute();
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($candidate) {
        // Send email (placeholder logic)
        $to = $candidate['email'];
        $subject = "Interview Invitation";
        $message = "Dear " . $candidate['name'] . ",\n\nYou have been invited for an interview. Please let us know your availability.\n\nBest regards,\nCinergie Digital";
        $headers = "From: no-reply@cinergiedigital.com";

        if (mail($to, $subject, $message, $headers)) {
            $_SESSION['email_success'] = "Email sent successfully to " . $candidate['email'];
        } else {
            $_SESSION['email_error'] = "Failed to send email.";
        }
    }
}

header('Location: candidates.php');
exit();
?>