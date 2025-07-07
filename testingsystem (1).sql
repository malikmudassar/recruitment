-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2025 at 11:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `testingsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Khani', 'mudassar.khani@cinergiedigital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-03-19 11:14:22');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cv_path` varchar(500) NOT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`)),
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `phone_no` varchar(15) DEFAULT NULL,
  `years_of_experience` int(11) DEFAULT NULL,
  `linkedin_profile` varchar(255) DEFAULT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `job_title` varchar(50) DEFAULT NULL,
  `notice_period` varchar(50) DEFAULT NULL,
  `salary_accept` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidateanswers`
--

CREATE TABLE `candidateanswers` (
  `answer_id` int(11) NOT NULL,
  `result_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `answer_text` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `candidate_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `category_id` int(3) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `job_id` int(11) NOT NULL,
  `cv_path` varchar(500) NOT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`)),
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `salary_package` varchar(50) DEFAULT NULL,
  `perks` text DEFAULT NULL,
  `location` varchar(50) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `screening_questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`screening_questions`)),
  `reference` varchar(50) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `test_id` int(11) DEFAULT NULL,
  `question_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `test_id`, `question_text`, `created_at`) VALUES
(1, 1, 'What are the key differences between Oracle SQL and PL/SQL?', '2025-03-24 08:43:28'),
(2, 1, 'How do you optimize a slow-running SQL query in Oracle?', '2025-03-24 08:43:28'),
(3, 1, 'What is an execution plan, and how do you interpret it?', '2025-03-24 08:43:28'),
(4, 1, 'What are materialized views, and how are they different from regular views?', '2025-03-24 08:43:28'),
(5, 1, 'How do you handle partitioning in Oracle, and what are its benefits?', '2025-03-24 08:43:28'),
(6, 1, 'What are indexes, and how do you decide which columns to index?', '2025-03-24 08:43:28'),
(7, 1, 'Can you explain the difference between a function and a procedure in PL/SQL?', '2025-03-24 08:43:28'),
(8, 1, 'How do you handle data migration in Oracle?', '2025-03-24 08:43:28'),
(9, 1, 'What are the best practices for database performance tuning?', '2025-03-24 08:43:28'),
(10, 1, 'What is the difference between a primary key and a unique key?', '2025-03-24 08:43:28'),
(11, 1, 'How do you implement caching strategies in Oracle?', '2025-03-24 08:43:28'),
(12, 1, 'What are the common types of joins in SQL, and how do they differ?', '2025-03-24 08:43:28'),
(13, 1, 'How do you handle errors in PL/SQL?', '2025-03-24 08:43:28'),
(14, 1, 'What is the difference between a database trigger and a stored procedure?', '2025-03-24 08:43:28'),
(15, 1, 'How do you monitor and optimize database performance in real-time?', '2025-03-24 08:43:28'),
(16, 1, 'What is the purpose of the WITH clause in SQL?', '2025-03-24 08:43:28'),
(17, 1, 'How do you ensure data integrity in a database?', '2025-03-24 08:43:28'),
(18, 1, 'What is the difference between DELETE and TRUNCATE?', '2025-03-24 08:43:28'),
(19, 1, 'How do you handle database backups and recovery?', '2025-03-24 08:43:28'),
(20, 1, 'What is the role of a database architect, and how do you approach designing a large-scale database system?', '2025-03-24 08:43:28'),
(21, 2, 'Explain the role of CLR in .NET applications.', '2025-03-26 08:51:15'),
(22, 2, 'What is the CTS (Common Type System), and why is it important in .NET?', '2025-03-26 08:51:15'),
(23, 2, 'Describe the garbage collection process in .NET. How does it manage memory?', '2025-03-26 08:51:15'),
(24, 2, 'When should you implement IDisposable, and how does the using statement help with resource management?', '2025-03-26 08:51:15'),
(25, 2, 'How does async/await work in C#? What happens internally when you use it?', '2025-03-26 08:51:15'),
(26, 2, 'Explain how LINQ queries are executed. What\'s the difference between IEnumerable<T> and IQueryable<T>?', '2025-03-26 08:51:15'),
(27, 2, 'What is Dependency Injection (DI) in ASP.NET Core? How does it improve code maintainability?', '2025-03-26 08:51:15'),
(28, 2, 'Can you explain middleware in ASP.NET Core? How do you create a custom middleware?', '2025-03-26 08:51:15'),
(29, 2, 'Describe the middleware pipeline in ASP.NET Core and its lifecycle.', '2025-03-26 08:51:15'),
(30, 2, 'What is the Repository Pattern? How does it differ from a direct database call?', '2025-03-26 08:51:15'),
(31, 2, 'Explain the Unit of Work pattern. How does it help in transaction management?', '2025-03-26 08:51:15'),
(32, 2, 'How would you design a scalable architecture for an enterprise .NET application?', '2025-03-26 08:51:15'),
(33, 2, 'How do you debug a .NET application that crashes unexpectedly in production?', '2025-03-26 08:51:15'),
(34, 2, 'What are some best practices for improving application performance in .NET?', '2025-03-26 08:51:15'),
(35, 2, 'How do you profile and optimize a slow-performing API in ASP.NET Core?', '2025-03-26 08:51:15'),
(36, 2, 'Explain a time when you faced and resolved a NullReferenceException. How did you approach it?', '2025-03-26 08:51:15'),
(37, 2, 'What are Minimal APIs in .NET 6/7? When would you use them instead of MVC?', '2025-03-26 08:51:15'),
(38, 2, 'What are source generators in C#? How do they improve performance and reduce runtime overhead?', '2025-03-26 08:51:15'),
(39, 2, 'Explain the performance improvements introduced in .NET 6/7/8.', '2025-03-26 08:51:15'),
(40, 2, 'What is FileScopedNamespace in .NET 6, and why was it introduced?', '2025-03-26 08:51:15'),
(41, 2, 'How would you secure an ASP.NET Core Web API from unauthorized access?', '2025-03-26 08:51:15'),
(42, 2, 'What are some common security vulnerabilities in .NET applications, and how do you prevent them?', '2025-03-26 08:51:15'),
(43, 2, 'How does OAuth2 and JWT work for authentication in .NET applications?', '2025-03-26 08:51:15'),
(44, 2, 'How would you prevent SQL Injection and Cross-Site Scripting (XSS) in a .NET application?', '2025-03-26 08:51:15');

-- --------------------------------------------------------

--
-- Table structure for table `testcategories`
--

CREATE TABLE `testcategories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `offshore_salary` varchar(100) DEFAULT NULL,
  `onsite_salary` varchar(100) DEFAULT NULL,
  `perks` varchar(50) NOT NULL DEFAULT 'N/A',
  `onsite_perks` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `testresults`
--

CREATE TABLE `testresults` (
  `result_id` int(11) NOT NULL,
  `candidate_id` int(11) DEFAULT NULL,
  `test_id` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `total_questions` int(11) DEFAULT NULL,
  `correct_answers` int(11) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE `tests` (
  `test_id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tests`
--

INSERT INTO `tests` (`test_id`, `job_id`, `title`, `description`, `duration`, `created_at`) VALUES
(1, 1, '20 Question theory test', 'Theory test for the following topics \r\n\r\n1. Key differences between Oracle SQL and PL/SQL \r\n\r\n2. Optimizing a slow-running SQL query \r\n\r\n3. Execution plan and interpretation \r\n\r\n4. Materialized views vs. regular views \r\n\r\n5. Partitioning in Oracle \r\n\r\n6. Indexes and column selection \r\n\r\n7. Difference between a function and a procedure \r\n\r\n8. Data migration in Oracle \r\n\r\n9. Best practices for database performance tuning \r\n\r\n10. Difference between primary key and unique key \r\n\r\n11. Caching strategies in Oracle \r\n\r\n12. Common types of joins in SQL \r\n\r\n13. Handling errors in PL/SQL \r\n\r\n14. Difference between a database trigger and a stored procedure \r\n\r\n15. Monitoring and optimizing database performance \r\n\r\n16. Purpose of the WITH clause \r\n\r\n17. Ensuring data integrity \r\n\r\n18. Difference between DELETE and TRUNCATE \r\n\r\n19. Handling database backups and recovery \r\n\r\n20. Role of a database architect ', 30, '2025-03-24 08:15:52'),
(2, 2, 'DotNet Theory ', '20 Question', 30, '2025-03-26 08:47:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `applications_ibfk_1` (`job_id`);

--
-- Indexes for table `candidateanswers`
--
ALTER TABLE `candidateanswers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `result_id` (`result_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`candidate_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_job_id` (`job_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `test_id` (`test_id`);

--
-- Indexes for table `testcategories`
--
ALTER TABLE `testcategories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `testresults`
--
ALTER TABLE `testresults`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `test_id` (`test_id`);

--
-- Indexes for table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`test_id`),
  ADD KEY `job_id` (`job_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `candidateanswers`
--
ALTER TABLE `candidateanswers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `candidate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `testcategories`
--
ALTER TABLE `testcategories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `testresults`
--
ALTER TABLE `testresults`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tests`
--
ALTER TABLE `tests`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE SET NULL;

--
-- Constraints for table `candidateanswers`
--
ALTER TABLE `candidateanswers`
  ADD CONSTRAINT `candidateanswers_ibfk_1` FOREIGN KEY (`result_id`) REFERENCES `testresults` (`result_id`),
  ADD CONSTRAINT `candidateanswers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`);

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `fk_job_id` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `testcategories` (`category_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
