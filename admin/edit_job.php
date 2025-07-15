<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
require '../db.php';

// Sanitize admin name
$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$error = '';
$success = '';

// Fetch job details
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header('Location: jobs.php');
    exit();
}

$job_id = $_GET['id'];
try {
    $stmt = $conn->prepare("SELECT * FROM Jobs WHERE job_id = :job_id");
    $stmt->execute([':job_id' => $job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        header('Location: jobs.php');
        exit();
    }

    // Decode screening questions
    $screening_questions = !empty($job['screening_questions']) ? json_decode($job['screening_questions'], true) : [];
} catch (PDOException $e) {
    $error = "Database error: Unable to fetch job details.";
    error_log("Database error: " . $e->getMessage());
    $job = null;
    $screening_questions = [];
}

// Fetch categories
try {
    $stmt = $conn->prepare("SELECT category_id, category_name, onsite_salary, offshore_salary, description, perks, onsite_perks FROM TestCategories");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: Unable to fetch categories.";
    error_log("Database error: " . $e->getMessage());
    $categories = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS));
    $requirements = trim($_POST['requirements'] ?? '');
    $salary_package = trim(filter_input(INPUT_POST, 'salary_package', FILTER_SANITIZE_SPECIAL_CHARS));
    $perks = trim(filter_input(INPUT_POST, 'perks', FILTER_SANITIZE_SPECIAL_CHARS));
    $reference = trim(filter_input(INPUT_POST, 'reference', FILTER_SANITIZE_SPECIAL_CHARS));
    $location = trim(filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS));
    $questions = filter_input(INPUT_POST, 'questions', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? [];

    // Basic validation
    if (empty($title)) {
        $error = 'Title is required.';
    } elseif (!$category_id) {
        $error = 'Please select a valid category.';
    } elseif (empty($location)) {
        $error = 'Location is required.';
    } elseif (empty($salary_package)) {
        $error = 'Salary package is required.';
    } else {
        try {
            $conn->beginTransaction();

            // Process screening questions
            $screening_questions = [];
            if (!empty($questions)) {
                foreach ($questions as $index => $q) {
                    $q_text = trim($q['text'] ?? '');
                    $q_type = $q['type'] ?? '';
                    if (empty($q_text) || !in_array($q_type, ['MCQ', 'Yes/No', 'Numerical'])) {
                        continue;
                    }

                    $question_data = [
                        'question_text' => $q_text,
                        'answer_type' => $q_type
                    ];

                    if ($q_type === 'MCQ') {
                        $options = isset($q['options']) ? array_values(array_filter(array_map('trim', (array)$q['options']), fn($opt) => !empty($opt))) : [];
                        if (!empty($options)) {
                            $question_data['options'] = $options;
                            $must_have = isset($q['must_have']) ? array_values(array_map('boolval', (array)$q['must_have'])) : array_fill(0, count($options), false);
                            $question_data['must_have'] = array_pad($must_have, count($options), false);
                        } else {
                            continue;
                        }
                    } elseif ($q_type === 'Yes/No') {
                        $must_have = $q['must_have'] ?? '';
                        if (in_array($must_have, ['Yes', 'No'])) {
                            $question_data['must_have'] = $must_have;
                        } else {
                            $question_data['must_have'] = null;
                        }
                    } elseif ($q_type === 'Numerical') {
                        $must_have = isset($q['must_have']) ? trim($q['must_have']) : '';
                        if ($must_have !== '' && is_numeric($must_have)) {
                            $question_data['must_have'] = floatval($must_have);
                        } else {
                            $question_data['must_have'] = null;
                        }
                    }

                    $screening_questions[] = $question_data;
                }
            }
            $screening_questions_json = !empty($screening_questions) ? json_encode($screening_questions, JSON_THROW_ON_ERROR) : null;

            // Update job in database
            $stmt = $conn->prepare("
                UPDATE Jobs 
                SET category_id = :category_id, title = :title, description = :description, requirements = :requirements, 
                    salary_package = :salary_package, perks = :perks, location = :location, screening_questions = :screening_questions, 
                    reference = :reference
                WHERE job_id = :job_id
            ");
            $stmt->execute([
                ':category_id' => $category_id,
                ':title' => $title,
                ':description' => $description,
                ':requirements' => $requirements,
                ':salary_package' => $salary_package,
                ':perks' => $perks,
                ':location' => $location,
                ':screening_questions' => $screening_questions_json,
                ':reference' => $reference,
                ':job_id' => $job_id
            ]);

            $conn->commit();
            $success = 'Job and screening questions updated successfully!';
            $_POST = [];
            // Refresh job data
            $stmt = $conn->prepare("SELECT * FROM Jobs WHERE job_id = :job_id");
            $stmt->execute([':job_id' => $job_id]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            $screening_questions = !empty($job['screening_questions']) ? json_decode($job['screening_questions'], true) : [];
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'Error updating job. Please try again.';
            error_log("Error updating job: " . $e->getMessage());
        } catch (JsonException $e) {
            $error = 'Error processing screening questions.';
            error_log("JSON error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to edit job postings">
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <title>Edit Job - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', system-ui, -apple-system, sans-serif;
            background: #F6F6F6;
            min-height: 100vh;
            margin: 0;
            color: #333333;
        }
        .container {
            max-width: 1024px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .card {
            background: #FFFFFF;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 2rem;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 500;
            color: #0A66C2;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #E0E0E0;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #333333;
            display: block;
            margin-bottom: 0.5rem;
        }
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #8696A7;
            border-radius: 6px;
            font-size: 0.875rem;
            color: #333333;
            background: #FFFFFF;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #0A66C2;
            box-shadow: 0 0 0 3px rgba(10, 102, 194, 0.2);
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        .question-block {
            background: #F9FAFB;
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .option-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .must-have-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .delete-btn {
            background: #D32F2F;
            color: #FFFFFF;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .delete-btn:hover {
            background: #B71C1C;
        }
        .alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .alert-error {
            background: #FEE2E2;
            color: #D32F2F;
        }
        .alert-success {
            background: #E7F3EF;
            color: #2E7D32;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
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
        .btn-secondary {
            background: #FFFFFF;
            color: #0A66C2;
            border: 1px solid #0A66C2;
        }
        .btn-secondary:hover {
            background: #F1F5F9;
        }
        .ql-container {
            border: 1px solid #8696A7;
            border-radius: 6px;
            font-size: 0.875rem;
            background: #FFFFFF;
            min-height: 150px;
        }
        .ql-container:focus-within {
            border-color: #0A66C2;
            box-shadow: 0 0 0 3px rgba(10, 102, 194, 0.2);
        }
        .ql-toolbar {
            border: 1px solid #8696A7;
            border-bottom: none;
            border-radius: 6px 6px 0 0;
            background: #F9FAFB;
        }
        .ql-editor {
            padding: 0.75rem;
            min-height: 120px;
            color: #333333;
        }
        .space {
            margin-top: 1rem;
        }
        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }
            .card {
                padding: 1.25rem;
            }
            .option-wrapper {
                flex-direction: column;
                align-items: stretch;
            }
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidenav.php'; ?>
    <div class="container">
        <div class="card">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Job Posting</h1>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form action="edit_job.php?id=<?php echo htmlspecialchars($job_id, ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="space-y-6" id="jobForm">
                <!-- Job Details Section -->
                <div>
                    <h2 class="section-title">Job Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="category_id" class="form-label">Category <span class="text-red-500">*</span></label>
                            <select id="category_id" name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($category['category_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-onsite="<?php echo htmlspecialchars($category['onsite_salary'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-offshore="<?php echo htmlspecialchars($category['offshore_salary'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-description="<?php echo htmlspecialchars($category['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-perks="<?php echo htmlspecialchars($category['perks'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-onsite-perks="<?php echo htmlspecialchars($category['onsite_perks'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo ($category['category_id'] == $job['category_id']) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="title" class="form-label">Job Title <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                value="<?php echo htmlspecialchars($job['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                class="form-input"
                                placeholder="e.g., Senior Software Engineer"
                                required
                            >
                        </div>
                        <div class="form-group md:col-span-2">
                            <label for="description" class="form-label">Experience Required</label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-textarea"
                                placeholder="Describe the job responsibilities and overview"
                            ><?php echo htmlspecialchars($job['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <div class="form-group md:col-span-2">
                            <label for="requirements" class="form-label">Job Description <span class="text-red-500">*</span></label>
                            <div id="requirements-editor" class="ql-container"></div>
                            <input
                                type="hidden"
                                id="requirements"
                                name="requirements"
                                value="<?php echo htmlspecialchars($job['requirements'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                    </div>
                </div>

                <!-- Compensation Section -->
                <div>
                    <h2 class="section-title">Compensation</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="salary_package" class="form-label">Salary Package <span class="text-red-500">*</span></label>
                            <select id="salary_package" name="salary_package" class="form-select" required>
                                <option value="">Select Salary Option</option>
                                <?php
                                foreach ($categories as $category) {
                                    if ($category['category_id'] == $job['category_id']) {
                                        if (!empty($category['onsite_salary'])) {
                                            echo '<option value="' . htmlspecialchars($category['onsite_salary'], ENT_QUOTES, 'UTF-8') . '" ' .
                                                ($job['salary_package'] == $category['onsite_salary'] ? 'selected' : '') .
                                                '>Onsite: ' . htmlspecialchars($category['onsite_salary'], ENT_QUOTES, 'UTF-8') . '</option>';
                                        }
                                        if (!empty($category['offshore_salary'])) {
                                            echo '<option value="' . htmlspecialchars($category['offshore_salary'], ENT_QUOTES, 'UTF-8') . '" ' .
                                                ($job['salary_package'] == $category['offshore_salary'] ? 'selected' : '') .
                                                '>Offshore: ' . htmlspecialchars($category['offshore_salary'], ENT_QUOTES, 'UTF-8') . '</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="perks" class="form-label">Perks</label>
                            <textarea
                                id="perks"
                                name="perks"
                                class="form-textarea"
                                placeholder="e.g., Health insurance, remote work options"
                            ><?php echo htmlspecialchars($job['perks'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Reference Section -->
                <div>
                    <h2 class="section-title">Reference</h2>
                    <div class="form-group">
                        <label for="reference" class="form-label">Reference</label>
                        <input
                            type="text"
                            id="reference"
                            name="reference"
                            class="form-input"
                            placeholder="e.g., FZ-AE-1"
                            value="<?php echo htmlspecialchars($job['reference'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                </div>

                <!-- Location Section -->
                <div>
                    <h2 class="section-title">Location</h2>
                    <div class="form-group">
                        <label for="location" class="form-label">Location <span class="text-red-500">*</span></label>
                        <select id="location" name="location" class="form-select" required>
                            <option value="">Select Location</option>
                            <option value="Dubai" <?php echo ($job['location'] == 'Dubai') ? 'selected' : ''; ?>>Dubai</option>
                            <option value="Lahore" <?php echo ($job['location'] == 'Lahore') ? 'selected' : ''; ?>>Lahore</option>
                        </select>
                    </div>
                </div>

                <!-- Screening Questions Section -->
                <div>
                    <h2 class="section-title">Screening Questions</h2>
                    <div id="question-container" class="space-y-3"></div>
                    <button
                        type="button"
                        class="btn btn-secondary mt-3"
                        onclick="addQuestion()"
                        aria-label="Add a new screening question"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Screening Question
                    </button>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-3">
                    <button type="submit" class="btn btn-primary">Update Job</button>
                    <a href="jobs.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script>
        // Initialize Quill editor
        const quill = new Quill('#requirements-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['link'],
                    ['clean']
                ]
            },
            placeholder: 'List the required skills and qualifications'
        });

        // Sync Quill content with hidden input
        const requirementsInput = document.getElementById('requirements');
        quill.on('text-change', () => {
            requirementsInput.value = quill.root.innerHTML;
        });

        // Set initial content if available
        if (requirementsInput.value) {
            quill.root.innerHTML = requirementsInput.value;
        }

        // Initialize question count and load existing questions
        let questionCount = 0;
        const existingQuestions = <?php echo json_encode($screening_questions); ?>;

        // Function to add a new question block
        function addQuestion(data = null) {
            const container = document.getElementById('question-container');
            const div = document.createElement('div');
            div.classList.add('question-block');

            const questionText = data ? data.question_text : '';
            const answerType = data ? data.answer_type : '';
            const mustHave = data ? data.must_have : null;

            div.innerHTML = `
                <div class="form-group">
                    <label for="question-${questionCount}" class="form-label">Screening Question <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="question-${questionCount}"
                        name="questions[${questionCount}][text]"
                        class="form-input"
                        placeholder="e.g., How would you rate yourself?"
                        value="${questionText ? questionText.replace(/"/g, '"') : ''}"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="type-${questionCount}" class="form-label">Answer Type <span class="text-red-500">*</span></label>
                    <select
                        id="type-${questionCount}"
                        name="questions[${questionCount}][type]"
                        class="form-select"
                        onchange="toggleOptions(this, ${questionCount}, ${data ? 'true' : 'false'})"
                        required
                    >
                        <option value="">Select Answer Type</option>
                        <option value="MCQ" ${answerType === 'MCQ' ? 'selected' : ''}>Multiple Choice</option>
                        <option value="Yes/No" ${answerType === 'Yes/No' ? 'selected' : ''}>Yes/No</option>
                        <option value="Numerical" ${answerType === 'Numerical' ? 'selected' : ''}>Numerical</option>
                    </select>
                </div>
                <div id="options-${questionCount}" class="space-y-2"></div>
                <button
                    type="button"
                    class="delete-btn mt-2"
                    onclick="this.closest('.question-block').remove()"
                    aria-label="Remove this screening question"
                >
                    Remove Question
                </button>
            `;

            container.appendChild(div);

            // Trigger options rendering
            const typeSelect = document.getElementById(`type-${questionCount}`);
            toggleOptions(typeSelect, questionCount, !!data);

            // Populate options for MCQ or must-have for Yes/No/Numerical
            if (data && answerType === 'MCQ' && data.options) {
                const mcqOptionsDiv = document.getElementById(`mcq-options-${questionCount}`);
                data.options.forEach((option, optIndex) => {
                    const optionWrapper = document.createElement('div');
                    optionWrapper.className = 'option-wrapper';
                    optionWrapper.innerHTML = `
                        <input
                            type="text"
                            name="questions[${questionCount}][options][]"
                            class="form-input"
                            placeholder="Enter answer option"
                            value="${option.replace(/"/g, '"')}"
                            required
                        >
                        <div class="must-have-checkbox">
                            <input
                                type="checkbox"
                                name="questions[${questionCount}][must_have][]"
                                value="true"
                                id="must-have-${questionCount}-${optIndex}"
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                ${data.must_have && data.must_have[optIndex] ? 'checked' : ''}
                            >
                            <label for="must-have-${questionCount}-${optIndex}" class="text-sm text-gray-700">Ideal Answer</label>
                        </div>
                        <button
                            type="button"
                            class="delete-btn"
                            onclick="removeMCQOption(this, ${questionCount})"
                            aria-label="Remove this option"
                        >
                            Remove
                        </button>
                    `;
                    mcqOptionsDiv.appendChild(optionWrapper);
                });
                updateDeleteButtons(questionCount);
            } else if (data && answerType === 'Yes/No') {
                const optionsDiv = document.getElementById(`options-${questionCount}`);
                optionsDiv.innerHTML = `
                    <div class="form-group">
                        <label for="must-have-${questionCount}" class="form-label">Ideal Answer</label>
                        <select
                            id="must-have-${questionCount}"
                            name="questions[${questionCount}][must_have]"
                            class="form-select"
                        >
                            <option value="">No preference</option>
                            <option value="Yes" ${mustHave === 'Yes' ? 'selected' : ''}>Yes</option>
                            <option value="No" ${mustHave === 'No' ? 'selected' : ''}>No</option>
                        </select>
                    </div>
                `;
            } else if (data && answerType === 'Numerical') {
                const optionsDiv = document.getElementById(`options-${questionCount}`);
                optionsDiv.innerHTML = `
                    <div class="form-group">
                        <label for="must-have-${questionCount}" class="form-label">Ideal Answer</label>
                        <input
                            type="number"
                            id="must-have-${questionCount}"
                            name="questions[${questionCount}][must_have]"
                            class="form-input"
                            placeholder="e.g., 8"
                            value="${mustHave !== null ? mustHave : ''}"
                            step="any"
                        >
                    </div>
                `;
            }

            questionCount++;
        }

        // Load existing screening questions
        existingQuestions.forEach(question => addQuestion(question));

        // Populate fields based on category selection
        const categorySelect = document.getElementById('category_id');
        const salarySelect = document.getElementById('salary_package');
        const locationSelect = document.getElementById('location');
        const perksTextarea = document.getElementById('perks');

        function populateSalaryOptions() {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const onsite = selectedOption.getAttribute('data-onsite') || '';
            const offshore = selectedOption.getAttribute('data-offshore') || '';
            const description = selectedOption.getAttribute('data-description') || '';

            // Update salary options
            salarySelect.innerHTML = '<option value="">Select Salary Option</option>';
            if (onsite) {
                const option = document.createElement('option');
                option.value = onsite;
                option.textContent = `Onsite: ${onsite}`;
                salarySelect.appendChild(option);
            }
            if (offshore) {
                const option = document.createElement('option');
                option.value = offshore;
                option.textContent = `Offshore: ${offshore}`;
                salarySelect.appendChild(option);
            }

            // Update description, but preserve existing perks and location
            document.getElementById('description').value = description;
        }

        function updatePerksAndLocation() {
            const selectedSalary = salarySelect.value;
            const selectedCategoryOption = categorySelect.options[categorySelect.selectedIndex];
            const onsiteSalary = selectedCategoryOption.getAttribute('data-onsite') || '';
            const offshoreSalary = selectedCategoryOption.getAttribute('data-offshore') || '';
            const perks = selectedCategoryOption.getAttribute('data-perks') || '';
            const onsitePerks = selectedCategoryOption.getAttribute('data-onsite-perks') || '';

            if (selectedSalary === onsiteSalary && onsiteSalary) {
                locationSelect.value = 'Dubai';
                perksTextarea.value = onsitePerks;
            } else if (selectedSalary === offshoreSalary && offshoreSalary) {
                locationSelect.value = 'Lahore';
                perksTextarea.value = perks;
            } else {
                locationSelect.value = '';
                perksTextarea.value = '';
            }
        }

        // Handle category selection change
        categorySelect.addEventListener('change', function () {
            populateSalaryOptions();
            // Reset salary to trigger perks/location update
            salarySelect.value = '';
            updatePerksAndLocation();
        });

        // Handle salary package selection change
        salarySelect.addEventListener('change', updatePerksAndLocation);

        // Initialize fields on page load
        window.addEventListener('load', () => {
            if (categorySelect.value) {
                populateSalaryOptions();
                // Set the salary package to the job's saved value
                const jobSalary = <?php echo json_encode($job['salary_package'] ?? ''); ?>;
                if (jobSalary && salarySelect.querySelector(`option[value="${jobSalary.replace(/"/g, '\\"')}"]`)) {
                    salarySelect.value = jobSalary;
                }
                updatePerksAndLocation();
            }
        });

        // Toggle MCQ, Yes/No, or Numerical options
        function toggleOptions(selectElem, index, hasExistingData = false) {
            const optionsDiv = document.getElementById(`options-${index}`);
            optionsDiv.innerHTML = '';

            if (selectElem.value === 'MCQ') {
                const mcqOptionsDiv = document.createElement('div');
                mcqOptionsDiv.id = `mcq-options-${index}`;
                mcqOptionsDiv.classList.add('space-y-2');
                optionsDiv.appendChild(mcqOptionsDiv);

                if (!hasExistingData) {
                    addMCQOption(index);
                }

                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'btn btn-secondary mt-2';
                addBtn.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Option
                `;
                addBtn.onclick = () => addMCQOption(index);
                optionsDiv.appendChild(addBtn);
            } else if (selectElem.value === 'Yes/No') {
                const mustHaveDiv = document.createElement('div');
                mustHaveDiv.className = 'form-group';
                mustHaveDiv.innerHTML = `
                    <label for="must-have-${index}" class="form-label">Ideal Answer</label>
                    <select
                        id="must-have-${index}"
                        name="questions[${index}][must_have]"
                        class="form-select"
                    >
                        <option value="">No preference</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                `;
                optionsDiv.appendChild(mustHaveDiv);
            } else if (selectElem.value === 'Numerical') {
                const mustHaveDiv = document.createElement('div');
                mustHaveDiv.className = 'form-group';
                mustHaveDiv.innerHTML = `
                    <label for="must-have-${index}" class="form-label">Ideal Answer</label>
                    <input
                        type="number"
                        id="must-have-${index}"
                        name="questions[${index}][must_have]"
                        class="form-input"
                        placeholder="e.g., 8"
                        step="any"
                    >
                `;
                optionsDiv.appendChild(mustHaveDiv);
            }
        }

        // Add an MCQ option
        function addMCQOption(index) {
            const mcqOptionsDiv = document.getElementById(`mcq-options-${index}`);
            const optionWrapper = document.createElement('div');
            optionWrapper.className = 'option-wrapper';

            optionWrapper.innerHTML = `
                <input
                    type="text"
                    name="questions[${index}][options][]"
                    class="form-input"
                    placeholder="Enter answer option"
                    required
                >
                <div class="must-have-checkbox">
                    <input
                        type="checkbox"
                        name="questions[${index}][must_have][]"
                        value="true"
                        id="must-have-${index}-${mcqOptionsDiv.children.length}"
                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    >
                    <label for="must-have-${index}-${mcqOptionsDiv.children.length}" class="text-sm text-gray-700">Ideal Answer</label>
                </div>
                <button
                    type="button"
                    class="delete-btn"
                    onclick="removeMCQOption(this, ${index})"
                    aria-label="Remove this option"
                >
                    Remove
                </button>
            `;

            mcqOptionsDiv.appendChild(optionWrapper);
            updateDeleteButtons(index);
        }

        // Remove an MCQ option
        function removeMCQOption(button, index) {
            const mcqOptionsDiv = document.getElementById(`mcq-options-${index}`);
            const options = mcqOptionsDiv.querySelectorAll('.option-wrapper');
            if (options.length > 1) {
                button.closest('.option-wrapper').remove();
                updateDeleteButtons(index);
            } else {
                alert('Multiple Choice questions must have at least one option.');
            }
        }

        // Update delete buttons' state
        function updateDeleteButtons(index) {
            const mcqOptionsDiv = document.getElementById(`mcq-options-${index}`);
            const deleteButtons = mcqOptionsDiv.querySelectorAll('.delete-btn');
            const options = mcqOptionsDiv.querySelectorAll('.option-wrapper');
            deleteButtons.forEach(btn => {
                btn.disabled = options.length <= 1;
            });
        }

        // Client-side form validation
        document.getElementById('jobForm').addEventListener('submit', function (e) {
            let isValid = true;
            const requirementsContent = quill.root.innerHTML.trim();
            if (requirementsContent === '<p><br></p>' || requirementsContent === '') {
                isValid = false;
                alert('Job Description is required.');
            }

            const questions = document.querySelectorAll('.question-block');
            questions.forEach((q, index) => {
                const type = q.querySelector(`select[name="questions[${index}][type]"]`).value;
                if (type === 'MCQ') {
                    const options = q.querySelectorAll(`input[name="questions[${index}][options][]"]`);
                    if (options.length < 2) {
                        isValid = false;
                        alert(`Question ${index + 1}: Multiple Choice questions must have at least two options.`);
                    }
                    options.forEach((opt, optIndex) => {
                        if (!opt.value.trim()) {
                            isValid = false;
                            alert(`Question ${index + 1}: Option ${optIndex + 1} must be filled.`);
                        }
                    });
                    const mustHaveChecked = q.querySelectorAll(`input[name="questions[${index}][must_have][]"]:checked`).length;
                    if (mustHaveChecked === 0) {
                        isValid = false;
                        alert(`Question ${index + 1}: At least one MCQ option must be marked as "Ideal Answer".`);
                    }
                } else if (type === 'Yes/No') {
                    const mustHave = q.querySelector(`select[name="questions[${index}][must_have]"]`).value;
                    if (!mustHave) {
                        isValid = false;
                        alert(`Question ${index + 1}: Yes/No questions must specify an ideal answer (Yes or No).`);
                    }
                } else if (type === 'Numerical') {
                    const mustHave = q.querySelector(`input[name="questions[${index}][must_have]"]`).value;
                    if (mustHave === '' || isNaN(mustHave)) {
                        isValid = false;
                        alert(`Question ${index + 1}: Numerical questions must specify a valid number as the ideal answer.`);
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>