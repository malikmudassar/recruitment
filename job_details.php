<?php

include 'db.php';


if (!isset($_GET['job_id'])) {
    echo "Job ID not provided.";
    exit();
}

$job_id = $_GET['job_id'];

// Fetch job details
$stmt = $conn->prepare("SELECT * FROM Jobs WHERE job_id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo "Job not found.";
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title><?= htmlspecialchars($job['title']) ?> - Job Details</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }

        .main-content {
            flex: 1;
        }

        /* Topbar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #e4e9ee;
            padding: 1rem 2rem;
            color: #212020;
        }

        .topbar .logo img {
            height: 40px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
        }

        .notification-icon {
            position: relative;
            margin-right: 1.5rem;
            cursor: pointer;
        }

        .notification-icon .badge {
            position: absolute;
            top: -5px;
            right: -10px;
            background-color: #e74c3c;
            color: #fff;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.8rem;
        }

        .user-dropdown {
            position: relative;
            cursor: pointer;
        }

        .user-dropdown .username {
            font-weight: bold;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            z-index: 1;
        }

        .dropdown-content a {
            display: block;
            padding: 0.5rem 1rem;
            color: #333;
            text-decoration: none;
        }

        .dropdown-content a:hover {
            background-color: #f4f4f9;
        }

        .user-dropdown:hover .dropdown-content {
            display: block;
        }

        #button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px
        }

        .main-heading {
            color: #3498db;
            text-align: center;
        }

        .position-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 600px;
            margin: 2rem auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .position-card p {
            margin: 0.5rem 0;
            color: #666;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 1rem;
            background-color: #2c3e50;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="topbar">
        <div class="logo">
            <img src="assets/images/logo.svg" alt="Company Logo">
        </div>
        <div class="topbar-right">
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </div>
            
        </div>
    </div>

    <div class="main-content">
        <h1 class="main-heading"><?= htmlspecialchars($job['title']) ?></h1>
        <section>
            <div class="position-card">
                <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($job['description'])) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($job['location']) ?></p>
                <p><strong>Salary:</strong> <?= htmlspecialchars($job['salary_package']) ?></p>
                <a id='button' href="apply.php?job_id=<?= $job['job_id'] ?>">Apply Now</a>
            </div>
        </section>
    </div>

    <footer>
        © 2025 Your Company. All rights reserved.
    </footer>
</body>

</html>
