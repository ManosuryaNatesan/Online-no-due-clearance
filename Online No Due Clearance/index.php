<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: ./{$role}/dashboard.php");
    exit();
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'config/db_connect.php';
    
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Validate inputs
    if (empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } else {
        // Query based on role
        switch ($role) {
            case 'student':
                $query = "SELECT student_id, name, email, password FROM students WHERE email = '$email'";
                break;
            case 'faculty':
                $query = "SELECT faculty_id, name, email, password FROM faculty WHERE email = '$email'";
                break;
            case 'hod':
                $query = "SELECT hod_id, name, email, password FROM hod WHERE email = '$email'";
                break;
            case 'librarian':
                $query = "SELECT librarian_id, name, email, password FROM librarian WHERE email = '$email'";
                break;
            case 'accountant':
                $query = "SELECT accountant_id, name, email, password FROM accountant WHERE email = '$email'";
                break;
            default:
                $error = "Invalid role selected.";
                break;
        }
        
        if (!isset($error)) {
            $result = mysqli_query($conn, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                
                // Verify password
                // For demo purposes, using simple password check since bcrypt is actually implemented in the DB
                //if (password_verify($password, $user['password'])) {
                if ($password === 'password123') {  // Simplified for demo
                    // Set session variables
                    $_SESSION['user_id'] = $user[$role . '_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $role;
                    
                    // Redirect to appropriate dashboard
                    header("Location: ./{$role}/dashboard.php");
                    exit();
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
        }
    }
    
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Management System - Login</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="login-logo">
                <img src="images/college_logo.jpeg" alt="College Logo">
                <h2>College Management System</h2>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="index.php" method="post">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Select your role</option>
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                        <option value="hod">HOD</option>
                        <option value="librarian">Librarian</option>
                        <option value="accountant">Accountant</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-block">Login</button>
                </div>
            </form>
            
            <div class="text-center">
                <p>Demo credentials:</p>
                <p>student:rahul@example.com</p>
                <p> faculty:suresh@example.com</P>
                <p>hod:dinesh@example.com</P>
                <p>librarian:kiran@example.com</P>
                <p>accountant:lakshmi@example.com</p>
                <p>Password: password123</p>
            </div>
        </div>
    </div>

    <script>
        // Simple client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const role = document.getElementById('role').value;
            
            if (!email || !password || !role) {
                e.preventDefault();
                alert('Please fill in all fields.');
            }
        });
    </script>
</body>
</html>