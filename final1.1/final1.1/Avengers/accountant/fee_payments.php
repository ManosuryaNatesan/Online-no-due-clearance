<?php
session_start();
// Check if user is logged in and is an accountant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get recent fee transactions
$query = "SELECT f.*, s.name as student_name, s.register_number, s.department
          FROM fees f
          JOIN students s ON f.student_id = s.student_id
          ORDER BY f.updated_at DESC
          LIMIT 20";
$recent_payments = mysqli_query($conn, $query);

// Process form submission for recording a payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_payment'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $fee_id = mysqli_real_escape_string($conn, $_POST['fee_id']);
    $payment_amount = mysqli_real_escape_string($conn, $_POST['payment_amount']);
    
    // Get current fee details
    $fee_query = "SELECT * FROM fees WHERE fee_id = $fee_id";
    $fee_result = mysqli_query($conn, $fee_query);
    
    if (mysqli_num_rows($fee_result) > 0) {
        $fee = mysqli_fetch_assoc($fee_result);
        $current_amount = $fee['amount'];
        $current_balance = $fee['balance'];
        $total_amount = $fee['total_amount'];
        
        // Calculate new values
        $new_amount = $current_amount + $payment_amount;
        $new_balance = $total_amount - $new_amount;
        
        // Determine fee status
        if ($new_balance <= 0) {
            $status = 'Paid';
        } else {
            $status = 'Partially Paid';
            
            // Check if due date is in the past and there's a balance
            if (strtotime($fee['due_date']) < strtotime(date('Y-m-d'))) {
                $status = 'Overdue';
                $days_overdue = floor((strtotime(date('Y-m-d')) - strtotime($fee['due_date'])) / (60 * 60 * 24));
                $fine_amount = $days_overdue * 25; // ₹25 per day
            }
        }
        
        // Update fee record
        $update_query = "UPDATE fees 
                         SET amount = $new_amount, 
                             balance = $new_balance, 
                             status = '$status', 
                             payment_date = CURRENT_DATE, 
                             updated_at = NOW()";
        
        // Add fine_amount to the update if it's overdue
        if (isset($fine_amount)) {
            $update_query .= ", fine_amount = $fine_amount";
        }
        
        $update_query .= " WHERE fee_id = $fee_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success_message = "Payment recorded successfully!";
            // Refresh recent payments
            $recent_payments = mysqli_query($conn, "SELECT f.*, s.name as student_name, s.register_number, s.department
                                                  FROM fees f
                                                  JOIN students s ON f.student_id = s.student_id
                                                  ORDER BY f.updated_at DESC
                                                  LIMIT 20");
        } else {
            $error_message = "Error recording payment: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Invalid fee record.";
    }
}

// Get students for dropdown
$students_query = "SELECT * FROM students ORDER BY department, name";
$students_result = mysqli_query($conn, $students_query);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Payments - College Management System</title>
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
                <li><a href="student_fees.php"><i class="fas fa-money-check-alt"></i> <span>Student Fees</span></a></li>
                <li><a href="fee_payments.php" class="active"><i class="fas fa-cash-register"></i> <span>Fee Payments</span></a></li>
                <li><a href="overdue.php"><i class="fas fa-exclamation-circle"></i> <span>Overdue Fees</span></a></li>
                <li><a href="manage_fines.php"><i class="fas fa-money-bill-wave"></i> <span>Manage Fines</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Fee Payments</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
<!--             
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> Record New Payment
                    <button class="btn btn-sm float-right" onclick="togglePaymentForm()">
                        <i class="fas fa-plus"></i> New Payment
                    </button>
                </div>
                <div class="card-body" id="payment-form" style="display: none;">
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_id">Student</label>
                                    <select id="student_id" name="student_id" class="form-control" required onchange="loadStudentFees()">
                                        <option value="">Select Student</option>
                                        <?php
                                        while ($student = mysqli_fetch_assoc($students_result)) {
                                            echo "<option value='" . $student['student_id'] . "'>" . $student['name'] . " (" . $student['register_number'] . ") - " . $student['department'] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fee_id">Fee Type</label>
                                    <select id="fee_id" name="fee_id" class="form-control">
                                        <option value="">Select Student First</option>
                                        <option value="Tuition">Tuition</option>
                                        <option value="Transport">Transport</option>
                                        <option value="Exam">Exam</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="current_balance">Current Balance</label>
                                    <input type="text" id="current_balance" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="payment_amount">Payment Amount</label>
                                    <input type="text" id="payment_amount" name="payment_amount" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="payment_date">Payment Date</label>
                                    <input type="date" id="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-3">
                            <button type="submit" name="record_payment" class="btn btn-success">Record Payment</button>
                            <button type="button" class="btn" onclick="togglePaymentForm()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div> -->
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> Search Payments
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
                                <select id="fee-type-filter" class="form-control">
                                    <option value="">All Fee Types</option>
                                    <option value="Tuition">Tuition</option>
                                    <option value="Transport">Transport</option>
                                    <option value="Exam">Exam</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <select id="status-filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Partially Paid">Partially Paid</option>
                                    <option value="Unpaid">Unpaid</option>
                                    <option value="Overdue">Overdue</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-history"></i> Recent Transactions
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($recent_payments) > 0): ?>
                        <div class="table-container">
                            <table class="table" id="transactions-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Fee Type</th>
                                        <th>Total Amount</th>
                                        <th>Paid Amount</th>
                                        <th>Balance</th>
                                        <th>Payment Date</th>
                                        <th>Status</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = mysqli_fetch_assoc($recent_payments)): ?>
                                        <tr data-fee-type="<?php echo $payment['fee_type']; ?>" data-status="<?php echo $payment['status']; ?>">
                                            <td><?php echo $payment['student_name']; ?></td>
                                            <td><?php echo $payment['register_number']; ?></td>
                                            <td><?php echo $payment['fee_type']; ?></td>
                                            <td>₹<?php echo number_format($payment['total_amount']); ?></td>
                                            <td>₹<?php echo number_format($payment['amount_paid']); ?></td>
                                            <td>₹<?php echo number_format($payment['balance']); ?></td>
                                            <td><?php echo $payment['payment_date'] ? date('d M Y', strtotime($payment['payment_date'])) : '-'; ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $payment['status'] == 'Paid' ? 'success' : 
                                                        ($payment['status'] == 'Partially Paid' ? 'warning' : 
                                                            ($payment['status'] == 'Overdue' ? 'danger' : 'info')); 
                                                ?>">
                                                    <?php echo $payment['status']; ?>
                                                </span>
                                            </td>
                                            <!-- <td>
                                                <a href="payment_receipt.php?id=<?php echo $payment['fee_id']; ?>" class="btn btn-sm">Receipt</a>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No recent transactions found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-circle"></i> Overdue Fees
                        </div>
                        <div class="card-body">
                            <p>Click the button below to view all overdue fees:</p>
                            <a href="overdue.php" class="btn">View Overdue Fees</a>
                        </div>
                    </div>
                </div>
                
                <!-- <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-file-export"></i> Generate Reports
                        </div>
                        <div class="card-body">
                            <p>Click the button below to generate payment reports:</p>
                            <a href="payment_reports.php" class="btn">Generate Reports</a>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </div>
    
    <script>
        function togglePaymentForm() {
            var form = document.getElementById("payment-form");
            if (form.style.display === "none") {
                form.style.display = "block";
            } else {
                form.style.display = "none";
            }
        }
        
        // Load student fees when a student is selected
        function loadStudentFees() {
            const studentId = document.getElementById("student_id").value;
            const feeSelect = document.getElementById("fee_id");
            
            if (!studentId) {
                feeSelect.innerHTML = '<option value="">Select Student First</option>';
                feeSelect.disabled = true;
                document.getElementById("current_balance").value = '';
                return;
            }
            
            // Fetch student's fees via AJAX
            fetch('get_student_fees.php?id=' + studentId)
                .then(response => response.json())
                .then(data => {
                    feeSelect.disabled = false;
                    feeSelect.innerHTML = '';
                    
                    if (data.length === 0) {
                        feeSelect.innerHTML = '<option value="">No fees found for this student</option>';
                    } else {
                        feeSelect.innerHTML = '<option value="">Select Fee</option>';
                        data.forEach(fee => {
                            // Only show fees that have a balance
                            if (fee.balance > 0) {
                                feeSelect.innerHTML += `<option value="${fee.fee_id}" data-balance="${fee.balance}">${fee.fee_type} - Balance: ₹${fee.balance} (Due: ${fee.due_date})</option>`;
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching student fees:', error);
                });
        }
        
        // Update current balance when fee is selected
        document.getElementById('fee_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const balance = selectedOption.dataset.balance || '';
            document.getElementById('current_balance').value = balance ? `₹${balance}` : '';
            document.getElementById('payment_amount').max = balance || 0;
        });
        
        // Search and filter functionality
        document.getElementById('search-input').addEventListener('keyup', filterTransactions);
        document.getElementById('fee-type-filter').addEventListener('change', filterTransactions);
        document.getElementById('status-filter').addEventListener('change', filterTransactions);
        
        function filterTransactions() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const feeTypeFilter = document.getElementById('fee-type-filter').value;
            const statusFilter = document.getElementById('status-filter').value;
            const rows = document.querySelectorAll('#transactions-table tbody tr');
            
            rows.forEach(row => {
                const student = row.cells[0].textContent.toLowerCase();
                const regNo = row.cells[1].textContent.toLowerCase();
                const feeType = row.getAttribute('data-fee-type');
                const status = row.getAttribute('data-status');
                
                // Check if row matches search and filters
                const matchesSearch = student.includes(searchValue) || regNo.includes(searchValue);
                const matchesFeeType = feeTypeFilter === '' || feeType === feeTypeFilter;
                const matchesStatus = statusFilter === '' || status === statusFilter;
                
                row.style.display = (matchesSearch && matchesFeeType && matchesStatus) ? '' : 'none';
            });
        }
    </script>
</body>
</html>