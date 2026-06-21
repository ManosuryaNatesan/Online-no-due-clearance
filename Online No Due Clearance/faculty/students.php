<?php
session_start();
// Check if user is logged in and is a faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get faculty details
$faculty_id = $_SESSION['user_id'];
$query = "SELECT * FROM faculty WHERE faculty_id = $faculty_id";
$result = mysqli_query($conn, $query);
$faculty = mysqli_fetch_assoc($result);

// Get all students in the faculty's department
$department = $faculty['department'];
$query = "SELECT * FROM students WHERE department = '$department' ORDER BY year_of_study, name";
$students_result = mysqli_query($conn, $query);

// Count students by year
$query = "SELECT year_of_study, COUNT(*) as count FROM students WHERE department = '$department' GROUP BY year_of_study ORDER BY year_of_study";
$year_counts_result = mysqli_query($conn, $query);
$year_counts = [];
while ($count = mysqli_fetch_assoc($year_counts_result)) {
    $year_counts[$count['year_of_study']] = $count['count'];
}

// Get department statistics
$query = "SELECT COUNT(*) as total_students FROM students WHERE department = '$department'";
$dept_stats_result = mysqli_query($conn, $query);
$total_students = mysqli_fetch_assoc($dept_stats_result)['total_students'];

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - College Management System</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/college_logo.jpeg" alt="College Logo">
                <h3>Faculty Portal</h3>
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
            <h1 class="page-title">Students</h1>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> Department Statistics
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-university fa-3x"></i>
                                <h3 class="mt-3"><?php echo $faculty['department']; ?> Department</h3>
                            </div>
                            <div class="summary-box">
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $total_students; ?></span>
                                    <span class="stat-label">Total Students</span>
                                </div>
                                
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo isset($year_counts[$i]) ? $year_counts[$i] : 0; ?></span>
                                        <span class="stat-label">Year <?php echo $i; ?> Students</span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
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
                                            <option value="1">Year 1</option>
                                            <option value="2">Year 2</option>
                                            <option value="3">Year 3</option>
                                            <option value="4">Year 4</option>
                                        </select>
                                    </div>
                                </div>
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
                                        <th>Year of Study</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Action</th>
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
                                            <td>
                                                <!-- <a href="student_details.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Details</a> -->
                                                <a href="internal_marks.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-secondary">Update Marks</a>
                                            </td>
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
        </div>
    </div>
    
    <script>
        // Search functionality
        document.getElementById('search-input').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#students-table tbody tr');
            const yearFilter = document.getElementById('year-filter').value;
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const regNo = row.cells[0].textContent.toLowerCase();
                const year = row.getAttribute('data-year');
                
                // Check if row matches both search and year filter
                const matchesSearch = name.includes(searchValue) || regNo.includes(searchValue);
                const matchesYear = yearFilter === '' || year === yearFilter;
                
                row.style.display = (matchesSearch && matchesYear) ? '' : 'none';
            });
        });
        
        // Year filter functionality
        document.getElementById('year-filter').addEventListener('change', function() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('#students-table tbody tr');
            const yearFilter = this.value;
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const regNo = row.cells[0].textContent.toLowerCase();
                const year = row.getAttribute('data-year');
                
                // Check if row matches both search and year filter
                const matchesSearch = searchValue === '' || name.includes(searchValue) || regNo.includes(searchValue);
                const matchesYear = yearFilter === '' || year === yearFilter;
                
                row.style.display = (matchesSearch && matchesYear) ? '' : 'none';
            });
        });
    </script>
</body>
</html>