<?php
session_start();
// Check if user is logged in and is a HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get HOD details
$hod_id = $_SESSION['user_id'];
$query = "SELECT * FROM hod WHERE hod_id = $hod_id";
$result = mysqli_query($conn, $query);
$hod = mysqli_fetch_assoc($result);

// Get the department that this HOD oversees
$department = $hod['department'];

// Get all students in the department
$query = "SELECT * FROM students WHERE department = '$department' ORDER BY year_of_study, name";
$students_result = mysqli_query($conn, $query);

// Get student statistics
$query = "SELECT 
          COUNT(*) as total_students,
          SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male_count,
          SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_count
          FROM students
          WHERE department = '$department'";
$stats_result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($stats_result);

// Get student count by year
$query = "SELECT year_of_study, COUNT(*) as count 
          FROM students 
          WHERE department = '$department'
          GROUP BY year_of_study
          ORDER BY year_of_study";
$year_stats_result = mysqli_query($conn, $query);
$year_stats = [];
while ($row = mysqli_fetch_assoc($year_stats_result)) {
    $year_stats[$row['year_of_study']] = $row['count'];
}

// Process form submission for adding new student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $register_number = mysqli_real_escape_string($conn, $_POST['register_number']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $year_of_study = mysqli_real_escape_string($conn, $_POST['year_of_study']);
    
    // Check if register number already exists
    $check_query = "SELECT * FROM students WHERE register_number = '$register_number'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error_message = "Register number already exists!";
    } else {
        // Default password is first 4 characters of name + last 4 of register number
        $name_part = substr($name, 0, 4);
        $reg_part = substr($register_number, -4);
        $password = password_hash($name_part . $reg_part, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO students (name, register_number, email, mobile, gender, department, year_of_study, password, profile_image) 
                         VALUES ('$name', '$register_number', '$email', '$mobile', '$gender', '$department', $year_of_study, '$password', 'default_student.jpg')";
        
        if (mysqli_query($conn, $insert_query)) {
            $success_message = "Student added successfully!";
            // Refresh students list
            $students_result = mysqli_query($conn, "SELECT * FROM students WHERE department = '$department' ORDER BY year_of_study, name");
            
            // Update stats
            $stats_result = mysqli_query($conn, "SELECT 
                                               COUNT(*) as total_students,
                                               SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male_count,
                                               SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_count
                                               FROM students
                                               WHERE department = '$department'");
            $stats = mysqli_fetch_assoc($stats_result);
            
            $year_stats_result = mysqli_query($conn, "SELECT year_of_study, COUNT(*) as count 
                                                    FROM students 
                                                    WHERE department = '$department'
                                                    GROUP BY year_of_study
                                                    ORDER BY year_of_study");
            $year_stats = [];
            while ($row = mysqli_fetch_assoc($year_stats_result)) {
                $year_stats[$row['year_of_study']] = $row['count'];
            }
        } else {
            $error_message = "Error adding student: " . mysqli_error($conn);
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - College Management System</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/college_logo.jpeg" alt="College Logo">
                <h3>HOD Portal</h3>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
                <li><a href="internal_marks.php"><i class="fas fa-chart-bar"></i> <span>Internal Marks</span></a></li>
                <li><a href="students.php" class="active"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
                <li><a href="library.php"><i class="fas fa-book"></i> <span>Library</span></a></li>
                <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> <span>Fees</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Manage Students - <?php echo $department; ?> Department</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> Department Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <h3><?php echo $stats['total_students']; ?></h3>
                                        <p>Total Students</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-male fa-3x mb-3"></i>
                                        <h3><?php echo $stats['male_count']; ?></h3>
                                        <p>Male Students</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-female fa-3x mb-3"></i>
                                        <h3><?php echo $stats['female_count']; ?></h3>
                                        <p>Female Students</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                                        <div class="row">
                                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                                <div class="col-3">
                                                    <h5><?php echo $year_stats[$i] ?? 0; ?></h5>
                                                    <small>Year <?php echo $i; ?></small>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-plus-circle"></i> Add New Student
                            <button class="btn btn-sm float-right" onclick="toggleAddForm()">
                                <i class="fas fa-plus"></i> Add Student
                            </button>
                        </div>
                        <div class="card-body" id="add-form" style="display: none;">
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Full Name</label>
                                            <input type="text" id="name" name="name" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="register_number">Register Number</label>
                                            <input type="text" id="register_number" name="register_number" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" id="email" name="email" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="mobile">Mobile Number</label>
                                            <input type="text" id="mobile" name="mobile" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="gender">Gender</label>
                                            <select id="gender" name="gender" class="form-control" required>
                                                <option value="">Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="year_of_study">Year of Study</label>
                                            <select id="year_of_study" name="year_of_study" class="form-control" required>
                                                <option value="">Select Year</option>
                                                <option value="1">1st Year</option>
                                                <option value="2">2nd Year</option>
                                                <option value="3">3rd Year</option>
                                                <option value="4">4th Year</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-2">
                                    <i class="fas fa-info-circle"></i> Default password will be the first 4 characters of name + last 4 digits of register number.
                                </div>
                                
                                <div class="form-group mt-3">
                                    <button type="submit" name="add_student" class="btn btn-success">Add Student</button>
                                    <button type="button" class="btn" onclick="toggleAddForm()">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> Search Students
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Search by name or register number">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <select id="year-filter" class="form-control">
                                    <option value="">All Years</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-list"></i> Student List
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($students_result) > 0): ?>
                        <div class="table-container">
                            <table class="table" id="students-table">
                                <thead>
                                    <tr>
                                        <th>Register No</th>
                                        <th>Name</th>
                                        <th>Year</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Gender</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                        <tr data-year="<?php echo $student['year_of_study']; ?>">
                                            <td><?php echo $student['register_number']; ?></td>
                                            <td><?php echo $student['name']; ?></td>
                                            <td><?php echo $student['year_of_study']; ?></td>
                                            <td><?php echo $student['email']; ?></td>
                                            <td><?php echo $student['mobile']; ?></td>
                                            <td><?php echo $student['gender']; ?></td>
                                            <!-- <td>
                                                <a href="student_details.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Details</a>
                                                <a href="edit_student.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No students found in your department.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-file-export"></i> Export Options
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <a href="export_students.php" class="btn">Export All Students</a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <a href="export_students.php?type=year" class="btn">Export by Year</a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <a href="export_email_list.php" class="btn">Export Email List</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
    
    <script>
        function toggleAddForm() {
            var form = document.getElementById("add-form");
            if (form.style.display === "none") {
                form.style.display = "block";
            } else {
                form.style.display = "none";
            }
        }
        
        // Search and filter functionality
        document.getElementById('search-input').addEventListener('keyup', filterStudents);
        document.getElementById('year-filter').addEventListener('change', filterStudents);
        
        function filterStudents() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const yearFilter = document.getElementById('year-filter').value;
            const rows = document.querySelectorAll('#students-table tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const regNo = row.cells[0].textContent.toLowerCase();
                const year = row.getAttribute('data-year');
                
                // Check if row matches search and year filter
                const matchesSearch = name.includes(searchValue) || regNo.includes(searchValue);
                const matchesYear = yearFilter === '' || year === yearFilter;
                
                row.style.display = (matchesSearch && matchesYear) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
