<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Database connection
$admin_name = htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8');

// Fetch jobs
try {
    $stmt = $conn->prepare("
        SELECT 
            job_id,
            title,
            reference,
            created_at
        FROM Jobs
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching jobs: " . $e->getMessage();
    error_log($error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Job References</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
        }
        main {
            margin-left: 250px;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 1px 6px rgba(0,0,0,0.1);
        }
        h1 {
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
            color: #1a202c;
        }
        .search-bar {
            margin-bottom: 1rem;
            max-width: 400px;
        }
        .search-bar input {
            width: 100%;
            padding: 0.5rem;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        thead {
            background-color: #edf2f7;
        }
        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem;
        }
        tr:hover {
            background-color: #f1f5f9;
        }
        a.btn-link {
            color: #2563eb;
            text-decoration: none;
        }
        a.btn-link:hover {
            text-decoration: underline;
        }
        .alert-error {
            background-color: #fed7d7;
            color: #c53030;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            main {
                margin-left: 0;
                padding: 1rem;
            }
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead {
                display: none;
            }
            tr {
                margin-bottom: 1rem;
                background: #fff;
                border-radius: 6px;
                box-shadow: 0 1px 4px rgba(0,0,0,0.05);
                padding: 1rem;
            }
            td {
                border: none;
                padding: 0.5rem 0;
                display: flex;
                justify-content: space-between;
            }
            td::before {
                font-weight: bold;
                content: attr(data-label);
                flex-basis: 40%;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidenav.php'; ?>

    <main>
        <div class="container">
            <div class="card">
                <h1>Job References</h1>

                <?php if (isset($error)): ?>
                    <div class="alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search by title or reference ID">
                </div>

                <table id="jobTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Reference ID</th>
                            <th>Date Created</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jobs)): ?>
                            <tr><td colspan="4">No jobs found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($jobs as $job): ?>
                                <tr data-search="<?php echo strtolower(htmlspecialchars($job['title'] . ' ' . $job['reference'], ENT_QUOTES, 'UTF-8')); ?>">
                                    <td data-label="Title"><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td data-label="Reference"><?php echo htmlspecialchars($job['reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td data-label="Created At"><?php echo htmlspecialchars(date("d M Y", strtotime($job['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td data-label="View">
                                        <a class="btn-link" href="candidates.php?job_id=<?php echo urlencode($job['job_id']); ?>">View Candidates</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script>
        // Search Functionality
        document.getElementById('searchInput').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#jobTable tbody tr');

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                row.style.display = searchData.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
