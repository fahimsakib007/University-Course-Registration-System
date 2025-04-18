<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing MySQL Database Connection</h2>";

// Test database connection
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "1. Database connection successful!<br>";
    
    // Test if tables exist
    try {
        $tables = ['users', 'courses', 'enrollments'];
        foreach ($tables as $table) {
            $query = "SHOW TABLES LIKE '$table'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                echo "2. Table '$table' exists!<br>";
            } else {
                echo "2. Table '$table' does NOT exist!<br>";
            }
        }

        // Test if we can query users table
        $query = "SELECT * FROM users LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        echo "3. Can query users table successfully!<br>";

        // Display server information
        echo "<br><h3>Server Information:</h3>";
        echo "PHP Version: " . phpversion() . "<br>";
        echo "MySQL Server Version: " . $db->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br>";
        echo "Database Name: course_registration<br>";

    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Database connection failed!<br>";
    echo "Please check:<br>";
    echo "1. Is MySQL running in XAMPP?<br>";
    echo "2. Is the database 'course_registration' created?<br>";
    echo "3. Are the credentials in config/database.php correct?<br>";
}
?> 