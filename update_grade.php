<?php
session_start();
header('Content-Type: application/json'); // Set JSON header

// Check if user is logged in and is an instructor
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'course_registration');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if all required parameters are present
if (!isset($_POST['student_id']) || !isset($_POST['course_id']) || !isset($_POST['grade'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Get and sanitize parameters
$student_id = intval($_POST['student_id']);
$course_id = intval($_POST['course_id']);
$grade = strtoupper(trim($_POST['grade']));

// Validate grade format
$valid_grades = ['A', 'B', 'C', 'D', 'F'];
if (!in_array($grade, $valid_grades)) {
    echo json_encode(['success' => false, 'message' => 'Invalid grade format. Please use A, B, C, D, or F']);
    exit;
}

try {
    // First check if the enrollment exists
    $check_stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND course_id = ?");
    $check_stmt->bind_param("ii", $student_id, $course_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student is not enrolled in this course']);
        exit;
    }
    
    // Update the grade
    $update_stmt = $conn->prepare("UPDATE enrollments SET grade = ? WHERE student_id = ? AND course_id = ?");
    $update_stmt->bind_param("sii", $grade, $student_id, $course_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Grade updated successfully']);
    } else {
        throw new Exception('Failed to update grade');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error updating grade: ' . $e->getMessage()]);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    $conn->close();
}
?> 