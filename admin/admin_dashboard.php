<?php
include('db.php');
session_start();

// Only allow admins
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = 'hr'; // force enum-safe value
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        $stmt = $conn->prepare("
            INSERT INTO admins (name, email, password, role, created_at)
            VALUES (:name, :email, :password, :role, :created_at)
        ");

        $stmt->execute([
            ':name'       => $name,
            ':email'      => $email,
            ':password'   => $password,
            ':role'       => $role,
            ':created_at' => $_SESSION['admin_id']
        ]);

        $message = "User created successfully ✅";

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $message = "Email already exists ❌";
        } else {
            $message = "Something went wrong ❌";
        }
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <title>Create HR User</title>
    <style>
        body { font-family: Arial; background: #f4f6f8; }
        .box {
            width: 400px;
            margin: 60px auto;
            padding: 20px;
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        input, select, button {
            width: 95%;
            padding: 10px;
            margin-top: 10px;
        }
        button {
            background: #2563eb;
            color: white;
            border: none;
            cursor: pointer;
        }
        .msg { margin-top: 10px; color: green; }
    </style>
</head>
<body>
  <?php include 'header.php'; ?>
    <?php include 'sidenav.php'; ?>
<div class="box">
    <h2>Create HR User</h2>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="HR Name" required>
        <input type="email" name="email" placeholder="HR Email" required>
        <input type="password" name="password" placeholder="Temporary Password" required>

        <select name="role">
            <option value="HR">HR</option>
            <option value="Senior HR">Admin</option>
        </select>

        <button type="submit">Create User</button>
    </form>
</div>

</body>
</html>
