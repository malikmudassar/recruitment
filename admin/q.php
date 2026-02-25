<?php
include 'db.php'; // Include database connection

$test_id = 1; // Replace with the actual test_id

$questions = [
    'What are the key differences between Oracle SQL and PL/SQL?',
    'How do you optimize a slow-running SQL query in Oracle?',
    'What is an execution plan, and how do you interpret it?',
    'What are materialized views, and how are they different from regular views?',
    'How do you handle partitioning in Oracle, and what are its benefits?',
    'What are indexes, and how do you decide which columns to index?',
    'Can you explain the difference between a function and a procedure in PL/SQL?',
    'How do you handle data migration in Oracle?',
    'What are the best practices for database performance tuning?',
    'What is the difference between a primary key and a unique key?',
    'How do you implement caching strategies in Oracle?',
    'What are the common types of joins in SQL, and how do they differ?',
    'How do you handle errors in PL/SQL?',
    'What is the difference between a database trigger and a stored procedure?',
    'How do you monitor and optimize database performance in real-time?',
    'What is the purpose of the WITH clause in SQL?',
    'How do you ensure data integrity in a database?',
    'What is the difference between DELETE and TRUNCATE?',
    'How do you handle database backups and recovery?',
    'What is the role of a database architect, and how do you approach designing a large-scale database system?'
];

foreach ($questions as $question) {
    $stmt = $conn->prepare("INSERT INTO questions (test_id, question_text) VALUES (:test_id, :question_text)");
    $stmt->bindParam(':test_id', $test_id);
    $stmt->bindParam(':question_text', $question);
    $stmt->execute();
}

echo "Questions inserted successfully!";
?>
