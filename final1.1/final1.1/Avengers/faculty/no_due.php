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

// Get pending no due requests for approval
$department = $faculty['department'];
$query = "SELECT r.*, s.name as student_name, s.register_number, s.year_of_study, s.department
          FROM no_due_requests r
          JOIN students s ON r.student_id = s.student_id
          WHERE s.department = '$department'
          ORDER BY r.request_date DESC";
$requests_result = mysqli_query($conn, $query);

// Process approval or rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve']) || isset($_POST['reject'])) {
        $request_id = mysqli_real_escape_string($conn, $_POST['request_id']);
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
        
        if (isset($_POST['approve'])) {
            $status = 'Approved';
        } else {
            $status = 'Rejected';
        }
        
        $update_query = "UPDATE no_due_requests 
                         SET faculty_approval = '$status', 
                             faculty_id = $faculty_id, 
                             faculty_remarks = '$remarks',
                             faculty_approval_date = NOW()
                         WHERE request_id = $request_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success_message = "No due request has been " . strtolower($status) . " successfully!";
            // Refresh requests
            $requests_result = mysqli_query($conn, "SELECT r.*, s.name as student_name, s.register_number, s.year_of_study, s.department
                                                   FROM no_due_requests r
                                                   JOIN students s ON r.student_id = s.student_id
                                                   WHERE s.department = '$department'
                                                   ORDER BY r.request_date DESC");
        } else {
            $error_message = "Error updating request: " . mysqli_error($conn);
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Due Approvals - College Management System</title>
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
                <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> <span>Fees</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php" class="active"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">No Due Certificate Approvals</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Approval Information
                </div>
                <div class="card-body">
                    <p>As a faculty member, you're responsible for approving no due requests from students in your department. Before approving, please ensure:</p>
                    <ul>
                        <li>All assignments have been submitted by the student</li>
                        <li>All necessary projects and lab work have been completed</li>
                        <li>Any department-specific requirements have been met</li>
                    </ul>
                    <p>The no due certificate will only be issued to students after approval from faculty, library, accounts, and the HOD.</p>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-clipboard-list"></i> Pending Approvals
                </div>
                <div class="card-body">
                    <?php 
                    $pending_count = 0;
                    mysqli_data_seek($requests_result, 0);
                    while ($request = mysqli_fetch_assoc($requests_result)) {
                        if ($request['faculty_approval'] == 'Pending') {
                            $pending_count++;
                        }
                    }
                    mysqli_data_seek($requests_result, 0);
                    
                    if ($pending_count > 0):
                    ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Year</th>
                                        <th>Request Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($requests_result, 0);
                                    while ($request = mysqli_fetch_assoc($requests_result)):
                                        if ($request['faculty_approval'] == 'Pending'):
                                    ?>
                                        <tr>
                                            <td><?php echo $request['student_name']; ?></td>
                                            <td><?php echo $request['register_number']; ?></td>
                                            <td><?php echo $request['year_of_study']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm" onclick="openApprovalForm(<?php echo $request['request_id']; ?>, '<?php echo $request['student_name']; ?>')">Review</button>
                                            </td>
                                        </tr>
                                    <?php 
                                        endif;
                                    endwhile; 
                                    mysqli_data_seek($requests_result, 0);
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No pending requests for approval.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-history"></i> All Requests
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($requests_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Year</th>
                                        <th>Request Date</th>
                                        <th>Faculty Status</th>
                                        <th>Library Status</th>
                                        <th>Accounts Status</th>
                                        <th>HOD Status</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($request = mysqli_fetch_assoc($requests_result)): ?>
                                        <tr>
                                            <td><?php echo $request['student_name']; ?></td>
                                            <td><?php echo $request['register_number']; ?></td>
                                            <td><?php echo $request['year_of_study']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $request['faculty_approval'] == 'Approved' ? 'success' : 
                                                        ($request['faculty_approval'] == 'Rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo $request['faculty_approval']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $request['librarian_approval'] == 'Approved' ? 'success' : 
                                                        ($request['librarian_approval'] == 'Rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo $request['librarian_approval']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $request['accountant_approval'] == 'Approved' ? 'success' : 
                                                        ($request['accountant_approval'] == 'Rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo $request['accountant_approval']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $request['hod_approval'] == 'Approved' ? 'success' : 
                                                        ($request['hod_approval'] == 'Rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo $request['hod_approval']; ?>
                                                </span>
                                            </td>
                                            <!-- <td>
                                                <a href="view_request.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm">View</a>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No requests found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Approval Modal -->
            <div id="approval-modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeApprovalForm()">&times;</span>
                    <h2>Review No Due Request</h2>
                    <form method="post" action="">
                        <input type="hidden" id="request_id" name="request_id">
                        
                        <div class="form-group">
                            <label>Student Name</label>
                            <input type="text" id="student_name" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="remarks">Remarks (Optional)</label>
                            <textarea id="remarks" name="remarks" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group mt-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" name="approve" class="btn btn-success btn-block">Approve</button>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" name="reject" class="btn btn-danger btn-block">Reject</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functionality
        function openApprovalForm(requestId, studentName) {
            document.getElementById("request_id").value = requestId;
            document.getElementById("student_name").value = studentName;
            document.getElementById("approval-modal").style.display = "block";
        }
        
        function closeApprovalForm() {
            document.getElementById("approval-modal").style.display = "none";
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById("approval-modal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>