<?php
session_start();
include 'db.php'; // Include database connection

$error = '';
$success = '';

// Fetch categories for the dropdown
$stmt = $conn->query("SELECT * FROM TestCategories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $category_id = $_POST['category_id'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($category_id)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM Candidates WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $existing_candidate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_candidate) {
            $error = 'Email is already registered.';
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert new candidate into the database
            $stmt = $conn->prepare("
                INSERT INTO Candidates (name, email, password, category_id, created_at)
                VALUES (:name, :email, :password, :category_id, NOW())
            ");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->execute();

            $success = 'Registration successful! You can now <a href="/">login</a>.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Candidate Portal</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="container">
        <div class="form-container">
            <div class="login-box">
                <div class="login-logo">
                    <img src="assets/images/logo.png" alt="Company Logo">
                    <h1>Register</h1>
                </div>
                <div class="login-form">
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="success-message"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <form action="register.php" method="POST">
                        <input type="text" name="name" placeholder="Full Name" required>
                        <input type="email" name="email" placeholder="Email" required>
                        <input type="password" name="password" placeholder="Password" required>
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"><?php echo $category['category_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Register</button>
                    </form>
                    <p>Already have an account? <a href="/">Login</a></p>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>
<style>
    /* Error and Success Messages */
    .error-message {
        color: #e74c3c;
        margin-bottom: 1rem;
        padding: 0.75rem;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 4px;
    }

    .success-message {
        color: #2ecc71;
        margin-bottom: 1rem;
        padding: 0.75rem;
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        border-radius: 4px;
    }

    .success-message a {
        color: #155724;
        text-decoration: underline;
    }
    .form-container {
        background: #fff;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
    }
    select {
        width: 100%;
        padding: 0.75rem;
        margin-bottom: 1rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }
</style>