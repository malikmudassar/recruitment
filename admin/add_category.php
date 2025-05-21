<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php'; // Include database connection
$admin_name = $_SESSION['admin_name'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = $_POST['category_name'];
    $description = $_POST['description'];
    $offshore_salary = $_POST['offshore_salary'];
    $onsite_salary = $_POST['onsite_salary'];
    $perks = $_POST['perks'];
    $onsite_perks = $_POST['onsite_perks']; // Use a separate variable for onsite_perks

    if (empty($category_name)) {
        $error = 'Category name is required.';
    } else {
        // Insert new category into the database
        $stmt = $conn->prepare("INSERT INTO TestCategories (category_name, description, offshore_salary, onsite_salary, perks, onsite_perks) VALUES (:category_name, :description, :offshore_salary, :onsite_salary, :perks, :onsite_perks)");
        $stmt->bindParam(':category_name', $category_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':offshore_salary', $offshore_salary);
        $stmt->bindParam(':onsite_salary', $onsite_salary);
        $stmt->bindParam(':perks', $perks);
        $stmt->bindParam(':onsite_perks', $onsite_perks);
        $stmt->execute();

        $success = 'Category added successfully!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category - Admin Panel</title>
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

        .form-container input, .form-container textarea {
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
    
    <?php include 'sidenav.php'; ?>

    <div class="main-content">
        <div class="form-container">
            <h1>Add New Category</h1>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            <form action="add_category.php" method="POST">
                <input type="text" name="category_name" placeholder="Category Name" required>
                <textarea name="description" placeholder="Experience Required" rows="4"></textarea>
                <input type="text" name="offshore_salary" placeholder="Offshore Salary" required>
                <textarea name="perks" placeholder="Offshore Perks" rows="4"></textarea>
                <input type="text" name="onsite_salary" placeholder="Onsite Salary" required>
                <textarea name="onsite_perks" placeholder="Onsite Perks" rows="4"></textarea>

                <button type="submit">Add Category</button>
            </form>
        </div>
    </div>

   
</body>
</html>