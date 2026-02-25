<?php
session_start();

// Load db.php from parent or current directory
$db_path = file_exists('../db.php') ? '../db.php' : 'db.php';
if (!file_exists($db_path)) {
    die("Error: Database configuration file (db.php) not found in parent or current directory.");
}
require $db_path;

$error = '';
$success = '';
$job = null;
$screening_questions = [];

// Get job_id from GET parameter
$job_id = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);
if (!$job_id) {
    $error = 'Invalid job ID.';
} else {
    // Fetch job details and screening questions
    try {
        $stmt = $conn->prepare("SELECT title, screening_questions FROM Jobs WHERE job_id = :job_id");
        $stmt->execute([':job_id' => $job_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$job) {
            $error = 'Job not found.';
        } else {
            $screening_questions = $job['screening_questions'] ? json_decode($job['screening_questions'], true) : [];
        }
    } catch (PDOException $e) {
        $error = 'Error fetching job details.';
        error_log("Error fetching job: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $answers = filter_input(INPUT_POST, 'answers', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? [];
    $notice_period = trim(filter_input(INPUT_POST, 'notice_period', FILTER_SANITIZE_SPECIAL_CHARS));
    $salary_ok = trim(filter_input(INPUT_POST, 'salary_ok', FILTER_SANITIZE_SPECIAL_CHARS));

    // Validate inputs
    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Name and valid email are required.';
    } elseif (empty($notice_period) || empty($salary_ok)) {
        $error = 'Please answer all questions.';
    } elseif (!isset($_FILES['cv']) || $_FILES['cv']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'CV upload is required.';
    } else {
        // Validate CV upload
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $cv = $_FILES['cv'];
        if ($cv['error'] !== UPLOAD_ERR_OK) {
            $error = 'Error uploading CV.';
        } elseif (!in_array($cv['type'], $allowed_types)) {
            $error = 'CV must be a PDF or Word document.';
        } elseif ($cv['size'] > $max_size) {
            $error = 'CV file size must be under 5MB.';
        } else {
            // Handle CV upload
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $cv_filename = uniqid('cv_') . '_' . basename($cv['name']);
            $cv_path = $upload_dir . $cv_filename;

            if (!move_uploaded_file($cv['tmp_name'], $cv_path)) {
                $error = 'Failed to save CV.';
            } else {
                try {
                    $conn->beginTransaction();

                    // Prepare answers as JSON
                    $all_answers = [];
                    foreach ($screening_questions as $index => $q) {
                        if (isset($answers[$index]) && !empty($answers[$index])) {
                            $all_answers[] = [
                                'question' => $q['question_text'],
                                'answer' => trim($answers[$index])
                            ];
                        }
                    }
                    $all_answers[] = [
                        'question' => 'What is your current notice period?',
                        'answer' => $notice_period
                    ];
                    $all_answers[] = [
                        'question' => 'Are you ok with salary?',
                        'answer' => $salary_ok
                    ];
                    $answers_json = json_encode($all_answers, JSON_UNESCAPED_UNICODE);

                    // Insert application
                    $stmt = $conn->prepare("
                        INSERT INTO Applications (job_id, name, email, cv_path, answers, submitted_at)
                        VALUES (:job_id, :name, :email, :cv_path, :answers, NOW())
                    ");
                    $stmt->execute([
                        ':job_id' => $job_id,
                        ':name' => $name,
                        ':email' => $email,
                        ':cv_path' => $cv_path,
                        ':answers' => $answers_json
                    ]);

                    $conn->commit();
                    $success = 'Application submitted successfully!';
                    $_POST = [];
                    $answers = [];
                    $notice_period = '';
                    $salary_ok = '';
                } catch (PDOException $e) {
                    $conn->rollBack();
                    unlink($cv_path); // Remove uploaded file on error
                    $error = 'Error submitting application: ' . $e->getMessage();
                    error_log("Error saving application: " . $e->getMessage());
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Apply for a job at Cinergie Digital">
    <title>Apply for Job - Cinergie Digital</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Inter', sans-serif;
        }

        header {
            background-color: #2563eb;
            color: white;
            padding: 1rem;
        }
        header .header-content {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header .header-content h1 {
            font-size: 1.5rem;
            font-weight: bold;
        }
        header .header-content nav a {
            color: white;
            margin-left: 1rem;
            text-decoration: none;
        }
        header .header-content nav a:hover {
            text-decoration: underline;
        }

        main {
            flex: 1;
            padding: 1.5rem;
        }
        main .form-container {
            max-width: 768px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            padding: 2rem;
        }
        main .form-container h1 {
            font-size: 1.875rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }

        .question-block {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .question-block label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .question-block input[type="text"],
        .question-block input[type="email"],
        .question-block textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .question-block input[type="text"]:focus,
        .question-block input[type="email"]:focus,
        .question-block textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .question-block input[type="radio"] {
            accent-color: #2563eb;
            margin-right: 0.5rem;
        }
        .question-block input[type="file"] {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .alert.error {
            background-color: #fef2f2;
            color: #dc2626;
        }
        .alert.success {
            background-color: #f0fdf4;
            color: #16a34a;
        }
        .alert span {
            font-weight: 500;
        }

        button[type="submit"] {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            background-color: #2563eb;
            color: white;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        button[type="submit"]:hover {
            background-color: #1d4ed8;
        }
        button[type="submit"]:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }

        footer {
            background-color: #1f2937;
            color: white;
            padding: 1rem;
            text-align: center;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <main>
        <div class="form-container">
            <h1>Apply for <?php echo $job ? htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') : 'Job'; ?></h1>

            <?php if ($error): ?>
                <div class="alert error" role="alert">
                    <span>Error:</span> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert success" role="alert">
                    <span>Success:</span> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($job && !$success): ?>
                <form action="apply.php?job_id=<?php echo $job_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="question-block">
                        <label for="name">Full Name <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                            placeholder="e.g., John Doe"
                            required
                        >
                    </div>

                    <div class="question-block">
                        <label for="email">Email <span class="text-red-500">*</span></label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                            placeholder="e.g., john.doe@example.com"
                            required
                        >
                    </div>

                    <div class="question-block">
                        <label for="cv">Upload CV (PDF or Word) <span class="text-red-500">*</span></label>
                        <input
                            type="file"
                            id="cv"
                            name="cv"
                            accept=".pdf,.doc,.docx"
                            required
                        >
                    </div>

                    <?php if (!empty($screening_questions)): ?>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Screening Questions</h2>
                            <?php foreach ($screening_questions as $index => $question): ?>
                                <div class="question-block">
                                    <label><?php echo htmlspecialchars($question['question_text'], ENT_QUOTES, 'UTF-8'); ?> <span class="text-red-500">*</span></label>
                                    <?php if ($question['answer_type'] === 'MCQ' && !empty($question['options'])): ?>
                                        <?php foreach ($question['options'] as $option): ?>
                                            <div class="mt-2">
                                                <label class="inline-flex items-center">
                                                    <input
                                                        type="radio"
                                                        name="answers[<?php echo $index; ?>]"
                                                        value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"
                                                        required
                                                    >
                                                    <span class="ml-2"><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php elseif ($question['answer_type'] === 'Yes/No'): ?>
                                        <div class="mt-2">
                                            <label class="inline-flex items-center">
                                                <input
                                                    type="radio"
                                                    name="answers[<?php echo $index; ?>]"
                                                    value="Yes"
                                                    required
                                                >
                                                <span class="ml-2">Yes</span>
                                            </label>
                                            <label class="inline-flex items-center ml-6">
                                                <input
                                                    type="radio"
                                                    name="answers[<?php echo $index; ?>]"
                                                    value="No"
                                                    required
                                                >
                                                <span class="ml-2">No</span>
                                            </label>
                                        </div>
                                    <?php else: // Not Related (Text Response) ?>
                                        <textarea
                                            name="answers[<?php echo $index; ?>]"
                                            rows="4"
                                            placeholder="Enter your response"
                                            required
                                        ><?php echo isset($_POST['answers'][$index]) ? htmlspecialchars($_POST['answers'][$index], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Additional Questions</h2>
                        <div class="question-block">
                            <label for="notice_period">What is your current notice period? <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                id="notice_period"
                                name="notice_period"
                                value="<?php echo isset($_POST['notice_period']) ? htmlspecialchars($_POST['notice_period'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                placeholder="e.g., 30 days"
                                required
                            >
                        </div>
                        <div class="question-block">
                            <label>Are you ok with salary? <span class="text-red-500">*</span></label>
                            <div class="mt-2">
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        name="salary_ok"
                                        value="Yes"
                                        required
                                    >
                                    <span class="ml-2">Yes</span>
                                </label>
                                <label class="inline-flex items-center ml-6">
                                    <input
                                        type="radio"
                                        name="salary_ok"
                                        value="No"
                                        required
                                    >
                                    <span class="ml-2">No</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit">Submit Application</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>© <?php echo date('Y'); ?> Cinergie Digital. All rights reserved.</p>
    </footer>
</body>
</html>