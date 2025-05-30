<?php
// db.php - Database connection file

// Database credentials for local development
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'emps');  // Your database name

// Create connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME,4306);

// Check connection
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
}

// Set charset to utf8mb4 for better Unicode support
$mysqli->set_charset("utf8mb4");
?>
