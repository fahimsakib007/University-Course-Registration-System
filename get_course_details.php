<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if course_id is provided
if (!isset($_GET['course_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Course ID is required']);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'course_registration');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Prepare and execute query
$stmt = $conn->prepare("SELECT course_id, course_code, course_name, department_id, instructor_id, 
                              COALESCE(schedule_day, '') as schedule_day, 
                              COALESCE(schedule_time, '') as schedule_time, 
                              room, max_students 
                       FROM courses WHERE course_id = ?");
$stmt->bind_param("i", $_GET['course_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($course = $result->fetch_assoc()) {
    // Debug log
    error_log("Course data: " . print_r($course, true));
    echo json_encode($course);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Course not found']);
}

$stmt->close();
$conn->close();
?> 