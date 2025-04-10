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

// Fetch categories for dropdown
$stmt = $conn->query("SELECT * FROM TestCategories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $salary_package = $_POST['salary_package'];
    $perks = $_POST['perks'];
    $location = $_POST['location'];

    if (empty($title) || empty($category_id)) {
        $error = 'Title and Category are required.';
    } else {
        // Insert new job into the database
        $stmt = $conn->prepare("
            INSERT INTO Jobs (category_id, title, description, requirements, salary_package, perks, location)
            VALUES (:category_id, :title, :description, :requirements, :salary_package, :perks, :location)
        ");
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':requirements', $requirements);
        $stmt->bindParam(':salary_package', $salary_package);
        $stmt->bindParam(':perks', $perks);
        $stmt->bindParam(':location', $location);
        $stmt->execute();

        $success = 'Job added successfully!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Job - Admin Panel</title>
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
            <h1>Add New Job</h1>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            <form action="add_job.php" method="POST">
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>"><?php echo $category['category_name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="title" placeholder="Job Title" required>
                <textarea name="description" placeholder="Job Description" rows="4"></textarea>
                <textarea name="requirements" placeholder="Requirements" rows="4"></textarea>
                <input type="text" name="salary_package" placeholder="Salary Package">
                <textarea name="perks" placeholder="Perks" rows="4"></textarea>
                <select name="location" required>
                    <option value="">Select Location</option>
                    <option value="Dubai">Dubai</option>
                    <option value="Lahore">Lahore</option>
                </select>
                <button type="submit">Add Job</button>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>