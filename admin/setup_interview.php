<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
require '../db.php';

// Initialize variables
$job_reference = isset($_POST['job_reference']) ? trim($_POST['job_reference']) : '';
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $questions = $_POST['questions'] ?? [];
    $prep_times = $_POST['prep_times'] ?? [];
    $answer_times = $_POST['answer_times'] ?? [];
    
    // Validate input
    if (empty($questions) || empty($job_reference)) {
        $errors[] = "No questions provided or job reference is missing.";
    } else {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Get max order for existing questions with the same job reference
            $stmt = $conn->prepare("SELECT MAX(`order`) as max_order FROM interviewquestions WHERE title = ?");
            $stmt->execute([$job_reference]);
            $max_order = $stmt->fetchColumn() ?: 0;
            
            // Insert each question
            foreach ($questions as $index => $question_text) {
                if (!empty($question_text)) {
                    $prep_time = intval($prep_times[$index] ?? 30);
                    $answer_time = intval($answer_times[$index] ?? 60);
                    $order = $max_order + $index + 1;
                    
                    // Validate times
                    if ($prep_time < 0 || $answer_time < 0) {
                        $errors[] = "Prep time and answer time must be non-negative.";
                        break;
                    }
                    
                    $stmt = $conn->prepare("
                        INSERT INTO interviewquestions 
                        (question_text, prep_time, answer_time, title, `order`) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$question_text, $prep_time, $answer_time, $job_reference, $order]);
                }
            }
            
            if (empty($errors)) {
                $conn->commit();
                $success = "Video interview questions created successfully for " . htmlspecialchars($job_reference) . "!";
            } else {
                $conn->rollBack();
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
            margin: 0;
        }
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: #1e293b;
            color: white;
            position: fixed;
            top: 64px;
            bottom: 0;
            overflow-y: auto;
            transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
        }
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
        }
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 2.5rem;
            background: transparent;
        }
        .question-block {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .question-block:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.12);
        }
        .btn-primary {
            background: #7c3aed;
            color: white;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn-primary:hover {
            background: #6d28d9;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
        input, textarea, select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
            background: #f9fafb;
        }
        input:focus, textarea:focus, select:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
            outline: none;
        }
        label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            font-size: 14px;
        }
        .alert-success, .alert-error {
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .alert-success {
            background: #dcfce7;
            border-left: 4px solid #16a34a;
            color: #166534;
        }
        .alert-error {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            color: #991b1b;
        }
        .remove-question {
            color: #ef4444;
            font-weight: 600;
            transition: color 0.2s;
        }
        .remove-question:hover {
            color: #b91c1c;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 220px;
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="wrapper">
        <?php include 'sidenav.php'; ?>
        <div class="main-content">
            
            
            <?php if ($success): ?>
                <div class="alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="bg-white p-6 rounded-xl shadow-lg max-w-3xl mx-auto">
                <div class="mb-6">
                    <label for="job_reference" class="block">Job Reference</label>
                    <input type="text" name="job_reference" id="job_reference" class="w-full" value="<?php echo htmlspecialchars($job_reference); ?>" required placeholder="Enter job title or reference">
                </div>

                <?php if (!empty($job_reference)): ?>
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Questions for <?php echo htmlspecialchars($job_reference); ?></h2>
                <?php endif; ?>

                <div id="questions-container">
                    <div class="question-block">
                        <div class="mb-4">
                            <label class="block">Question 1</label>
                            <textarea name="questions[]" class="w-full" rows="4" required placeholder="Type your question here"></textarea>
                        </div>
                        <div class="flex space-x-4">
                            <div class="w-1/2">
                                <label class="block">Prep Time (seconds)</label>
                                <input type="number" name="prep_times[]" class="w-full" value="30" min="0" required>
                            </div>
                            <div class="w-1/2">
                                <label class="block">Answer Time (seconds)</label>
                                <input type="number" name="answer_times[]" class="w-full" value="60" min="0" required>
                            </div>
                        </div>
                        <button type="button" class="remove-question mt-3">Remove Question</button>
                    </div>
                </div>
                
                <button type="button" id="add-question" class="btn-primary mt-6">Add New Question</button>
                
                <div class="flex space-x-4 mt-8">
                    <button type="submit" class="btn-primary">Save Questions</button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='dashboard.php'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let questionCount = 1;
        
        document.getElementById('add-question').addEventListener('click', () => {
            questionCount++;
            const container = document.getElementById('questions-container');
            const newQuestion = document.createElement('div');
            newQuestion.className = 'question-block';
            newQuestion.innerHTML = `
                <div class="mb-4">
                    <label class="block">Question ${questionCount}</label>
                    <textarea name="questions[]" class="w-full" rows="4" required placeholder="Type your question here"></textarea>
                </div>
                <div class="flex space-x-4">
                    <div class="w-1/2">
                        <label class="block">Prep Time (seconds)</label>
                        <input type="number" name="prep_times[]" class="w-full" value="30" min="0" required>
                    </div>
                    <div class="w-1/2">
                        <label class="block">Answer Time (seconds)</label>
                        <input type="number" name="answer_times[]" class="w-full" value="60" min="0" required>
                    </div>
                </div>
                <button type="button" class="remove-question mt-3">Remove Question</button>
            `;
            container.appendChild(newQuestion);
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-question')) {
                if (document.querySelectorAll('.question-block').length > 1) {
                    e.target.closest('.question-block').remove();
                    questionCount--;
                    document.querySelectorAll('.question-block').forEach((block, index) => {
                        block.querySelector('label').textContent = `Question ${index + 1}`;
                    });
                }
            }
        });
    </script>
</body>
</html>