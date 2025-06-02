<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f2ef;
        }

        .sidenav {
            width: 220px;
            height: 100vh;
            background-color: #ffffff;
            border-right: 1px solid #e0e0e0;
            position: fixed;
            top: 0;
            left: 0;
            padding: 16px 0;
            box-sizing: border-box;
            overflow-y: auto;
        }

        .sidenav .admin-avatar {
            display: block;
            width: 40px; /* Reduced icon size */
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
            color: #0a66c2; /* LinkedIn blue */
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
    </style>
</head>
<body>
    <nav class="sidenav">
        <img src="https://cinergiedigital.com/favicon.svg" alt="Admin Avatar" class="admin-avatar">
        <ul>
            <li><a href="index.php">Dashboard</a></li>
 
            <!-- Jobs Sub-Menu -->
            <li class="submenu">
                <a href="javascript:void(0)" class="submenu-toggle">Jobs</a>
                <ul class="submenu-items">
                    <li><a href="add_job.php">Add Job</a></li>
                    <li><a href="jobs.php">List Jobs</a></li>
                </ul>
                  
            </li>
            <!-- Categories Sub-Menu -->
            <li class="submenu">
                <a href="javascript:void(0)" class="submenu-toggle">Categories</a>
                <ul class="submenu-items">
                    <li><a href="add_category.php">Add Category</a></li>
                    <li><a href="categories.php">List Categories</a></li>
                </ul>
                
            </li>
          
         <li><a href="job_reference.php">Candidate cv</a></li> 
          

         <div class="admin-info">
            <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
          </nav>

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