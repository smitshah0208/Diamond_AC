<?php
// Suppress all errors to prevent HTML output in JSON responses
error_reporting(0);
ini_set('display_errors', 0);

// Database configuration
$host = 'localhost';
$username = 'root';        // Change if needed
$password = '';            // Change if needed
$database = 'ac_test';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection (silently)
if ($conn->connect_error) {
    $conn = null;
    die(json_encode(['error' => 'Database connection failed']));
}

// Set charset for proper character handling
$conn->set_charset("utf8mb4");
?> 