<nav class="sidenav">
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

        <!-- Candidates Sub-Menu -->
        <li class="submenu">
            <a href="javascript:void(0)" class="submenu-toggle">Candidates</a>
            <ul class="submenu-items">
                <li><a href="add_candidate.php">Add Candidate</a></li>
                <li><a href="candidates.php">List Candidates</a></li>
            </ul>
        </li>

        <!-- Tests Sub-Menu -->
        <li class="submenu">
            <a href="javascript:void(0)" class="submenu-toggle">Tests</a>
            <ul class="submenu-items">
                <li><a href="add_test.php">Add Test</a></li>
                <li><a href="tests.php">List Tests</a></li>
            </ul>
        </li>

        <!-- Tests Sub-Menu -->
        <li class="submenu">
            <a href="javascript:void(0)" class="submenu-toggle">Questions</a>
            <ul class="submenu-items">
                <li><a href="add_questiont.php">Add Question</a></li>
                <li><a href="questions.php">List Questions</a></li>
            </ul>
        </li>
    </ul>
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