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

// Get pending no due requests for final approval
$query = "SELECT r.*, s.name as student_name, s.register_number, s.year_of_study
          FROM no_due_requests r
          JOIN students s ON r.student_id = s.student_id
          WHERE s.department = '$department'
          AND r.faculty_approval = 'Approved'
          AND r.librarian_approval = 'Approved'
          AND r.accountant_approval = 'Approved'
          AND r.hod_approval = 'Pending'
          ORDER BY r.request_date ASC";
$pending_result = mysqli_query($conn, $query);

// Get all no due requests for the department
$query = "SELECT r.*, s.name as student_name, s.register_number, s.year_of_study
          FROM no_due_requests r
          JOIN students s ON r.student_id = s.student_id
          WHERE s.department = '$department'
          ORDER BY r.request_date DESC";
$all_requests_result = mysqli_query($conn, $query);

// Process approval or rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve']) || isset($_POST['reject'])) {
        $request_id = mysqli_real_escape_string($conn, $_POST['request_id']);
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
        
        if (isset($_POST['approve'])) {
            $status = 'Approved';
            $final_status = 'Approved'; // Since HOD is the final approver
            $success_message = "No due request has been approved successfully!";
        } else {
            $status = 'Rejected';
            $final_status = 'Rejected';
            $success_message = "No due request has been rejected!";
        }
        
        $update_query = "UPDATE no_due_requests 
                       SET hod_approval = '$status', 
                           hod_remarks = '$remarks',
                           hod_approval_date = NOW(),
                           final_status = '$final_status'
                       WHERE request_id = $request_id";
        
        if (mysqli_query($conn, $update_query)) {
            // Refresh requests
            $pending_result = mysqli_query($conn, "SELECT r.*, s.name as student_name, s.register_number, s.year_of_study
                                                  FROM no_due_requests r
                                                  JOIN students s ON r.student_id = s.student_id
                                                  WHERE s.department = '$department'
                                                  AND r.faculty_approval = 'Approved'
                                                  AND r.librarian_approval = 'Approved'
                                                  AND r.accountant_approval = 'Approved'
                                                  AND r.hod_approval = 'Pending'
                                                  ORDER BY r.request_date ASC");
            
            $all_requests_result = mysqli_query($conn, "SELECT r.*, s.name as student_name, s.register_number, s.year_of_study
                                                       FROM no_due_requests r
                                                       JOIN students s ON r.student_id = s.student_id
                                                       WHERE s.department = '$department'
                                                       ORDER BY r.request_date DESC");
        } else {
            $error_message = "Error updating request: " . mysqli_error($conn);
        }
    }
}

// Get no due statistics
$query = "SELECT 
          COUNT(*) as total_requests,
          SUM(CASE WHEN final_status = 'Approved' THEN 1 ELSE 0 END) as approved,
          SUM(CASE WHEN final_status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
          SUM(CASE WHEN final_status = 'Pending' THEN 1 ELSE 0 END) as pending
          FROM no_due_requests r
          JOIN students s ON r.student_id = s.student_id
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
    <title>No Due Approvals - College Management System</title>
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
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> No Due Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                        <h3><?php echo $stats['total_requests']; ?></h3>
                                        <p>Total Requests</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                        <h3><?php echo $stats['approved']; ?></h3>
                                        <p>Approved</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-times-circle fa-3x mb-3 text-danger"></i>
                                        <h3><?php echo $stats['rejected']; ?></h3>
                                        <p>Rejected</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-hourglass-half fa-3x mb-3 text-warning"></i>
                                        <h3><?php echo $stats['pending']; ?></h3>
                                        <p>Pending</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Approval Information
                </div>
                <div class="card-body">
                    <p>As the Head of Department, you're responsible for providing the final approval for no due certificates. Before approving:</p>
                    <ul>
                        <li>Verify that all previous approvals (Faculty, Library, Accounts) have been obtained</li>
                        <li>Ensure the student has met all departmental obligations</li>
                        <li>Check for any pending academic requirements</li>
                    </ul>
                    <p>Your approval is the final step in issuing the No Due Certificate, which is required for students to receive their certificates and transcripts.</p>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-clipboard-list"></i> Pending Final Approvals
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($pending_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Year</th>
                                        <th>Request Date</th>
                                        <th>Faculty</th>
                                        <th>Library</th>
                                        <th>Accounts</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($request = mysqli_fetch_assoc($pending_result)): ?>
                                        <tr>
                                            <td><?php echo $request['student_name']; ?></td>
                                            <td><?php echo $request['register_number']; ?></td>
                                            <td><?php echo $request['year_of_study']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <?php echo $request['faculty_approval']; ?>
                                                </span>
                                                <small><?php echo date('d M', strtotime($request['faculty_approval_date'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <?php echo $request['librarian_approval']; ?>
                                                </span>
                                                <small><?php echo date('d M', strtotime($request['librarian_approval_date'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <?php echo $request['accountant_approval']; ?>
                                                </span>
                                                <small><?php echo date('d M', strtotime($request['accountant_approval_date'])); ?></small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm" onclick="openApprovalForm(<?php echo $request['request_id']; ?>, '<?php echo $request['student_name']; ?>')">Review</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No pending requests for final approval.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> Search Requests
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Search by student name or register number">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <select id="year-filter" class="form-control">
                                    <option value="">All Years</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <select id="status-filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-history"></i> All No Due Requests
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($all_requests_result) > 0): ?>
                        <div class="table-container">
                            <table class="table" id="requests-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Year</th>
                                        <th>Request Date</th>
                                        <th>Faculty</th>
                                        <th>Library</th>
                                        <th>Accounts</th>
                                        <th>HOD</th>
                                        <th>Final Status</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($request = mysqli_fetch_assoc($all_requests_result)): ?>
                                        <tr 
                                            data-year="<?php echo $request['year_of_study']; ?>"
                                            data-status="<?php echo $request['hod_approval']; ?>"
                                        >
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
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $request['final_status'] == 'Approved' ? 'success' : 
                                                        ($request['final_status'] == 'Rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo $request['final_status']; ?>
                                                </span>
                                            </td>
                                            <!-- <td>
                                                <a href="view_request.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm">View Details</a>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No no-due requests found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Approval Modal -->
            <div id="approval-modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeApprovalForm()">&times;</span>
                    <h2>Provide Final Approval</h2>
                    <form method="post" action="">
                        <input type="hidden" id="request_id" name="request_id">
                        
                        <div class="form-group">
                            <label>Student Name</label>
                            <input type="text" id="student_name" class="form-control" readonly>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i> This request has been approved by Faculty, Library and Accounts. Your approval will finalize the No Due Certificate.
                        </div>
                        
                        <div class="form-group mt-3">
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
        // Search and filter functionality
        document.getElementById('search-input').addEventListener('keyup', filterRequests);
        document.getElementById('year-filter').addEventListener('change', filterRequests);
        document.getElementById('status-filter').addEventListener('change', filterRequests);
        
        function filterRequests() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const yearFilter = document.getElementById('year-filter').value;
            const statusFilter = document.getElementById('status-filter').value;
            const rows = document.querySelectorAll('#requests-table tbody tr');
            
            rows.forEach(row => {
                const student = row.cells[0].textContent.toLowerCase();
                const regNo = row.cells[1].textContent.toLowerCase();
                const year = row.getAttribute('data-year');
                const status = row.getAttribute('data-status');
                
                // Check if row matches search, year and status filters
                const matchesSearch = student.includes(searchValue) || regNo.includes(searchValue);
                const matchesYear = yearFilter === '' || year === yearFilter;
                const matchesStatus = statusFilter === '' || status === statusFilter;
                
                row.style.display = (matchesSearch && matchesYear && matchesStatus) ? '' : 'none';
            });
        }
        
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