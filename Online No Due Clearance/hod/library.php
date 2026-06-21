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

// Get library statistics for the department
$query = "SELECT 
          COUNT(DISTINCT r.record_id) as total_records,
          SUM(CASE WHEN r.status = 'Issued' THEN 1 ELSE 0 END) as issued_books,
          SUM(CASE WHEN r.status = 'Returned' THEN 1 ELSE 0 END) as returned_books,
          SUM(CASE WHEN r.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_books,
          SUM(r.fine_amount) as total_fine,
          COUNT(DISTINCT s.student_id) as student_count
          FROM library_records r
          JOIN students s ON r.student_id = s.student_id
          WHERE s.department = '$department'";
$stats_result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($stats_result);

// Get students with overdue books
$query = "SELECT s.student_id, s.name, s.register_number, s.year_of_study, 
          COUNT(r.record_id) as overdue_books, 
          SUM(r.fine_amount) as total_fine
          FROM students s
          JOIN library_records r ON s.student_id = r.student_id
          WHERE s.department = '$department' AND r.status = 'Overdue'
          GROUP BY s.student_id
          ORDER BY SUM(r.fine_amount) DESC";
$overdue_result = mysqli_query($conn, $query);

// Get students with highest number of books
$query = "SELECT s.student_id, s.name, s.register_number, s.year_of_study, 
          COUNT(r.record_id) as total_books
          FROM students s
          JOIN library_records r ON s.student_id = r.student_id
          WHERE s.department = '$department'
          GROUP BY s.student_id
          ORDER BY total_books DESC
          LIMIT 5";
$top_borrowers_result = mysqli_query($conn, $query);

// Get recent library activities for department students
$query = "SELECT r.*, s.name as student_name, s.register_number, s.year_of_study
          FROM library_records r
          JOIN students s ON r.student_id = s.student_id
          WHERE s.department = '$department'
          ORDER BY r.updated_at DESC
          LIMIT 10";
$recent_activities_result = mysqli_query($conn, $query);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Records - College Management System</title>
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
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
                <li><a href="library.php" class="active"><i class="fas fa-book"></i> <span>Library</span></a></li>
                <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> <span>Fees</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Library Records - <?php echo $department; ?> Department</h1>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> Library Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-book fa-3x mb-3"></i>
                                        <h3><?php echo $stats['total_records'] ?: 0; ?></h3>
                                        <p>Total Transactions</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-bookmark fa-3x mb-3"></i>
                                        <h3><?php echo $stats['issued_books'] ?: 0; ?></h3>
                                        <p>Currently Issued</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-undo fa-3x mb-3"></i>
                                        <h3><?php echo $stats['returned_books'] ?: 0; ?></h3>
                                        <p>Returned Books</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                        <h3><?php echo $stats['overdue_books'] ?: 0; ?></h3>
                                        <p>Overdue Books</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-money-bill-wave fa-3x mb-3"></i>
                                        <h3>₹<?php echo $stats['total_fine'] ?: 0; ?></h3>
                                        <p>Total Fine</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <h3><?php echo $stats['student_count'] ?: 0; ?></h3>
                                        <p>Active Users</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-triangle"></i> Students with Overdue Books
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($overdue_result) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Register No</th>
                                                <th>Year</th>
                                                <th>Overdue Books</th>
                                                <th>Fine Amount</th>
                                                <!-- <th>Action</th> -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($student = mysqli_fetch_assoc($overdue_result)): ?>
                                                <tr>
                                                    <td><?php echo $student['name']; ?></td>
                                                    <td><?php echo $student['register_number']; ?></td>
                                                    <td><?php echo $student['year_of_study']; ?></td>
                                                    <td>
                                                        <span class="badge badge-danger"><?php echo $student['overdue_books']; ?></span>
                                                    </td>
                                                    <td>₹<?php echo $student['total_fine']; ?></td>
                                                    <!-- <td>
                                                        <a href="student_library.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Details</a>
                                                    </td> -->
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No students with overdue books.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-medal"></i> Top Borrowers
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($top_borrowers_result) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Register No</th>
                                                <th>Year</th>
                                                <th>Total Books</th>
                                                <!-- <th>Action</th> -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($student = mysqli_fetch_assoc($top_borrowers_result)): ?>
                                                <tr>
                                                    <td><?php echo $student['name']; ?></td>
                                                    <td><?php echo $student['register_number']; ?></td>
                                                    <td><?php echo $student['year_of_study']; ?></td>
                                                    <td>
                                                        <span class="badge badge-info"><?php echo $student['total_books']; ?></span>
                                                    </td>
                                                    <!-- <td>
                                                        <a href="student_library.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Details</a>
                                                    </td> -->
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No library records found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-history"></i> Recent Library Activities
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($recent_activities_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Year</th>
                                        <th>Book ID</th>
                                        <th>Book Name</th>
                                        <th>Action</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($activity = mysqli_fetch_assoc($recent_activities_result)): 
                                        $action = 'Issue';
                                        $date = $activity['issue_date'];
                                        
                                        if ($activity['status'] == 'Returned') {
                                            $action = 'Return';
                                            $date = $activity['return_date'];
                                        } elseif ($activity['status'] == 'Overdue') {
                                            $action = 'Overdue';
                                            $date = $activity['due_date'];
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo $activity['student_name']; ?></td>
                                            <td><?php echo $activity['register_number']; ?></td>
                                            <td><?php echo $activity['year_of_study']; ?></td>
                                            <td><?php echo $activity['book_id']; ?></td>
                                            <td><?php echo $activity['book_name']; ?></td>
                                            <td><?php echo $action; ?></td>
                                            <td><?php echo date('d M Y', strtotime($date)); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $activity['status'] == 'Issued' ? 'info' : 
                                                        ($activity['status'] == 'Returned' ? 'success' : 'danger'); 
                                                ?>">
                                                    <?php echo $activity['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No recent library activities found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> View Reports
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>View by Year</label>
                                <select id="year-report" class="form-control" onchange="redirectToYearReport()">
                                    <option value="">Select Year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>View by Status</label>
                                <select id="status-report" class="form-control" onchange="redirectToStatusReport()">
                                    <option value="">Select Status</option>
                                    <option value="Issued">Currently Issued</option>
                                    <option value="Overdue">Overdue Books</option>
                                    <option value="Fines">Outstanding Fines</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <a href="export_library_records.php" class="btn btn-block">Export Full Report</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
    
    <script>
        function redirectToYearReport() {
            var year = document.getElementById("year-report").value;
            if (year) {
                window.location.href = "library_report.php?type=year&value=" + year;
            }
        }
        
        function redirectToStatusReport() {
            var status = document.getElementById("status-report").value;
            if (status) {
                window.location.href = "library_report.php?type=status&value=" + status;
            }
        }
    </script>
</body>
</html>