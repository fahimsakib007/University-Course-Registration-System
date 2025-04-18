-- Drop database if exists and create new one
DROP DATABASE IF EXISTS course_registration;
CREATE DATABASE course_registration;
USE course_registration;

-- Create departments table
CREATE TABLE departments (
    department_id INT PRIMARY KEY AUTO_INCREMENT,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    department_code VARCHAR(10) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table for storing user credentials and information
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('student', 'instructor', 'admin') NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    department VARCHAR(100),
    program VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create courses table
CREATE TABLE courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    department_id INT,
    credits INT NOT NULL,
    instructor_id INT,
    max_students INT DEFAULT 30,
    current_students INT DEFAULT 0,
    description TEXT,
    schedule_day VARCHAR(50),
    schedule_time VARCHAR(50),
    room VARCHAR(50),
    semester ENUM('Fall', 'Spring', 'Summer'),
    year YEAR,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE
);

-- Create enrollments table for student course registration
CREATE TABLE enrollments (
    enrollment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    status ENUM('enrolled', 'dropped', 'completed') DEFAULT 'enrolled',
    grade VARCHAR(2),
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id)
);

-- Create notifications table for system notifications
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

-- Insert basic departments
INSERT INTO departments (department_name, department_code, description) VALUES
('Computer Science', 'CS', 'Department of Computer Science and Software Engineering'),
('Mathematics', 'MATH', 'Department of Mathematical Sciences'),
('Physics', 'PHYS', 'Department of Physics and Astronomy'),
('Engineering', 'ENG', 'Department of Engineering'),
('Business', 'BUS', 'Department of Business Administration');

-- Create indexes for better performance
CREATE INDEX idx_user_role ON users(role);
CREATE INDEX idx_user_department ON users(department);
CREATE INDEX idx_course_department ON courses(department_id);
CREATE INDEX idx_course_instructor ON courses(instructor_id);
CREATE INDEX idx_enrollment_student ON enrollments(student_id);
CREATE INDEX idx_enrollment_course ON enrollments(course_id);
CREATE INDEX idx_notification_user ON notifications(user_id);

-- Create trigger for enrollment notifications
DELIMITER //
CREATE TRIGGER after_enrollment_insert
AFTER INSERT ON enrollments
FOR EACH ROW
BEGIN
    DECLARE instructor_id INT;
    SELECT instructor_id INTO instructor_id FROM courses WHERE course_id = NEW.course_id;
    
    -- Update course current_students count
    UPDATE courses 
    SET current_students = current_students + 1 
    WHERE course_id = NEW.course_id;
    
    -- Create notification for instructor
    INSERT INTO notifications (user_id, course_id, title, message)
    SELECT 
        instructor_id,
        NEW.course_id,
        'New Enrollment',
        CONCAT(
            (SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE user_id = NEW.student_id),
            ' has enrolled in ',
            (SELECT course_code FROM courses WHERE course_id = NEW.course_id)
        );
END //

-- Create trigger for course drops
CREATE TRIGGER after_enrollment_delete
AFTER DELETE ON enrollments
FOR EACH ROW
BEGIN
    DECLARE instructor_id INT;
    SELECT instructor_id INTO instructor_id FROM courses WHERE course_id = OLD.course_id;
    
    -- Update course current_students count
    UPDATE courses 
    SET current_students = current_students - 1 
    WHERE course_id = OLD.course_id;
    
    -- Create notification for instructor
    INSERT INTO notifications (user_id, course_id, title, message)
    SELECT 
        instructor_id,
        OLD.course_id,
        'Course Drop',
        CONCAT(
            (SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE user_id = OLD.student_id),
            ' has dropped ',
            (SELECT course_code FROM courses WHERE course_id = OLD.course_id)
        );
END //
DELIMITER ; 