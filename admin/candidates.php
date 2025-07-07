`````````<?php
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
$job_id_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;
$rank_filter = isset($_GET['rank']) ? $_GET['rank'] : 'all';

// Fetch applications with job details
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
            a.status,
            a.answers, 
            a.notice_period,
            a.salary_accept,
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
        // Parse screening questions
        $raw_screening_questions = $app['screening_questions'] ?? '[]';
        $screening_questions = json_decode($raw_screening_questions, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($screening_questions)) {
            error_log("Invalid JSON in screening_questions for job ID {$app['job_id']}: " . $raw_screening_questions);
            $screening_questions = [];
        }

        // Parse candidate answers
        $raw_answers = $app['answers'] ?? '[]';
        $candidate_answers = json_decode($raw_answers, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($candidate_answers)) {
            error_log("Invalid JSON in answers for application ID {$app['application_id']}: " . $raw_answers);
            $candidate_answers = [];
        }

        // Normalize screening questions and extract must_have
        $normalized_questions = [];
        $must_have_items = [];
        $must_have_matches = 0;
        $total_must_haves = 0;

        foreach ($screening_questions as $index => $item) {
            if (is_array($item) && isset($item['question_text']) && is_string($item['question_text'])) {
                $text = trim($item['question_text']);
                if ($text !== '') {
                    $normalized_questions[] = $text;

                    // Handle must_have
                    if (isset($item['must_have'])) {
                        $total_must_haves++;
                        $matched = false;

                        // Find the corresponding answer
                        $normalized_question = trim(strtolower($text));
                        foreach ($candidate_answers as $answer) {
                            if (!isset($answer['question']) || !isset($answer['answer'])) {
                                continue;
                            }
                            $normalized_answer_question = trim(strtolower($answer['question']));
                            if ($normalized_answer_question === $normalized_question || 
                                stripos($normalized_answer_question, $normalized_question) !== false || 
                                stripos($normalized_question, $normalized_answer_question) !== false) {
                                // Check must-have criteria
                                if ($item['answer_type'] === 'Numerical' && is_numeric($item['must_have']) && is_numeric($answer['answer'])) {
                                    if ((float)$answer['answer'] >= (float)$item['must_have']) {
                                        $matched = true;
                                    }
                                }
                             
                                elseif ($item['answer_type'] === 'MCQ' && is_array($item['must_have']) && isset($item['options'])) {
                                    $answer_array = is_array($answer['answer']) ? $answer['answer'] : [$answer['answer']];
                                    $required_indices = array_keys(array_filter($item['must_have']));
                                    $required_options = array_intersect_key($item['options'], array_flip($required_indices));
                                    $all_required_present = true;
                                    foreach ($required_options as $opt) {
                                        if (!in_array($opt, $answer_array)) {
                                            $all_required_present = false;
                                            break;
                                        }
                                    }
                                    if ($all_required_present) {
                                        $matched = true;
                                    }
                                }
                                elseif ($item['answer_type'] === 'Yes/No' && is_string($item['must_have']) && is_string($answer['answer'])) {
                                    $required_answer = strtolower(trim($item['must_have']));
                                    $candidate_answer = strtolower(trim($answer['answer']));
                                    if ($required_answer === $candidate_answer) {
                                        $matched = true;
                                    }}
                                break;
                            }
                        }

                        if ($matched) {
                            $must_have_matches++;
                        }

                        // Format must_have for display
                        if ($item['answer_type'] === 'Numerical' && is_numeric($item['must_have'])) {
                            $must_have_items[] = "Minimum {$text}: {$item['must_have']}";
                        } elseif ($item['answer_type'] === 'MCQ' && is_array($item['must_have']) && isset($item['options'])) {
                            $required_options = [];
                            foreach ($item['must_have'] as $idx => $is_required) {
                                if ($is_required && isset($item['options'][$idx])) {
                                    $required_options[] = $item['options'][$idx];
                                }
                            }
                            if (!empty($required_options)) {
                                $must_have_items[] = "Required technologies: " . implode(', ', $required_options);
                            }
                        }
                        elseif ($item['answer_type'] === 'Yes/No' && is_string($item['must_have'])) {
                             $must_have_items[] = "{$text}: Must answer '" . ucfirst(strtolower($item['must_have'])) . "'";
                            }
                    }
                }
            } else {
                error_log("Invalid item at index $index for job ID {$app['job_id']}: " . json_encode($item));
            }
        }

        $app['must_have_items'] = $must_have_items;

        // Calculate score
        $score = 0;
        if (strtolower($app['salary_accept'] ?? '') === 'yes') {
            $score += 1;
        }
        if ($app['notice_period'] !== null && (int)$app['notice_period'] <= 30) {
            $score += 1;
        }
        $score += $must_have_matches;

        $app['score'] = $score;
        $app['max_score'] = 2 + $total_must_haves;

        // Map screening questions to answers
        $app['screening_qa'] = [];
        foreach ($normalized_questions as $question) {
            $matched_answer = null;
            $best_similarity = 0;
            $similarity_threshold = 70;

            foreach ($candidate_answers as $answer) {
                if (!isset($answer['question']) || !is_string($answer['question']) || !isset($answer['answer'])) {
                    error_log("Invalid answer format for application ID {$app['application_id']}: " . json_encode($answer));
                    continue;
                }

                $normalized_answer_question = trim(strtolower($answer['question']));
                $normalized_question = trim(strtolower($question));

                if ($normalized_answer_question === $normalized_question || 
                    stripos($normalized_answer_question, $normalized_question) !== false || 
                    stripos($normalized_question, $normalized_answer_question) !== false) {
                    $matched_answer = $answer['answer'];
                    break;
                }

                similar_text($normalized_answer_question, $normalized_question, $similarity);
                if ($similarity > $best_similarity && $similarity >= $similarity_threshold) {
                    $best_similarity = $similarity;
                    $matched_answer = $answer['answer'];
                }
            }

            $app['screening_qa'][] = [
                'question' => $question,
                'answer' => $matched_answer ?? 'N/A'
            ];
        }

        // Fallback: If no matches, include all answers
        if (empty($app['screening_qa']) && !empty($candidate_answers)) {
            foreach ($candidate_answers as $answer) {
                if (isset($answer['question']) && isset($answer['answer']) && is_string($answer['question'])) {
                    $app['screening_qa'][] = [
                        'question' => $answer['question'],
                        'answer' => $answer['answer']
                    ];
                }
            }
            if (!empty($app['screening_qa'])) {
                error_log("Fallback used for application ID {$app['application_id']}: Displaying all candidate answers due to no matches");
            }
        }

        // Normalize salary_accept for filtering
        $app['salary_ok'] = $app['salary_accept'] !== null ? strtolower($app['salary_accept']) : null;
    }
    unset($app); // Unset the reference to prevent side effects

    // Sort applications by score if rank filter is applied
    if ($rank_filter === 'high_to_low') {
        usort($applications, function($a, $b) {
            return $b['score'] - $a['score'];
        });
    } elseif ($rank_filter === 'low_to_high') {
        usort($applications, function($a, $b) {
            return $a['score'] - $b['score'];
        });
    }

    // Debug: Log applications and scores after sorting
    error_log("Applications after sorting (rank_filter=$rank_filter): " . print_r(array_map(function($app) {
        return [
            'application_id' => $app['application_id'],
            'name' => $app['name'],
            'score' => $app['score'],
            'max_score' => $app['max_score']
        ];
    }, $applications), true));

} catch (PDOException $e) {
    error_log("Error fetching applications: " . $e->getMessage());
    $error = "Error fetching applications: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates - Admin Panel</title>
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f0f2f5;
            color: #1a1a1a;
            margin: 0;
            min-height: 100vh;
            line-height: 1.5;
        }
        main {
            margin-left: 250px;
            padding: 2rem;
        }
        .container {
            max-width: 1280px;
            margin: 0 auto;
        }
        .card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #181818;
        }
        .header span {
            font-size: 0.875rem;
            color: #666;
        }
        .actions-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .search-bar {
            flex: 1;
            max-width: 400px;
        }
        .search-bar input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ccd0d5;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }
        .search-bar input:focus {
            outline: none;
            border-color: #0073b1;
            box-shadow: 0 0 0 2px rgba(0, 115, 177, 0.2);
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            background: #ffffff;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        .filter-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .filter-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccd0d5;
            border-radius: 6px;
            font-size: 0.875rem;
            color: #333;
            background: #ffffff;
            transition: border-color 0.2s;
        }
        .filter-select:focus {
            outline: none;
            border-color: #0073b1;
            box-shadow: 0 0 0 2px rgba(0, 115, 177, 0.2);
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }
        .btn-primary {
            background: #0073b1;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #005f8f;
            transform: translateY(-1px);
        }
        .btn-link {
            color: #0073b1;
            text-decoration: none;
            font-weight: 500;
        }
        .btn-link:hover {
            text-decoration: underline;
            color: #005f8f;
        }
        .kanban-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }
        .candidate-card {
            background: #ffffff;
            border: 1px solid #e1e4e8;
            border-radius: 8px;
            padding: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            cursor: pointer;
        }
        .candidate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .candidate-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 1.5rem 0 0;
            color: #181818;
        }
        .candidate-card p {
            font-size: 0.875rem;
            margin: 0.25rem 0;
            color: #444;
            word-break: break-word;
        }
        .candidate-card .snippet {
            font-size: 0.75rem;
            color: #666;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .score-badge {
            background: #0073b1;
            color: #ffffff;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            position: absolute;
            top: 10px;
            left: 10px;
            margin-bottom:2rem;
        }
         .status-badge {
            background: #0073b1;
            color: #ffffff;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            position: absolute;
            top: 10px;
            left: 100px;
            margin-bottom:2rem;
        }
        .status-accepted {
            background: #28a745; /* Green */
        }
        .status-rejected {
            background: #dc3545; /* Red */
        }
        .status-maybe {
            background: #ffc107; /* Yellow */
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
            font-size: 1.25rem;
            color: #666;
            padding: 5px;
        }
        .menu-btn:hover {
            color: #0073b1;
        }
        .menu-dropdown {
            display: none;
            position: absolute;
            right: 0;
            background: #ffffff;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            z-index: 1000;
        }
        .menu-dropdown a {
            display: block;
            padding: 0.5rem 1rem;
            color: #d32f2f;
            text-decoration: none;
            font-size: 0.875rem;
        }
        .menu-dropdown a:hover {
            background: #f6f8fa;
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
            background: #ffffff;
            border-radius: 8px;
            padding: 1.5rem;
            width: 100%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
            color: #181818;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #666;
            cursor: pointer;
        }
        .modal-close:hover {
            color: #181818;
        }
        .modal-body p, .modal-body li {
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            color: #333;
        }
        .modal-body .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #181818;
            margin: 1rem 0 0.5rem;
        }
        .modal-body .btn-link {
            margin-top: 0.5rem;
            display: inline-block;
        }
        .alert-error {
            background: #fce7e7;
            color: #d32f2f;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .alert-success {
            background: #e7f4e8;
            color: #2e7d32;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
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
            .kanban-container {
                grid-template-columns: 1fr;
            }
            .card-elements{
                margin: 1rem;
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
                <div class="header">
                    <h1>Candidates</h1>
                    <span>Welcome, <?php echo htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8'); ?></span>
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
                <?php if (isset($error)): ?>
                    <div class="alert-error" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $error; ?>
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
                        <label for="rank" class="filter-label">Ranking</label>
                        <select id="rank" name="rank" class="filter-select">
                            <option value="all" <?php echo $rank_filter === 'all' ? 'selected' : ''; ?>>Default</option>
                            <option value="high_to_low" <?php echo $rank_filter === 'high_to_low' ? 'selected' : ''; ?>>Score: High to Low</option>
                            <option value="low_to_high" <?php echo $rank_filter === 'low_to_high' ? 'selected' : ''; ?>>Score: Low to High</option>
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
                                $app_salary_ok = $app['salary_accept'] !== null ? strtolower($app['salary_accept']) : null;
                                if ($app_salary_ok === null || $app_salary_ok !== $salary_filter) {
                                    $show_row = false;
                                }
                            }
                            ?>
                            <?php if ($show_row): ?>
                                <?php
                                // Explicitly create a clean array for data-details
                                $details = [
                                    'application_id' => $app['application_id'] ?? '',
                                    'reference' => $app['reference'] ?? '',
                                    'job_title' => $app['job_title'] ?? '',
                                    'job_id' => $app['job_id'] ?? '',
                                    'name' => $app['name'] ?? '',
                                    'email' => $app['email'] ?? '',
                                    'phone_no' => $app['phone_no'] ?? '',
                                    'years_of_experience' => $app['years_of_experience'] ?? '',
                                    'linkedin_profile' => $app['linkedin_profile'] ?? '',
                                    'cv_path' => $app['cv_path'] ?? '',
                                    'notice_period' => $app['notice_period'] ?? '',
                                    'salary_accept' => $app['salary_accept'] ?? '',
                                    'submitted_at' => $app['submitted_at'] ?? '',
                                    'score' => $app['score'] ?? 0,
                                    'max_score' => $app['max_score'] ?? 0,
                                    'screening_qa' => $app['screening_qa'] ?? [],
                                    'must_have_items' => $app['must_have_items'] ?? []
                                ];
                                ?>
                                <div class="candidate-card" 
                                     data-notice="<?php echo htmlspecialchars($app['notice_period'] ?? 'null', ENT_QUOTES, 'UTF-8'); ?>" 
                                     data-salary="<?php echo htmlspecialchars($app['salary_accept'] !== null ? strtolower($app['salary_accept']) : 'null', ENT_QUOTES, 'UTF-8'); ?>" 
                                     data-score="<?php echo htmlspecialchars($app['score'] ?? 0, ENT_QUOTES, 'UTF-8'); ?>" 
                                     data-search="<?php echo htmlspecialchars(strtolower(($app['job_title'] ?? '') . ' ' . ($app['reference'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                                     data-details='<?php echo htmlspecialchars(json_encode($details), ENT_QUOTES, 'UTF-8'); ?>'
                                     data-application-id="<?php echo htmlspecialchars($app['application_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="score-badge">Score: <?php echo htmlspecialchars($app['score'] . '/' . $app['max_score'], ENT_QUOTES, 'UTF-8'); ?></div>
                                     <div class="status-badge status-<?php echo htmlspecialchars(strtolower($app['status']), ENT_QUOTES, 'UTF-8'); ?>">
    <strong>Status:</strong> <?php echo htmlspecialchars($app['status'], ENT_QUOTES, 'UTF-8'); ?>
</div>
                                    <div class="card-menu">
                                        <button class="menu-btn" aria-label="More options">⋮</button>
                                        <div class="menu-dropdown">
                                            <a href="delete_application.php?id=<?php echo urlencode($app['application_id']); ?>&job_id=<?php echo urlencode($_GET['job_id'] ?? ''); ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this application?');">Delete</a>
                                        <a href="accept_application.php?id=<?php echo urlencode($app['application_id']); ?>&job_id=<?php echo urlencode($_GET['job_id'] ?? ''); ?>" class="accept-button" onclick="return confirm('Are you sure you want to accept this application?');">Accept</a>
                                         <a href="reject_application.php?id=<?php echo urlencode($app['application_id']); ?>&job_id=<?php echo urlencode($_GET['job_id'] ?? ''); ?>" class="reject-button" onclick="return confirm('Are you sure you want to reject this application?');">Reject</a>
                                          <a href="Maybe_application.php?id=<?php echo urlencode($app['application_id']); ?>&job_id=<?php echo urlencode($_GET['job_id'] ?? ''); ?>" class="Maybe-button" onclick="return confirm('Are you sure you want to May be this application?');">Maybe</a>
                                        </div>
                                    </div>
                                    
                                    <h3><?php echo htmlspecialchars($app['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><strong>Reference ID:</strong> <?php echo htmlspecialchars($app['reference'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    
                                                <p><strong>Job:</strong> <?php echo htmlspecialchars($app['job_title'] ?? 'Unknown Job (ID: ' . $app['job_id'] . ')', ENT_QUOTES, 'UTF-8'); ?></p>
                                    
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="detailsModal" 
                     class="modal modal-hidden" 
                     role="dialog" 
                     aria-labelledby="detailsModalTitle" 
                     aria-hidden="true">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 id="detailsModalTitle">Candidate Details</h2>
                            <button id="closeDetailsModal" class="modal-close" aria-label="Close modal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body" id="detailsContent"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script>
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterForm');
        const detailsModal = document.getElementById('detailsModal');
        const detailsContent = document.getElementById('detailsContent');
        const closeDetailsModal = document.getElementById('closeDetailsModal');
        const kanbanView = document.getElementById('kanbanView');

        // Apply filters and maintain server-side sorting
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const noticeFilter = document.getElementById('notice').value;
            const salaryFilter = document.getElementById('salary').value.toLowerCase();
            const rankFilter = document.getElementById('rank').value;
            const searchTerm = searchInput.value.toLowerCase().trim();

            const cards = Array.from(document.querySelectorAll('.candidate-card'));
            cards.forEach(card => {
                const notice = card.dataset.notice === 'null' ? null : parseInt(card.dataset.notice);
                const salary = card.dataset.salary === 'null' ? null : card.dataset.salary.toLowerCase();
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

                card.style.display = matchesSearch && matchesFilters ? '' : 'none';
            });

            // Client-side sorting to reinforce server-side sorting
            if (rankFilter !== 'all') {
                cards.sort((a, b) => {
                    const scoreA = parseInt(a.dataset.score || 0);
                    const scoreB = parseInt(b.dataset.score || 0);
                    return rankFilter === 'high_to_low' ? scoreB - scoreA : scoreA - scoreB;
                });
                kanbanView.innerHTML = '';
                cards.forEach(card => kanbanView.appendChild(card));
            }

            const params = new URLSearchParams();
            if (noticeFilter !== 'all') params.set('notice', noticeFilter);
            if (salaryFilter !== 'all') params.set('salary', salaryFilter);
            if (rankFilter !== 'all') params.set('rank', rankFilter);
            <?php if ($job_id_filter !== null): ?>
                params.set('job_id', '<?php echo $job_id_filter; ?>');
            <?php endif; ?>
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
        });

        // Trigger filter on search input
        searchInput.addEventListener('input', function () {
            filterForm.dispatchEvent(new Event('submit'));
        });

        // Initial filter application
        filterForm.dispatchEvent(new Event('submit'));

        // Modal handling
        document.querySelectorAll('.candidate-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('.card-menu') || e.target.closest('.menu-dropdown') || e.target.closest('.delete-button')) {
                    return;
                }
                let details;
                try {
                    details = JSON.parse(card.dataset.details || '{}');
                } catch (error) {
                    console.error('Error parsing data-details for application ID ' + card.dataset.applicationId, error);
                    details = {};
                }
                const screeningQA = details.screening_qa || [];
                let screeningHTML = '<div class="section-title">Screening Questions</div>';
                if (screeningQA.length > 0) {
                    screeningHTML += '<ul class="space-y-3">';
                    screeningQA.forEach(qa => {
                        const questionText = qa.question || 'Unknown Question';
                        const answerText = qa.answer || 'N/A';
                        screeningHTML += `
                            <li class="border-b border-gray-200 py-2">
                                <strong class="text-gray-700">${questionText}</strong>: ${answerText}
                            </li>
                        `;
                    });
                    screeningHTML += '</ul>';
                } else {
                    screeningHTML += '<p class="text-gray-500">No screening questions available.</p>';
                }

                const mustHaveItems = details.must_have_items || [];
                let mustHaveHTML = '<div class="section-title">Must Have Requirements</div>';
                if (mustHaveItems.length > 0) {
                    mustHaveHTML += '<ul class="space-y-3">';
                    mustHaveItems.forEach(item => {
                        mustHaveHTML += `
                            <li class="border-b border-gray-200 py-2">
                                ${item}
                            </li>
                        `;
                    });
                    mustHaveHTML += '</ul>';
                } else {
                    mustHaveHTML += '<p class="text-gray-500">No must-have requirements found.</p>';
                }

                detailsContent.innerHTML = `
                    <p><strong>Score:</strong> ${details.score || 0}/${details.max_score || 0}</p>
                    <p><strong>Reference ID:</strong> ${details.reference || 'N/A'}</p>
                    <p><strong>Name:</strong> ${details.name || 'N/A'}</p>
                    <p><strong>Job:</strong> ${details.job_title || 'Unknown Job (ID: ' + (details.job_id || 'N/A') + ')'}</p>
                    <p><strong>Email:</strong> ${details.email || 'N/A'}</p>
                    <p><strong>Phone:</strong> ${details.phone_no || 'N/A'}</p>
                    <p><strong>Experience:</strong> ${details.years_of_experience || 'N/A'} years</p>
                    <p><strong>LinkedIn:</strong> ${details.linkedin_profile ? `<a href="${details.linkedin_profile}" target="_blank" class="btn-link">View Profile</a>` : 'N/A'}</p>
                    <p><strong>CV:</strong> ${details.cv_path ? `<a href="${details.cv_path}" class="btn-link" download>Download CV</a>` : 'No CV'}</p>
                    <p><strong>Notice Period:</strong> ${details.notice_period || 'N/A'} days</p>
                    <p><strong>Salary Acceptance:</strong> ${details.salary_accept || 'N/A'}</p>
                    <p><strong>Submitted:</strong> ${details.submitted_at || 'N/A'}</p>
                    ${screeningHTML}
                    ${mustHaveHTML}
                `;
                detailsModal.classList.remove('modal-hidden');
                detailsModal.classList.add('modal-visible');
                detailsModal.classList.remove('hidden');
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

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !detailsModal.classList.contains('hidden')) {
                closeDetailsModalFunc();
            }
        });
    </script>
</body>
</html>`````````