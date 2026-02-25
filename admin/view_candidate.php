<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection
$admin_name = $_SESSION['admin_name'];
// Fetch candidate details
if (isset($_GET['id'])) {
    $candidate_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM candidates WHERE candidate_id = :candidate_id");
    $stmt->bindParam(':candidate_id', $candidate_id);
    $stmt->execute();
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidate) {
        header('Location: candidates.php');
        exit();
    }
} else {
    header('Location: candidates.php');
    exit();
}

// Fetch tests taken by the candidate
$stmt = $conn->prepare("
    SELECT t.title, tr.score, tr.total_questions, tr.correct_answers, tr.started_at, tr.completed_at
    FROM testresults tr
    INNER JOIN tests t ON tr.test_id = t.test_id
    WHERE tr.candidate_id = :candidate_id
");
$stmt->bindParam(':candidate_id', $candidate_id);
$stmt->execute();
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Candidate - Admin Panel</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }

        /* Profile Container */
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-container h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }

        .profile-container p {
            margin: 0.5rem 0;
        }

        /* Tests Table */
        .tests-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        .tests-table th, .tests-table td {
            padding: 0.75rem;
            border: 1px solid #ddd;
            text-align: left;
        }

        .tests-table th {
            background-color: #3498db;
            color: #fff;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidenav.php'; ?>

    <div class="main-content">
        <div class="profile-container">
            <h1>Candidate Profile</h1>
            <p><strong>Name:</strong> <?php echo $candidate['name']; ?></p>
            <p><strong>Email:</strong> <?php echo $candidate['email']; ?></p>
            <p><strong>Registered At:</strong> <?php echo $candidate['created_at']; ?></p>

            <h2>Tests Taken</h2>
            <?php if (count($tests) > 0): ?>
                <table class="tests-table">
                    <thead>
                        <tr>
                            <th>Test Title</th>
                            <th>Score</th>
                            <th>Total Questions</th>
                            <th>Correct Answers</th>
                            <th>Started At</th>
                            <th>Completed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $test): ?>
                            <tr>
                                <td><?php echo $test['title']; ?></td>
                                <td><?php echo $test['score']; ?></td>
                                <td><?php echo $test['total_questions']; ?></td>
                                <td><?php echo $test['correct_answers']; ?></td>
                                <td><?php echo $test['started_at']; ?></td>
                                <td><?php echo $test['completed_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No tests taken yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
