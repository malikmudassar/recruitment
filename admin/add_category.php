<?php
session_start();

// Only allow admins
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}


// Include database connection
require 'db.php';

// Sanitize admin name
$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim(filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS));
    $offshore_salary = trim(filter_input(INPUT_POST, 'offshore_salary', FILTER_SANITIZE_SPECIAL_CHARS));
    $onsite_salary = trim(filter_input(INPUT_POST, 'onsite_salary', FILTER_SANITIZE_SPECIAL_CHARS));
    $perks = trim(filter_input(INPUT_POST, 'perks', FILTER_SANITIZE_SPECIAL_CHARS));
    $onsite_perks = trim(filter_input(INPUT_POST, 'onsite_perks', FILTER_SANITIZE_SPECIAL_CHARS));

    // Validation
    if (empty($category_name)) {
        $error = 'Category name is required.';
    } elseif (empty($offshore_salary)) {
        $error = 'Offshore salary is required.';
    } elseif (empty($onsite_salary)) {
        $error = 'Onsite salary is required.';
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO testcategories (category_name, description, offshore_salary, onsite_salary, perks, onsite_perks)
                VALUES (:category_name, :description, :offshore_salary, :onsite_salary, :perks, :onsite_perks)
            ");
            $stmt->execute([
                ':category_name' => $category_name,
                ':description' => $description,
                ':offshore_salary' => $offshore_salary,
                ':onsite_salary' => $onsite_salary,
                ':perks' => $perks,
                ':onsite_perks' => $onsite_perks
            ]);

            $success = 'Category added successfully!';
            $_POST = []; // Clear form data after successful submission
        } catch (PDOException $e) {
            $error = 'Error adding category. Please try again.';
            error_log("Error adding category: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to add new job categories">
    <link rel="icon" type="image/svg+xml" href="https://cinergiedigital.com/favicon.svg">
    <title>Add Category - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
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
            margin-left: 200px;
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
        .form-textarea:focus {
            outline: none;
            border-color: #0A66C2;
            box-shadow: 0 0 0 3px rgba(10, 102, 194, 0.2);
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
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
        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }
            .card {
                padding: 1.25rem;
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
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Add New Category</h1>

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

            <form action="add_category.php" method="POST" class="space-y-6">
                <!-- Category Details Section -->
                <div>
                    <h2 class="section-title">Category Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="category_name" class="form-label">Category Name <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                id="category_name"
                                name="category_name"
                                value="<?php echo isset($_POST['category_name']) ? htmlspecialchars($_POST['category_name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                class="form-input"
                                placeholder="e.g., Software Development"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label for="description" class="form-label">Experience Required</label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-textarea"
                                placeholder="Describe the experience required"
                            ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Compensation Section -->
                <div>
                    <h2 class="section-title">Compensation</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="offshore_salary" class="form-label">Offshore Salary <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                id="offshore_salary"
                                name="offshore_salary"
                                value="<?php echo isset($_POST['offshore_salary']) ? htmlspecialchars($_POST['offshore_salary'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                class="form-input"
                                placeholder="e.g., $50,000 - $70,000"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label for="onsite_salary" class="form-label">Onsite Salary <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                id="onsite_salary"
                                name="onsite_salary"
                                value="<?php echo isset($_POST['onsite_salary']) ? htmlspecialchars($_POST['onsite_salary'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                class="form-input"
                                placeholder="e.g., $60,000 - $80,000"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label for="perks" class="form-label">Offshore Perks</label>
                            <textarea
                                id="perks"
                                name="perks"
                                class="form-textarea"
                                placeholder="e.g., Health insurance, remote work"
                            ><?php echo isset($_POST['perks']) ? htmlspecialchars($_POST['perks'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="onsite_perks" class="form-label">Onsite Perks</label>
                            <textarea
                                id="onsite_perks"
                                name="onsite_perks"
                                class="form-textarea"
                                placeholder="e.g., Relocation assistance, housing allowance"
                            ><?php echo isset($_POST['onsite_perks']) ? htmlspecialchars($_POST['onsite_perks'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-3">
                    <button type="submit" class="btn btn-primary">Add Category</button>
                    <a href="categories.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
