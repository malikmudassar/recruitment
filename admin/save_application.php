<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

try {
    // Include database connection
    include '../db.php';
    if (!$conn) {
        error_log('Database connection failed: Connection object is null');
        throw new Exception('Database connection failed');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
        throw new Exception('Method not allowed');
    }

    // Log incoming POST data
    error_log('POST data: ' . print_r($_POST, true));
    error_log('FILES data: ' . print_r($_FILES, true));

    // Access FormData
    $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : null;
    $full_name = isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName'], ENT_QUOTES, 'UTF-8') : null;
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : null;
    $phone_no = isset($_POST['phoneNo']) ? htmlspecialchars($_POST['phoneNo'], ENT_QUOTES, 'UTF-8') : null;
    $experience = isset($_POST['experience']) ? (int)$_POST['experience'] : null;
    $linkedin = isset($_POST['linkedin_profile']) ? htmlspecialchars($_POST['linkedin_profile'], ENT_QUOTES, 'UTF-8') : null;
    $notice_period = isset($_POST['noticePeriod']) ? htmlspecialchars($_POST['noticePeriod'], ENT_QUOTES, 'UTF-8') : null;
    $salary_package = isset($_POST['salaryPackage']) ? $_POST['salaryPackage'] : null;
    $screening_questions = isset($_POST['screening_questions']) ? json_decode($_POST['screening_questions'], true) : [];
    $reference = isset($_POST['reference']) ? htmlspecialchars($_POST['reference'], ENT_QUOTES, 'UTF-8') : null;
    $job_title = isset($_POST['job_title']) ? htmlspecialchars($_POST['job_title'], ENT_QUOTES, 'UTF-8') : null;

    // Validate required fields
    $required_fields = ['job_id', 'fullName', 'email', 'phoneNo', 'experience', 'linkedin_profile', 'noticePeriod', 'salaryPackage'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            error_log("Missing required field: $field");
            throw new Exception("Missing required field: $field");
        }
    }

    if (!$job_id || $job_id <= 0) {
        error_log('Invalid job ID: ' . $job_id);
        throw new Exception('Invalid job ID');
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log('Invalid email address: ' . $email);
        throw new Exception('Invalid email address');
    }

    // Validate salaryPackage
    if (!in_array($salary_package, ['Yes', 'No'])) {
        error_log('Invalid salary package value: ' . $salary_package);
        throw new Exception('Invalid salary package value');
    }

    // Check for duplicate application
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Applications WHERE email = ? AND job_id = ?");
    if (!$stmt) {
        error_log('Failed to prepare duplicate check SQL: ' . $conn->errorInfo()[2]);
        throw new Exception('Failed to prepare duplicate check SQL');
    }
    $stmt->execute([$email, $job_id]);
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        error_log('Duplicate application: Email ' . $email . ' for job_id ' . $job_id);
        throw new Exception('You have already applied for this job');
    }

    // Handle CV file upload
    $cv_path = null;
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                error_log('Failed to create upload directory: ' . $upload_dir);
                throw new Exception('Failed to create upload directory');
            }
        }
        if (!is_writable($upload_dir)) {
            error_log('Upload directory is not writable: ' . $upload_dir);
            throw new Exception('Upload directory is not writable');
        }
        $cv_name = uniqid() . '_' . basename($_FILES['cv']['name']);
        $cv_path = $upload_dir . $cv_name;
        if (!move_uploaded_file($_FILES['cv']['tmp_name'], $cv_path)) {
            error_log('Failed to move uploaded CV file to: ' . $cv_path);
            throw new Exception('Failed to upload CV');
        }
    } else {
        $error = isset($_FILES['cv']['error']) ? $_FILES['cv']['error'] : 'No file provided';
        error_log('CV upload error: ' . $error);
        throw new Exception('CV file is required');
    }

    // Prepare answers JSON
    $answers = [];
    $answers[] = ['question' => 'What is your current notice period', 'answer' => $notice_period];
    $answers[] = ['question' => 'Do you agree with the salary package', 'answer' => $salary_package];

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
    if ($answers_json === false) {
        error_log('Failed to encode answers JSON: ' . json_last_error_msg());
        throw new Exception('Failed to encode answers JSON');
    }

    // Log answers for debugging
    error_log('Answers array: ' . print_r($answers, true));
    error_log('Answers JSON: ' . $answers_json);

    // Insert into database
    $stmt = $conn->prepare("
        INSERT INTO Applications (
            job_id, name, email, phone_no, years_of_experience, linkedin_profile, cv_path, answers, submitted_at, reference, job_title
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
    ");
    if (!$stmt) {
        error_log('Failed to prepare SQL statement: ' . $conn->errorInfo()[2]);
        throw new Exception('Failed to prepare SQL statement');
    }

    $result = $stmt->execute([
        $job_id,
        $full_name,
        $email,
        $phone_no,
        $experience,
        $linkedin,
        $cv_path,
        $answers_json,
        $reference,
        $job_title
    ]);

    if (!$result) {
        error_log('SQL execution failed: ' . $stmt->errorInfo()[2]);
        throw new Exception('Failed to insert into database');
    }

    
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
} catch (PDOException $e) {
    error_log('PDOException: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Cannot add or update a child row') !== false) {
       
        echo json_encode(['success' => false, 'message' => 'This job is no longer available.']);
    } else {
       
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Throwable: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>