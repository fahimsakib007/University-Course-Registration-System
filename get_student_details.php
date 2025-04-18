<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if student ID is provided
if (!isset($_GET['student_id'])) {
    echo json_encode(['error' => 'Student ID is required']);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'course_registration');
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$student_id = intval($_GET['student_id']);

// Get student details
$stmt = $conn->prepare("
    SELECT u.*, d.department_name
    FROM users u
    LEFT JOIN departments d ON u.department = d.department_name
    WHERE u.user_id = ? AND u.role = 'student'
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    echo json_encode(['error' => 'Student not found']);
    exit;
}

// Get student's enrolled courses
$courses_stmt = $conn->prepare("
    SELECT c.course_code, c.course_name, e.grade
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    WHERE e.student_id = ?
");
$courses_stmt->bind_param("i", $student_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();

$courses = [];
while ($course = $courses_result->fetch_assoc()) {
    $courses[] = $course;
}

// Prepare response data
$response = [
    'user_id' => $student['user_id'],
    'first_name' => $student['first_name'],
    'last_name' => $student['last_name'],
    'email' => $student['email'],
    'department' => $student['department'],
    'program' => $student['program'],
    'courses' => $courses
];

echo json_encode($response);

$stmt->close();
$courses_stmt->close();
$conn->close();
?> 