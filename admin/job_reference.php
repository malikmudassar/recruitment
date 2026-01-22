<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8');

try {
    $stmt = $conn->prepare("
        SELECT 
            j.job_id,
            j.title,
            j.reference,
            j.location,
            j.created_at,
            j.is_archived,
            a.name AS hr_name,
            COUNT(c.candidate_id) AS total_candidates
        FROM jobs j
        JOIN admins a ON a.admin_id = j.hr_id
        LEFT JOIN candidates c ON c.job_id = j.job_id
        GROUP BY j.job_id
        ORDER BY j.created_at DESC
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching jobs";
    error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Jobs</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f9;
}
main {
    margin-left: 250px;
    padding: 2rem;
}
.card {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}
th {
    background: #2563eb;
    color: #fff;
}
tr:hover {
    background: #f1f5f9;
}
.status-active {
    color: #16a34a;
    font-weight: bold;
}
.status-archived {
    color: #dc2626;
    font-weight: bold;
}
.action a {
    margin-right: 8px;
    text-decoration: none;
    font-size: 14px;
}
.edit { color: #2563eb; }
.delete { color: #dc2626; }
.archive { color: #ea580c; }
.view { color: #059669; }

@media (max-width: 768px) {
    main { margin-left: 0; }
    table, thead, tbody, th, td, tr { display: block; }
    thead { display: none; }
    tr {
        margin-bottom: 1rem;
        background: #fff;
        padding: 1rem;
        border-radius: 6px;
    }
    td {
        display: flex;
        justify-content: space-between;
    }
    td::before {
        content: attr(data-label);
        font-weight: bold;
    }
}
</style>
</head>

<body>

<?php include 'header.php'; ?>
<?php include 'sidenav.php'; ?>

<main>
<div class="card">
    <h2>Jobs Management</h2>

    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Reference</th>
                <th>HR</th>
                <th>Location</th>
                <th>Date & Time</th>
                <th>Status</th>
                <th>Candidates</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($jobs)): ?>
            <tr><td colspan="8">No jobs found</td></tr>
        <?php else: ?>
            <?php foreach ($jobs as $job): ?>
            <tr>
                <td data-label="Title"><?php echo htmlspecialchars($job['title']); ?></td>
                <td data-label="Reference"><?php echo htmlspecialchars($job['reference']); ?></td>
                <td data-label="HR"><?php echo htmlspecialchars($job['hr_name']); ?></td>
                <td data-label="Location"><?php echo htmlspecialchars($job['location']); ?></td>
                <td data-label="Created">
                    <?php echo date("d M Y, h:i A", strtotime($job['created_at'])); ?>
                </td>
                <td data-label="Status" class="<?php echo $job['is_archived'] ? 'status-archived' : 'status-active'; ?>">
                    <?php echo $job['is_archived'] ? 'Archived' : 'Active'; ?>
                </td>
                <td data-label="Candidates">
                    <?php echo $job['total_candidates']; ?>
                    <br>
                    <a class="view" href="candidates.php?job_id=<?php echo $job['job_id']; ?>">View</a>
                </td>
                <td data-label="Actions" class="action">
                    <a class="edit" href="edit_job.php?id=<?php echo $job['job_id']; ?>">Edit</a>
                    <a class="delete" href="delete_job.php?id=<?php echo $job['job_id']; ?>"
                       onclick="return confirm('Delete this job?');">Delete</a>
                    <a class="archive" href="toggle_archive.php?id=<?php echo $job['job_id']; ?>"
                       onclick="return confirm('Are you sure?');">
                       <?php echo $job['is_archived'] ? 'Unarchive' : 'Archive'; ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</main>

<?php include 'footer.php'; ?>
</body>
</html>
