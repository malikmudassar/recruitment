<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['candidate_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php'; // Include database connection

$candidate_id = $_SESSION['candidate_id'];
$candidate_name = $_SESSION['name'];
$error = '';
$success = '';

// Handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch candidate's current password from the database
    $stmt = $conn->prepare("SELECT password FROM Candidates WHERE candidate_id = :candidate_id");
    $stmt->bindParam(':candidate_id', $candidate_id);
    $stmt->execute();
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($candidate && password_verify($current_password, $candidate['password'])) {
        if ($new_password === $confirm_password) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Update the password in the database
            $update_stmt = $conn->prepare("UPDATE Candidates SET password = :password WHERE candidate_id = :candidate_id");
            $update_stmt->bindParam(':password', $hashed_password);
            $update_stmt->bindParam(':candidate_id', $candidate_id);
            $update_stmt->execute();

            $success = "Password updated successfully!";
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Candidate Portal</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
                <span class="badge">3</span> <!-- Notification count -->
            </div>
            <div class="user-dropdown">
                <span class="username"><?php echo $candidate_name; ?></span>
                <div class="dropdown-content">
                    <a href="profile.php">Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="profile-container">
            <h1>Profile</h1>
            <p>Welcome, <?php echo $_SESSION['name']; ?>!</p>

            <!-- Password Change Form -->
            <form action="profile.php" method="POST">
                <h2>Change Password</h2>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                <input type="password" name="current_password" placeholder="Current Password" required>
                <input type="password" name="new_password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                <button type="submit">Update Password</button>
            </form>
        </div>
    </div>
    

    <?php include 'includes/footer.php'; ?>
</body>
</html>

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
/* Profile Container */
.profile-container {
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.profile-container h1 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.profile-container h2 {
    color: #3498db;
    margin-bottom: 1.5rem;
}

.profile-container input {
    width: 100%;
    padding: 0.75rem;
    margin-bottom: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.profile-container button {
    width: 100%;
    padding: 0.75rem;
    background-color: #3498db;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
}

.profile-container button:hover {
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