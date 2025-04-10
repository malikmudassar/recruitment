<?php
session_start();
include 'db.php'; // Include database connection

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch candidate from database
    $stmt = $conn->prepare("SELECT * FROM Candidates WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($candidate && password_verify($password, $candidate['password'])) {
        // Login successful
        $_SESSION['candidate_id'] = $candidate['candidate_id'];
        $_SESSION['name'] = $candidate['name'];
        header('Location: dashboard.php'); // Redirect to dashboard
        exit();
    } else {
        $error = "Invalid email or password.";
    }

    $categories = $conn->query("SELECT * FROM testcategories")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Candidate Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <!-- Left Column: Login Form -->
        <div class="login-form">
            <h2>Login to Your Account</h2>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
                <p>Don't have an account? <a href="#" id="show-register">Register</a></p>
            </form>
        </div>
        <!-- Register Form -->
        <div id="register-form" style="display: none;">
            <h2>Register</h2>
            <form action="register.php" method="POST">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <SELECT name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Register</button>
            </form>
            <p>Already have an account? <a href="#" id="show-login">Login</a></p>
        </div>
        <!-- Right Column: Background Image -->
        <div class="login-image">
            <img src="assets/images/login-bg.jpg" alt="Login Background">
        </div>
    </div>
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

/* Login Container */
.login-container {
    display: flex;
    height: 100vh;
}

/* Left Column: Login Form */
.login-form {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    background-color: #fff;
}

.login-form h2 {
    margin-bottom: 1.5rem;
    color: #2c3e50;
}

.login-form input {
    width: 100%;
    padding: 0.75rem;
    margin-bottom: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.login-form button {
    width: 105%;
    padding: 0.75rem;
    background-color: #3498db;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
}

.login-form button:hover {
    background-color: #2980b9;
}

.login-form p {
    margin-top: 1rem;
    font-size: 0.9rem;
}

.login-form a {
    color: #3498db;
    text-decoration: none;
}

.login-form a:hover {
    text-decoration: underline;
}

.error-message {
    color: #e74c3c;
    margin-bottom: 1rem;
}

/* Right Column: Background Image */
.login-image {
    flex: 1;
    background-color: #3498db;
    overflow: hidden;
}

.login-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>