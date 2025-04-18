<?php
require_once 'src/classes/UserFactory.php';
require_once 'src/classes/Course.php';

// Create users using Factory Pattern
try {
    // Create a student
    $studentData = [
        'username' => 'john_doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'studentId' => 'ST001',
        'major' => 'Computer Science'
    ];
    $student = UserFactory::createUser('student', $studentData);

    // Create an instructor
    $instructorData = [
        'username' => 'prof_smith',
        'email' => 'smith@example.com',
        'password' => 'password456',
        'department' => 'Computer Science'
    ];
    $instructor = UserFactory::createUser('instructor', $instructorData);

    // Create a course
    $course = new Course('CS101', 'Introduction to Programming', 'CS-101', 30);
    $course->setInstructor($instructor);

    // Demonstrate Observer Pattern
    // Student enrolls in the course
    $student->enrollCourse($course);

    // Instructor updates the course (which notifies enrolled students)
    $course->notifyStudents("New assignment posted for CS101!");

    // Student drops the course
    $student->dropCourse($course);

    echo "Example completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 