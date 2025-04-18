<?php
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin.php");
            break;
        case 'instructor':
            header("Location: instructor.php");
            break;
        case 'student':
            header("Location: student.php");
            break;
    }
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'course_registration');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            // Handle login
            $username = $_POST['username'];
            $password = $_POST['password'];
            $role = $_POST['role'];

            if ($role === '--Choose--') {
                $error = 'Please select a role';
            } else {
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
                $stmt->bind_param("ss", $username, $role);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['department'] = $user['department'];
                        $_SESSION['program'] = $user['program'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];

                        // Redirect based on role
                        switch ($user['role']) {
                            case 'admin':
                                header("Location: admin.php");
                                break;
                            case 'instructor':
                                header("Location: instructor.php");
                                break;
                            case 'student':
                                header("Location: student.php");
                                break;
                        }
                        exit;
                    } else {
                        $error = 'Invalid password';
                    }
                } else {
                    $error = 'User not found';
                }
            }
        } elseif ($_POST['action'] === 'register') {
            // Handle registration
            $username = $_POST['username'];
            $password = $_POST['password'];
            $email = $_POST['email'];
            $role = $_POST['role'];
            $firstName = $_POST['first_name'];
            $lastName = $_POST['last_name'];
            $department = $_POST['department'];
            $program = $role === 'student' ? $_POST['program'] : null;

            // Check if username exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Username already exists!";
            } else {
                // Check if email exists
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = "Email already registered!";
                } else {
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, first_name, last_name, department, program) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssss", $username, $hashedPassword, $email, $role, $firstName, $lastName, $department, $program);
                    
                    if ($stmt->execute()) {
                        $success = "Registration successful! Please login with your credentials.";
                    } else {
                        $error = "Registration failed! Please try again.";
                    }
                }
            }
        }
    }
}

// Fetch departments for registration form
$departments = $conn->query("SELECT department_name FROM departments");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slim Shady University - Login</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1607237138185-eedd9c632b0b?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            position: relative;
        }

        .header {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 1rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .nav-links {
            float: right;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }

        .university-name {
            text-align: center;
            color: #00ff7f;
            font-size: 3em;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            margin-top: 60px;
            margin-bottom: 20px;
            font-family: 'Arial Black', sans-serif;
            letter-spacing: 2px;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                text-shadow: 0 0 10px #00ff7f, 0 0 20px #00ff7f, 0 0 30px #00ff7f;
            }
            to {
                text-shadow: 0 0 20px #00ff7f, 0 0 30px #00ff7f, 0 0 40px #00ff7f;
            }
        }

        .motto {
            text-align: center;
            color: white;
            font-style: italic;
            margin-bottom: 40px;
            font-size: 1.5em;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            margin-top: 20px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .login-container, .register-container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 400px;
        }

        .form-title {
            text-align: center;
            color: #2196F3;
            margin-bottom: 2rem;
            font-size: 2em;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: bold;
        }

        select, input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        select:focus, input:focus {
            outline: none;
            border-color: #2196F3;
            box-shadow: 0 0 5px rgba(33, 150, 243, 0.3);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #1976D2;
        }

        .error {
            color: #f44336;
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background-color: #ffebee;
            border-radius: 4px;
        }

        .success {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background-color: #E8F5E9;
            border-radius: 4px;
        }

        .footer {
            text-align: center;
            color: white;
            padding: 1rem;
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.7);
        }

        @media (max-width: 768px) {
            .university-name {
                font-size: 2em;
                margin-top: 80px;
            }

            .motto {
                font-size: 1.2em;
            }

            .container {
                padding: 10px;
            }

            .login-container, .register-container {
                width: 95%;
                margin: 10px auto;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav-links">
            <a href="#">Home</a>
            <a href="#">About</a>
            <a href="#">Contact</a>
        </div>
    </div>

    <h1 class="university-name">Slim Shady University</h1>
    <p class="motto">"Will the Real Graduates Please Stand Up"</p>

    <div class="container">
        <!-- Login Form -->
        <div class="login-container">
            <h2 class="form-title">Login</h2>
            <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="role">Select Role:</label>
                    <select id="role" name="role" required>
                        <option>--Choose--</option>
                        <option value="student">Student</option>
                        <option value="instructor">Instructor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>

        <!-- Registration Form -->
        <div class="register-container">
            <h2 class="form-title">Register</h2>
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label for="reg_username">Username:</label>
                    <input type="text" id="reg_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="reg_password">Password:</label>
                    <input type="password" id="reg_password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="reg_role">Role:</label>
                    <select id="reg_role" name="role" required onchange="toggleProgramField()">
                        <option value="student">Student</option>
                        <option value="instructor">Instructor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department">Department:</label>
                    <select id="department" name="department" required>
                        <?php 
                        $departments->data_seek(0);
                        while($dept = $departments->fetch_assoc()): 
                        ?>
                            <option value="<?= htmlspecialchars($dept['department_name']) ?>">
                                <?= htmlspecialchars($dept['department_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" id="program_field">
                    <label for="program">Program:</label>
                    <input type="text" id="program" name="program" placeholder="e.g., BSc in CS, Year 2">
                </div>
                <button type="submit" class="btn">Register</button>
            </form>
        </div>
    </div>

    <div class="footer">
        © 2025 Slim Shady University. All rights reserved.
    </div>

    <script>
        function toggleProgramField() {
            const role = document.getElementById('reg_role').value;
            const programField = document.getElementById('program_field');
            const programInput = document.getElementById('program');
            
            if (role === 'student') {
                programField.style.display = 'block';
                programInput.required = true;
            } else {
                programField.style.display = 'none';
                programInput.required = false;
                programInput.value = '';
            }
        }

        // Call on page load
        toggleProgramField();
    </script>
</body>
</html>
<?php
$conn->close();
?>
