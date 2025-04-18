<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'course_registration');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_course'])) {
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];
    $department_id = $_POST['department_id'];
    $instructor_id = $_POST['instructor_id'];
    $credits = $_POST['credits'];
    $max_students = $_POST['max_students'];
    $schedule_day = $_POST['schedule_day'];
    $schedule_time = $_POST['schedule_time'];
    $room = $_POST['room'];
    $semester = $_POST['semester'];
    $year = $_POST['year'];

    $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, department_id, instructor_id, credits, max_students, schedule_day, schedule_time, room, semester, year) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiiissssi", $course_code, $course_name, $department_id, $instructor_id, $credits, $max_students, $schedule_day, $schedule_time, $room, $semester, $year);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Course created successfully!";
    } else {
        $_SESSION['error_message'] = "Error creating course: " . $conn->error;
    }
    $stmt->close();
    
    // Redirect back to the course management section
    header("Location: admin.php#course-management");
    exit;
}

// Handle course editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_course'])) {
    $course_id = $_POST['course_id'];
    $course_code = $_POST['edit_course_code'];
    $course_name = $_POST['edit_course_name'];
    $department_id = $_POST['edit_department_id'];
    $instructor_id = $_POST['edit_instructor_id'];
    $schedule_day = $_POST['edit_schedule_day'];
    $schedule_time = $_POST['edit_schedule_time'];
    $room = $_POST['edit_room'];
    $max_students = $_POST['edit_max_students'];

    $stmt = $conn->prepare("UPDATE courses SET course_code = ?, course_name = ?, department_id = ?, instructor_id = ?, schedule_day = ?, schedule_time = ?, room = ?, max_students = ? WHERE course_id = ?");
    $stmt->bind_param("ssiiissii", $course_code, $course_name, $department_id, $instructor_id, $schedule_day, $schedule_time, $room, $max_students, $course_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Course updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating course: " . $conn->error;
    }
    $stmt->close();
    
    // Redirect back to the course management section
    header("Location: admin.php#course-management");
    exit;
}

// Handle course deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_course'])) {
    $course_id = $_POST['course_id'];

    // First check if there are any enrollments for this course
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
    $check_stmt->bind_param("i", $course_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $check_stmt->close();

    if ($count > 0) {
        $_SESSION['error_message'] = "Cannot delete course: There are students enrolled in this course.";
    } else {
        $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Course deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting course: " . $conn->error;
        }
        $stmt->close();
    }
    
    // Redirect back to the course management section
    header("Location: admin.php#course-management");
    exit;
}

// Fetch all students
$students_query = "SELECT u.*, d.department_name 
                  FROM users u 
                  LEFT JOIN departments d ON u.department = d.department_name 
                  WHERE u.role = 'student'";
$students_result = $conn->query($students_query);

// Fetch all courses with proper schedule formatting
$courses_query = "SELECT c.*, d.department_name, CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                 CASE 
                    WHEN c.schedule_day = '0' THEN ''
                    WHEN c.schedule_day IS NULL THEN ''
                    ELSE c.schedule_day
                 END as formatted_day,
                 CASE 
                    WHEN c.schedule_time IS NULL THEN ''
                    ELSE TIME_FORMAT(c.schedule_time, '%H:%i')
                 END as formatted_time
                 FROM courses c 
                 LEFT JOIN departments d ON c.department_id = d.department_id 
                 LEFT JOIN users u ON c.instructor_id = u.user_id";
$courses_result = $conn->query($courses_query);

// Fetch departments for dropdown
$departments_query = "SELECT * FROM departments";
$departments_result = $conn->query($departments_query);

// Fetch instructors for dropdown
$instructors_query = "SELECT user_id, first_name, last_name FROM users WHERE role = 'instructor'";
$instructors_result = $conn->query($instructors_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Course Registration System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f4f4f4;
        }

        .container {
            width: 95%;
            margin: auto;
            padding: 20px;
        }

        h1, h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #5c2d91;
            color: white;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
        }

        .btn-view {
            background-color: #5c2d91;
            color: white;
        }

        .btn-edit {
            background-color: #28a745;
            color: white;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        form {
            display: grid;
            gap: 15px;
            max-width: 600px;
            margin: 20px 0;
        }

        input, select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .submit-btn {
            background-color: #5c2d91;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #4a2472;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 70%;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 20px;
            cursor: pointer;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .nav-tabs {
            display: flex;
            margin-bottom: 20px;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            color: #333;
            font-size: 16px;
        }

        .nav-tab.active {
            color: #5c2d91;
            border-bottom: 2px solid #5c2d91;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .student-details {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            margin-top: 20px;
        }

        .student-details h3 {
            color: #5c2d91;
            margin-bottom: 15px;
        }

        .student-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-item {
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .info-label {
            font-weight: bold;
            color: #5c2d91;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>

        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('student-management')">Student Management</button>
            <button class="nav-tab" onclick="showTab('course-management')">Course Management</button>
            <a href="logout.php" class="btn" style="margin-left: auto;">Logout</a>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Student Management Section -->
        <div id="student-management" class="tab-content active">
            <div class="section">
                <h2>Enrolled Students</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($student = $students_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['department']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td>
                                <button class="btn btn-view" onclick="viewStudentDetails(<?php echo $student['user_id']; ?>)">View Details</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Course Management Section -->
        <div id="course-management" class="tab-content">
            <div class="section">
                <h2>Create New Course</h2>
                <form method="POST" action="admin.php">
                    <input type="text" name="course_code" placeholder="Course Code" required>
                    <input type="text" name="course_name" placeholder="Course Name" required>
                    <select name="department_id" required>
                        <option value="">Select Department</option>
                        <?php 
                        $departments_result->data_seek(0);
                        while($dept = $departments_result->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $dept['department_id']; ?>">
                            <?php echo htmlspecialchars($dept['department_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <select name="instructor_id" required>
                        <option value="">Select Instructor</option>
                        <?php while($instructor = $instructors_result->fetch_assoc()): ?>
                        <option value="<?php echo $instructor['user_id']; ?>">
                            <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <input type="number" name="credits" placeholder="Credits" required>
                    <input type="number" name="max_students" placeholder="Maximum Students" required>
                    <select name="schedule_day" required>
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                    </select>
                    <input type="time" name="schedule_time" required>
                    <input type="text" name="room" placeholder="Room Number" required>
                    <select name="semester" required>
                        <option value="">Select Semester</option>
                        <option value="Fall">Fall</option>
                        <option value="Spring">Spring</option>
                        <option value="Summer">Summer</option>
                    </select>
                    <input type="number" name="year" placeholder="Year" required min="2024" max="2030">
                    <input type="submit" name="create_course" value="Create Course" class="submit-btn">
                </form>
            </div>

            <div class="section">
                <h2>Course List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Instructor</th>
                            <th>Schedule</th>
                            <th>Room</th>
                            <th>Enrolled/Max</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($course = $courses_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['department_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                            <td>
                                <?php 
                                $schedule = trim($course['formatted_day'] . ' ' . $course['formatted_time']);
                                echo htmlspecialchars($schedule);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($course['room']); ?></td>
                            <td><?php echo htmlspecialchars($course['current_students'] . '/' . $course['max_students']); ?></td>
                            <td>
                                <button class="btn btn-edit" onclick="editCourse(<?php echo $course['course_id']; ?>)">Edit</button>
                                <form method="POST" action="admin.php" style="display: inline;">
                                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                    <button type="button" class="btn btn-delete" onclick="confirmDelete(this.form, '<?php echo htmlspecialchars($course['course_code']); ?>')">Delete</button>
                                    <input type="hidden" name="delete_course" value="1">
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeStudentModal()">&times;</span>
            <div id="studentDetails"></div>
        </div>
    </div>

    <!-- Course Edit Modal -->
    <div id="courseModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCourseModal()">&times;</span>
            <h3>Edit Course</h3>
            <form method="POST" action="admin.php" id="editCourseForm">
                <input type="hidden" name="course_id" id="edit_course_id">
                <input type="text" name="edit_course_code" id="edit_course_code" placeholder="Course Code" required>
                <input type="text" name="edit_course_name" id="edit_course_name" placeholder="Course Name" required>
                <select name="edit_department_id" id="edit_department_id" required>
                    <option value="">Select Department</option>
                    <?php 
                    $departments_result->data_seek(0);
                    while($dept = $departments_result->fetch_assoc()): 
                    ?>
                    <option value="<?php echo $dept['department_id']; ?>">
                        <?php echo htmlspecialchars($dept['department_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <select name="edit_instructor_id" id="edit_instructor_id" required>
                    <option value="">Select Instructor</option>
                    <?php 
                    $instructors_result->data_seek(0);
                    while($instructor = $instructors_result->fetch_assoc()): 
                    ?>
                    <option value="<?php echo $instructor['user_id']; ?>">
                        <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <select name="edit_schedule_day" id="edit_schedule_day" required>
                    <option value="">Select Day</option>
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                </select>
                <input type="time" name="edit_schedule_time" id="edit_schedule_time" required>
                <input type="text" name="edit_room" id="edit_room" placeholder="Room Number" required>
                <input type="number" name="edit_max_students" id="edit_max_students" placeholder="Maximum Students" required>
                <input type="submit" name="edit_course" value="Save Changes" class="submit-btn">
            </form>
        </div>
    </div>

    <!-- Delete Course Form (Hidden) -->
    <form id="deleteCourseForm" method="POST" action="admin.php" style="display: none;">
        <input type="hidden" name="course_id" id="delete_course_id">
        <input type="hidden" name="delete_course" value="1">
    </form>

    <script>
    function showTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected tab content
        document.getElementById(tabId).classList.add('active');
        
        // Update active tab button
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`.nav-tab[onclick="showTab('${tabId}')"]`).classList.add('active');

        // Update URL hash
        window.location.hash = tabId;
    }

    function viewStudentDetails(studentId) {
        fetch(`get_student_details.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                const modal = document.getElementById('studentModal');
                const detailsDiv = document.getElementById('studentDetails');
                
                let enrolledCourses = data.courses.map(course => 
                    `<div class="info-item">
                        <span class="info-label">Course:</span> ${course.course_code} - ${course.course_name}<br>
                        <span class="info-label">Grade:</span> ${course.grade || 'Not graded'}
                    </div>`
                ).join('');

                detailsDiv.innerHTML = `
                    <h3>Student Details</h3>
                    <div class="student-info">
                        <div class="info-item">
                            <span class="info-label">Name:</span> ${data.first_name} ${data.last_name}
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span> ${data.email}
                        </div>
                        <div class="info-item">
                            <span class="info-label">Department:</span> ${data.department}
                        </div>
                        <div class="info-item">
                            <span class="info-label">Program:</span> ${data.program}
                        </div>
                    </div>
                    <h3 style="margin-top: 20px;">Enrolled Courses</h3>
                    <div class="student-info">
                        ${enrolledCourses}
                    </div>
                `;
                
                modal.style.display = "block";
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error fetching student details');
            });
    }

    function closeStudentModal() {
        document.getElementById('studentModal').style.display = "none";
    }

    function editCourse(courseId) {
        fetch(`get_course_details.php?course_id=${courseId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Course data:', data); // Debug log
                document.getElementById('edit_course_id').value = data.course_id;
                document.getElementById('edit_course_code').value = data.course_code;
                document.getElementById('edit_course_name').value = data.course_name;
                document.getElementById('edit_department_id').value = data.department_id;
                document.getElementById('edit_instructor_id').value = data.instructor_id;
                document.getElementById('edit_schedule_day').value = data.schedule_day === '0' ? '' : data.schedule_day;
                
                // Fix for schedule time display
                const timeStr = data.schedule_time;
                const formattedTime = timeStr ? timeStr.substring(0, 5) : '';
                document.getElementById('edit_schedule_time').value = formattedTime;
                
                document.getElementById('edit_room').value = data.room;
                document.getElementById('edit_max_students').value = data.max_students;
                
                document.getElementById('courseModal').style.display = "block";
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error fetching course details');
            });
    }

    function closeCourseModal() {
        document.getElementById('courseModal').style.display = "none";
    }

    function confirmDelete(form, courseCode) {
        if (confirm('Are you sure you want to delete the course ' + courseCode + '? This action cannot be undone.')) {
            form.submit();
        }
    }

    // Close modals when clicking outside of them
    window.onclick = function(event) {
        if (event.target == document.getElementById('studentModal')) {
            document.getElementById('studentModal').style.display = "none";
        }
        if (event.target == document.getElementById('courseModal')) {
            document.getElementById('courseModal').style.display = "none";
        }
    }

    // Check URL hash on page load
    window.onload = function() {
        const hash = window.location.hash.substring(1);
        if (hash) {
            showTab(hash);
        }
    }
    </script>
</body>
</html>
<?php
$conn->close();
?>
