<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php'; // Include database connection
$admin_name = $_SESSION['admin_name'];
// Fetch all categories
$stmt = $conn->query("SELECT * FROM testcategories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Admin Panel</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidenav.php'; ?>

    <div class="main-content">
        <h1>Categories</h1>
      
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Offshore Salary</th>
                    <th>offsore Perks</th>
                    <th>Onsite Salary</th>
                    <th>Onsite Perks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo $category['category_id']; ?></td>
                        <td><?php echo $category['category_name']; ?></td>
                        <td><?php echo $category['description']; ?></td>
                        <td><?php echo $category['offshore_salary']; ?></td>
                        <td><?php echo $category['perks']; ?></td>
                        <td><?php echo $category['onsite_salary']; ?></td>
                        <td><?php echo $category['onsite_perks']; ?></td>
                        <td>
                            <a href="edit_category.php?id=<?php echo $category['category_id']; ?>" class="edit-button">Edit</a>
                            <a href="delete_category.php?id=<?php echo $category['category_id']; ?>" class="delete-button" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
