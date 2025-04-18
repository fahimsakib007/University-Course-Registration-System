<?php
session_start();

// Redirect if not logged in or wrong role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'course_registration');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get student data from session
$username = $_SESSION['username'];
$studentId = $_SESSION['user_id'];
$department = $_SESSION['department'] ?? 'Computer Science';
$email = $_SESSION['email'] ?? 'john.doe@ssu.edu';
$program = $_SESSION['program'] ?? 'BSc in CS, Year 2, Semester 1';

// Handle course registration/dropping
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register':
                $courseId = $_POST['course_id'];
                // Check if already registered
                $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
                $stmt->bind_param("ii", $studentId, $courseId);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    // Check if course is full
                    $stmt = $conn->prepare("SELECT current_students, max_students FROM courses WHERE course_id = ?");
                    $stmt->bind_param("i", $courseId);
                    $stmt->execute();
                    $course = $stmt->get_result()->fetch_assoc();
                    
                    if ($course['current_students'] < $course['max_students']) {
                        // Register for course
                        $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id, status) VALUES (?, ?, 'enrolled')");
                        $stmt->bind_param("ii", $studentId, $courseId);
                        $stmt->execute();
                        
                        // Update current_students count
                        $stmt = $conn->prepare("UPDATE courses SET current_students = current_students + 1 WHERE course_id = ?");
                        $stmt->bind_param("i", $courseId);
                        $stmt->execute();
                    }
                }
                break;
                
            case 'drop':
                $courseId = $_POST['course_id'];
                // Drop the course
                $stmt = $conn->prepare("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?");
                $stmt->bind_param("ii", $studentId, $courseId);
                $stmt->execute();
                
                // Update current_students count
                $stmt = $conn->prepare("UPDATE courses SET current_students = current_students - 1 WHERE course_id = ?");
                $stmt->bind_param("i", $courseId);
                $stmt->execute();
                break;
        }
    }
}

// Fetch enrolled courses
$stmt = $conn->prepare("
    SELECT c.*, e.grade, i.username as instructor_name 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.course_id 
    LEFT JOIN users i ON c.instructor_id = i.user_id 
    WHERE e.student_id = ?
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$enrolledCourses = $stmt->get_result();

// Fetch available courses
$availableCourses = $conn->query("
    SELECT c.*, i.username as instructor_name, 
           (c.max_students - c.current_students) as available_slots 
    FROM courses c 
    LEFT JOIN users i ON c.instructor_id = i.user_id 
    WHERE c.current_students < c.max_students
");

// Fetch instructors
$instructors = $conn->query("
    SELECT DISTINCT i.username, i.email, c.course_code 
    FROM users i 
    JOIN courses c ON i.user_id = c.instructor_id 
    WHERE i.role = 'instructor'
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #e0f7fa;
      color: #006064;
      display: flex;
      min-height: 100vh;
    }
    nav {
      width: 220px;
      background-color: #006064;
      color: white;
      padding: 1rem;
      display: flex;
      flex-direction: column;
    }
    nav a {
      color: white;
      text-decoration: none;
      margin: 0.5rem 0;
      cursor: pointer;
    }
    nav a:hover {
      text-decoration: underline;
    }
    .container {
      flex: 1;
      padding: 2rem;
    }
    section {
      display: none;
      margin-bottom: 2rem;
    }
    section.active {
      display: block;
    }
    h2 {
      border-bottom: 2px solid #006064;
      padding-bottom: 0.5rem;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 0.5rem;
      text-align: left;
    }
    th {
      background-color: #b2ebf2;
    }
    button {
      margin-top: 0.5rem;
      padding: 0.5rem 1rem;
      background-color: #006064;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background-color: #004d40;
    }
    .alert {
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 4px;
      color: white;
    }
    .alert-success {
      background-color: #43a047;
    }
    .alert-error {
      background-color: #e53935;
    }
    .badge {
      background-color: #006064;
      color: white;
      padding: 0.2rem 0.5rem;
      border-radius: 12px;
      font-size: 0.8rem;
    }
  </style>
</head>
<body>
  <nav>
    <h2>Dashboard</h2>
    <a onclick="showSection('profile')">Profile</a>
    <a onclick="showSection('registration')">Register/Drop Course</a>
    <a onclick="showSection('courses')">View Courses</a>
    <a onclick="showSection('instructors')">Instructors</a>
    <a onclick="showSection('grades')">View Grades</a>
    <a href="logout.php">Logout</a>
  </nav>

  <div class="container">
    <section id="profile" class="active">
      <h2>Student Profile</h2>
      <p><strong>Name:</strong> <?= htmlspecialchars($username) ?></p>
      <p><strong>ID:</strong> <?= htmlspecialchars($studentId) ?></p>
      <p><strong>Department:</strong> <?= htmlspecialchars($department) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
      <p><strong>Program:</strong> <?= htmlspecialchars($program) ?></p>
    </section>

    <section id="registration">
      <h2>Course Registration</h2>
      <div class="card">
        <h3>Available Courses</h3>
        <table>
          <thead>
            <tr>
              <th>Course Code</th>
              <th>Course Name</th>
              <th>Credits</th>
              <th>Available Slots</th>
              <th>Instructor</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while($course = $availableCourses->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($course['course_code']) ?></td>
              <td><?= htmlspecialchars($course['course_name']) ?></td>
              <td><?= htmlspecialchars($course['credits']) ?></td>
              <td><?= htmlspecialchars($course['available_slots']) ?></td>
              <td><?= htmlspecialchars($course['instructor_name'] ?? 'TBD') ?></td>
              <td>
                <form method="POST" style="margin: 0;">
                  <input type="hidden" name="action" value="register">
                  <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                  <button type="submit">Register</button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="card">
        <h3>My Registered Courses</h3>
        <table>
          <thead>
            <tr>
              <th>Course Code</th>
              <th>Course Name</th>
              <th>Credits</th>
              <th>Instructor</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $enrolledCourses->data_seek(0);
            while($course = $enrolledCourses->fetch_assoc()): 
            ?>
            <tr>
              <td><?= htmlspecialchars($course['course_code']) ?></td>
              <td><?= htmlspecialchars($course['course_name']) ?></td>
              <td><?= htmlspecialchars($course['credits']) ?></td>
              <td><?= htmlspecialchars($course['instructor_name'] ?? 'TBD') ?></td>
              <td>
                <form method="POST" style="margin: 0;">
                  <input type="hidden" name="action" value="drop">
                  <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                  <button type="submit" onclick="return confirm('Are you sure you want to drop this course?')">Drop</button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="courses">
      <h2>My Courses</h2>
      <table>
        <thead>
          <tr>
            <th>Course Code</th>
            <th>Course Name</th>
            <th>Credits</th>
            <th>Schedule</th>
            <th>Instructor</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $enrolledCourses->data_seek(0);
          while($course = $enrolledCourses->fetch_assoc()): 
          ?>
          <tr>
            <td><?= htmlspecialchars($course['course_code']) ?></td>
            <td><?= htmlspecialchars($course['course_name']) ?></td>
            <td><?= htmlspecialchars($course['credits']) ?></td>
            <td><?= htmlspecialchars($course['schedule'] ?? 'TBD') ?></td>
            <td><?= htmlspecialchars($course['instructor_name'] ?? 'TBD') ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </section>

    <section id="instructors">
      <h2>Instructor Information</h2>
      <table>
        <thead>
          <tr>
            <th>Course</th>
            <th>Instructor</th>
            <th>Email</th>
          </tr>
        </thead>
        <tbody>
          <?php while($instructor = $instructors->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($instructor['course_code']) ?></td>
            <td><?= htmlspecialchars($instructor['username']) ?></td>
            <td><?= htmlspecialchars($instructor['email']) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </section>

    <section id="grades">
      <h2>Grades</h2>
      <table id="gradeTable">
        <thead>
          <tr>
            <th>Course</th>
            <th>Grade</th>
            <th>Credits</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $enrolledCourses->data_seek(0);
          while($course = $enrolledCourses->fetch_assoc()): 
          ?>
          <tr>
            <td><?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?></td>
            <td><?= htmlspecialchars($course['grade'] ?? 'Not Graded') ?></td>
            <td><?= htmlspecialchars($course['credits']) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <button onclick="downloadGradeReport()">Download Grade Report</button>
    </section>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script>
    function showSection(id) {
      document.querySelectorAll('section').forEach(section => {
        section.classList.remove('active');
      });
      document.getElementById(id).classList.add('active');
    }

    async function downloadGradeReport() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      const table = document.getElementById('gradeTable');
      const rows = table.querySelectorAll('tbody tr');

      let y = 10;
      doc.setFontSize(14);
      doc.text("Grade Report", 14, y);
      y += 10;

      doc.setFontSize(12);
      doc.text("Course", 14, y);
      doc.text("Grade", 100, y);
      doc.text("Credits", 150, y);
      y += 10;

      rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        doc.text(cells[0].innerText, 14, y);
        doc.text(cells[1].innerText, 100, y);
        doc.text(cells[2].innerText, 150, y);
        y += 10;
      });

      doc.save("grade_report.pdf");
    }
  </script>
</body>
</html>
<?php
$conn->close();
?>
