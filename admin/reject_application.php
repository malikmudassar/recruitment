<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'C:/xampp/htdocs/recruitment/PHPMailer-master/src/Exception.php';
require 'C:/xampp/htdocs/recruitment/PHPMailer-master/src/PHPMailer.php';
require 'C:/xampp/htdocs/recruitment/PHPMailer-master/src/SMTP.php';

// Ensure output is sent immediately
// ob_start();
// echo "Debug: Script started at " . date('Y-m-d H:i:s') . "<br>";

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    // echo "Debug: Admin not logged in, redirecting to login.php<br>";
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection
// echo "Debug: Database connection included<br>";

// Verify PDO connection
if (!$conn) {
    // echo "Debug: Database connection failed<br>";
    exit();
} else {
    // echo "Debug: Database connection successful<br>";
}

// Ensure PDO throws exceptions
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$response = ['success' => false, 'message' => ''];

// Get job_id from the URL
$job_id = isset($_GET['job_id']) ? filter_var($_GET['job_id'], FILTER_VALIDATE_INT) : 0;
// echo "Debug: job_id = $job_id<br>";

if (isset($_GET['id'])) {
    $application_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    // echo "Debug: application_id = $application_id<br>";

    if ($application_id === false || $application_id <= 0) {
        $response['message'] = 'Invalid application ID.';
        // echo "Debug: Invalid application ID<br>";
    } else {
        try {
            // Verify if application_id exists
            $check_stmt = $conn->prepare("SELECT application_id FROM applications WHERE application_id = :application_id");
            $check_stmt->bindParam(':application_id', $application_id, PDO::PARAM_INT);
            $check_stmt->execute();
            $exists = $check_stmt->fetch(PDO::FETCH_ASSOC);
            // echo "Debug: Application ID exists: " . ($exists ? 'Yes' : 'No') . "<br>";

            // Fetch candidate details
            $stmt = $conn->prepare("
                SELECT a.email, a.name, j.title 
                FROM applications a 
                JOIN jobs j ON a.job_id = j.job_id 
                WHERE a.application_id = :application_id
            ");
            $stmt->bindParam(':application_id', $application_id, PDO::PARAM_INT);
            $stmt->execute();
            $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

            // echo "Debug: Candidate query executed. Rows found: " . ($candidate ? 1 : 0) . "<br>";
            if ($candidate) {
                // echo "Debug: Candidate found - Email: {$candidate['email']}, Full Name: {$candidate['full_name']}, Job Title: {$candidate['title']}<br>";
            } else {
                // echo "Debug: No candidate found for application_id = $application_id<br>";
            }

            if ($candidate) {
                $email = $candidate['email'];
                $full_name = $candidate['name'];
                $job_title = $candidate['title'];

                // Update application status to 'rejected'
                $stmt = $conn->prepare("UPDATE applications SET status = 'Rejected' WHERE application_id = :application_id");
                $stmt->bindParam(':application_id', $application_id, PDO::PARAM_INT);
                $stmt->execute();
                $rows_affected = $stmt->rowCount();

                // echo "Debug: Update query executed. Rows affected: $rows_affected<br>";

                if ($rows_affected > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Application rejected successfully.';
                    // echo "Debug: Application status updated to 'rejected' for application_id = $application_id<br>";

                    // Send rejection email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'hr.cinergiedigital@gmail.com';
                        $mail->Password = 'ieym tfmr huqa auhg';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port = 465;

                        $mail->setFrom('hr.cinergiedigital@gmail.com', 'HR Team');
                        $mail->addAddress($email, $full_name);

                        $mail->isHTML(true);
                        $mail->Subject = "Application Update for $job_title";
                        $mail->Body = "<p>Dear $full_name,</p>
                                       <p>Thank you for your interest in joining Cinergie Digital and for taking the time to apply for the <strong>$job_title</strong> position.</p>
                                       <p>After reviewing your profile, we found that your experience does not fully align with the requirements for this particular role. However, we truly appreciate your effort and the interest you’ve shown in our organization.</p>
                                       <p>We will keep your profile on record and reach out should a position arise that is better suited to your skills and experience.</p>
                                       <p>Wishing you all the best in your career journey.</p>
                                       <p>Warm regards,<br>Cinergie Digital Recruitment Team</p>";

                        $mail->send();
                        error_log("Rejection email sent to $email for $job_title");
                        // echo "Debug: Rejection email sent to $email<br>";
                    } catch (Exception $e) {
                        error_log("Email error: " . $mail->ErrorInfo);
                        $response['message'] .= ' However, failed to send rejection email.';
                        // echo "Debug: Email error: " . $mail->ErrorInfo . "<br>";
                    }
                } else {
                    $response['message'] = 'No application found with the given ID.';
                    // echo "Debug: No rows updated for application_id = $application_id<br>";
                }
            } else {
                $response['message'] = 'No candidate found for this application.';
            }
        } catch (PDOException $e) {
            error_log("Error rejecting application: " . $e->getMessage());
            $response['message'] = 'Failed to reject application. Please try again later.';
            // echo "Debug: PDO Error: " . $e->getMessage() . "<br>";
        }
    }
} else {
    $response['message'] = 'Application ID is required.';
    // echo "Debug: Application ID not provided<br>";
}

// Flush output to ensure it’s displayed
// ob_flush();
// flush();

// Temporarily disable redirect to see output
// echo "Debug: Response: " . json_encode($response) . "<br>";
// $redirect_url = 'candidates.php';
// if ($job_id > 0) {
//     $redirect_url .= '?job_id=' . $job_id;
// }
// echo "Debug: Would redirect to $redirect_url<br>";
// header('Location: ' . $redirect_url);
// exit();

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