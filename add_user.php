<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = $_POST['user_type'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($user_type) && !empty($username) && !empty($password)) {
        try {
            // Here you would typically save to database
            // For now, we'll just redirect back with a success message
            $_SESSION['message'] = "User added successfully!";
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