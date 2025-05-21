<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection
$admin_name = htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8');

// Fetch all jobs with reference IDs
try {
    $stmt = $conn->prepare("
        SELECT 
            job_id,
            title,
            reference
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job References - Admin Panel</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #F6F6F6;
            color: #333333;
            margin: 0;
            min-height: 100vh;
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
            background: #FFFFFF;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
        }
        .actions-bar {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .search-bar {
            flex: 1;
            max-width: 400px;
        }
        .search-bar input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #8696A7;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        .job-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .job-card {
            background: #FFFFFF;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 1rem;
            width: 250px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        .job-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
            color: #333333;
        }
        .job-card p {
            font-size: 0.875rem;
            margin: 0;
            color: #555555;
        }
        .btn-link {
            color: #0A66C2;
            text-decoration: none;
        }
        .btn-link:hover {
            text-decoration: underline;
        }
        .alert-error {
            background: #FEE2E2;
            color: #D32F2F;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            main {
                margin-left: 0;
                padding: 1rem;
            }
            .actions-bar {
                flex-direction: column;
                gap: 1rem;
            }
            .search-bar {
                max-width: 100%;
            }
            .job-card {
                width: 100%;
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
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Job References</h1>
                    <span class="text-gray-600 text-sm">Welcome, <?php echo htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert-error" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <!-- Actions Bar -->
                <div class="actions-bar">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search by job title or reference ID" aria-label="Search jobs">
                    </div>
                </div>

                <!-- Job List -->
                <div id="jobList" class="job-list">
                    <?php if (empty($jobs)): ?>
                        <p class="text-center text-gray-500">No jobs found.</p>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                            <a href="candidates.php?job_id=<?php echo htmlspecialchars($job['job_id'], ENT_QUOTES, 'UTF-8'); ?>" class="job-card btn-link" data-search="<?php echo htmlspecialchars(strtolower($job['title'] . ' ' . $job['reference']), ENT_QUOTES, 'UTF-8'); ?>">
                                <h3><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><strong>Reference ID:</strong> <?php echo htmlspecialchars($job['reference'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script>
        // Search Functionality
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase().trim();
            const jobCards = document.querySelectorAll('.job-card');

            jobCards.forEach(card => {
                const searchData = card.dataset.search || '';
                card.style.display = searchTerm === '' || searchData.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>