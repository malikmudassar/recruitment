<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php'; // Include database connection

$admin_name = $_SESSION['admin_name'];

// Fetch counts from the database
$candidates_count = $conn->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$categories_count = $conn->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$tests_count = $conn->query("SELECT COUNT(*) FROM Tests")->fetchColumn();
$passed_candidates_count = $conn->query("SELECT COUNT(DISTINCT candidate_id) FROM TestResults WHERE score >= 70")->fetchColumn(); // Assuming 70% is the passing score
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cinergie Recruiters</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset and General Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f2ef; /* LinkedIn's background gray */
            color: #0a2239; /* Dark text for readability */
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background-color: #ffffff;
            border-bottom: 1px solid #e0e0e0;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .header .logo {
            font-size: 24px;
            font-weight: 700;
            color: #0a66c2; /* LinkedIn blue */
        }

        .header .search-bar {
            background-color: #edf3f8;
            border-radius: 4px;
            padding: 8px 16px;
            width: 300px;
            display: flex;
            align-items: center;
        }

        .header .search-bar input {
            border: none;
            background: none;
            outline: none;
            width: 100%;
            font-size: 14px;
        }

        /* Sidenav Styles */
        .sidenav {
            width: 220px;
            height: calc(100vh - 60px);
            background-color: #ffffff;
            border-right: 1px solid #e0e0e0;
            position: fixed;
            top: 60px;
            left: 0;
            padding: 16px 0;
            box-sizing: border-box;
            overflow-y: auto;
        }

        .sidenav .admin-avatar {
            display: block;
            width: 40px;
            height: 40px;
            margin: 16px auto;
            border-radius: 50%;
            object-fit: contain;
        }

        .sidenav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidenav ul li {
            margin: 4px 0;
        }

        .sidenav ul li a {
            display: flex;
            align-items: center;
            padding: 10px 24px;
            color: #0a66c2;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.2s, color 0.2s;
        }

        .sidenav ul li a:hover {
            background-color: #e8f0fe;
            color: #004182;
        }

        .submenu-toggle {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 24px;
            color: #0a66c2;
            font-size: 14px;
            font-weight: 600;
        }

        .submenu-toggle::after {
            content: '▾';
            font-size: 12px;
            transition: transform 0.2s;
        }

        .submenu.active .submenu-toggle::after {
            transform: rotate(180deg);
        }

        .submenu-items {
            display: none;
            background-color: #f8f9fa;
            padding-left: 16px;
        }

        .submenu.active .submenu-items {
            display: block;
        }

        .submenu-items li a {
            padding: 8px 24px 8px 40px;
            font-weight: 400;
            color: #333;
        }

        .submenu-items li a:hover {
            background-color: #e8f0fe;
            color: #004182;
        }

        .admin-info {
            position: absolute;
            bottom: 16px;
            width: 100%;
            padding: 16px 24px;
            box-sizing: border-box;
            border-top: 1px solid #e0e0e0;
        }

        .admin-info .admin-name {
            display: block;
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
        }

        .admin-info .logout-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: #0a66c2;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .admin-info .logout-button:hover {
            background-color: #004182;
        }
        

        /* Main Content */
        .main-content {
            margin-left: 220px;
            padding: 84px 24px 24px;
            max-width: 1200px;
            margin-right: auto;
        }

        .welcome-section {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .welcome-section img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
        }

        .welcome-section h1 {
            font-size: 24px;
            font-weight: 600;
            color: #0a66c2;
            margin-bottom: 4px;
        }

        .welcome-section p {
            font-size: 16px;
            color: #666;
        }

        /* Cards Container */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        

        /* Card Styles */
        .card {
            background-color: #ffffff;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 16px;
            display: flex;
            align-items: center;
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .card-icon {
            font-size: 24px;
            color: #0a66c2;
            margin-right: 16px;
        }

        .card-content h2 {
            font-size: 16px;
            font-weight: 600;
            color: #0a2239;
            margin-bottom: 8px;
        }

        .card-content p {
            font-size: 24px;
            font-weight: 700;
            color: #0a66c2;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 76px 16px 16px;
            }

            .sidenav {
                width: 100%;
                height: auto;
                position: static;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }

            .welcome-section {
                flex-direction: column;
                text-align: center;
            }

            .welcome-section img {
                margin-bottom: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        
        <div class="logo">Cinergie Recruiters</div>
      
    </header>

    <!-- Sidenav -->
    <nav class="sidenav">
        <img src="https://cinergiedigital.com/favicon.svg" alt="Admin Avatar" class="admin-avatar">
        <ul>
            <li><a href="index.php">Dashboard</a></li>
               
            <li class="submenu">
                <a href="javascript:void(0)" class="submenu-toggle">Jobs</a>
                <ul class="submenu-items">
                    <li><a href="add_job.php">Add Job</a></li>
                    <li><a href="jobs.php">List Jobs</a></li>
                </ul>
               
            </li>
            <li class="submenu">
                <a href="javascript:void(0)" class="submenu-toggle">Categories</a>
                <ul class="submenu-items">
                    <li><a href="add_category.php">Add Category</a></li>
                    <li><a href="categories.php">List Categories</a></li>
                </ul>
            </li>
           <li><a href="job_reference.php">candidate cv</a></li> 
           <li><a href="candidates.php">previous candidates</a></li>  
           
        <div class="admin-info">
            <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="welcome-section">
           
            <div>
                <h1>Welcome, <?php echo htmlspecialchars($admin_name); ?>!</h1>
                <p>At Cinergie Recruiters, you're shaping the future by connecting top talent with exciting opportunities. Let's make impactful hires today!</p>
            </div>
        </div>

        <div class="cards-container">
            <!-- Candidates Card -->
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <h2>Candidates</h2>
                    <p><?php echo $candidates_count; ?></p>
                </div>
            </div>

            <!-- Active Jobs Card -->
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="card-content">
                    <h2>Active Jobs</h2>
                    <p><?php echo $categories_count; ?></p>
                </div>
            </div>

            <!-- Tests Card -->
            
        </div>
    </div>

  

    <script>
        // Toggle sub-menus
        document.querySelectorAll('.submenu-toggle').forEach(toggle => {
            toggle.addEventListener('click', function () {
                const submenu = this.parentElement;
                submenu.classList.toggle('active');
            });
        });
    </script>
</body>
</html>