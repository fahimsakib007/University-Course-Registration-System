<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = $_POST['course_code'] ?? '';
    $course_name = $_POST['course_name'] ?? '';
    $instructor = $_POST['instructor'] ?? '';
    $max_students = $_POST['max_students'] ?? '';
    $schedule = $_POST['schedule'] ?? '';

    if (!empty($course_code) && !empty($course_name) && !empty($instructor) && 
        !empty($max_students) && !empty($schedule)) {
        try {
            // Here you would typically save to database
            // For now, we'll just redirect back with a success message
            $_SESSION['message'] = "Course added successfully!";
            header('Location: admin.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: admin.php');
            exit;
        }
    } else {
        $_SESSION['error'] = "Please fill in all fields.";
        header('Location: admin.php');
        exit;
    }
}
?> 