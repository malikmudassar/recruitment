<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php';

$error = '';
$success = '';

// Fetch question details
if (!isset($_GET['id'])) {
    header('Location: questions.php');
    exit();
}

$question_id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM Questions WHERE question_id = ?");
$stmt->execute([$question_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    header('Location: questions.php');
    exit();
}

// Fetch tests for dropdown
$tests = $conn->query("SELECT * FROM Tests")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_id = $_POST['test_id'];
    $question_text = $_POST['question_text'];

    if (empty($test_id) || empty($question_text)) {
        $error = 'All fields are required';
    } else {
        $stmt = $conn->prepare("UPDATE Questions SET test_id = ?, question_text = ? WHERE question_id = ?");
        if ($stmt->execute([$test_id, $question_text, $question_id])) {
            $success = 'Question updated successfully!';
            // Refresh question data
            $stmt = $conn->prepare("SELECT * FROM Questions WHERE question_id = ?");
            $stmt->execute([$question_id]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to update question';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question - Admin Panel</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <style>
        /* Same styles as add_question.php */
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .success {
            color: #2ecc71;
            margin-bottom: 15px;
        }
        .submit-btn {
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidenav.php'; ?>

    <div class="main-content">
        <div class="form-container">
            <h1>Edit Question</h1>
            
            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="test_id">Test</label>
                    <select name="test_id" id="test_id" required>
                        <option value="">Select Test</option>
                        <?php foreach ($tests as $test): ?>
                            <option value="<?= $test['test_id'] ?>" <?= $test['test_id'] == $question['test_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($test['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="question_text">Question</label>
                    <textarea name="question_text" id="question_text" required><?= htmlspecialchars($question['question_text']) ?></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Update Question</button>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>