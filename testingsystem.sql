-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2025 at 02:09 PM
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
  `job_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','reviewed','rejected','accepted') DEFAULT 'pending'
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
  `password` varchar(255) NOT NULL,
  `category_id` int(3) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`candidate_id`, `name`, `email`, `password`, `category_id`, `created_at`) VALUES
(1, 'John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '2025-03-19 07:27:41'),
(2, 'Dhaniya Nithin', 'dhanyanithin18@gmail.com', '$2y$10$dEbQgBv.hU7BmBkh7otWf.FA7uBIbQew5Ij8T0JgiFT.47jdUlu9y', 1, '2025-03-21 06:17:59'),
(3, 'Naveen Senagasetti', 'senagasetti.naveen@gmail.com', '$2y$10$TCM6aq.ca4IC9vY0NPtC9uo7o9FuDK81ZqPomMZ72mEK2mGhTFlVe', 1, '2025-03-21 06:20:52'),
(4, 'Fayad Abdul Salam', 'fayadabdulsalam@gmail.com', '$2y$10$mAe1KxLavr.EAk5EFvVkHOp6g569f3YgnjiaMTeB/kv.mkk6O.s2q', 2, '2025-03-21 06:23:50'),
(5, 'Pradeep Prajapati', 'prdpprajapati07@gmail.com', '$2y$10$zs7z8U7uFWuakO6HPAphUuTR/R1gqwHHv8A4VsR7o5E59UY2la6iu', 1, '2025-03-21 06:26:11');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`job_id`, `title`, `description`, `requirements`, `salary_package`, `perks`, `location`, `category_id`, `created_at`) VALUES
(1, 'STE Req ID 89', 'Cinergie Digital is seeking a highly skilled Senior Technical Architect with expertise in Oracle SQL, database design, optimization, and performance tuning. The ideal candidate will have a strong background in developing scalable database solutions, with experience in the airline industry or Passenger Service Systems (PSS) being a significant advantage. This role requires collaboration with application developers, architects, and business stakeholders to design robust database structures that support business needs effectively.\r\n\r\nKey Responsibilities:\r\n•	Architect, design, and develop high-performance database solutions with a focus on Oracle SQL.\r\n•	Optimize and fine-tune database queries, indexing strategies, and stored procedures to enhance performance.\r\n•	Oversee database modeling, schema design, and data normalization to align with best practices.\r\n•	Work closely with software engineers, DevOps teams, and business analysts to integrate database structures with applications.\r\n•	Implement database security best practices, ensuring compliance with industry standards and data protection regulations.\r\n•	Design and maintain disaster recovery solutions, backup strategies, and high-availability configurations.\r\n•	Lead efforts in data migration, ETL processes, and integration between different systems.\r\n•	Troubleshoot and resolve complex database-related issues to maintain high availability and reliability.\r\n•	Develop technical documentation, including ER diagrams, database dictionaries, and stored procedures.\r\n•	Stay abreast of emerging technologies, particularly in cloud-based database solutions such as AWS RDS, Azure SQL, or Oracle Cloud.\r\n', 'Required Skills & Experience:\r\n•	8+ years of experience in database development and architecture, specializing in Oracle SQL and PL/SQL.\r\n•	Strong expertise in database performance tuning, indexing, execution plans, and optimization techniques.\r\n•	Experience in large-scale transactional database design and implementation.\r\n•	Knowledge of partitioning, materialized views, caching strategies, and query optimization.\r\n•	Hands-on experience with ETL processes, data migration, and system integrations.\r\n•	Familiarity with Passenger Service Systems (PSS) or experience working in the airline domain is highly desirable.\r\n•	Understanding of cloud-based databases (AWS RDS, Azure SQL, or Oracle Cloud) is a plus.\r\n•	Strong analytical and problem-solving skills with attention to detail.\r\n•	Excellent communication and leadership skills, with the ability to mentor and guide development teams.\r\n\r\nPreferred Qualifications:\r\n•	Bachelor\'s or Master’s degree in Computer Science, Information Technology, or a related field.\r\n•	Oracle Database certifications (e.g., Oracle Certified Professional – OCP) are a plus.\r\n•	Prior experience working with high-volume transactional databases in the airline industry.\r\n•	Familiarity with modern database architectures and microservices-driven development.\r\n', '550000', 'Medical, Paid Leaves, PF', 'Lahore', 7, '2025-03-24 05:51:42'),
(2, 'SSE .NET FZ PK ID 150', 'Key Responsibilities\r\n• Application Development: Develop and maintain robust, scalable, and high-\r\nperformance .NET applications using C#, ASP.NET, and MVC frameworks.\r\n• Microservices: Work with microservices architectures, leveraging Kubernetes and\r\nDocker containers for deployment and orchestration.\r\n• API Development: Develop RESTful APIs using a design-first approach with\r\nOpenAPI or RAML, and integrate them with front-end systems and third-party\r\nservices.\r\n• Code Quality: Write clean, maintainable, and testable code adhering to best practices\r\nand industry standards. Participate in code reviews to maintain coding standards\r\nacross the team.\r\n• Performance Optimization: Optimize application performance and troubleshoot issues\r\nrelated to resource consumption, containerization, and deployment.\r\n• Containerization: Implement containerization solutions using Docker and Kubernetes\r\nfor cloud deployments.\r\n• Collaboration: Work with DevOps, QA, and Product Management teams to deliver\r\nhigh-quality software solutions.\r\n• Continuous Improvement: Continuously improve software development processes\r\nand stay updated with emerging technologies and industry trends', 'Technical/Core Skills:\r\n• Strong expertise in .NET application development with C#, ASP.NET, and MVC.\r\n• Comprehensive understanding of object-oriented design principles and design\r\npatterns.\r\n• Strong knowledge of data structures and algorithms.\r\n• Experience with containerization technologies like Docker and orchestration using\r\nKubernetes.\r\n• Proficiency in deploying containerized applications on Azure, AWS, or GCP.\r\n• Knowledge of CI/CD pipelines and DevOps practices.\r\nTools and Methodologies:\r\n• Familiarity with Agile methodologies and tools like VSO, Jira, Git, and Jenkins.\r\n• Experience with monitoring tools (e.g., Prometheus, Grafana) and logging\r\nframeworks (e.g., ELK Stack, Splunk).', '450000', 'Medical, Paid Leaves', 'Lahore', 1, '2025-03-26 08:46:26');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `testcategories`
--

INSERT INTO `testcategories` (`category_id`, `category_name`, `description`, `created_at`) VALUES
(1, 'SSE .NET ', '(5-8) yrs', '2025-03-20 04:19:59'),
(2, 'SE .NET', '(3-5) yrs', '2025-03-20 04:20:12'),
(3, 'SSE JAVA', '(5-8) yrs', '2025-03-20 04:20:25'),
(4, 'SE JAVA', '(3-5) yrs', '2025-03-20 04:20:35'),
(5, 'Senior Data Engineer', '5+ years', '2025-03-20 04:21:05'),
(6, 'Junior Data Engineer', '3+ years', '2025-03-20 04:21:15'),
(7, 'STE PL/SQL', '5+ yrs', '2025-03-24 05:48:12');

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
  ADD KEY `job_id` (`job_id`),
  ADD KEY `candidate_id` (`candidate_id`);

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
  ADD UNIQUE KEY `email` (`email`);

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
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `testcategories`
--
ALTER TABLE `testcategories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`),
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`);

--
-- Constraints for table `candidateanswers`
--
ALTER TABLE `candidateanswers`
  ADD CONSTRAINT `candidateanswers_ibfk_1` FOREIGN KEY (`result_id`) REFERENCES `testresults` (`result_id`),
  ADD CONSTRAINT `candidateanswers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`);

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `testcategories` (`category_id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`);

--
-- Constraints for table `testresults`
--
ALTER TABLE `testresults`
  ADD CONSTRAINT `testresults_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`),
  ADD CONSTRAINT `testresults_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`);

--
-- Constraints for table `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `tests_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
