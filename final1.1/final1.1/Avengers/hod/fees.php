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

// Get fee statistics for the department
$query = "SELECT 
          SUM(f.total_amount) as total_fees,
          SUM(f.amount_paid) as paid_amount,
          SUM(f.balance) as balance_amount,
          SUM(f.fine_amount) as total_fine,
          COUNT(DISTINCT s.student_id) as student_count
          FROM fees f
          JOIN students s ON f.student_id = s.student_id
          WHERE s.department = '$department'";
$stats_result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($stats_result);

// Get fees paid % by year
$year_stats = [];
for ($year = 1; $year <= 4; $year++) {
    $query = "SELECT 
              SUM(f.total_amount) as total_fees,
              SUM(f.amount_paid) as paid_amount
              FROM fees f
              JOIN students s ON f.student_id = s.student_id
              WHERE s.department = '$department' AND s.year_of_study = $year";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    
    if ($data['total_fees'] > 0) {
        $year_stats[$year] = round(($data['paid_amount'] / $data['total_fees']) * 100);
    } else {
        $year_stats[$year] = 0;
    }
}

// Get students with outstanding dues
$query = "SELECT s.student_id, s.name, s.register_number, s.year_of_study,
          SUM(f.balance) as total_balance,
          SUM(f.fine_amount) as total_fine,
          SUM(CASE WHEN f.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_count
          FROM students s
          JOIN fees f ON s.student_id = f.student_id
          WHERE s.department = '$department' AND (f.balance > 0 OR f.fine_amount > 0)
          GROUP BY s.student_id
          ORDER BY total_balance DESC";
$outstanding_result = mysqli_query($conn, $query);

// Get fee payment statistics by type
$query = "SELECT 
          f.fee_type,
          SUM(f.total_amount) as total_amount,
          SUM(f.amount_paid) as paid_amount,
          SUM(f.balance) as balance
          FROM fees f
          JOIN students s ON f.student_id = s.student_id
          WHERE s.department = '$department'
          GROUP BY f.fee_type";
$fee_types_result = mysqli_query($conn, $query);

// Get recent fee payments
$query = "SELECT f.*, s.name as student_name, s.register_number, s.year_of_study
          FROM fees f
          JOIN students s ON f.student_id = s.student_id
          WHERE s.department = '$department' AND f.payment_date IS NOT NULL
          ORDER BY f.payment_date DESC
          LIMIT 10";
$recent_payments_result = mysqli_query($conn, $query);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees Overview - College Management System</title>
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
            <h1 class="page-title">Fees Overview - <?php echo $department; ?> Department</h1>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> Fee Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <h3><?php echo $stats['student_count']; ?></h3>
                                        <p>Total Students</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-money-check fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['total_fees'] ?: 0); ?></h3>
                                        <p>Total Fees</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['paid_amount'] ?: 0); ?></h3>
                                        <p>Collected</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-hourglass-half fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['balance_amount'] ?: 0); ?></h3>
                                        <p>Outstanding</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['total_fine'] ?: 0); ?></h3>
                                        <p>Fine Amount</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-percentage fa-3x mb-3"></i>
                                        <?php 
                                        $collection_percentage = 0;
                                        if ($stats['total_fees'] > 0) {
                                            $collection_percentage = round(($stats['paid_amount'] / $stats['total_fees']) * 100);
                                        }
                                        
                                        $badge_class = 'danger';
                                        if ($collection_percentage >= 90) {
                                            $badge_class = 'success';
                                        } elseif ($collection_percentage >= 70) {
                                            $badge_class = 'info';
                                        } elseif ($collection_percentage >= 50) {
                                            $badge_class = 'warning';
                                        }
                                        ?>
                                        <h3 class="text-<?php echo $badge_class; ?>"><?php echo $collection_percentage; ?>%</h3>
                                        <p>Collection</p>
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
                            <i class="fas fa-chart-bar"></i> Collection by Year
                        </div>
                        <div class="card-body">
                            <?php foreach ($year_stats as $year => $percentage): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Year <?php echo $year; ?></span>
                                        <span><?php echo $percentage; ?>%</span>
                                    </div>
                                    <div class="progress">
                                        <?php
                                        $bar_class = 'bg-danger';
                                        if ($percentage >= 90) {
                                            $bar_class = 'bg-success';
                                        } elseif ($percentage >= 70) {
                                            $bar_class = 'bg-info';
                                        } elseif ($percentage >= 50) {
                                            $bar_class = 'bg-warning';
                                        }
                                        ?>
                                        <div class="progress-bar <?php echo $bar_class; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <!-- <div class="text-center mt-3">
                                <a href="year_wise_fees.php" class="btn">View Detailed Report</a>
                            </div> -->
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-receipt"></i> Fee Type Summary
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($fee_types_result) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Fee Type</th>
                                                <th>Total Amount</th>
                                                <th>Collected</th>
                                                <th>Outstanding</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($fee_type = mysqli_fetch_assoc($fee_types_result)): 
                                                $collection_percentage = 0;
                                                if ($fee_type['total_amount'] > 0) {
                                                    $collection_percentage = round(($fee_type['paid_amount'] / $fee_type['total_amount']) * 100);
                                                }
                                                
                                                $badge_class = 'danger';
                                                if ($collection_percentage >= 90) {
                                                    $badge_class = 'success';
                                                } elseif ($collection_percentage >= 70) {
                                                    $badge_class = 'info';
                                                } elseif ($collection_percentage >= 50) {
                                                    $badge_class = 'warning';
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo $fee_type['fee_type']; ?></td>
                                                    <td>₹<?php echo number_format($fee_type['total_amount']); ?></td>
                                                    <td>₹<?php echo number_format($fee_type['paid_amount']); ?></td>
                                                    <td>₹<?php echo number_format($fee_type['balance']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $badge_class; ?>">
                                                            <?php echo $collection_percentage; ?>%
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No fee records found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle"></i> Students with Outstanding Dues
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($outstanding_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Year</th>
                                        <th>Outstanding Amount</th>
                                        <th>Fine Amount</th>
                                        <th>Overdue Items</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = mysqli_fetch_assoc($outstanding_result)): ?>
                                        <tr>
                                            <td><?php echo $student['name']; ?></td>
                                            <td><?php echo $student['register_number']; ?></td>
                                            <td><?php echo $student['year_of_study']; ?></td>
                                            <td>₹<?php echo number_format($student['total_balance']); ?></td>
                                            <td>
                                                <?php if ($student['total_fine'] > 0): ?>
                                                    <span class="badge badge-warning">₹<?php echo number_format($student['total_fine']); ?></span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['overdue_count'] > 0): ?>
                                                    <span class="badge badge-danger"><?php echo $student['overdue_count']; ?></span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <!-- <td>
                                                <a href="student_fees.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Details</a>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No students with outstanding dues found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-history"></i> Recent Fee Payments
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($recent_payments_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Year</th>
                                        <th>Fee Type</th>
                                        <th>Amount Paid</th>
                                        <th>Payment Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = mysqli_fetch_assoc($recent_payments_result)): ?>
                                        <tr>
                                            <td><?php echo $payment['student_name']; ?></td>
                                            <td><?php echo $payment['register_number']; ?></td>
                                            <td><?php echo $payment['year_of_study']; ?></td>
                                            <td><?php echo $payment['fee_type']; ?></td>
                                            <td>₹<?php echo number_format($payment['amount_paid']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $payment['status'] == 'Paid' ? 'success' : 
                                                        ($payment['status'] == 'Partially Paid' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo $payment['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No recent fee payments found.</p>
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
                                    <option value="due">Outstanding Dues</option>
                                    <option value="overdue">Overdue Fees</option>
                                    <option value="fine">With Fines</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <a href="export_fee_records.php" class="btn btn-block">Export Full Report</a>
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
                window.location.href = "fee_report.php?type=year&value=" + year;
            }
        }
        
        function redirectToStatusReport() {
            var status = document.getElementById("status-report").value;
            if (status) {
                window.location.href = "fee_report.php?type=status&value=" + status;
            }
        }
    </script>
</body>
</html>