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

// Get student year distribution in the department
$query = "SELECT year_of_study, COUNT(*) as count 
          FROM students 
          WHERE department = '$department'
          GROUP BY year_of_study
          ORDER BY year_of_study";
$year_counts = mysqli_query($conn, $query);

// Get marks statistics by year
$stats = [];
for ($year = 1; $year <= 4; $year++) {
    $query = "SELECT 
              COUNT(DISTINCT s.student_id) as students_count,
              COUNT(DISTINCT im.subject) as subjects_count,
              AVG(im.marks / im.max_marks * 100) as avg_percentage
              FROM internal_marks im
              JOIN students s ON im.student_id = s.student_id
              WHERE s.department = '$department' AND s.year_of_study = $year";
    $result = mysqli_query($conn, $query);
    $stats[$year] = mysqli_fetch_assoc($result);
}

// Get students with low performance (less than 40% average)
$query = "SELECT s.student_id, s.name, s.register_number, s.year_of_study,
          AVG(im.marks / im.max_marks * 100) as avg_percentage
          FROM internal_marks im
          JOIN students s ON im.student_id = s.student_id
          WHERE s.department = '$department'
          GROUP BY s.student_id
          HAVING avg_percentage < 40
          ORDER BY avg_percentage ASC";
$low_performers = mysqli_query($conn, $query);

// Get faculty performance in terms of student average
$query = "SELECT f.name, f.faculty_id, COUNT(DISTINCT im.subject) as subjects_taught,
          AVG(im.marks / im.max_marks * 100) as avg_percentage
          FROM internal_marks im
          JOIN faculty f ON im.faculty_id = f.faculty_id
          JOIN students s ON im.student_id = s.student_id
          WHERE s.department = '$department'
          GROUP BY f.faculty_id
          ORDER BY avg_percentage DESC";
$faculty_performance = mysqli_query($conn, $query);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Marks Overview - College Management System</title>
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
                <li><a href="internal_marks.php" class="active"><i class="fas fa-chart-bar"></i> <span>Internal Marks</span></a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
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
            <h1 class="page-title">Internal Marks Overview - <?php echo $department; ?> Department</h1>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line"></i> Performance Overview by Year
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php for ($year = 1; $year <= 4; $year++): ?>
                                    <div class="col-md-3">
                                        <div class="card">
                                            <div class="card-header text-center">
                                                <strong>Year <?php echo $year; ?></strong>
                                            </div>
                                            <div class="card-body">
                                                <div class="text-center">
                                                    <h3><?php echo number_format($stats[$year]['avg_percentage'] ?: 0, 1); ?>%</h3>
                                                    <p>Average Score</p>
                                                    
                                                    <p class="mt-3 mb-0">
                                                        <strong>Students:</strong> <?php echo $stats[$year]['students_count'] ?: 0; ?>
                                                    </p>
                                                    <p class="mb-0">
                                                        <strong>Subjects:</strong> <?php echo $stats[$year]['subjects_count'] ?: 0; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <!-- <div class="card-footer text-center">
                                                <a href="year_details.php?year=<?php echo $year; ?>" class="btn btn-sm">View Details</a>
                                            </div> -->
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-triangle"></i> Low Performers (Below 40%)
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($low_performers) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Register No</th>
                                                <th>Year</th>
                                                <th>Average (%)</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($student = mysqli_fetch_assoc($low_performers)): ?>
                                                <tr>
                                                    <td><?php echo $student['name']; ?></td>
                                                    <td><?php echo $student['register_number']; ?></td>
                                                    <td><?php echo $student['year_of_study']; ?></td>
                                                    <td>
                                                        <span class="badge badge-danger">
                                                            <?php echo number_format($student['avg_percentage'], 1); ?>%
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="student_marks.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Details</a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No students with performance below 40%.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chalkboard-teacher"></i> Faculty Performance
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($faculty_performance) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Faculty Name</th>
                                                <th>Subjects</th>
                                                <th>Class Average</th>
                                                <!-- <th>Action</th> -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($faculty = mysqli_fetch_assoc($faculty_performance)): 
                                                $avg_percentage = $faculty['avg_percentage'];
                                                $badge_class = 'success';
                                                
                                                if ($avg_percentage < 40) {
                                                    $badge_class = 'danger';
                                                } elseif ($avg_percentage < 60) {
                                                    $badge_class = 'warning';
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo $faculty['name']; ?></td>
                                                    <td><?php echo $faculty['subjects_taught']; ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $badge_class; ?>">
                                                            <?php echo number_format($avg_percentage, 1); ?>%
                                                        </span>
                                                    </td>
                                                    <!-- <td>
                                                        <a href="faculty_performance.php?id=<?php echo $faculty['faculty_id']; ?>" class="btn btn-sm">View Details</a>
                                                    </td> -->
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No faculty performance data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-search"></i> View Marks by Category
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <form action="view_marks.php" method="get">
                                        <div class="form-group">
                                            <label for="year">Select Year</label>
                                            <select id="year" name="year" class="form-control" required>
                                                <option value="">Select Year</option>
                                                <option value="1">1st Year</option>
                                                <option value="2">2nd Year</option>
                                                <option value="3">3rd Year</option>
                                                <option value="4">4th Year</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn">View by Year</button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <form action="view_marks.php" method="get">
                                        <div class="form-group">
                                            <label for="exam_type">Select Exam</label>
                                            <select id="exam_type" name="exam_type" class="form-control" required>
                                                <option value="">Select Exam</option>
                                                <option value="Internal 1">Internal 1</option>
                                                <option value="Internal 2">Internal 2</option>
                                                <option value="Internal 3">Internal 3</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn">View by Exam</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
             -->
            <!-- <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-file-export"></i> Export Reports
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <a href="export_marks.php?type=department" class="btn">Export Department Report</a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <a href="export_marks.php?type=faculty" class="btn">Export Faculty Performance</a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <a href="export_marks.php?type=low_performers" class="btn">Export Low Performers List</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</body>
</html>