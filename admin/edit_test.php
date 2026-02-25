<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection
$admin_name = $_SESSION['admin_name'];
$error = '';
$success = '';

// Fetch test details
if (isset($_GET['id'])) {
    $test_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM tests WHERE test_id = :test_id");
    $stmt->bindParam(':test_id', $test_id);
    $stmt->execute();
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test) {
        header('Location: tests.php');
        exit();
    }
} else {
    header('Location: tests.php');
    exit();
}

// Fetch jobs for the dropdown
$stmt = $conn->query("SELECT * FROM Jobs");
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = $_POST['job_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];

    if (empty($job_id) || empty($title) || empty($duration)) {
        $error = 'Job, Title, and Duration are required.';
    } else {
        // Update test in the database
        $stmt = $conn->prepare("
            UPDATE tests 
            SET job_id = :job_id, title = :title, description = :description, duration = :duration
            WHERE test_id = :test_id
        ");
        $stmt->bindParam(':job_id', $job_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':duration', $duration);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->execute();

        $success = 'Test updated successfully!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Test - Admin Panel</title>
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

        /* Form Styles */
        .form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-container h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }

        .form-container input, .form-container textarea, .form-container select {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-container button {
            width: 100%;
            padding: 0.75rem;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
        }

        .form-container button:hover {
            background-color: #2980b9;
        }

        .error-message {
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .success-message {
            color: #2ecc71;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidenav.php'; ?>

    <div class="main-content">
        <div class="form-container">
            <h1>Edit Test</h1>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            <form action="edit_test.php?id=<?php echo $test_id; ?>" method="POST">
                <select name="job_id" required>
                    <option value="">Select Job</option>
                    <?php foreach ($jobs as $job): ?>
                        <option value="<?php echo $job['job_id']; ?>" <?php echo ($job['job_id'] == $test['job_id']) ? 'selected' : ''; ?>>
                            <?php echo $job['title']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="title" placeholder="Test Title" value="<?php echo $test['title']; ?>" required>
                <textarea name="description" placeholder="Test Description" rows="4"><?php echo $test['description']; ?></textarea>
                <input type="number" name="duration" placeholder="Duration (Minutes)" value="<?php echo $test['duration']; ?>" required>
                <button type="submit">Update Test</button>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
