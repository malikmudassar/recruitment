<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include '../db.php';
$admin_name = htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8');

// Get filter parameters
$notice_filter = isset($_GET['notice']) ? $_GET['notice'] : 'all';
$salary_filter = isset($_GET['salary']) ? strtolower($_GET['salary']) : 'all';
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : 'all';
$job_id_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;

// Define ideal criteria for built-in questions
$ideal_criteria = [
    'max_notice_period' => 30,
    'salary_acceptance' => 'yes'
];

// Function to rate a candidate based on answers
function rateCandidate($notice_period, $salary_ok, $candidate_answers, $screening_questions, $ideal_criteria) {
    $score = 0;
    $max_score = 2;

    if ($notice_period !== null && is_numeric($notice_period) && $notice_period <= $ideal_criteria['max_notice_period']) {
        $score += 1;
    }

    if ($salary_ok !== null && strtolower($salary_ok) === $ideal_criteria['salary_acceptance']) {
        $score += 1;
    }

    $screening_score = 0;
    $must_have_count = 0;
    
    if (!empty($screening_questions) && !empty($candidate_answers)) {
        foreach ($screening_questions as $q) {
            if (isset($q['must_have']) && $q['must_have'] === true) {
                $must_have_count++;
                $expected_answer = isset($q['expected_answer']) ? strtolower(trim($q['expected_answer'])) : '';
                $question_text = isset($q['question']) ? strtolower(trim($q['question'])) : '';
                $question_type = isset($q['type']) ? strtolower(trim($q['type'])) : 'yes/no';

                foreach ($candidate_answers as $answer) {
                    if (stripos($answer['question'], $question_text) !== false) {
                        $candidate_answer = strtolower(trim($answer['answer']));
                        if ($question_type === 'yes/no' && $candidate_answer === $expected_answer) {
                            $screening_score += 1;
                        } elseif ($question_type === 'mcq' && $candidate_answer === $expected_answer) {
                            $screening_score += 1;
                        } elseif ($question_type === 'numerical' && is_numeric($candidate_answer) && is_numeric($expected_answer)) {
                            $num_answer = (float)$candidate_answer;
                            $expected_num = (float)$expected_answer;
                            if ($num_answer == $expected_num) {
                                $screening_score += 1;
                            }
                        }
                        break;
                    }
                }
            }
        }
    }

    $max_score += $must_have_count;
    $percentage = $max_score > 0 ? ($score + $screening_score) / $max_score * 100 : 0;

    if ($percentage >= 80) {
        $rating = '1';
    } elseif ($percentage >= 50) {
        $rating = '2';
    } else {
        $rating = '3';
    }

    return [
        'rating' => $rating,
        'score' => round($score + $screening_score, 2),
        'max_score' => $max_score,
        'percentage' => round($percentage, 2)
    ];
}

// Fetch applications with job titles and screening questions
try {
    $query = "
        SELECT 
            a.application_id,
            a.job_id, 
            a.reference,
            a.job_title,
            j.screening_questions,
            a.name, 
            a.email, 
            a.phone_no, 
            a.years_of_experience, 
            a.linkedin_profile, 
            a.cv_path, 
            a.answers, 
            a.submitted_at
        FROM Applications a
        LEFT JOIN Jobs j ON a.job_id = j.job_id
    ";
    if ($job_id_filter !== null) {
        $query .= " WHERE a.job_id = :job_id";
    }
    $query .= " ORDER BY a.submitted_at DESC";
    
    $stmt = $conn->prepare($query);
    if ($job_id_filter !== null) {
        $stmt->bindParam(':job_id', $job_id_filter, PDO::PARAM_INT);
    }
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($applications as &$app) {
        $app['notice_period'] = null;
        $app['salary_ok'] = null;
        $app['rating'] = '3';
        $app['score'] = 0;
        $app['percentage'] = 0;

        $raw_answers = $app['answers'] ?? '[]';
        $candidate_answers = json_decode($raw_answers, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($candidate_answers)) {
            error_log("Invalid JSON in answers for application ID {$app['application_id']}: " . $raw_answers);
            $candidate_answers = [];
            $app['answers'] = '[]';
        }

        $raw_screening_questions = $app['screening_questions'] ?? '[]';
        $screening_questions = json_decode($raw_screening_questions, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($screening_questions)) {
            error_log("Invalid JSON in screening_questions for job ID {$app['job_id']}: " . $raw_screening_questions);
            $screening_questions = [];
        }

        foreach ($candidate_answers as $answer) {
            if (stripos($answer['question'], 'notice period') !== false) {
                $clean_answer = preg_replace('/[^0-9]/', '', $answer['answer']);
                if (is_numeric($clean_answer)) {
                    $app['notice_period'] = (int)$clean_answer;
                } elseif (in_array(strtolower($answer['answer']), ['yes', 'no'])) {
                    $app['notice_period'] = strtolower($answer['answer']) === 'yes' ? 29 : 31;
                } else {
                    error_log("Unexpected notice period format for application ID {$app['application_id']}: " . $answer['answer']);
                }
            }
            if (stripos($answer['question'], 'salary') !== false) {
                $answer_lower = strtolower(trim($answer['answer']));
                if (preg_match('/^(yes|y|true)$/i', $answer_lower)) {
                    $app['salary_ok'] = 'yes';
                } elseif (preg_match('/^(no|n|false)$/i', $answer_lower)) {
                    $app['salary_ok'] = 'no';
                } else {
                    error_log("Unexpected salary answer format for application ID {$app['application_id']}: " . $answer['answer']);
                }
            }
        }

        $result = rateCandidate(
            $app['notice_period'],
            $app['salary_ok'],
            $candidate_answers,
            $screening_questions,
            $ideal_criteria
        );
        $app['rating'] = $result['rating'];
        $app['score'] = $result['score'];
        $app['max_score'] = $result['max_score'];
        $app['percentage'] = $result['percentage'];
    }
    unset($app);

    usort($applications, function ($a, $b) {
        return $b['percentage'] <=> $a['percentage'];
    });
} catch (PDOException $e) {
    error_log("Error fetching applications: " . $e->getMessage());
    $error = "Error fetching applications: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates - Admin Panel</title>
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
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            background: #FFFFFF;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        .filter-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #333333;
            margin-bottom: 0.5rem;
        }
        .filter-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #8696A7;
            border-radius: 6px;
            font-size: 0.875rem;
            color: #333333;
            background: #FFFFFF;
        }
        .filter-select:focus {
            outline: none;
            border-color: #0A66C2;
            box-shadow: 0 0 0 3px rgba(10, 102, 194, 0.2);
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary {
            background: #0A66C2;
            color: #FFFFFF;
        }
        .btn-primary:hover {
            background: #004182;
        }
        .btn-link {
            color: #0A66C2;
            text-decoration: none;
        }
        .btn-link:hover {
            text-decoration: underline;
        }
        .kanban-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .candidate-card {
            background: #FFFFFF;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 1rem;
            width: 250px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .candidate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        .candidate-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
            color: #333333;
        }
        .candidate-card p {
            font-size: 0.875rem;
            margin: 0;
            color: #555555;
            word-break: break-word;
        }
        .card-menu {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .menu-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            padding: 5px;
        }
        .menu-dropdown {
            display: none;
            position: absolute;
            right: 0;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .menu-dropdown a {
            display: block;
            padding: 8px 12px;
            color: #d32f2f;
            text-decoration: none;
        }
        .menu-dropdown a:hover {
            background-color: #f9f9f9;
        }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.3s ease-in-out;
            z-index: 1000;
        }
        .modal-hidden {
            opacity: 0;
            pointer-events: none;
        }
        .modal-visible {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-content {
            background: #FFFFFF;
            border-radius: 8px;
            padding: 1.5rem;
            width: 100%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333333;
        }
        .modal-body p, .modal-body li {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: #333333;
        }
        .modal-body .rating {
            font-weight: 500;
            color: #0A66C2;
        }
        .modal-body .score {
            font-weight: 500;
            color: #2e7d32;
        }
        .modal-body .percentage {
            font-weight: 500;
            color: #7b1fa2;
        }
        .modal-body .actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .alert-error {
            background: #FEE2E2;
            color: #D32F2F;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            main {
                margin-left: 0;
                padding: 1rem;
            }
            .filter-form {
                flex-direction: column;
            }
            .filter-group {
                min-width: 100%;
            }
            .actions-bar {
                flex-direction: column;
                gap: 1rem;
            }
            .search-bar {
                max-width: 100%;
            }
            .candidate-card {
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
                    <h1 class="text-2xl font-bold text-gray-900">Candidates</h1>
                    <span class="text-gray-600 text-sm">Welcome, <?php echo htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert-success" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert-error" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="actions-bar">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search by reference or job title" aria-label="Search candidates">
                    </div>
                </div>

                <form id="filterForm" method="GET" action="candidates.php" class="filter-form">
                    <?php if ($job_id_filter !== null): ?>
                        <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id_filter, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>
                    <div class="filter-group">
                        <label for="notice" class="filter-label">Notice Period</label>
                        <select id="notice" name="notice" class="filter-select">
                            <option value="all" <?php echo $notice_filter === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="below30" <?php echo $notice_filter === 'below30' ? 'selected' : ''; ?>>30 days or less</option>
                            <option value="above30" <?php echo $notice_filter === 'above30' ? 'selected' : ''; ?>>More than 30 days</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="salary" class="filter-label">Salary Acceptance</label>
                        <select id="salary" name="salary" class="filter-select">
                            <option value="all" <?php echo $salary_filter === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="yes" <?php echo $salary_filter === 'yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="no" <?php echo $salary_filter === 'no' ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="rating" class="filter-label">Rating</label>
                        <select id="rating" name="rating" class="filter-select">
                            <option value="all" <?php echo $rating_filter === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="1" <?php echo $rating_filter === '1' ? 'selected' : ''; ?>>Excellent (1)</option>
                            <option value="2" <?php echo $rating_filter === '2' ? 'selected' : ''; ?>>Average (2)</option>
                            <option value="3" <?php echo $rating_filter === '3' ? 'selected' : ''; ?>>Poor (3)</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                    </div>
                </form>

                <div id="kanbanView" class="kanban-container">
                    <?php if (empty($applications)): ?>
                        <p class="text-center text-gray-500">No applications found.</p>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <?php
                            $show_row = true;
                            if ($notice_filter !== 'all') {
                                if ($app['notice_period'] === null) {
                                    $show_row = false;
                                } elseif ($notice_filter === 'below30' && $app['notice_period'] > 30) {
                                    $show_row = false;
                                } elseif ($notice_filter === 'above30' && $app['notice_period'] <= 30) {
                                    $show_row = false;
                                }
                            }
                            if ($salary_filter !== 'all') {
                                $app_salary_ok = $app['salary_ok'] !== null ? strtolower($app['salary_ok']) : null;
                                if ($app_salary_ok === null || $app_salary_ok !== $salary_filter) {
                                    $show_row = false;
                                }
                            }
                            if ($rating_filter !== 'all') {
                                if ($app['rating'] !== $rating_filter) {
                                    $show_row = false;
                                }
                            }
                            ?>
                            <?php if ($show_row): ?>
                                <div class="candidate-card" 
                                     data-notice="<?php echo $app['notice_period'] ?? 'null'; ?>" 
                                     data-salary="<?php echo $app['salary_ok'] !== null ? strtolower($app['salary_ok']) : 'null'; ?>" 
                                     data-rating="<?php echo $app['rating'] ?? 'null'; ?>"
                                     data-search="<?php echo htmlspecialchars(strtolower(($app['job_title'] ?? '') . ' ' . ($app['reference'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                                     data-details='<?php echo htmlspecialchars(json_encode($app), ENT_QUOTES, 'UTF-8'); ?>'
                                     data-application-id="<?php echo $app['application_id'] ?? ''; ?>">
                                    <div class="card-menu">
                                        <button class="menu-btn">⋮</button>
                                        <div class="menu-dropdown">
                                            <a href="delete_application.php?id=<?php echo urlencode($app['application_id']); ?>&job_id=<?php echo urlencode($_GET['job_id'] ?? ''); ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this application?');">Delete</a>
                                        </div>
                                    </div>
                                    <h3><?php echo htmlspecialchars($app['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><strong>Reference ID:</strong> <?php echo htmlspecialchars($app['reference'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p><strong>Job:</strong> <?php echo htmlspecialchars($app['job_title'] ?? 'Unknown Job (ID: ' . $app['job_id'] . ')', ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p><strong>Rating:</strong> <?php echo $app['rating'] === '1' ? 'Excellent' : ($app['rating'] === '2' ? 'Average' : 'Poor'); ?></p>
                                    <p><strong>Score:</strong> <?php echo $app['score']; ?>/<?php echo $app['max_score']; ?></p>
                                    <p><strong>Percentage:</strong> <?php echo $app['percentage']; ?>%</p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="detailsModal" 
                     class="modal hidden modal-hidden" 
                     role="dialog" 
                     aria-labelledby="detailsModalTitle" 
                     aria-hidden="true">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 id="detailsModalTitle" class="text-lg font-bold text-gray-800">Candidate Details</h2>
                            <button id="closeDetailsModal" 
                                    class="text-gray-500 hover:text-gray-700 focus:outline-none" 
                                    aria-label="Close modal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body" id="detailsContent"></div>
                    </div>
                </div>

                <div id="answersModal" 
                     class="modal hidden modal-hidden" 
                     role="dialog" 
                     aria-labelledby="answersModalTitle" 
                     aria-hidden="true">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 id="answersModalTitle" class="text-lg font-bold text-gray-800">Candidate Answers</h2>
                            <button id="closeAnswersModal" 
                                    class="text-gray-500 hover:text-gray-700 focus:outline-none" 
                                    aria-label="Close modal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <ul id="answersList" class="space-y-3"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script>
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.candidate-card');
            cards.forEach(card => {
                const searchData = card.dataset.search || '';
                const matchesSearch = searchTerm === '' || searchData.includes(searchTerm);
                card.style.display = matchesSearch ? '' : 'none';
            });
            filterForm.dispatchEvent(new Event('submit'));
        });

        const filterForm = document.getElementById('filterForm');
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const noticeFilter = document.getElementById('notice').value;
            const salaryFilter = document.getElementById('salary').value.toLowerCase();
            const ratingFilter = document.getElementById('rating').value;
            const searchTerm = searchInput.value.toLowerCase().trim();

            const cards = document.querySelectorAll('.candidate-card');
            cards.forEach(card => {
                const notice = card.dataset.notice === 'null' ? null : parseInt(card.dataset.notice);
                const salary = card.dataset.salary === 'null' ? null : card.dataset.salary.toLowerCase();
                const rating = card.dataset.rating === 'null' ? null : card.dataset.rating;
                const searchData = card.dataset.search || '';

                const matchesSearch = searchTerm === '' || searchData.includes(searchTerm);
                let matchesFilters = true;

                if (noticeFilter !== 'all') {
                    if (notice === null) {
                        matchesFilters = false;
                    } else if (noticeFilter === 'below30' && notice > 30) {
                        matchesFilters = false;
                    } else if (noticeFilter === 'above30' && notice <= 30) {
                        matchesFilters = false;
                    }
                }

                if (salaryFilter !== 'all') {
                    if (salary === null || salary !== salaryFilter) {
                        matchesFilters = false;
                    }
                }

                if (ratingFilter !== 'all') {
                    if (rating === null || rating !== ratingFilter) {
                        matchesFilters = false;
                    }
                }

                card.style.display = matchesSearch && matchesFilters ? '' : 'none';
            });

            const params = new URLSearchParams();
            if (noticeFilter !== 'all') params.set('notice', noticeFilter);
            if (salaryFilter !== 'all') params.set('salary', salaryFilter);
            if (ratingFilter !== 'all') params.set('rating', ratingFilter);
            <?php if ($job_id_filter !== null): ?>
                params.set('job_id', '<?php echo $job_id_filter; ?>');
            <?php endif; ?>
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
        });

        filterForm.dispatchEvent(new Event('submit'));

        const detailsModal = document.getElementById('detailsModal');
        const detailsContent = document.getElementById('detailsContent');
        const closeDetailsModal = document.getElementById('closeDetailsModal');
        const cards = document.querySelectorAll('.candidate-card');

        cards.forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('.card-menu') || e.target.closest('.menu-dropdown') || e.target.closest('.delete-button')) {
                    return;
                }
                const details = JSON.parse(card.dataset.details || '{}');
                const answers = details.answers || '[]';
                detailsContent.innerHTML = `
                    <p><strong>Reference ID:</strong> ${details.reference || 'N/A'}</p>
                    <p><strong>Name:</strong> ${details.name || 'N/A'}</p>
                    <p><strong>Job:</strong> ${details.job_title || 'Unknown Job (ID: ' + (details.job_id || 'N/A') + ')'}</p>
                    <p><strong>Email:</strong> ${details.email || 'N/A'}</p>
                    <p><strong>Phone:</strong> ${details.phone_no || 'N/A'}</p>
                    <p><strong>Experience:</strong> ${details.years_of_experience || 'N/A'} years</p>
                    <p><strong>LinkedIn:</strong> ${details.linkedin_profile ? `<a href="${details.linkedin_profile}" target="_blank" class="btn-link">Profile</a>` : 'N/A'}</p>
                    <p><strong>CV:</strong> ${details.cv_path ? `<a href="${details.cv_path}" class="btn-link" download>Download</a>` : 'No CV'}</p>
                    <p><strong>Answers:</strong> <button class="btn-link view-answers" data-answers='${answers}'>View</button></p>
                    <p><strong>Submitted:</strong> ${details.submitted_at || 'N/A'}</p>
                    <p><strong>Rating:</strong> <span class="rating">${
                        details.rating === '1' ? 'Excellent' : 
                        details.rating === '2' ? 'Average' : 'Poor'
                    }</span></p>
                    <p><strong>Score:</strong> <span class="score">${details.score || '0'}/${details.max_score || '0'}</span></p>
                    <p><strong>Percentage:</strong> <span class="percentage">${details.percentage || '0'}%</span></p>
                `;
                detailsModal.classList.remove('modal-hidden');
                detailsModal.classList.add('modal-visible');
                detailsModal.classList.remove('hidden');
                bindAnswersButtons();
            });
        });

        function closeDetailsModalFunc() {
            detailsModal.classList.add('modal-hidden');
            detailsModal.classList.remove('modal-visible');
            setTimeout(() => detailsModal.classList.add('hidden'), 300);
        }

        closeDetailsModal.addEventListener('click', closeDetailsModalFunc);

        detailsModal.addEventListener('click', (e) => {
            if (e.target === detailsModal) {
                closeDetailsModalFunc();
            }
        });

        const answersModal = document.getElementById('answersModal');
        const answersList = document.getElementById('answersList');
        const closeAnswersModal = document.getElementById('closeAnswersModal');

        function bindAnswersButtons() {
            const viewAnswersButtons = document.querySelectorAll('.view-answers');
            viewAnswersButtons.forEach(button => {
                button.removeEventListener('click', handleAnswersClick);
                button.addEventListener('click', handleAnswersClick);
            });
        }

        function handleAnswersClick(e) {
            try {
                const rawAnswers = e.target.dataset.answers || '[]';
                const answers = JSON.parse(rawAnswers);
                if (!Array.isArray(answers)) {
                    throw new Error('Answers is not an array');
                }
                answersList.innerHTML = answers.length > 0 ? answers.map(answer => `
                    <li class="border-b border-gray-200 py-2">
                        <strong class="text-gray-700">${answer.question || 'Unknown Question'}</strong>: ${answer.answer || 'N/A'}
                    </li>
                `).join('') : '<p class="text-gray-500">No screening answers provided.</p>';
                answersModal.classList.remove('modal-hidden');
                answersModal.classList.add('modal-visible');
                answersModal.classList.remove('hidden');
            } catch (error) {
                console.error('Error processing answers:', error.message, 'Raw data:', e.target.dataset.answers);
                answersList.innerHTML = `<p class="text-red-500">Error loading answers: ${error.message}. Please verify the data format in the database.</p>`;
                answersModal.classList.remove('modal-hidden');
                answersModal.classList.add('modal-visible');
                answersModal.classList.remove('hidden');
            }
        }

        function closeAnswersModalFunc() {
            answersModal.classList.add('modal-hidden');
            answersModal.classList.remove('modal-visible');
            setTimeout(() => answersModal.classList.add('hidden'), 300);
        }

        closeAnswersModal.addEventListener('click', closeAnswersModalFunc);

        answersModal.addEventListener('click', (e) => {
            if (e.target === answersModal) {
                closeAnswersModalFunc();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (!detailsModal.classList.contains('hidden')) {
                    closeDetailsModalFunc();
                }
                if (!answersModal.classList.contains('hidden')) {
                    closeAnswersModalFunc();
                }
            }
        });

        document.querySelectorAll('.menu-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const dropdown = button.nextElementSibling;
                document.querySelectorAll('.menu-dropdown').forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.style.display = 'none';
                    }
                });
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            });
        });

        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.card-menu') && !e.target.closest('.menu-dropdown')) {
                document.querySelectorAll('.menu-dropdown').forEach(dropdown => {
                    dropdown.style.display = 'none';
                });
            }
        });

        bindAnswersButtons();
    </script>
</body>
</html>