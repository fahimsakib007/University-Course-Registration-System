<?php
session_start();

// Check if user is logged in and is an instructor
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: index.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'course_registration');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get instructor data
$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$instructor_email = $_SESSION['email'];
$instructor_department = $_SESSION['department'];

// Get instructor's courses
$courses_query = $conn->prepare("SELECT * FROM courses WHERE instructor_id = ?");
$courses_query->bind_param("i", $instructor_id);
$courses_query->execute();
$courses_result = $courses_query->get_result();

// Get students in instructor's courses with enrollments and grades
$students_query = $conn->prepare("
    SELECT DISTINCT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.program,
        c.course_id,
        c.course_code,
        c.course_name,
        e.grade,
        e.enrollment_id
    FROM users u
    JOIN enrollments e ON u.user_id = e.student_id
    JOIN courses c ON e.course_id = c.course_id
    WHERE c.instructor_id = ?
    ORDER BY c.course_code, u.last_name, u.first_name
");
$students_query->bind_param("i", $instructor_id);
$students_query->execute();
$students_result = $students_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - Course Registration System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            display: flex;
            min-height: 100vh;
            background-color: #f0f2f5;
        }

        .sidebar {
            width: 250px;
            background-color: #1976D2;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
        }

        .sidebar h1 {
            font-size: 1.5em;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 15px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 1.1em;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
            cursor: pointer;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-links a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            flex-grow: 1;
        }

        .content-section {
            display: none;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .content-section.active {
            display: block;
        }

        .profile-info {
            margin-bottom: 20px;
        }

        .profile-info h2 {
            color: #1976D2;
            margin-bottom: 20px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-item strong {
            display: inline-block;
            width: 120px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f5f5f5;
            color: #333;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .grade-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-transform: uppercase;
            margin-right: 10px;
        }

        .grade-status {
            display: inline-block;
            width: 20px;
            text-align: center;
            font-weight: bold;
        }

        .save-grade {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .save-grade:hover {
            background-color: #45a049;
        }

        .save-grade:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            color: white;
            display: none;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification.success {
            background-color: #4CAF50;
        }

        .notification.error {
            background-color: #f44336;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h1>Instructor Dashboard</h1>
        <ul class="nav-links">
            <li><a onclick="showSection('profile')" class="active">Profile</a></li>
            <li><a onclick="showSection('courses')">My Courses</a></li>
            <li><a onclick="showSection('students')">Manage Students</a></li>
            <li><a onclick="showSection('grades')">Grade Management</a></li>
            <li><a onclick="showSection('schedule')">Schedule</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div id="notification" class="notification"></div>
        
        <!-- Profile Section -->
        <div id="profile" class="content-section active">
            <div class="profile-info">
                <h2>Instructor Profile</h2>
                <div class="info-item">
                    <strong>Name:</strong> <?php echo htmlspecialchars($instructor_name); ?>
                </div>
                <div class="info-item">
                    <strong>ID:</strong> <?php echo htmlspecialchars($instructor_id); ?>
                </div>
                <div class="info-item">
                    <strong>Department:</strong> <?php echo htmlspecialchars($instructor_department); ?>
                </div>
                <div class="info-item">
                    <strong>Email:</strong> <?php echo htmlspecialchars($instructor_email); ?>
                </div>
            </div>
        </div>

        <!-- Courses Section -->
        <div id="courses" class="content-section">
            <h2>My Courses</h2>
            <table>
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Schedule</th>
                        <th>Room</th>
                        <th>Current Students</th>
                        <th>Max Students</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($course = $courses_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($course['schedule_day'] . ' ' . $course['schedule_time']); ?></td>
                        <td><?php echo htmlspecialchars($course['room']); ?></td>
                        <td><?php echo htmlspecialchars($course['current_students']); ?></td>
                        <td><?php echo htmlspecialchars($course['max_students']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Students Section -->
        <div id="students" class="content-section">
            <h2>Manage Students</h2>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Email</th>
                        <th>Program</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $students_result->data_seek(0);
                    while($student = $students_result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['course_code'] . ' - ' . $student['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['program']); ?></td>
                        <td>Enrolled</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Grades Section -->
        <div id="grades" class="content-section">
            <h2>Grade Management</h2>
            <p style="margin-bottom: 15px; color: #666;">Enter grades as A, B, C, D, or F</p>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Current Grade</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $students_result->data_seek(0);
                    while($student = $students_result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['course_code']); ?></td>
                        <td>
                            <input type="text" class="grade-input" 
                                   value="<?php echo htmlspecialchars($student['grade'] ?? ''); ?>" 
                                   maxlength="1"
                                   pattern="[A-Fa-f]"
                                   data-student-id="<?php echo $student['user_id']; ?>" 
                                   data-course-id="<?php echo $student['course_id']; ?>">
                            <span class="grade-status"></span>
                        </td>
                        <td>
                            <button class="save-grade" onclick="saveGrade(this)">Save Grade</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Schedule Section -->
        <div id="schedule" class="content-section">
            <h2>Teaching Schedule</h2>
            <div class="schedule-grid">
                <?php
                $courses_result->data_seek(0);
                while($course = $courses_result->fetch_assoc()): 
                ?>
                <div class="schedule-item">
                    <strong><?php echo htmlspecialchars($course['course_code']); ?></strong><br>
                    <?php echo htmlspecialchars($course['course_name']); ?><br>
                    <?php echo htmlspecialchars($course['schedule_day'] . ' ' . $course['schedule_time']); ?><br>
                    Room: <?php echo htmlspecialchars($course['room']); ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
    function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Show selected section
        document.getElementById(sectionId).classList.add('active');
        
        // Update active nav link
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`.nav-links a[onclick="showSection('${sectionId}')"]`).classList.add('active');
    }

    function showNotification(message, type) {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.className = `notification ${type}`;
        notification.style.display = 'block';
        
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }

    function saveGrade(button) {
        const row = button.closest('tr');
        const input = row.querySelector('.grade-input');
        const statusSpan = row.querySelector('.grade-status');
        const studentId = input.dataset.studentId;
        const courseId = input.dataset.courseId;
        const grade = input.value.toUpperCase();

        // Reset status
        statusSpan.textContent = '';
        statusSpan.className = 'grade-status';

        // Validate grade format
        if (!['A', 'B', 'C', 'D', 'F'].includes(grade)) {
            showNotification('Invalid grade. Please use A, B, C, D, or F', 'error');
            input.style.borderColor = '#f44336';
            return;
        }

        // Disable button and show loading state
        button.disabled = true;
        button.textContent = 'Saving...';

        // Send grade to server
        fetch('update_grade.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `student_id=${studentId}&course_id=${courseId}&grade=${grade}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification('Grade saved successfully!', 'success');
                input.value = grade;
                input.style.borderColor = '#4CAF50';
                statusSpan.textContent = '✓';
                statusSpan.style.color = '#4CAF50';
            } else {
                throw new Error(data.message || 'Failed to save grade');
            }
        })
        .catch(error => {
            showNotification('Error saving grade: ' + error.message, 'error');
            input.style.borderColor = '#f44336';
            statusSpan.textContent = '✗';
            statusSpan.style.color = '#f44336';
        })
        .finally(() => {
            // Re-enable button
            button.disabled = false;
            button.textContent = 'Save Grade';
        });
    }

    // Add input validation for grades
    document.querySelectorAll('.grade-input').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            const grade = this.value;
            const statusSpan = this.closest('td').querySelector('.grade-status');
            
            if (grade && !['A', 'B', 'C', 'D', 'F'].includes(grade)) {
                this.style.borderColor = '#f44336';
                statusSpan.textContent = '!';
                statusSpan.style.color = '#f44336';
            } else {
                this.style.borderColor = '#ddd';
                statusSpan.textContent = '';
            }
        });
    });
    </script>
</body>
</html>
<?php
$conn->close();
?>
