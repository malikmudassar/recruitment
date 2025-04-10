<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection
$admin_name = $_SESSION['admin_name'];
// Fetch all candidates
$stmt = $conn->query("
    SELECT c.candidate_id, c.name, c.email, c.created_at, tc.category_name as category 
    FROM Candidates c
    INNER JOIN TestCategories tc ON c.category_id = tc.category_id
");
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Display email success/error messages
if (isset($_SESSION['email_success'])) {
    echo "<script>alert('" . $_SESSION['email_success'] . "');</script>";
    unset($_SESSION['email_success']);
}
if (isset($_SESSION['email_error'])) {
    echo "<script>alert('" . $_SESSION['email_error'] . "');</script>";
    unset($_SESSION['email_error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates - Admin Panel</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        table th, table td {
            padding: 0.75rem;
            border: 1px solid #ddd;
            text-align: left;
        }

        table th {
            background-color: #3498db;
            color: #fff;
        }

        /* Buttons */
        .view-button {
            color: #3498db;
            text-decoration: none;
        }

        .email-button {
            color: #2ecc71;
            text-decoration: none;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidenav.php'; ?>

    <div class="main-content">
        <h1>Candidates</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Registered At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($candidates as $candidate): ?>
                    <tr>
                        <td><?php echo $candidate['candidate_id']; ?></td>
                        <td><?php echo $candidate['category']; ?></td>
                        <td><?php echo $candidate['name']; ?></td>
                        <td><?php echo $candidate['email']; ?></td>
                        <td><?php echo $candidate['created_at']; ?></td>
                        <td>
                            <a href="view_candidate.php?id=<?php echo $candidate['candidate_id']; ?>" class="view-button">View Profile</a>
                            <a href="send_email.php?id=<?php echo $candidate['candidate_id']; ?>" class="email-button">Send Email</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>