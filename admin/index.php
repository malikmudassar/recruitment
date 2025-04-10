<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection

$admin_name = $_SESSION['admin_name'];

// Fetch counts from the database
$candidates_count = $conn->query("SELECT COUNT(*) FROM Candidates")->fetchColumn();
$categories_count = $conn->query("SELECT COUNT(*) FROM TestCategories")->fetchColumn();
$tests_count = $conn->query("SELECT COUNT(*) FROM Tests")->fetchColumn();
$passed_candidates_count = $conn->query("SELECT COUNT(DISTINCT candidate_id) FROM TestResults WHERE score >= 70")->fetchColumn(); // Assuming 70% is the passing score
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Candidate Portal</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidenav.php'; ?>

    <div class="main-content">
        <h1>Welcome, <?php echo $admin_name; ?>!</h1>
        <p>This is the admin dashboard. Here's a quick overview of the system.</p>

        <div class="cards-container">
            <!-- Candidates Card -->
            <div class="card card-blue">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <h2>Candidates</h2>
                    <p><?php echo $candidates_count; ?></p>
                </div>
            </div>

            <!-- Categories Card -->
            <div class="card card-green">
                <div class="card-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="card-content">
                    <h2>Categories</h2>
                    <p><?php echo $categories_count; ?></p>
                </div>
            </div>

            <!-- Tests Card -->
            <div class="card card-orange">
                <div class="card-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="card-content">
                    <h2>Tests</h2>
                    <p><?php echo $tests_count; ?></p>
                </div>
            </div>

            <!-- Passed Candidates Card -->
            <div class="card card-purple">
                <div class="card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-content">
                    <h2>Passed Candidates</h2>
                    <p><?php echo $passed_candidates_count; ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
<style>
    /* General Styles */
body {
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f9;
    color: #333;
}

/* Main Content */
.main-content {
    margin-left: 250px; /* Same as sidenav width */
    padding: 2rem;
}

.main-content h1 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.main-content p {
    color: #666;
    margin-bottom: 2rem;
}

/* Cards Container */
.cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

/* Card Styles */
.card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.card-icon {
    font-size: 2rem;
    margin-right: 1.5rem;
}

.card-content h2 {
    font-size: 1.25rem;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.card-content p {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
    margin: 0;
}

/* Card Colors */
.card-blue {
    background-color: #3498db;
    color: #fff;
}

.card-blue .card-content h2,
.card-blue .card-content p {
    color: #fff;
}

.card-green {
    background-color: #2ecc71;
    color: #fff;
}

.card-green .card-content h2,
.card-green .card-content p {
    color: #fff;
}

.card-orange {
    background-color: #e67e22;
    color: #fff;
}

.card-orange .card-content h2,
.card-orange .card-content p {
    color: #fff;
}

.card-purple {
    background-color: #9b59b6;
    color: #fff;
}

.card-purple .card-content h2,
.card-purple .card-content p {
    color: #fff;
}
</style>