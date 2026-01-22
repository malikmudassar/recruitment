<?php


// Include database connection
require '../db.php';

// Get job reference from URL
$job_reference = isset($_GET['job_reference']) ? trim($_GET['job_reference']) : '';
$questions = [];
$error = '';

if (empty($job_reference)) {
    $error = "No job reference provided.";
} else {
    try {
        // Fetch questions for the specified job reference
        $stmt = $conn->prepare("SELECT question_text, prep_time, answer_time, `order` FROM interviewquestions WHERE title = ? ORDER BY `order`");
        $stmt->execute([$job_reference]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Questions for <?php echo htmlspecialchars($job_reference); ?> - Test Guerrilla</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2. Secondary

System: .2.19/dist/tailwind.min.css" rel="stylesheet">
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
        .question-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .question-card:hover {
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
            text-decoration: none;
        }
        .btn-primary:hover {
            background: #6d28d9;
            transform: translateY(-1px);
        }
        .alert-error {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            color: #991b1b;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
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
    
    <div class="wrapper">
       
        <div class="main-content">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Questions for <?php echo htmlspecialchars($job_reference); ?></h1>
            
            <?php if ($error): ?>
                <div class="alert-error">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php elseif (empty($questions)): ?>
                <div class="alert-error">
                    <p>No questions found for this job reference.</p>
                </div>
            <?php else: ?>
                <div class="max-w-3xl mx-auto">
                    <?php foreach ($questions as $question): ?>
                        <div class="question-card">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Question <?php echo htmlspecialchars($question['order']); ?></h3>
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($question['question_text']); ?></p>
                            <div class="flex space-x-4">
                                <div>
                                    <span class="font-semibold text-gray-700">Prep Time:</span>
                                    <span class="text-gray-600"><?php echo htmlspecialchars($question['prep_time']); ?> seconds</span>
                                </div>
                                <div>
                                    <span class="font-semibold text-gray-700">Answer Time:</span>
                                    <span class="text-gray-600"><?php echo htmlspecialchars($question['answer_time']); ?> seconds</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-8 max-w-3xl mx-auto">
                <a href="reference_link.php" class="btn-primary">Back to Job References</a>
            </div>
        </div>
    </div>
</body>
</html>