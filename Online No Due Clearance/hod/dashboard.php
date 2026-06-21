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

// Get department students count
$query = "SELECT COUNT(*) as student_count FROM students WHERE department = '{$hod['department']}'";
$student_count_result = mysqli_query($conn, $query);
$student_count = mysqli_fetch_assoc($student_count_result)['student_count'];

// Get department faculty count
$query = "SELECT COUNT(*) as faculty_count FROM faculty WHERE department = '{$hod['department']}'";
$faculty_count_result = mysqli_query($conn, $query);
$faculty_count = mysqli_fetch_assoc($faculty_count_result)['faculty_count'];

// Get pending no due requests for final approval
$query = "SELECT r.request_id, s.name as student_name, s.register_number, r.request_date, 
          r.faculty_approval, r.librarian_approval, r.accountant_approval, r.hod_approval,
          r.faculty_approval_date, r.librarian_approval_date, r.accountant_approval_date
          FROM no_due_requests r
          JOIN students s ON r.student_id = s.student_id
          WHERE s.department = '{$hod['department']}'
          AND r.faculty_approval = 'Approved'
          AND r.librarian_approval = 'Approved'
          AND r.accountant_approval = 'Approved'
          AND r.hod_approval = 'Pending'
          ORDER BY r.request_date ASC";
$no_due_result = mysqli_query($conn, $query);

// Get recent library activities for department students
$query = "SELECT l.record_id, s.name as student_name, l.book_name, l.issue_date, l.due_date, l.status
          FROM library_records l
          JOIN students s ON l.student_id = s.student_id
          WHERE s.department = '{$hod['department']}'
          ORDER BY l.updated_at DESC
          LIMIT 5";
$library_result = mysqli_query($conn, $query);

// ✅ Fixed: Get recent fee payments with correct column `amount_paid`
$query = "SELECT f.fee_id, s.name as student_name, f.fee_type, f.amount_paid, f.total_amount, f.status, f.updated_at
          FROM fees f
          JOIN students s ON f.student_id = s.student_id
          WHERE s.department = '{$hod['department']}'
          AND f.payment_date IS NOT NULL
          ORDER BY f.updated_at DESC
          LIMIT 5";
$fees_result = mysqli_query($conn, $query);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard - College Management System</title>
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
            <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> <span>Manage Students</span></a></li>
            <!-- <li><a href="faculty.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Faculty</span></a></li> -->
            <li><a href="library.php"><i class="fas fa-book"></i> <span>Library Records</span></a></li>
            <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> <span>Fees Records</span></a></li>
            <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
            <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
        </ul>

        <div class="logout">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <h1 class="page-title">HOD Dashboard</h1>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user"></i> HOD Information
                    </div>
                    <div class="card-body">
                        <div class="profile-card">
                            <img src="../images/<?php echo $hod['profile_image']; ?>" alt="Profile Image" class="profile-image">
                            <h3><?php echo $hod['name']; ?></h3>
                            <p>Department: <?php echo $hod['department']; ?></p>
                            <p>Email: <?php echo $hod['email']; ?></p>
                            <p>Contact: <?php echo $hod['mobile']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie"></i> Department Statistics
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 text-center">
                                <i class="fas fa-user-graduate fa-3x mb-3"></i>
                                <h3><?php echo $student_count; ?></h3>
                                <p>Students</p>
                            </div>
                            <div class="col-md-6 text-center">
                                <i class="fas fa-chalkboard-teacher fa-3x mb-3"></i>
                                <h3><?php echo $faculty_count; ?></h3>
                                <p>Faculty</p>
                            </div>
                        </div>
                        <!-- <div class="text-center mt-3">
                            <a href="department_report.php" class="btn">View Full Department Report</a>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>

        <!-- No Due Approvals -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clipboard-check"></i> No Due Approvals
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($no_due_result) > 0): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Register No</th>
                                <th>Faculty Approval</th>
                                <th>Library Approval</th>
                                <th>Accounts Approval</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php while ($request = mysqli_fetch_assoc($no_due_result)): ?>
                                <tr>
                                    <td><?php echo $request['student_name']; ?></td>
                                    <td><?php echo $request['register_number']; ?></td>
                                    <td>
                                        <span class="badge badge-success">Approved</span>
                                        <small><?php echo date('d M', strtotime($request['faculty_approval_date'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">Approved</span>
                                        <small><?php echo date('d M', strtotime($request['librarian_approval_date'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">Approved</span>
                                        <small><?php echo date('d M', strtotime($request['accountant_approval_date'])); ?></small>
                                    </td>
                                    <td>
                                        <a href="no_due.php?id=<?php echo $request['request_id']; ?>" class="btn btn-success">Review</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No pending requests for final approval.</p>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="no_due.php" class="btn">View All No Due Requests</a>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-book"></i> Recent Library Activities
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($library_result) > 0): ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Book</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php while ($record = mysqli_fetch_assoc($library_result)): ?>
                                        <tr>
                                            <td><?php echo $record['student_name']; ?></td>
                                            <td><?php echo $record['book_name']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($record['due_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo ($record['status'] == 'Returned' ? 'success' : ($record['status'] == 'Overdue' ? 'danger' : 'info')); ?>">
                                                    <?php echo $record['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No recent library activities found.</p>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="library.php" class="btn">View All Library Records</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ Recent Fee Payments with Fixed Column -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-money-bill-wave"></i> Recent Fee Payments
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($fees_result) > 0): ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Fee Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php while ($fee = mysqli_fetch_assoc($fees_result)): ?>
                                        <tr>
                                            <td><?php echo $fee['student_name']; ?></td>
                                            <td><?php echo $fee['fee_type']; ?></td>
                                            <td>₹<?php echo $fee['amount_paid']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo ($fee['status'] == 'Paid' ? 'success' : ($fee['status'] == 'Partially Paid' ? 'warning' : 'danger')); ?>">
                                                    <?php echo $fee['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No recent fee payments found.</p>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="fees.php" class="btn">View All Fee Records</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bullhorn"></i> Quick Links
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <i class="fas fa-user-graduate fa-3x mb-3"></i>
                                <h4>Manage Students</h4>
                                <p>View, add, edit or delete student records</p>
                                <a href="students.php" class="btn">Manage Students</a>
                            </div>
                            <!-- <div class="col-md-4 text-center">
                                <i class="fas fa-chalkboard-teacher fa-3x mb-3"></i>
                                <h4>Manage Faculty</h4>
                                <p>View, add, edit or delete faculty records</p>
                                <a href="faculty.php" class="btn">Manage Faculty</a>
                            </div> -->
                            <div class="col-md-4 text-center">
                                <i class="fas fa-clipboard-check fa-3x mb-3"></i>
                                <h4>No Due Approvals</h4>
                                <p>Review and provide final approval for no due requests</p>
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
