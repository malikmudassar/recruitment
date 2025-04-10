<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['candidate_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';

$candidate_id = $_SESSION['candidate_id'];
$candidate_name = $_SESSION['name'];

// Get candidate's category_id
$stmt = $conn->prepare("SELECT category_id FROM Candidates WHERE candidate_id = ?");
$stmt->execute([$candidate_id]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);
$category_id = $candidate['category_id'];

// Fetch open positions matching candidate's category
$open_positions = $conn->prepare("
    SELECT j.job_id, j.title, j.description, j.location, j.salary_package 
    FROM Jobs j
    WHERE j.category_id = ?
    ORDER BY j.created_at DESC
");
$open_positions->execute([$category_id]);
$open_positions = $open_positions->fetchAll(PDO::FETCH_ASSOC);

// Fetch applied positions
$applied_positions = $conn->prepare("
    SELECT j.job_id, j.title, j.description, j.location, j.salary_package
    FROM Jobs j
    INNER JOIN Applications a ON j.job_id = a.job_id
    WHERE a.candidate_id = ?
    ORDER BY a.applied_at DESC
");
$applied_positions->execute([$candidate_id]);
$applied_positions = $applied_positions->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Candidate Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Topbar -->
    <div class="topbar">
        <div class="logo">
            <img src="assets/images/logo.svg" alt="Company Logo">
        </div>
        <div class="topbar-right">
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </div>
            <div class="user-dropdown">
                <span class="username"><?= htmlspecialchars($candidate_name) ?></span>
                <div class="dropdown-content">
                    <a href="profile.php">Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="main-content">
        <div class="dashboard-container">
            <h1>Welcome, <?= htmlspecialchars($candidate_name) ?>!</h1>

            <!-- Open Positions -->
            <section class="open-positions">
                <h2>Open Positions</h2>
                <div class="positions-grid" id="open-positions">
                    <?php foreach ($open_positions as $position): ?>
                    <div class="position-card">
                        <h3><?= htmlspecialchars($position['title']) ?></h3>
                        <p><?= htmlspecialchars($position['description']) ?></p>
                        <p><strong>Location:</strong> <?= htmlspecialchars($position['location']) ?></p>
                        <p><strong>Salary:</strong> <?= htmlspecialchars($position['salary_package']) ?></p>
                        <button onclick="applyForJob(<?= $position['job_id'] ?>)">Apply Now</button>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($open_positions)): ?>
                        <p>No open positions matching your category currently available.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Applied Positions -->
            <section class="applied-positions">
                <h2>Applied Positions</h2>
                <div class="positions-grid" id="applied-positions">
                    <?php foreach ($applied_positions as $position): ?>
                    <div class="position-card">
                        <h3><?= htmlspecialchars($position['title']) ?></h3>
                        <p><?= htmlspecialchars($position['description']) ?></p>
                        <p><strong>Location:</strong> <?= htmlspecialchars($position['location']) ?></p>
                        <p><strong>Salary:</strong> <?= htmlspecialchars($position['salary_package']) ?></p>
                        <button class="applied-btn" disabled>Applied</button>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($applied_positions)): ?>
                        <p>You haven't applied to any positions yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <style>
    /* General Styles */
body {
    margin: 0;
    padding: 0;
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f9;
    color: #333;
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

/* Dashboard Container */
.dashboard-container {
    padding: 2rem;
}

.dashboard-container h1 {
    color: #2c3e50;
}

/* Positions Grid */
.positions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.position-card {
    background-color: #fff;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.position-card h3 {
    margin: 0 0 0.5rem;
    color: #3498db;
}

.position-card p {
    margin: 0.5rem 0;
    color: #666;
}

.position-card button {
    background-color: #3498db;
    color: #fff;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
}

.position-card button:hover {
    background-color: #2980b9;
}

/* Footer */
footer {
    text-align: center;
    padding: 1rem;
    background-color: #2c3e50;
    color: #fff;
    margin-top: 2rem;
}
</style>

    <script>
    function applyForJob(jobId) {
        fetch('apply_job.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `job_id=${jobId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Application submitted successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while applying');
        });
    }
    </script>
</body>
</html>