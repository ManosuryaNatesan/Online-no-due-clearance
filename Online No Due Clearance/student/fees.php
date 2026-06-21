<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get student details
$student_id = $_SESSION['user_id'];
$query = "SELECT * FROM students WHERE student_id = $student_id";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);

// Get fee records
$query = "SELECT * FROM fees WHERE student_id = $student_id ORDER BY due_date ASC";
$fees_result = mysqli_query($conn, $query);

// Calculate totals
$query = "SELECT 
            SUM(amount_paid) as total_paid,
            SUM(total_amount) as total_fees,
            SUM(balance) as total_balance,
            SUM(fine_amount) as total_fine
          FROM fees 
          WHERE student_id = $student_id";
$totals_result = mysqli_query($conn, $query);
$totals = mysqli_fetch_assoc($totals_result);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees Due - College Management System</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/college_logo.jpeg" alt="College Logo">
                <h3>Student Portal</h3>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
                <li><a href="internal_marks.php"><i class="fas fa-chart-bar"></i> <span>Internal Marks</span></a></li>
                <li><a href="library.php"><i class="fas fa-book"></i> <span>Library</span></a></li>
                <li><a href="fees.php" class="active"><i class="fas fa-money-bill-wave"></i> <span>Fees Due</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Application</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Fees Information</h1>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> Fees Summary
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-money-bill-wave fa-3x"></i>
                            </div>
                            <div class="summary-box">
                                <div class="stat-item">
                                    <span class="stat-value">₹<?php echo $totals['total_fees']; ?></span>
                                    <span class="stat-label">Total Fees</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value">₹<?php echo $totals['total_paid']; ?></span>
                                    <span class="stat-label">Total Paid</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value">₹<?php echo $totals['total_balance']; ?></span>
                                    <span class="stat-label">Balance Due</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value">₹<?php echo $totals['total_fine']; ?></span>
                                    <span class="stat-label">Total Fine Amount</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-circle"></i> Overdue Fees
                        </div>
                        <div class="card-body">
                            <?php 
                            $has_overdue = false;
                            mysqli_data_seek($fees_result, 0);
                            while ($fee = mysqli_fetch_assoc($fees_result)) {
                                if ($fee['status'] == 'Overdue') {
                                    $has_overdue = true;
                                    break;
                                }
                            }
                            mysqli_data_seek($fees_result, 0);
                            
                            if ($has_overdue):
                            ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Fee Type</th>
                                                <th>Balance</th>
                                                <th>Due Date</th>
                                                <th>Fine Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            mysqli_data_seek($fees_result, 0);
                                            while ($fee = mysqli_fetch_assoc($fees_result)):
                                                if ($fee['status'] == 'Overdue'):
                                            ?>
                                                <tr>
                                                    <td><?php echo $fee['fee_type']; ?></td>
                                                    <td>₹<?php echo $fee['balance']; ?></td>
                                                    <td><?php echo date('d M Y', strtotime($fee['due_date'])); ?></td>
                                                    <td>₹<?php echo $fee['fine_amount']; ?></td>
                                                </tr>
                                            <?php 
                                                endif;
                                            endwhile; 
                                            mysqli_data_seek($fees_result, 0);
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-info-circle"></i> Fine is calculated at the rate of ₹25 per day after the due date.
                                </div>
                            <?php else: ?>
                                <p>No overdue fees.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-money-check-alt"></i> All Fee Records
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($fees_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fee Type</th>
                                        <th>Total Amount</th>
                                        <th>Paid Amount</th>
                                        <th>Balance</th>
                                        <th>Due Date</th>
                                        <th>Payment Date</th>
                                        <th>Fine</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($fee = mysqli_fetch_assoc($fees_result)): ?>
                                        <tr>
                                            <td><?php echo $fee['fee_type']; ?></td>
                                            <td>₹<?php echo $fee['total_amount']; ?></td>
                                            <td>₹<?php echo $fee['amount_paid']; ?></td>
                                            <td>₹<?php echo $fee['balance']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($fee['due_date'])); ?></td>
                                            <td>
                                                <?php echo $fee['payment_date'] ? date('d M Y', strtotime($fee['payment_date'])) : '-'; ?>
                                            </td>
                                            <td>
                                                <?php echo $fee['fine_amount'] > 0 ? '₹' . $fee['fine_amount'] : '-'; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $fee['status'] == 'Paid' ? 'success' : 
                                                        ($fee['status'] == 'Partially Paid' ? 'warning' : 
                                                        ($fee['status'] == 'Overdue' ? 'danger' : 'info')); 
                                                ?>">
                                                    <?php echo $fee['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No fee records found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Information
                </div>
                <div class="card-body">
                    <div class="info-box">
                        <h4>Fee Payment Instructions</h4>
                        <ul>
                            <li>All fee payments should be made at the college accounts office.</li>
                            <li>Online payment facility may be made available soon. Please check for updates.</li>
                            <li>Payment can be made by cash, cheque, or demand draft.</li>
                            <li>After making the payment, ensure you collect the receipt.</li>
                            <li>A fine of ₹25 per day will be charged for late payments after the due date.</li>
                            <li>No due certificate will be issued only after clearing all fee payments.</li>
                        </ul>
                        
                        <h4 class="mt-4">Office Hours</h4>
                        <p>Monday to Friday: 9:00 AM to 3:00 PM</p>
                        <p>Saturday: 9:00 AM to 12:00 PM</p>
                        <p>Sunday: Closed</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>