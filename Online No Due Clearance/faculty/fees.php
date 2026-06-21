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

// Get all students in the faculty's department with fee details
$department = $faculty['department'];
$query = "SELECT s.*, 
          SUM(f.total_amount) as total_fees,
          SUM(f.amount_paid) as paid_amount,
          SUM(f.balance) as balance_amount,
          SUM(f.fine_amount) as fine_amount,
          SUM(CASE WHEN f.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_count
          FROM students s
          LEFT JOIN fees f ON s.student_id = f.student_id
          WHERE s.department = '$department'
          GROUP BY s.student_id
          ORDER BY s.year_of_study, s.name";
$students_result = mysqli_query($conn, $query);

// Get department fee statistics
$query = "SELECT 
          SUM(f.total_amount) as total_fees,
          SUM(f.amount_paid) as total_collected,
          SUM(f.balance) as total_pending,
          SUM(f.fine_amount) as total_fine,
          COUNT(DISTINCT s.student_id) as student_count
          FROM fees f
          JOIN students s ON f.student_id = s.student_id
          WHERE s.department = '$department'";
$stats_result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($stats_result);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Records - College Management System</title>
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
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
                <li><a href="library.php"><i class="fas fa-book"></i> <span>Library</span></a></li>
                <li><a href="fees.php" class="active"><i class="fas fa-money-bill-wave"></i> <span>Fees</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Fee Records</h1>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> Department Fee Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <h3><?php echo $stats['student_count'] ?: 0; ?></h3>
                                        <p>Total Students</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-money-check fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['total_fees'] ?: 0); ?></h3>
                                        <p>Total Fee Amount</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['total_collected'] ?: 0); ?></h3>
                                        <p>Total Collected</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-hourglass-half fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['total_pending'] ?: 0); ?></h3>
                                        <p>Total Pending</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['total_fine'] ?: 0); ?></h3>
                                        <p>Total Fine</p>
                                    </div>
                                </div>
                            </div>
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Search by name or register number">
                            </div>
                        </div>
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <select id="status-filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="due">Has Due</option>
                                    <option value="overdue">Has Overdue</option>
                                    <option value="fine">Has Fine</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-list"></i> Students Fee Status
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
                                        <th>Total Fees</th>
                                        <th>Paid Amount</th>
                                        <th>Balance</th>
                                        <th>Fine</th>
                                        <th>Status</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                        <tr 
                                            data-year="<?php echo $student['year_of_study']; ?>"
                                            data-due="<?php echo ($student['balance_amount'] > 0) ? 'yes' : 'no'; ?>"
                                            data-overdue="<?php echo ($student['overdue_count'] > 0) ? 'yes' : 'no'; ?>"
                                            data-fine="<?php echo ($student['fine_amount'] > 0) ? 'yes' : 'no'; ?>"
                                        >
                                            <td><?php echo $student['register_number']; ?></td>
                                            <td><?php echo $student['name']; ?></td>
                                            <td><?php echo $student['year_of_study']; ?></td>
                                            <td>₹<?php echo number_format($student['total_fees'] ?: 0); ?></td>
                                            <td>₹<?php echo number_format($student['paid_amount'] ?: 0); ?></td>
                                            <td>₹<?php echo number_format($student['balance_amount'] ?: 0); ?></td>
                                            <td>
                                                <?php if ($student['fine_amount'] > 0): ?>
                                                    <span class="badge badge-warning">₹<?php echo number_format($student['fine_amount']); ?></span>
                                                <?php else: ?>
                                                    ₹0
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['overdue_count'] > 0): ?>
                                                    <span class="badge badge-danger">Overdue</span>
                                                <?php elseif ($student['balance_amount'] > 0): ?>
                                                    <span class="badge badge-warning">Has Due</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Paid</span>
                                                <?php endif; ?>
                                            </td>
                                            <!-- <td>
                                                <a href="view_fee_records.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Details</a>
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
            
            <!-- <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-exclamation-circle"></i> Overdue Fee Items
                </div>
                <div class="card-body">
                    <p>Click the button below to see all overdue fee items for students in your department:</p>
                    <a href="overdue_fees.php" class="btn">View Overdue Fees</a>
                </div>
            </div> -->
        </div>
    </div>
    
    <script>
        // Search functionality
        document.getElementById('search-input').addEventListener('keyup', function() {
            filterStudents();
        });
        
        // Year filter functionality
        document.getElementById('year-filter').addEventListener('change', function() {
            filterStudents();
        });
        
        // Status filter functionality
        document.getElementById('status-filter').addEventListener('change', function() {
            filterStudents();
        });
        
        function filterStudents() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const yearFilter = document.getElementById('year-filter').value;
            const statusFilter = document.getElementById('status-filter').value;
            const rows = document.querySelectorAll('#students-table tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const regNo = row.cells[0].textContent.toLowerCase();
                const year = row.getAttribute('data-year');
                const hasDue = row.getAttribute('data-due');
                const hasOverdue = row.getAttribute('data-overdue');
                const hasFine = row.getAttribute('data-fine');
                
                // Check if row matches search, year, and status filters
                const matchesSearch = name.includes(searchValue) || regNo.includes(searchValue);
                const matchesYear = yearFilter === '' || year === yearFilter;
                
                let matchesStatus = true;
                if (statusFilter === 'due') {
                    matchesStatus = hasDue === 'yes';
                } else if (statusFilter === 'overdue') {
                    matchesStatus = hasOverdue === 'yes';
                } else if (statusFilter === 'fine') {
                    matchesStatus = hasFine === 'yes';
                }
                
                row.style.display = (matchesSearch && matchesYear && matchesStatus) ? '' : 'none';
            });
        }
    </script>
</body>
</html>