<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

$student_id = $_SESSION['user_id'];

// Check if student already has a pending no due request
$query = "SELECT * FROM no_due_requests WHERE student_id = $student_id ORDER BY request_date DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$existing_request = mysqli_fetch_assoc($result);

// Process new application form
$success_message = $error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply'])) {
    // Check if there's an existing request that's still pending
    if ($existing_request && $existing_request['final_status'] == 'Pending') {
        $error_message = "You already have a pending No Due request.";
    } else {
        // Check if student has any overdue library books or unpaid fees
        $check_library = "SELECT COUNT(*) as overdue_books FROM library_records 
                          WHERE student_id = $student_id AND status = 'Overdue'";
        $library_result = mysqli_query($conn, $check_library);
        $library_row = mysqli_fetch_assoc($library_result);
        
        $check_fees = "SELECT COUNT(*) as unpaid_fees FROM fees 
                       WHERE student_id = $student_id AND status IN ('Unpaid', 'Overdue')";
        $fees_result = mysqli_query($conn, $check_fees);
        $fees_row = mysqli_fetch_assoc($fees_result);
        
        // Allow the request but show a warning
        $has_warnings = false;
        if ($library_row['overdue_books'] > 0 || $fees_row['unpaid_fees'] > 0) {
            $has_warnings = true;
        }
        
        // Insert new request
        $query = "INSERT INTO no_due_requests (student_id) VALUES ($student_id)";
        if (mysqli_query($conn, $query)) {
            if ($has_warnings) {
                $success_message = "No Due request submitted successfully, but there are pending issues that may delay approval.";
            } else {
                $success_message = "No Due request submitted successfully!";
            }
            // Refresh the existing request data
            $query = "SELECT * FROM no_due_requests WHERE student_id = $student_id ORDER BY request_date DESC LIMIT 1";
            $result = mysqli_query($conn, $query);
            $existing_request = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}

// Get faculty associated with this student's department
$query = "SELECT f.faculty_id, f.name FROM faculty f 
          JOIN students s ON f.department = s.department 
          WHERE s.student_id = $student_id";
$faculty_result = mysqli_query($conn, $query);

// Get library status
$query = "SELECT COUNT(*) as total_books, 
          SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue_books,
          SUM(fine_amount) as total_fine
          FROM library_records
          WHERE student_id = $student_id";
$library_status = mysqli_query($conn, $query);
$library_info = mysqli_fetch_assoc($library_status);

// Get fees status
$query = "SELECT 
          SUM(CASE WHEN status IN ('Unpaid', 'Overdue') THEN balance ELSE 0 END) as total_dues,
          SUM(fine_amount) as total_fine
          FROM fees
          WHERE student_id = $student_id";
$fees_status = mysqli_query($conn, $query);
$fees_info = mysqli_fetch_assoc($fees_status);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Due Application - College Management System</title>
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
                <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> <span>Fees Due</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php" class="active"><i class="fas fa-clipboard-check"></i> <span>No Due Application</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">No Due Application</h1>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-circle"></i> Pre-Application Status Check
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table class="table">
                                    <tr>
                                        <th>Library Status</th>
                                        <td>
                                            <?php if ($library_info['overdue_books'] > 0): ?>
                                                <span class="badge badge-danger">
                                                    <?php echo $library_info['overdue_books']; ?> overdue books
                                                </span>
                                                <?php if ($library_info['total_fine'] > 0): ?>
                                                    <p>Fine amount: ₹<?php echo $library_info['total_fine']; ?></p>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge badge-success">All clear</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Fees Status</th>
                                        <td>
                                            <?php if ($fees_info['total_dues'] > 0): ?>
                                                <span class="badge badge-danger">
                                                    Pending dues: ₹<?php echo $fees_info['total_dues']; ?>
                                                </span>
                                                <?php if ($fees_info['total_fine'] > 0): ?>
                                                    <p>Fine amount: ₹<?php echo $fees_info['total_fine']; ?></p>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge badge-success">All clear</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <?php if ($library_info['overdue_books'] > 0 || $fees_info['total_dues'] > 0): ?>
                                <div class="alert alert-warning">
                                    <p><strong>Warning:</strong> You have pending issues that may affect your No Due approval. It's recommended to resolve these before applying.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clipboard-check"></i> No Due Application
                        </div>
                        <div class="card-body">
                            <?php if ($existing_request): ?>
                                <h3>Your Request Status</h3>
                                <div class="table-container">
                                    <table class="table">
                                        <tr>
                                            <th>Request Date</th>
                                            <td><?php echo date('d M Y', strtotime($existing_request['request_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Faculty Approval</th>
                                            <td>
                                                <span class="badge badge-<?php echo ($existing_request['faculty_approval'] == 'Approved' ? 'success' : ($existing_request['faculty_approval'] == 'Rejected' ? 'danger' : 'warning')); ?>">
                                                    <?php echo $existing_request['faculty_approval']; ?>
                                                </span>
                                                <?php if ($existing_request['faculty_remarks']): ?>
                                                    <p>Remarks: <?php echo $existing_request['faculty_remarks']; ?></p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Library Approval</th>
                                            <td>
                                                <span class="badge badge-<?php echo ($existing_request['librarian_approval'] == 'Approved' ? 'success' : ($existing_request['librarian_approval'] == 'Rejected' ? 'danger' : 'warning')); ?>">
                                                    <?php echo $existing_request['librarian_approval']; ?>
                                                </span>
                                                <?php if ($existing_request['librarian_remarks']): ?>
                                                    <p>Remarks: <?php echo $existing_request['librarian_remarks']; ?></p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Accounts Approval</th>
                                            <td>
                                                <span class="badge badge-<?php echo ($existing_request['accountant_approval'] == 'Approved' ? 'success' : ($existing_request['accountant_approval'] == 'Rejected' ? 'danger' : 'warning')); ?>">
                                                    <?php echo $existing_request['accountant_approval']; ?>
                                                </span>
                                                <?php if ($existing_request['accountant_remarks']): ?>
                                                    <p>Remarks: <?php echo $existing_request['accountant_remarks']; ?></p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>HOD Approval</th>
                                            <td>
                                                <span class="badge badge-<?php echo ($existing_request['hod_approval'] == 'Approved' ? 'success' : ($existing_request['hod_approval'] == 'Rejected' ? 'danger' : 'warning')); ?>">
                                                    <?php echo $existing_request['hod_approval']; ?>
                                                </span>
                                                <?php if ($existing_request['hod_remarks']): ?>
                                                    <p>Remarks: <?php echo $existing_request['hod_remarks']; ?></p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Final Status</th>
                                            <td>
                                                <span class="badge badge-<?php echo ($existing_request['final_status'] == 'Approved' ? 'success' : ($existing_request['final_status'] == 'Rejected' ? 'danger' : 'warning')); ?>">
                                                    <?php echo $existing_request['final_status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <?php if ($existing_request['final_status'] == 'Approved'): ?>
                                    <div class="text-center mt-3">
                                        <a href="download_no_due.php" class="btn btn-success">Download No Due Form</a>
                                    </div>
                                <?php elseif ($existing_request['final_status'] == 'Rejected'): ?>
                                    <div class="text-center mt-3">
                                        <form action="no_due.php" method="post">
                                            <button type="submit" name="apply" class="btn">Apply Again</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                <div class="text-center">
                                    <p>You haven't applied for No Due Certificate yet.</p>
                                    <form action="no_due.php" method="post">
                                        <button type="submit" name="apply" class="btn">Apply Now</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> No Due Process Information
                </div>
                <div class="card-body">
                    <ol>
                        <li>Submit your No Due application through this portal.</li>
                        <li>The application will be routed to your faculty advisor for initial approval.</li>
                        <li>Once approved by faculty, the library department will verify your book return status.</li>
                        <li>Next, the accounts department will verify your fee payment status.</li>
                        <li>Finally, your department HOD will review and provide the final approval.</li>
                        <li>Once all approvals are received, you can download your No Due Certificate from this portal.</li>
                    </ol>
                    <div class="alert alert-info">
                        <p><strong>Note:</strong> Please ensure all library books are returned and fee payments are completed to avoid delays in the approval process.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>