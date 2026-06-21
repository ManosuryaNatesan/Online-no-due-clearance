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

// Get pending assignments
$query = "SELECT a.assignment_id, a.title, a.subject, a.due_date, f.name as faculty_name
          FROM assignments a
          JOIN faculty f ON a.faculty_id = f.faculty_id
          WHERE a.department = '{$student['department']}' 
          AND a.year_of_study = {$student['year_of_study']}
          AND a.assignment_id NOT IN (
              SELECT s.assignment_id FROM assignment_submissions s 
              WHERE s.student_id = $student_id
          )
          ORDER BY a.due_date ASC";
$assignments_result = mysqli_query($conn, $query);

// Get internal marks
$query = "SELECT i.subject, i.exam_type, i.marks, i.max_marks
          FROM internal_marks i
          WHERE i.student_id = $student_id
          ORDER BY i.subject, i.exam_type";
$marks_result = mysqli_query($conn, $query);

// Get library records
$query = "SELECT * FROM library_records
          WHERE student_id = $student_id
          ORDER BY status DESC, due_date ASC";
$library_result = mysqli_query($conn, $query);

// Get fees details
$query = "SELECT * FROM fees
          WHERE student_id = $student_id
          ORDER BY status";
$fees_result = mysqli_query($conn, $query);

// Get no due request status
$query = "SELECT * FROM no_due_requests
          WHERE student_id = $student_id
          ORDER BY request_date DESC
          LIMIT 1";
$no_due_result = mysqli_query($conn, $query);
$no_due_request = mysqli_fetch_assoc($no_due_result);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - College Management System</title>
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
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>               
                 
                <li><a href="internal_marks.php"><i class="fas fa-chart-bar"></i> <span>Internal Marks</span></a></li>
                <li><a href="library.php"><i class="fas fa-book"></i> <span>Library</span></a></li>
                <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> <span>Fees Due</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Application</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Student Dashboard</h1>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-user"></i> Student Information
                        </div>
                        <div class="card-body">
                            <div class="profile-card">
                                <img src="../images/<?php echo $student['profile_image']; ?>" alt="Profile Image" class="profile-image">
                                <h3><?php echo $student['name']; ?></h3>
                                <p>Register Number: <?php echo $student['register_number']; ?></p>
                                <p>Department: <?php echo $student['department']; ?></p>
                                <p>Year of Study: <?php echo $student['year_of_study']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clipboard-check"></i> No Due Status
                        </div>
                        <div class="card-body">
                            <?php if ($no_due_request): ?>
                                <table class="table">
                                    <tr>
                                        <th>Requested On</th>
                                        <td><?php echo date('d M Y', strtotime($no_due_request['request_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Faculty Approval</th>
                                        <td>
                                            <span class="badge badge-<?php echo ($no_due_request['faculty_approval'] == 'Approved' ? 'success' : ($no_due_request['faculty_approval'] == 'Rejected' ? 'danger' : 'warning')); ?>">
                                                <?php echo $no_due_request['faculty_approval']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Library Approval</th>
                                        <td>
                                            <span class="badge badge-<?php echo ($no_due_request['librarian_approval'] == 'Approved' ? 'success' : ($no_due_request['librarian_approval'] == 'Rejected' ? 'danger' : 'warning')); ?>">
                                                <?php echo $no_due_request['librarian_approval']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Accounts Approval</th>
                                        <td>
                                            <span class="badge badge-<?php echo ($no_due_request['accountant_approval'] == 'Approved' ? 'success' : ($no_due_request['accountant_approval'] == 'Rejected' ? 'danger' : 'warning')); ?>">
                                                <?php echo $no_due_request['accountant_approval']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>HOD Approval</th>
                                        <td>
                                            <span class="badge badge-<?php echo ($no_due_request['hod_approval'] == 'Approved' ? 'success' : ($no_due_request['hod_approval'] == 'Rejected' ? 'danger' : 'warning')); ?>">
                                                <?php echo $no_due_request['hod_approval']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Final Status</th>
                                        <td>
                                            <span class="badge badge-<?php echo ($no_due_request['final_status'] == 'Approved' ? 'success' : ($no_due_request['final_status'] == 'Rejected' ? 'danger' : 'warning')); ?>">
                                                <?php echo $no_due_request['final_status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                                
                                <?php if ($no_due_request['final_status'] == 'Approved'): ?>
                                    <div class="text-center mt-3">
                                        <a href="download_no_due.php" class="btn btn-success">Download No Due Form</a>
                                    </div>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                <div class="text-center">
                                    <p>You have not applied for No Due Certificate yet.</p>
                                    <a href="no_due.php" class="btn">Apply Now</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-tasks"></i> Pending Assignments
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($assignments_result) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Subject</th>
                                                <th>Faculty</th>
                                                <th>Due Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($assignment = mysqli_fetch_assoc($assignments_result)): ?>
                                                <tr>
                                                    <td><?php echo $assignment['title']; ?></td>
                                                    <td><?php echo $assignment['subject']; ?></td>
                                                    <td><?php echo $assignment['faculty_name']; ?></td>
                                                    <td><?php echo date('d M Y', strtotime($assignment['due_date'])); ?></td>
                                                    <td>
                                                        <a href="assignments.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn">Submit</a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No pending assignments.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i> Recent Internal Marks
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($marks_result) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Exam</th>
                                                <th>Marks</th>
                                                <th>Max Marks</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($mark = mysqli_fetch_assoc($marks_result)): ?>
                                                <tr>
                                                    <td><?php echo $mark['subject']; ?></td>
                                                    <td><?php echo $mark['exam_type']; ?></td>
                                                    <td><?php echo $mark['marks']; ?></td>
                                                    <td><?php echo $mark['max_marks']; ?></td>
                                                    <td><?php echo round(($mark['marks'] / $mark['max_marks']) * 100, 2); ?>%</td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No internal marks available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-book"></i> Library Books
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($library_result) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Book Name</th>
                                                <th>Issue Date</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                                <th>Fine</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($book = mysqli_fetch_assoc($library_result)): ?>
                                                <tr>
                                                    <td><?php echo $book['book_name']; ?></td>
                                                    <td><?php echo date('d M Y', strtotime($book['issue_date'])); ?></td>
                                                    <td><?php echo date('d M Y', strtotime($book['due_date'])); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo ($book['status'] == 'Returned' ? 'success' : ($book['status'] == 'Overdue' ? 'danger' : 'info')); ?>">
                                                            <?php echo $book['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>₹<?php echo $book['fine_amount']; ?></td>
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
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-money-bill-wave"></i> Fees Status
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($fees_result) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Fee Type</th>
                                                <th>Total Amount</th>
                                                <th>Paid</th>
                                                <th>Balance</th>
                                                <th>Due Date</th>
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
                                                        <span class="badge badge-<?php echo ($fee['status'] == 'Paid' ? 'success' : ($fee['status'] == 'Unpaid' || $fee['status'] == 'Overdue' ? 'danger' : 'warning')); ?>">
                                                            <?php echo $fee['status']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No fees records found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>