<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
include '../db.php';
$admin_name = htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8');

// Basic counts
$candidates_count = $conn->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$jobs_count = $conn->query("SELECT COUNT(*) FROM jobs")->fetchColumn();

// Check columns in tables
$applications_columns = $conn->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_COLUMN);
$jobs_columns = $conn->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);

// Weekly applications
if (in_array('application_date', $applications_columns)) {
    $weekly_applications = $conn->query("SELECT COUNT(*) FROM applications WHERE application_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
} elseif (in_array('created_at', $applications_columns)) {
    $weekly_applications = $conn->query("SELECT COUNT(*) FROM applications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
} else {
    $weekly_applications = $candidates_count;
    error_log("No date column found in applications table for weekly count");
}

// Monthly jobs
if (in_array('posted_date', $jobs_columns)) {
    $monthly_jobs = $conn->query("SELECT COUNT(*) FROM jobs WHERE posted_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
} elseif (in_array('created_at', $jobs_columns)) {
    $monthly_jobs = $conn->query("SELECT COUNT(*) FROM jobs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
} else {
    $monthly_jobs = $jobs_count;
    error_log("No date column found in jobs table for monthly count");
}

// Active jobs
if (in_array('is_archived', $jobs_columns)) {
    $active_jobs = $conn->query("SELECT COUNT(*) FROM jobs WHERE is_archived = 0")->fetchColumn();
    error_log("Active jobs count (is_archived = 0): $active_jobs");
} elseif (in_array('status', $jobs_columns)) {
    $active_jobs = $conn->query("SELECT COUNT(*) FROM jobs WHERE status = 'active'")->fetchColumn();
    error_log("Active jobs count (status = 'active'): $active_jobs");
} else {
    $active_jobs = $jobs_count;
    error_log("No status or is_archived column in jobs table, defaulting to total jobs: $active_jobs");
}

// Recent applications
$recent_applications = $weekly_applications;

// Pending interviews
try {
    $pending_interviews = $conn->query("SELECT COUNT(*) FROM interviews WHERE status = 'scheduled'")->fetchColumn();
} catch (PDOException $e) {
    $pending_interviews = 0;
    error_log("Error fetching pending interviews: " . $e->getMessage());
}

// Hired candidates
if (in_array('status', $applications_columns)) {
    $hired_candidates = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'hired'")->fetchColumn();
} else {
    $hired_candidates = 0;
    error_log("No status column in applications table for hired candidates");
}

// Top 5 jobs
try {
    $popular_jobs = $conn->query("SELECT j.job_title, COUNT(a.id) as application_count 
                                 FROM jobs j LEFT JOIN applications a ON j.id = a.job_id 
                                 GROUP BY j.id ORDER BY application_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $popular_jobs = [];
    error_log("Error fetching popular jobs: " . $e->getMessage());
}

// Application trends
$application_trends = [];
if (in_array('application_date', $applications_columns)) {
    $application_trends = $conn->query("SELECT DATE_FORMAT(application_date, '%Y-%m-%d') as day, 
                                       COUNT(*) as count 
                                       FROM applications 
                                       WHERE application_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                                       GROUP BY day ORDER BY day")->fetchAll(PDO::FETCH_ASSOC);
} elseif (in_array('created_at', $applications_columns)) {
    $application_trends = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as day, 
                                       COUNT(*) as count 
                                       FROM applications 
                                       WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                                       GROUP BY day ORDER BY day")->fetchAll(PDO::FETCH_ASSOC);
}

// Prepare chart data
$chart_labels = [];
$chart_data = [];
foreach ($application_trends as $trend) {
    $chart_labels[] = date('M j', strtotime($trend['day']));
    $chart_data[] = $trend['count'];
}
if (empty($chart_labels)) {
    for ($i = 30; $i >= 0; $i--) {
        $chart_labels[] = date('M j', strtotime("-$i days"));
        $chart_data[] = rand(0, 5);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recruiter Dashboard - Cinergie</title>
    <link rel="icon" href="https://cinergiedigital.com/favicon.svg" type="image/svg+xml">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #0056b3;
            --secondary-color: #ffc107;
            --accent-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        .dark-mode {
            background-color: #121212 !important;
            color: #e0e0e0 !important;
        }
        .sidebar {
            height: 100vh;
            background-color: #ffffff;
            padding: 1rem;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
        }
        .dark-mode .sidebar {
            background-color: #1e1e1e;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
        }
        .main {
            margin-left: 250px;
            padding: 2rem;
        }
        .card {
            background: white;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        .dark-mode .card {
            background: #2d2d2d;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .stat-card {
            border-left: 4px solid var(--primary-color);
        }
        .stat-card .icon {
            font-size: 1.8rem;
            color: var(--primary-color);
        }
        .dark-mode .stat-card .icon {
            color: var(--secondary-color);
        }
        .welcome-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .welcome-header::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
        }
        .dark-mode .welcome-header {
            background: linear-gradient(135deg, #1a3a6a, #0d4d6e);
        }
        .welcome-msg {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .welcome-sub {
            opacity: 0.9;
            font-weight: 300;
        }
        .toggle-btn {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: rgba(255,255,255,0.2);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
        }
        .toggle-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .job-list {
            list-style-type: none;
            padding: 0;
        }
        .job-list li {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dark-mode .job-list li {
            border-bottom: 1px solid #444;
        }
        .job-list li:last-child {
            border-bottom: none;
        }
        .job-list li:hover {
            background: rgba(0,0,0,0.03);
        }
        .dark-mode .job-list li:hover {
            background: rgba(255,255,255,0.05);
        }
        .badge-count {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .dark-mode .badge-count {
            background-color: var(--secondary-color);
            color: var(--dark-color);
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dark-mode .card-header {
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .card-header .view-all {
            font-size: 0.85rem;
            color: var(--primary-color);
            text-decoration: none;
        }
        .dark-mode .card-header .view-all {
            color: var(--secondary-color);
        }
        .progress-thin {
            height: 6px;
            border-radius: 3px;
        }
        .progress-bar {
            background-color: var(--primary-color);
        }
        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px dashed #eee;
        }
        .dark-mode .activity-item {
            border-bottom: 1px dashed #444;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-time {
            font-size: 0.75rem;
            color: #6c757d;
        }
        @media (max-width: 992px) {
            .main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<?php include 'sidenav.php'; ?>
<div class="main">
    <div class="container-fluid">
        <!-- Welcome Header -->
        <div class="welcome-header">
            <button class="toggle-btn" id="darkModeToggle">
                <i class="fas fa-moon"></i>
            </button>
            <h1 class="welcome-msg">Recruitment Portal, <?php echo htmlspecialchars($admin_name); ?></h1>
            <p class="welcome-sub">Here's what's happening with your recruitment pipeline today</p>
        </div>
        <!-- Stats Cards Row -->
        <div class="row">
            <!-- Total Jobs -->
            <div class="col-md-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">TOTAL JOBS</h6>
                                <h3 class="mb-0"><?php echo $jobs_count; ?></h3>
                                <small class="text-success"><i class="fas fa-arrow-up"></i> <?php echo $monthly_jobs; ?> this month</small>
                            </div>
                            <div class="icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Total Candidates -->
            <div class="col-md-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">TOTAL CANDIDATES</h6>
                                <h3 class="mb-0"><?php echo $candidates_count; ?></h3>
                                <small class="text-success"><i class="fas fa-arrow-up"></i> <?php echo $weekly_applications; ?> this week</small>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Active Jobs -->
            <div class="col-md-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">ACTIVE JOBS</h6>
                                <h3 class="mb-0"><?php echo $active_jobs; ?></h3>
                                <small class="text-muted">Currently recruiting</small>
                            </div>
                            <div class="icon">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;
    if (localStorage.getItem('darkMode') === 'enabled') {
        body.classList.add('dark-mode');
        darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    }
    darkModeToggle.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        if (body.classList.contains('dark-mode')) {
            localStorage.setItem('darkMode', 'enabled');
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            localStorage.setItem('darkMode', 'disabled');
            darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        }
    });
</script>
</body>
</html>
