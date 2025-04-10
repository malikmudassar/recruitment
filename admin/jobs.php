<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection
$admin_name = $_SESSION['admin_name'];
// Fetch all jobs with category names
$stmt = $conn->query("
    SELECT j.job_id, j.title, j.description, j.requirements, j.salary_package, j.perks, j.location, c.category_name 
    FROM Jobs j
    INNER JOIN TestCategories c ON j.category_id = c.category_id
");
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs - Admin Panel</title>
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
        <h1>Jobs</h1>
        <a href="add_job.php" class="add-button">Add New Job</a>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Salary Package</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><?php echo $job['job_id']; ?></td>
                        <td><?php echo $job['title']; ?></td>
                        <td><?php echo $job['category_name']; ?></td>
                        <td><?php echo $job['location']; ?></td>
                        <td><?php echo $job['salary_package']; ?></td>
                        <td>
                            <a href="edit_job.php?id=<?php echo $job['job_id']; ?>" class="edit-button">Edit</a>
                            <a href="delete_job.php?id=<?php echo $job['job_id']; ?>" class="delete-button" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>