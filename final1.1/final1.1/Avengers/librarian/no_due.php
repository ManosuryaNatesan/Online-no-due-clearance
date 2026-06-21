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

// Get pending no due requests for approval
$query = "SELECT r.*, s.name as student_name, s.register_number, s.department, s.year_of_study
          FROM no_due_requests r
          JOIN students s ON r.student_id = s.student_id
          WHERE r.faculty_approval = 'Approved' AND r.librarian_approval = 'Pending'
          ORDER BY r.request_date ASC";
$pending_result = mysqli_query($conn, $query);

// Get all no due requests
$query = "SELECT r.*, s.name as student_name, s.register_number, s.department, s.year_of_study
          FROM no_due_requests r
          JOIN students s ON r.student_id = s.student_id
          ORDER BY r.request_date DESC";
$all_requests_result = mysqli_query($conn, $query);

// Process approval or rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve']) || isset($_POST['reject'])) {
        $request_id = mysqli_real_escape_string($conn, $_POST['request_id']);
        $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
        
        // Check if student has any active books or fines
        $check_query = "SELECT COUNT(*) as active_books, SUM(fine_amount) as total_fine
                       FROM library_records 
                       WHERE student_id = $student_id 
                       AND (status = 'Issued' OR status = 'Overdue' OR fine_amount > 0)";
        $check_result = mysqli_query($conn, $check_query);
        $check_data = mysqli_fetch_assoc($check_result);
        
        if (isset($_POST['approve'])) {
            if ($check_data['active_books'] > 0 || $check_data['total_fine'] > 0) {
                $error_message = "Cannot approve. Student has " . $check_data['active_books'] . " active books or ₹" . $check_data['total_fine'] . " in fines.";
            } else {
                $status = 'Approved';
                $success_message = "No due request has been approved successfully!";
            }
        } else {
            $status = 'Rejected';
            $success_message = "No due request has been rejected!";
        }
        
        if (!isset($error_message)) {
            $update_query = "UPDATE no_due_requests 
                           SET librarian_approval = '$status', 
                               librarian_remarks = '$remarks',
                               librarian_approval_date = NOW()
                           WHERE request_id = $request_id";
            
            if (mysqli_query($conn, $update_query)) {
                // Refresh requests
                $pending_result = mysqli_query($conn, "SELECT r.*, s.name as student_name, s.register_number, s.department, s.year_of_study
                                                     FROM no_due_requests r
                                                     JOIN students s ON r.student_id = s.student_id
                                                     WHERE r.faculty_approval = 'Approved' AND r.librarian_approval = 'Pending'
                                                     ORDER BY r.request_date ASC");
                
                $all_requests_result = mysqli_query($conn, "SELECT r.*, s.name as student_name, s.register_number, s.department, s.year_of_study
                                                          FROM no_due_requests r
                                                          JOIN students s ON r.student_id = s.student_id
                                                          ORDER BY r.request_date DESC");
            } else {
                $error_message = "Error updating request: " . mysqli_error($conn);
            }
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
                <h3>Library Portal</h3>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="book_records.php"><i class="fas fa-book"></i> <span>Book Records</span></a></li>
                <li><a href="issued_books.php"><i class="fas fa-bookmark"></i> <span>Issued Books</span></a></li>
                <li><a href="return_books.php"><i class="fas fa-undo"></i> <span>Return Books</span></a></li>
                <li><a href="manage_fines.php"><i class="fas fa-money-bill-wave"></i> <span>Manage Fines</span></a></li>
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
                    <p>As a librarian, you're responsible for approving no due requests from students who have cleared all library dues. Before approving, please ensure:</p>
                    <ul>
                        <li>All books have been returned by the student</li>
                        <li>No outstanding fines are due from the student</li>
                        <li>All library records for the student are in good standing</li>
                    </ul>
                    <p>The no due certificate will only be issued to students after approval from faculty, library, accounts, and the HOD.</p>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-clipboard-list"></i> Pending Approvals
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($pending_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Department</th>
                                        <th>Year</th>
                                        <th>Request Date</th>
                                        <th>Faculty Approval</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($request = mysqli_fetch_assoc($pending_result)): ?>
                                        <tr>
                                            <td><?php echo $request['student_name']; ?></td>
                                            <td><?php echo $request['register_number']; ?></td>
                                            <td><?php echo $request['department']; ?></td>
                                            <td><?php echo $request['year_of_study']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <?php echo $request['faculty_approval']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm" onclick="openApprovalForm(<?php echo $request['request_id']; ?>, <?php echo $request['student_id']; ?>, '<?php echo $request['student_name']; ?>')">Review</button>
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
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-history"></i> All Requests
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" id="search-input" class="form-control" placeholder="Search by student name or register number">
                        </div>
                        <div class="col-md-6">
                            <select id="status-filter" class="form-control">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                    
                    <?php if (mysqli_num_rows($all_requests_result) > 0): ?>
                        <div class="table-container">
                            <table class="table" id="requests-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Department</th>
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
                                    <?php while ($request = mysqli_fetch_assoc($all_requests_result)): ?>
                                        <tr data-status="<?php echo $request['librarian_approval']; ?>">
                                            <td><?php echo $request['student_name']; ?></td>
                                            <td><?php echo $request['register_number']; ?></td>
                                            <td><?php echo $request['department']; ?></td>
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
                        <input type="hidden" id="student_id" name="student_id">
                        
                        <div class="form-group">
                            <label>Student Name</label>
                            <input type="text" id="student_name" class="form-control" readonly>
                        </div>
                        
                        <div id="student-status" class="mt-3"></div>
                        
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
        document.getElementById('status-filter').addEventListener('change', filterRequests);
        
        function filterRequests() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;
            const rows = document.querySelectorAll('#requests-table tbody tr');
            
            rows.forEach(row => {
                const student = row.cells[0].textContent.toLowerCase();
                const regNo = row.cells[1].textContent.toLowerCase();
                const status = row.getAttribute('data-status');
                
                // Check if row matches search and status filter
                const matchesSearch = student.includes(searchValue) || regNo.includes(searchValue);
                const matchesStatus = statusFilter === '' || status === statusFilter;
                
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }
        
        // Modal functionality
        function openApprovalForm(requestId, studentId, studentName) {
            document.getElementById("request_id").value = requestId;
            document.getElementById("student_id").value = studentId;
            document.getElementById("student_name").value = studentName;
            
            // Get student library status
            fetch('get_student_library_status.php?id=' + studentId)
                .then(response => response.json())
                .then(data => {
                    let statusHTML = '<h4>Library Status</h4>';
                    
                    if (data.active_books > 0) {
                        statusHTML += '<div class="alert alert-danger">Student has ' + data.active_books + ' books that are not returned.</div>';
                    } else {
                        statusHTML += '<div class="alert alert-success">All books have been returned.</div>';
                    }
                    
                    if (data.total_fine > 0) {
                        statusHTML += '<div class="alert alert-danger">Student has ₹' + data.total_fine + ' in outstanding fines.</div>';
                    } else {
                        statusHTML += '<div class="alert alert-success">No outstanding fines.</div>';
                    }
                    
                    document.getElementById("student-status").innerHTML = statusHTML;
                });
                
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
