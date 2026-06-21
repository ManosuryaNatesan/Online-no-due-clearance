<?php
session_start();
// Check if user is logged in and is an accountant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get all overdue fees
$query = "SELECT f.*, s.name as student_name, s.register_number, s.department, s.year_of_study
          FROM fees f
          JOIN students s ON f.student_id = s.student_id
          WHERE f.status = 'Overdue'
          ORDER BY f.due_date ASC";
$overdue_result = mysqli_query($conn, $query);

// Get overdue statistics
$query = "SELECT 
          COUNT(*) as total_overdue,
          SUM(balance) as total_balance,
          SUM(fine_amount) as total_fine,
          COUNT(DISTINCT student_id) as students_count
          FROM fees
          WHERE status = 'Overdue'";
$stats_result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($stats_result);

// Process form submission for updating fine
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_fine'])) {
    $fee_id = mysqli_real_escape_string($conn, $_POST['fee_id']);
    $fine_amount = mysqli_real_escape_string($conn, $_POST['fine_amount']);
    
    // Update fine amount
    $update_query = "UPDATE fees SET fine_amount = $fine_amount WHERE fee_id = $fee_id";
    
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Fine updated successfully!";
        // Refresh overdue fees
        $overdue_result = mysqli_query($conn, "SELECT f.*, s.name as student_name, s.register_number, s.department, s.year_of_study
                                              FROM fees f
                                              JOIN students s ON f.student_id = s.student_id
                                              WHERE f.status = 'Overdue'
                                              ORDER BY f.due_date ASC");
        
        // Refresh stats
        $stats_result = mysqli_query($conn, "SELECT 
                                           COUNT(*) as total_overdue,
                                           SUM(balance) as total_balance,
                                           SUM(fine_amount) as total_fine,
                                           COUNT(DISTINCT student_id) as students_count
                                           FROM fees
                                           WHERE status = 'Overdue'");
        $stats = mysqli_fetch_assoc($stats_result);
    } else {
        $error_message = "Error updating fine: " . mysqli_error($conn);
    }
}

// Process form submission for automatic fine calculation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['calculate_all_fines'])) {
    // Update all overdue fees with automatic fine calculation
    $update_query = "UPDATE fees 
                    SET fine_amount = GREATEST(0, DATEDIFF(CURRENT_DATE, due_date) * 25)
                    WHERE status = 'Overdue'";
    
    if (mysqli_query($conn, $update_query)) {
        $success_message = "All fines have been automatically calculated!";
        // Refresh overdue fees
        $overdue_result = mysqli_query($conn, "SELECT f.*, s.name as student_name, s.register_number, s.department, s.year_of_study
                                              FROM fees f
                                              JOIN students s ON f.student_id = s.student_id
                                              WHERE f.status = 'Overdue'
                                              ORDER BY f.due_date ASC");
        
        // Refresh stats
        $stats_result = mysqli_query($conn, "SELECT 
                                           COUNT(*) as total_overdue,
                                           SUM(balance) as total_balance,
                                           SUM(fine_amount) as total_fine,
                                           COUNT(DISTINCT student_id) as students_count
                                           FROM fees
                                           WHERE status = 'Overdue'");
        $stats = mysqli_fetch_assoc($stats_result);
    } else {
        $error_message = "Error calculating fines: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Fees - College Management System</title>
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
                <li><a href="fee_payments.php"><i class="fas fa-cash-register"></i> <span>Fee Payments</span></a></li>
                <li><a href="overdue.php" class="active"><i class="fas fa-exclamation-circle"></i> <span>Overdue Fees</span></a></li>
                <li><a href="manage_fines.php"><i class="fas fa-money-bill-wave"></i> <span>Manage Fines</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Overdue Fees</h1>
            
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
                            <i class="fas fa-info-circle"></i> Overdue Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                        <h3><?php echo $stats['total_overdue'] ?: 0; ?></h3>
                                        <p>Total Overdue Records</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <h3><?php echo $stats['students_count'] ?: 0; ?></h3>
                                        <p>Students with Overdue</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-money-check-alt fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['total_balance'] ?: 0); ?></h3>
                                        <p>Total Overdue Amount</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-money-bill-wave fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['total_fine'] ?: 0); ?></h3>
                                        <p>Total Fine Amount</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <form method="post" action="">
                                    <button type="submit" name="calculate_all_fines" class="btn btn-warning" onclick="return confirm('Are you sure you want to calculate fines for all overdue fees?')">
                                        <i class="fas fa-calculator"></i> Auto Calculate All Fines
                                    </button>
                                </form>
                                <p class="text-muted mt-2">Fine is calculated at the rate of ₹25 per day after the due date.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> Search Overdue Fees
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Search by student name or register number">
                            </div>
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <select id="fee-type-filter" class="form-control">
                                    <option value="">All Fee Types</option>
                                    <option value="Tuition">Tuition</option>
                                    <option value="Transport">Transport</option>
                                    <option value="Exam">Exam</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-list"></i> Overdue Fees
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($overdue_result) > 0): ?>
                        <div class="table-container">
                            <table class="table" id="overdue-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Department</th>
                                        <th>Year</th>
                                        <th>Fee Type</th>
                                        <th>Balance</th>
                                        <th>Due Date</th>
                                        <th>Days Overdue</th>
                                        <th>Fine</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($overdue = mysqli_fetch_assoc($overdue_result)): 
                                        $days_overdue = floor((strtotime(date('Y-m-d')) - strtotime($overdue['due_date'])) / (60 * 60 * 24));
                                    ?>
                                        <tr 
                                            data-dept="<?php echo $overdue['department']; ?>"
                                            data-fee-type="<?php echo $overdue['fee_type']; ?>"
                                        >
                                            <td><?php echo $overdue['student_name']; ?></td>
                                            <td><?php echo $overdue['register_number']; ?></td>
                                            <td><?php echo $overdue['department']; ?></td>
                                            <td><?php echo $overdue['year_of_study']; ?></td>
                                            <td><?php echo $overdue['fee_type']; ?></td>
                                            <td>₹<?php echo number_format($overdue['balance']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($overdue['due_date'])); ?></td>
                                            <td><?php echo $days_overdue; ?> days</td>
                                            <td>₹<?php echo number_format($overdue['fine_amount']); ?></td>
                                            <td>
                                                <button class="btn btn-sm" onclick="openFineForm(<?php echo $overdue['fee_id']; ?>, <?php echo $overdue['fine_amount']; ?>, <?php echo $days_overdue * 25; ?>)">Update Fine</button>
                                                <!-- <a href="record_payment.php?id=<?php echo $overdue['fee_id']; ?>" class="btn btn-sm btn-success">Record Payment</a> -->
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No overdue fees found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-file-export"></i> Export Overdue
                        </div>
                        <div class="card-body">
                            <p>Export the list of overdue fees for record-keeping or further analysis:</p>
                            <a href="export_overdue_fees.php" class="btn">Export to CSV</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-envelope"></i> Send Reminders
                        </div>
                        <div class="card-body">
                            <p>Send automatic reminders to students with overdue fees:</p>
                            <a href="send_fee_reminders.php" class="btn">Send Reminders</a>
                        </div>
                    </div>
                </div>
            </div>
             -->
            <!-- Fine Update Modal -->
            <div id="fine-modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeFineForm()">&times;</span>
                    <h2>Update Fine Amount</h2>
                    <form method="post" action="">
                        <input type="hidden" id="fee_id" name="fee_id">
                        
                        <div class="form-group">
                            <label for="suggested_fine">Suggested Fine (₹25/day)</label>
                            <input type="text" id="suggested_fine" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="fine_amount">Fine Amount (₹)</label>
                            <input type="number" id="fine_amount" name="fine_amount" class="form-control" min="0" step="1" required>
                        </div>
                        
                        <div class="form-group mt-3">
                            <button type="submit" name="update_fine" class="btn btn-success">Update Fine</button>
                            <button type="button" class="btn" onclick="closeFineForm()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Search and filter functionality
        document.getElementById('search-input').addEventListener('keyup', filterOverdue);
        document.getElementById('dept-filter').addEventListener('change', filterOverdue);
        document.getElementById('fee-type-filter').addEventListener('change', filterOverdue);
        
        function filterOverdue() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const deptFilter = document.getElementById('dept-filter').value;
            const feeTypeFilter = document.getElementById('fee-type-filter').value;
            const rows = document.querySelectorAll('#overdue-table tbody tr');
            
            rows.forEach(row => {
                const student = row.cells[0].textContent.toLowerCase();
                const regNo = row.cells[1].textContent.toLowerCase();
                const dept = row.getAttribute('data-dept');
                const feeType = row.getAttribute('data-fee-type');
                
                // Check if row matches search and filters
                const matchesSearch = student.includes(searchValue) || regNo.includes(searchValue);
                const matchesDept = deptFilter === '' || dept === deptFilter;
                const matchesFeeType = feeTypeFilter === '' || feeType === feeTypeFilter;
                
                row.style.display = (matchesSearch && matchesDept && matchesFeeType) ? '' : 'none';
            });
        }
        
        // Fine modal functionality
        function openFineForm(feeId, currentFine, suggestedFine) {
            document.getElementById("fee_id").value = feeId;
            document.getElementById("fine_amount").value = currentFine;
            document.getElementById("suggested_fine").value = '₹' + suggestedFine;
            document.getElementById("fine-modal").style.display = "block";
        }
        
        function closeFineForm() {
            document.getElementById("fine-modal").style.display = "none";
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById("fine-modal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
