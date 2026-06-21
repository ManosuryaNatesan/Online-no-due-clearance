<?php
session_start();
// Check if user is logged in and is a librarian
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get librarian details
$librarian_id = $_SESSION['user_id'];
$query = "SELECT * FROM librarian WHERE librarian_id = $librarian_id";
$result = mysqli_query($conn, $query);
$librarian = mysqli_fetch_assoc($result);

// Get total books issued
$query = "SELECT COUNT(*) as issued_books FROM library_records WHERE status = 'Issued'";
$issued_books_result = mysqli_query($conn, $query);
$issued_books = mysqli_fetch_assoc($issued_books_result)['issued_books'];

// Get overdue books
$query = "SELECT COUNT(*) as overdue_books FROM library_records WHERE status = 'Overdue'";
$overdue_books_result = mysqli_query($conn, $query);
$overdue_books = mysqli_fetch_assoc($overdue_books_result)['overdue_books'];

// Get total fine amount pending
$query = "SELECT SUM(fine_amount) as total_fine FROM library_records WHERE fine_amount > 0 AND status IN ('Issued', 'Overdue')";
$fine_result = mysqli_query($conn, $query);
$total_fine = mysqli_fetch_assoc($fine_result)['total_fine'] ?: 0;

// Get recent book activities
$query = "SELECT l.record_id, s.name as student_name, l.book_name, l.issue_date, l.due_date, l.status, l.fine_amount
          FROM library_records l
          JOIN students s ON l.student_id = s.student_id
          ORDER BY l.updated_at DESC
          LIMIT 10";
$records_result = mysqli_query($conn, $query);

// Get pending no due requests for approval
$query = "SELECT r.request_id, s.name as student_name, s.register_number, r.request_date, r.librarian_approval
          FROM no_due_requests r
          JOIN students s ON r.student_id = s.student_id
          WHERE r.librarian_approval = 'Pending' AND r.faculty_approval = 'Approved'
          ORDER BY r.request_date ASC";
$no_due_result = mysqli_query($conn, $query);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - College Management System</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/college_logo.jpeg" alt="College Logo">
                <h3>Library Portal</h3>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="book_records.php"><i class="fas fa-book"></i> <span>Book Records</span></a></li>
                <li><a href="issued_books.php"><i class="fas fa-bookmark"></i> <span>Issue Book</span></a></li>
                <li><a href="return_books.php"><i class="fas fa-undo"></i> <span>Return Book</span></a></li>
                <li><a href="manage_fines.php"><i class="fas fa-money-bill-wave"></i> <span>Manage Fines</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Librarian Dashboard</h1>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-user"></i> Librarian Information
                        </div>
                        <div class="card-body">
                            <div class="profile-card">
                                <img src="../images/<?php echo $librarian['profile_image']; ?>" alt="Profile Image" class="profile-image">
                                <h3><?php echo $librarian['name']; ?></h3>
                                <p>Email: <?php echo $librarian['email']; ?></p>
                                <p>Contact: <?php echo $librarian['mobile']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-pie"></i> Library Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-book fa-3x mb-3"></i>
                                        <h3><?php echo $issued_books; ?></h3>
                                        <p>Books Issued</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                        <h3><?php echo $overdue_books; ?></h3>
                                        <p>Overdue Books</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-money-bill-wave fa-3x mb-3"></i>
                                        <h3>₹<?php echo $total_fine; ?></h3>
                                        <p>Total Fine</p>
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="text-center mt-3">
                                <a href="reports.php" class="btn">View Detailed Reports</a>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
            
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
                                        <th>Request Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($request = mysqli_fetch_assoc($no_due_result)): ?>
                                        <tr>
                                            <td><?php echo $request['student_name']; ?></td>
                                            <td><?php echo $request['register_number']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                                            <td>
                                                <a href="no_due.php?id=<?php echo $request['request_id']; ?>" class="btn btn-success">Review</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No pending requests for approval.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-book"></i> Recent Book Activities
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($records_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Book Name</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Fine</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = mysqli_fetch_assoc($records_result)): ?>
                                        <tr>
                                            <td><?php echo $record['student_name']; ?></td>
                                            <td><?php echo $record['book_name']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($record['issue_date'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($record['due_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo ($record['status'] == 'Returned' ? 'success' : ($record['status'] == 'Overdue' ? 'danger' : 'info')); ?>">
                                                    <?php echo $record['status']; ?>
                                                </span>
                                            </td>
                                            <td>₹<?php echo $record['fine_amount']; ?></td>
                                            <td>
                                                <?php if ($record['status'] != 'Returned'): ?>
                                                    <a href="return_book.php?id=<?php echo $record['record_id']; ?>" class="btn">Return</a>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Returned</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No book records found.</p>
                    <?php endif; ?>
                    <div class="text-center mt-3">
                        <a href="book_records.php" class="btn">View All Book Records</a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-bullhorn"></i> Quick Links
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-bookmark fa-3x mb-3"></i>
                                        <h4>Issue Book</h4>
                                        <p>Issue a new book to a student</p>
                                        <a href="issued_books.php" class="btn">Issue Book</a>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-undo fa-3x mb-3"></i>
                                        <h4>Return Book</h4>
                                        <p>Process a book return</p>
                                        <a href="return_books.php" class="btn">Return Book</a>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                        <h4>Overdue Books</h4>
                                        <p>View all overdue books</p>
                                        <a href="manage_fines.php" class="btn">View Overdue</a>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-clipboard-check fa-3x mb-3"></i>
                                        <h4>No Due Approvals</h4>
                                        <p>Review student no due requests</p>
                                        <a href="no_due.php" class="btn">View Requests</a>
                                    </div>
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