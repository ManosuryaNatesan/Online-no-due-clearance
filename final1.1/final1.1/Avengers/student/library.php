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

// Get student's library records
$query = "SELECT * FROM library_records WHERE student_id = $student_id ORDER BY issue_date DESC";
$library_result = mysqli_query($conn, $query);

// Calculate total fine
$query = "SELECT SUM(fine_amount) as total_fine FROM library_records WHERE student_id = $student_id AND status = 'Overdue'";
$fine_result = mysqli_query($conn, $query);
$total_fine = mysqli_fetch_assoc($fine_result)['total_fine'] ?: 0;

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
                <h3>Student Portal</h3>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
                <li><a href="internal_marks.php"><i class="fas fa-chart-bar"></i> <span>Internal Marks</span></a></li>
                <li><a href="library.php" class="active"><i class="fas fa-book"></i> <span>Library</span></a></li>
                <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> <span>Fees Due</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Application</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Library Records</h1>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> Library Summary
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-book fa-3x"></i>
                            </div>
                            <div class="summary-box">
                                <?php
                                // Count books by status
                                $issued_count = 0;
                                $returned_count = 0;
                                $overdue_count = 0;
                                
                                mysqli_data_seek($library_result, 0);
                                while ($record = mysqli_fetch_assoc($library_result)) {
                                    if ($record['status'] == 'Issued') {
                                        $issued_count++;
                                    } else if ($record['status'] == 'Returned') {
                                        $returned_count++;
                                    } else if ($record['status'] == 'Overdue') {
                                        $overdue_count++;
                                    }
                                }
                                mysqli_data_seek($library_result, 0);
                                ?>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $issued_count; ?></span>
                                    <span class="stat-label">Books Currently Issued</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $returned_count; ?></span>
                                    <span class="stat-label">Books Returned</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $overdue_count; ?></span>
                                    <span class="stat-label">Overdue Books</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value">₹<?php echo $total_fine; ?></span>
                                    <span class="stat-label">Total Fine Amount</span>
                                </div>
                            </div>
                            
                            <?php if ($overdue_count > 0 || $total_fine > 0): ?>
                                <div class="alert alert-danger mt-3">
                                    <i class="fas fa-exclamation-circle"></i> You have overdue books or pending fines. Please return the books and clear your fines.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-circle"></i> Overdue Books
                        </div>
                        <div class="card-body">
                            <?php 
                            $has_overdue = false;
                            mysqli_data_seek($library_result, 0);
                            while ($record = mysqli_fetch_assoc($library_result)) {
                                if ($record['status'] == 'Overdue') {
                                    $has_overdue = true;
                                    break;
                                }
                            }
                            mysqli_data_seek($library_result, 0);
                            
                            if ($has_overdue):
                            ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Book ID</th>
                                                <th>Book Name</th>
                                                <th>Issue Date</th>
                                                <th>Due Date</th>
                                                <th>Fine Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            mysqli_data_seek($library_result, 0);
                                            while ($record = mysqli_fetch_assoc($library_result)):
                                                if ($record['status'] == 'Overdue'):
                                            ?>
                                                <tr>
                                                    <td><?php echo $record['book_id']; ?></td>
                                                    <td><?php echo $record['book_name']; ?></td>
                                                    <td><?php echo date('d M Y', strtotime($record['issue_date'])); ?></td>
                                                    <td><?php echo date('d M Y', strtotime($record['due_date'])); ?></td>
                                                    <td>₹<?php echo $record['fine_amount']; ?></td>
                                                </tr>
                                            <?php 
                                                endif;
                                            endwhile; 
                                            mysqli_data_seek($library_result, 0);
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-info-circle"></i> Fine is calculated at the rate of ₹5 per day after the due date.
                                </div>
                            <?php else: ?>
                                <p>No overdue books.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-book"></i> All Library Records
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($library_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Book ID</th>
                                        <th>Book Name</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Return Date</th>
                                        <th>Fine</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = mysqli_fetch_assoc($library_result)): ?>
                                        <tr>
                                            <td><?php echo $record['book_id']; ?></td>
                                            <td><?php echo $record['book_name']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($record['issue_date'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($record['due_date'])); ?></td>
                                            <td>
                                                <?php echo $record['return_date'] ? date('d M Y', strtotime($record['return_date'])) : '-'; ?>
                                            </td>
                                            <td>
                                                <?php echo $record['fine_amount'] > 0 ? '₹' . $record['fine_amount'] : '-'; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $record['status'] == 'Issued' ? 'info' : 
                                                        ($record['status'] == 'Returned' ? 'success' : 'danger'); 
                                                ?>">
                                                    <?php echo $record['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No library records found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>