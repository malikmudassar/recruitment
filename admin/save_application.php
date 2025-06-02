<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'C:/xampp/htdocs/recruitment/PHPMailer-master/src/Exception.php';
require 'C:/xampp/htdocs/recruitment/PHPMailer-master/src/PHPMailer.php';
require 'C:/xampp/htdocs/recruitment/PHPMailer-master/src/SMTP.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

try {
    include '../db.php';
    if (!$conn) throw new Exception('Database connection failed');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Method not allowed');

    error_log('POST: ' . print_r($_POST, true));
    error_log('FILES: ' . print_r($_FILES, true));

    $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : null;
    $full_name = htmlspecialchars($_POST['fullName'], ENT_QUOTES, 'UTF-8');
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone_no = htmlspecialchars($_POST['phoneNo'], ENT_QUOTES, 'UTF-8');
    $experience = (int)$_POST['experience'];
    $linkedin = htmlspecialchars($_POST['linkedin_profile'], ENT_QUOTES, 'UTF-8');
    $notice_period = htmlspecialchars($_POST['noticePeriod'], ENT_QUOTES, 'UTF-8');
    $salary_package = $_POST['salaryPackage'];
    $screening_questions = json_decode($_POST['screening_questions'], true);
    $reference = htmlspecialchars($_POST['reference'], ENT_QUOTES, 'UTF-8');
    $job_title = htmlspecialchars($_POST['job_title'], ENT_QUOTES, 'UTF-8');

    $required = ['job_id', 'fullName', 'email', 'phoneNo', 'experience', 'linkedin_profile', 'noticePeriod', 'salaryPackage'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) throw new Exception("Missing required field: $field");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email address');
    if (!in_array($salary_package, ['Yes', 'No'])) throw new Exception('Invalid salary package value');

    $stmt = $conn->prepare("SELECT COUNT(*) FROM Applications WHERE email = ? AND job_id = ?");
    $stmt->execute([$email, $job_id]);
    if ($stmt->fetchColumn() > 0) throw new Exception('You have already applied for this job');

    $cv_path = null;
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        if (!is_writable($upload_dir)) throw new Exception('Upload directory is not writable');

        $cv_name = uniqid() . '_' . basename($_FILES['cv']['name']);
        $cv_path = $upload_dir . $cv_name;
        if (!move_uploaded_file($_FILES['cv']['tmp_name'], $cv_path)) throw new Exception('Failed to upload CV');
    } else {
        throw new Exception('CV file is required');
    }

    // $answers = [
    //     ['question' => 'What is your current notice period', 'answer' => $notice_period],
    //     ['question' => 'Do you agree with the salary package', 'answer' => $salary_package],
    // ];
    // $answers = []
    if (is_array($screening_questions)) {
        foreach ($screening_questions as $q) {
            if (isset($q['question']) && isset($q['answer'])) {
                $answer = is_array($q['answer']) ? implode(', ', $q['answer']) : $q['answer'];
                $answers[] = [
                    'question' => htmlspecialchars($q['question'], ENT_QUOTES, 'UTF-8'),
                    'answer' => htmlspecialchars($answer, ENT_QUOTES, 'UTF-8')
                ];
            }
        }
    }
    $answers_json = json_encode($answers);

    $stmt = $conn->prepare("
        INSERT INTO Applications (
            job_id, name, email, phone_no, years_of_experience, linkedin_profile, cv_path, answers, submitted_at, reference, job_title, notice_period, salary_accept 
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ? )
    ");
    $stmt->execute([
        $job_id,
        $full_name,
        $email,
        $phone_no,
        $experience,
        $linkedin,
        $cv_path,
        $answers_json,
        $reference,
        $job_title,
        $notice_period,
        $salary_package
    ]);

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
        if ($salary_package === 'No') {
            // Rejection email if salary package is "No"
            $mail->Subject = "Application Update for $job_title";
            $mail->Body = "<p>Dear $full_name,</p>
                           <p>Thank you for applying to <strong>$job_title</strong>.</p>
                           <p>We regret to inform you that we will not be moving forward with your application, as the salary package does not meet your expectations.</p>
                           <p>We wish you the best in your job search.</p>
                           <p>Best regards,<br>HR Team</p>";
        } else {
            // Confirmation email if salary package is "Yes"
            $mail->Subject = "Your application for $job_title";
            $mail->Body = "<p>Dear $full_name,</p>
                           <p>Thank you for applying to <strong>$job_title</strong>. We've received your application and will be reviewing it shortly.For further information u can contact us on  hr@cinergiedigital.com</p>
                           <p>Best regards,<br>HR Team</p>";
        }

        $mail->send();
        error_log("Email sent to $email: " . ($salary_package === 'No' ? 'Rejection' : 'Confirmation'));
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
    }

    echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
} catch (PDOException $e) {
    error_log('PDOException: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Throwable: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}