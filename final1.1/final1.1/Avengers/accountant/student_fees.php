<?php
session_start();
// Check if user is logged in and is an accountant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get all students with fee details
$query = "SELECT s.*, 
          SUM(f.total_amount) as total_fees,
          SUM(f.amount_paid) as paid_amount,
          SUM(f.balance) as balance_amount,
          SUM(f.fine_amount) as fine_amount,
          SUM(CASE WHEN f.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_count
          FROM students s
          LEFT JOIN fees f ON s.student_id = f.student_id
          GROUP BY s.student_id
          ORDER BY s.department, s.year_of_study, s.name";
$students_result = mysqli_query($conn, $query);

// Process form submission for updating fee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_fee'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $fee_type = mysqli_real_escape_string($conn, $_POST['fee_type']);
    $total_amount = mysqli_real_escape_string($conn, $_POST['total_amount']);
    $amount_paid = mysqli_real_escape_string($conn, $_POST['amount_paid']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    
    $balance = $total_amount - $amount_paid;
    $payment_date = $amount_paid > 0 ? date('Y-m-d') : NULL;
    
    // Determine fee status
    if ($balance <= 0) {
        $status = 'Paid';
    } elseif ($amount_paid > 0) {
        $status = 'Partially Paid';
    } else {
        $status = 'Unpaid';
    }
    
    // Check if due date is in the past and there's a balance
    if (strtotime($due_date) < strtotime(date('Y-m-d')) && $balance > 0) {
        $status = 'Overdue';
        $days_overdue = floor((strtotime(date('Y-m-d')) - strtotime($due_date)) / (60 * 60 * 24));
        $fine_amount = $days_overdue * 25; // ₹25 per day
    } else {
        $fine_amount = 0;
    }
    
    // Insert new fee record
    $insert_query = "INSERT INTO fees (student_id, fee_type, amount_paid, total_amount, balance, due_date, payment_date, fine_amount, status) 
                     VALUES ($student_id, '$fee_type', $amount_paid, $total_amount, $balance, '$due_date', " . ($payment_date ? "'$payment_date'" : "NULL") . ", $fine_amount, '$status')";
    
    if (mysqli_query($conn, $insert_query)) {
        $success_message = "Fee record added successfully!";
        // Refresh students data
        $students_result = mysqli_query($conn, "SELECT s.*, 
                                               SUM(f.total_amount) as total_fees,
                                               SUM(f.amount_paid) as paid_amount,
                                               SUM(f.balance) as balance_amount,
                                               SUM(f.fine_amount) as fine_amount,
                                               SUM(CASE WHEN f.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_count
                                               FROM students s
                                               LEFT JOIN fees f ON s.student_id = f.student_id
                                               GROUP BY s.student_id
                                               ORDER BY s.department, s.year_of_study, s.name");
    } else {
        $error_message = "Error adding fee record: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Fees - College Management System</title>
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
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="student_fees.php" class="active"><i class="fas fa-money-check-alt"></i> <span>Student Fees</span></a></li>
                <li><a href="fee_payments.php"><i class="fas fa-cash-register"></i> <span>Fee Payments</span></a></li>
                <li><a href="overdue.php"><i class="fas fa-exclamation-circle"></i> <span>Overdue Fees</span></a></li>
                <li><a href="manage_fines.php"><i class="fas fa-money-bill-wave"></i> <span>Manage Fines</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Student Fees Management</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>Record New Payment
                    <button class="btn btn-sm float-right" onclick="toggleAddFeeForm()">
                        <i class="fas fa-plus"></i> New Payment
                    </button>
                </div>
                <div class="card-body" id="add-fee-form" style="display: none;">
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_id">Student</label>
                                    <select id="student_id" name="student_id" class="form-control" required>
                                        <option value="">Select Student</option>
                                        <?php
                                        mysqli_data_seek($students_result, 0);
                                        while ($student = mysqli_fetch_assoc($students_result)) {
                                            echo "<option value='" . $student['student_id'] . "'>" . $student['name'] . " (" . $student['register_number'] . ") - " . $student['department'] . "</option>";
                                        }
                                        mysqli_data_seek($students_result, 0);
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fee_type">Fee Type</label>
                                    <select id="fee_type" name="fee_type" class="form-control" required>
                                        <option value="">Select Fee Type</option>
                                        <option value="Tuition">Tuition Fee</option>
                                        <option value="Transport">Transport Fee</option>
                                        <option value="Exam">Exam Fee</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="total_amount">Current Balance</label>
                                    <input type="number" id="total_amount" name="total_amount" class="form-control" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="amount_paid">Payment Amount</label>
                                    <input type="number" id="amount_paid" name="amount_paid" class="form-control" min="0" value="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="due_date">Payment Date</label>
                                    <input type="date" id="due_date" name="due_date" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-3">
                            <button type="submit" name="add_fee" class="btn btn-success">Record Payment</button>
                            <button type="button" class="btn" onclick="toggleAddFeeForm()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> Search Students
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Search by name or register number">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <select id="dept-filter" class="form-control">
                                    <option value="">All Departments</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Electronics">Electronics</option>
                                    <option value="Mechanical">Mechanical</option>
                                    <option value="Civil">Civil</option>
                                    <option value="Electrical">Electrical</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <select id="status-filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="due">Has Due</option>
                                    <option value="overdue">Has Overdue</option>
                                    <option value="fine">Has Fine</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-users"></i> Students Fee Status
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($students_result) > 0): ?>
                        <div class="table-container">
                            <table class="table" id="students-table">
                                <thead>
                                    <tr>
                                        <th>Register No</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Year</th>
                                        <th>Total Fees</th>
                                        <th>Paid Amount</th>
                                        <th>Balance</th>
                                        <th>Fine</th>
                                        <th>Status</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                        <tr 
                                            data-dept="<?php echo $student['department']; ?>"
                                            data-due="<?php echo ($student['balance_amount'] > 0) ? 'yes' : 'no'; ?>"
                                            data-overdue="<?php echo ($student['overdue_count'] > 0) ? 'yes' : 'no'; ?>"
                                            data-fine="<?php echo ($student['fine_amount'] > 0) ? 'yes' : 'no'; ?>"
                                        >
                                            <td><?php echo $student['register_number']; ?></td>
                                            <td><?php echo $student['name']; ?></td>
                                            <td><?php echo $student['department']; ?></td>
                                            <td><?php echo $student['year_of_study']; ?></td>
                                            <td>₹<?php echo number_format($student['total_fees'] ?: 0); ?></td>
                                            <td>₹<?php echo number_format($student['paid_amount'] ?: 0); ?></td>
                                            <td>₹<?php echo number_format($student['balance_amount'] ?: 0); ?></td>
                                            <td>
                                                <?php if ($student['fine_amount'] > 0): ?>
                                                    <span class="badge badge-warning">₹<?php echo number_format($student['fine_amount']); ?></span>
                                                <?php else: ?>
                                                    ₹0
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['overdue_count'] > 0): ?>
                                                    <span class="badge badge-danger">Overdue</span>
                                                <?php elseif ($student['balance_amount'] > 0): ?>
                                                    <span class="badge badge-warning">Has Due</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Paid</span>
                                                <?php endif; ?>
                                            </td>
                                            <!-- <td>
                                                <a href="view_student_fees.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Details</a>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No students found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-file-export"></i> Export Options
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <a href="export_all_fees.php" class="btn">Export All Fee Records</a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <a href="export_due_fees.php" class="btn">Export Due Fee Records</a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <a href="export_overdue_fees.php" class="btn">Export Overdue Fee Records</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
    
    <script>
        function toggleAddFeeForm() {
            var form = document.getElementById("add-fee-form");
            if (form.style.display === "none") {
                form.style.display = "block";
            } else {
                form.style.display = "none";
            }
        }
        
        // Search and filter functionality
        document.getElementById('search-input').addEventListener('keyup', filterStudents);
        document.getElementById('dept-filter').addEventListener('change', filterStudents);
        document.getElementById('status-filter').addEventListener('change', filterStudents);
        
        function filterStudents() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const deptFilter = document.getElementById('dept-filter').value;
            const statusFilter = document.getElementById('status-filter').value;
            const rows = document.querySelectorAll('#students-table tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const regNo = row.cells[0].textContent.toLowerCase();
                const dept = row.getAttribute('data-dept');
                const hasDue = row.getAttribute('data-due');
                const hasOverdue = row.getAttribute('data-overdue');
                const hasFine = row.getAttribute('data-fine');
                
                // Check if row matches search, department, and status filters
                const matchesSearch = name.includes(searchValue) || regNo.includes(searchValue);
                const matchesDept = deptFilter === '' || dept === deptFilter;
                
                let matchesStatus = true;
                if (statusFilter === 'due') {
                    matchesStatus = hasDue === 'yes';
                } else if (statusFilter === 'overdue') {
                    matchesStatus = hasOverdue === 'yes';
                } else if (statusFilter === 'fine') {
                    matchesStatus = hasFine === 'yes';
                }
                
                row.style.display = (matchesSearch && matchesDept && matchesStatus) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
