<?php
session_start();
// Check if user is logged in and is an accountant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get accountant details
$accountant_id = $_SESSION['user_id'];
$query = "SELECT * FROM accountant WHERE accountant_id = $accountant_id";
$result = mysqli_query($conn, $query);
$accountant = mysqli_fetch_assoc($result);

// Get total fees collected
$query = "SELECT SUM(total_amount - balance) as total_collected FROM fees WHERE status = 'Paid'";
$fees_collected_result = mysqli_query($conn, $query);
$total_collected = mysqli_fetch_assoc($fees_collected_result)['total_collected'] ?: 0;

// Get total fees pending
$query = "SELECT SUM(balance) as total_pending FROM fees WHERE status != 'Paid'";
$fees_pending_result = mysqli_query($conn, $query);
$total_pending = mysqli_fetch_assoc($fees_pending_result)['total_pending'] ?: 0;

// Get total fine amount pending
$query = "SELECT SUM(fine_amount) as total_fine FROM fees WHERE fine_amount > 0";
$fine_result = mysqli_query($conn, $query);
$total_fine = mysqli_fetch_assoc($fine_result)['total_fine'] ?: 0;

// Get recent fee transactions
$query = "SELECT f.fee_id, s.name as student_name, s.register_number, f.fee_type, f.total_amount, f.balance, f.status, f.updated_at
          FROM fees f
          JOIN students s ON f.student_id = s.student_id
          ORDER BY f.updated_at DESC
          LIMIT 10";
$fees_result = mysqli_query($conn, $query);

// Get pending no due requests for approval
$query = "SELECT r.request_id, s.name as student_name, s.register_number, r.request_date, r.accountant_approval
          FROM no_due_requests r
          JOIN students s ON r.student_id = s.student_id
          WHERE r.accountant_approval = 'Pending' AND r.faculty_approval = 'Approved'
          ORDER BY r.request_date ASC";
$no_due_result = mysqli_query($conn, $query);

// Get overdue fees
$query = "SELECT f.fee_id, s.name as student_name, s.register_number, f.fee_type, f.balance, f.due_date, f.fine_amount
          FROM fees f
          JOIN students s ON f.student_id = s.student_id
          WHERE f.status = 'Overdue'
          ORDER BY f.due_date ASC
          LIMIT 5";
$overdue_result = mysqli_query($conn, $query);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard - College Management System</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div class="dashboard">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../images/college_logo.jpeg" alt="College Logo">
            <h3>Accounts Portal</h3>
        </div>
        <ul class="sidebar-menu">
            <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
            <li><a href="student_fees.php"><i class="fas fa-money-check-alt"></i> <span>Student Fees</span></a></li>
            <li><a href="fee_payments.php"><i class="fas fa-cash-register"></i> <span>Fee Payments</span></a></li>
            <li><a href="overdue.php"><i class="fas fa-exclamation-circle"></i> <span>Overdue Fees</span></a></li>
            <li><a href="fines.php"><i class="fas fa-money-bill-wave"></i> <span>Manage Fines</span></a></li>
            <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
        </ul>
        <div class="logout">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <h1 class="page-title">Accountant Dashboard</h1>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><i class="fas fa-user"></i> Accountant Information</div>
                    <div class="card-body">
                        <div class="profile-card">
                            <img src="../images/<?php echo $accountant['profile_image']; ?>" class="profile-image" alt="Profile">
                            <h3><?php echo $accountant['name']; ?></h3>
                            <p>Email: <?php echo $accountant['email']; ?></p>
                            <p>Contact: <?php echo $accountant['mobile']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><i class="fas fa-chart-pie"></i> Fee Statistics</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <i class="fas fa-money-bill-alt fa-3x mb-2"></i>
                                <h3>₹<?php echo number_format($total_collected); ?></h3>
                                <p>Total Collected</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-hourglass-half fa-3x mb-2"></i>
                                <h3>₹<?php echo number_format($total_pending); ?></h3>
                                <p>Total Pending</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-exclamation-circle fa-3x mb-2"></i>
                                <h3>₹<?php echo number_format($total_fine); ?></h3>
                                <p>Total Fine</p>
                            </div>
                        </div>
                        <!-- <div class="text-center mt-3">
                            <a href="reports.php" class="btn">View Detailed Reports</a>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>

        <!-- No Due Requests -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><i class="fas fa-clipboard-check"></i> No Due Approvals</div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($no_due_result) > 0): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Register No</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($r = mysqli_fetch_assoc($no_due_result)): ?>
                                        <tr>
                                            <td><?php echo $r['student_name']; ?></td>
                                            <td><?php echo $r['register_number']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($r['request_date'])); ?></td>
                                            <td><a href="no_due.php?id=<?php echo $r['request_id']; ?>" class="btn btn-success">Review</a></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No pending no due requests.</p>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="no_due.php" class="btn">View All</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Fees -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><i class="fas fa-exclamation-circle"></i> Overdue Fees</div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($overdue_result) > 0): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Fee Type</th>
                                        <th>Balance</th>
                                        <th>Due</th>
                                        <th>Fine</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($o = mysqli_fetch_assoc($overdue_result)): ?>
                                        <tr>
                                            <td><?php echo $o['student_name']; ?></td>
                                            <td><?php echo $o['fee_type']; ?></td>
                                            <td>₹<?php echo $o['balance']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($o['due_date'])); ?></td>
                                            <td>₹<?php echo $o['fine_amount']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No overdue fees found.</p>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="overdue.php" class="btn">View All Overdues</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card mt-4">
            <div class="card-header"><i class="fas fa-money-check-alt"></i> Recent Fee Transactions</div>
            <div class="card-body">
                <?php if (mysqli_num_rows($fees_result) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Register No</th>
                                <th>Fee Type</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($f = mysqli_fetch_assoc($fees_result)): ?>
                                <tr>
                                    <td><?php echo $f['student_name']; ?></td>
                                    <td><?php echo $f['register_number']; ?></td>
                                    <td><?php echo $f['fee_type']; ?></td>
                                    <td>₹<?php echo $f['total_amount'] - $f['balance']; ?></td>
                                    <td>₹<?php echo $f['balance']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $f['status'] == 'Paid' ? 'success' : ($f['status'] == 'Overdue' ? 'danger' : 'warning'); ?>">
                                            <?php echo $f['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($f['updated_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No fee transactions found.</p>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="fee_payments.php" class="btn">View All Payments</a>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row mt-4">
            <div class="col">
                <div class="card">
                    <div class="card-header"><i class="fas fa-bullhorn"></i> Quick Links</div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <i class="fas fa-money-check-alt fa-3x mb-2"></i>
                                <h4>Student Fees</h4>
                                <a href="student_fees.php" class="btn">Manage Fees</a>
                            </div>
                            <!-- <div class="col-md-3">
                                <i class="fas fa-cash-register fa-3x mb-2"></i>
                                <h4>Record Payment</h4>
                                <a href="record_payment.php" class="btn">Record Payment</a>
                            </div> -->
                            <div class="col-md-3">
                                <i class="fas fa-exclamation-circle fa-3x mb-2"></i>
                                <h4>Overdue Fees</h4>
                                <a href="overdue.php" class="btn">View Overdue</a>
                            </div>
                            <div class="col-md-3">
                                <i class="fas fa-clipboard-check fa-3x mb-2"></i>
                                <h4>No Due Approvals</h4>
                                <a href="no_due.php" class="btn">View Requests</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
