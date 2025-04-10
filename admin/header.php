<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Candidate Portal</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="admin-header">
    <div class="logo">
        <img src="assets/images/logo.png" alt="Company Logo">
    </div>
    <div class="admin-info">
        <span class="admin-name"><?php echo $admin_name; ?></span>
        <a href="logout.php" class="logout-button">Logout</a>
    </div>
</header>
<?php 
$admin_name = $_SESSION['admin_name'];
?>