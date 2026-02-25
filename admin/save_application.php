<?php
/************************************
 * DEBUG MODE – REMOVE AFTER FIXING
 ************************************/
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php_errors.log');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal PHP error',
            'error'   => $e
        ]);
    }
});

/************************************
 * HEADERS
 ************************************/
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/************************************
 * AWS SDK (S3)
 ************************************/
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/************************************
 * MAILER
 ************************************/
use PHPMailer\PHPMailer\PHPMailer;
// Avoid name conflict with global Exception
use PHPMailer\PHPMailer\Exception as MailException;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

try {
    /************************************
     * DATABASE
     ************************************/
    include '../db.php';
    if (!isset($conn)) {
        throw new \Exception('Database connection variable $conn not found');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new \Exception('Method not allowed');
    }

    error_log('POST DATA: ' . print_r($_POST, true));
    error_log('FILES DATA: ' . print_r($_FILES, true));

    /************************************
     * INPUT SANITIZATION
     ************************************/
    $job_id          = isset($_POST['job_id']) ? (int)$_POST['job_id'] : null;
    $full_name       = htmlspecialchars($_POST['fullName'] ?? '', ENT_QUOTES, 'UTF-8');
    $email           = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone_no        = htmlspecialchars($_POST['phoneNo'] ?? '', ENT_QUOTES, 'UTF-8');
    $experience      = (int)($_POST['experience'] ?? 0);
    $linkedin        = htmlspecialchars($_POST['linkedin_profile'] ?? '', ENT_QUOTES, 'UTF-8');
    $notice_period   = htmlspecialchars($_POST['noticePeriod'] ?? '', ENT_QUOTES, 'UTF-8');
    $salary_package  = $_POST['salaryPackage'] ?? '';
    $reference       = htmlspecialchars($_POST['reference'] ?? '', ENT_QUOTES, 'UTF-8');
    $job_title       = htmlspecialchars($_POST['job_title'] ?? '', ENT_QUOTES, 'UTF-8');

    $screening_questions = json_decode($_POST['screening_questions'] ?? '[]', true);

    /************************************
     * REQUIRED FIELD CHECK
     ************************************/
    $required = [
        'job_id',
        'fullName',
        'email',
        'phoneNo',
        'experience',
        'linkedin_profile',
        'noticePeriod',
        'salaryPackage'
    ];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new \Exception("Missing required field: $field");
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new \Exception('Invalid email address');
    }

    if (!in_array($salary_package, ['Yes', 'No'], true)) {
        throw new \Exception('Invalid salary package value');
    }

    /************************************
     * DUPLICATE APPLICATION CHECK
     ************************************/
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM applications WHERE email = ? AND job_id = ?"
    );
    $stmt->execute([$email, $job_id]);

    if ($stmt->fetchColumn() > 0) {
        throw new \Exception('You have already applied for this job');
    }

    /************************************
     * FILE UPLOAD (S3 instead of local)
     ************************************/
    if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
        throw new \Exception('CV file is required');
    }

    // ---- Configure your bucket + key prefix here ----
    $s3Bucket = 'cinergie-recruitment-bucket';   // <-- CHANGE to your bucket
    $keyPrefix = 'applications/cv/';             // folder-like prefix in S3

    // Optional: validate file type (keep simple)
    $originalName = basename($_FILES['cv']['name']);
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
    $uniqueName = uniqid('cv_', true) . '_' . $safeName;

    // Build S3 object key
    $s3Key = $keyPrefix . $job_id . '/' . $uniqueName;

    // IMPORTANT: Do NOT hardcode keys. SDK will read env vars:
    // AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, (and AWS_SESSION_TOKEN if using temporary credentials)
    // $s3 = new S3Client([
    //     'version' => 'latest',
    //     'region'  => 'me-central-1',
    //     // credentials omitted on purpose (reads env/instance profile)
    // ]);
    $s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'me-central-1',
]);

    try {
        $result = $s3->putObject([
            'Bucket'      => $s3Bucket,
            'Key'         => $s3Key,
            'SourceFile'  => $_FILES['cv']['tmp_name'],
            'ContentType' => $_FILES['cv']['type'] ?: 'application/octet-stream',
            // keep private (recommended). Portal can use signed URLs.
            'ACL'         => 'private',
        ]);

        // Store either the key or the URL. Key is usually best.
        // $cv_path_db = $result['ObjectURL']; // (URL, still private object)
        $cv_path_db = $s3Key;

        error_log('S3 upload success. Key=' . $s3Key);
    } catch (AwsException $e) {
    $awsMsg  = $e->getAwsErrorMessage();
    $awsCode = $e->getAwsErrorCode();
    $status  = $e->getStatusCode();

    error_log("S3 upload failed. HTTP=$status Code=$awsCode Msg=$awsMsg");
    error_log("S3 exception full: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'S3 upload failed',
        'aws' => [
            'http' => $status,
            'code' => $awsCode,
            'msg'  => $awsMsg ?: $e->getMessage(),
        ],
        'version' => '2026-02-10-1358'
    ]);
    exit;
    }

    /************************************
     * SCREENING QUESTIONS
     ************************************/
    $answers = [];

    if (is_array($screening_questions)) {
        foreach ($screening_questions as $q) {
            if (isset($q['question'], $q['answer'])) {
                $answer = is_array($q['answer'])
                    ? implode(', ', $q['answer'])
                    : $q['answer'];

                $answers[] = [
                    'question' => htmlspecialchars($q['question'], ENT_QUOTES, 'UTF-8'),
                    'answer'   => htmlspecialchars($answer, ENT_QUOTES, 'UTF-8')
                ];
            }
        }
    }

    $answers_json = json_encode($answers, JSON_UNESCAPED_UNICODE);

    /************************************
     * DATABASE INSERT
     ************************************/
    $stmt = $conn->prepare("
        INSERT INTO applications (
            job_id,
            name,
            email,
            phone_no,
            years_of_experience,
            linkedin_profile,
            cv_path,
            answers,
            submitted_at,
            reference,
            job_title,
            notice_period,
            salary_accept
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)
    ");

    $stmt->execute([
        $job_id,
        $full_name,
        $email,
        $phone_no,
        $experience,
        $linkedin,
        $cv_path_db,       // now S3 key instead of local path
        $answers_json,
        $reference,
        $job_title,
        $notice_period,
        $salary_package
    ]);

    /************************************
     * EMAIL (unchanged)
     ************************************/
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hr.cinergiedigital@gmail.com';
        $mail->Password   = 'REPLACE_WITH_NEW_APP_PASSWORD';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom('hr.cinergiedigital@gmail.com', 'HR Team');
        $mail->addAddress($email, $full_name);
        $mail->isHTML(true);

        if ($salary_package === 'No') {
            $mail->Subject = "Application Update for $job_title";
            $mail->Body = "
                <p>Dear $full_name,</p>
                <p>Thank you for applying to <strong>$job_title</strong>.</p>
                <p>We regret to inform you that we will not be moving forward as the salary package does not match your expectations.</p>
                <p>Best regards,<br>HR Team</p>
            ";
        } else {
            $mail->Subject = "Your application for $job_title";
            $mail->Body = "
                <p>Dear $full_name,</p>
                <p>Thank you for applying to <strong>$job_title</strong>.</p>
                <p>We have received your application and will review it shortly.</p>
                <p>Best regards,<br>HR Team</p>
            ";
        }

        $mail->send();
        error_log("Email sent to $email");
    } catch (MailException $e) {
        error_log('Email error: ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully',
        'cv'      => $cv_path_db
    ]);

} catch (\PDOException $e) {
    error_log('PDOException: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (\Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log('Throwable: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
