<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection
$admin_name = $_SESSION['admin_name'];
// Fetch all tests with job titles
$stmt = $conn->query("
    SELECT t.test_id, t.title, t.description, t.duration, t.created_at, j.title AS job_title
    FROM tests t
    INNER JOIN jobs j ON t.job_id = j.job_id
");
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tests - Admin Panel</title>
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

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        table th, table td {
            padding: 0.75rem;
            border: 1px solid #ddd;
            text-align: left;
        }

        table th {
            background-color: #3498db;
            color: #fff;
        }

        /* Buttons */
        .add-button {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #2ecc71;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .add-button:hover {
            background-color: #27ae60;
        }

        .edit-button {
            color: #3498db;
            text-decoration: none;
        }

        .delete-button {
            color: #e74c3c;
            text-decoration: none;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidenav.php'; ?>

    <div class="main-content">
        <h1>Tests</h1>
        <a href="add_test.php" class="add-button">Add New Test</a>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Job</th>
                    <th>Duration (Minutes)</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tests as $test): ?>
                    <tr>
                        <td><?php echo $test['test_id']; ?></td>
                        <td><?php echo $test['title']; ?></td>
                        <td><?php echo $test['job_title']; ?></td>
                        <td><?php echo $test['duration']; ?></td>
                        <td><?php echo $test['created_at']; ?></td>
                        <td>
                            <a href="edit_test.php?id=<?php echo $test['test_id']; ?>" class="edit-button">Edit</a>
                            <a href="delete_test.php?id=<?php echo $test['test_id']; ?>" class="delete-button" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
