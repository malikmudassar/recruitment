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

    
}
    $categories = $conn->query("SELECT * FROM testcategories")->fetchAll();
    // print_r($categories);exit;
?>
<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="form-container">
        <div class="login-logo">
            <img src="assets/images/logo.png" alt="Cinergie Digital">
        </div>
        <!-- Login Form -->
        <div id="login-form">
            <h2>Login</h2>
            <form action="login.php" method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
            <div class="login-logo">
                <p>Don't have an account? <a href="#" id="show-register">Register</a></p>
            </div>
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
                        <option value="<?php echo $category['category_id']; ?>"><?php echo $category['category_name'].' '.$category['description']; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Register</button>
            </form>
            <div class="login-logo">
                <p>Already have an account? <a href="#" id="show-login">Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<style>
    .login-logo {
        text-align: center;
        margin-bottom: 2rem;
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