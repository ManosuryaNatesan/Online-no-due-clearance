<?php
session_start();
// Check if user is logged in and is an accountant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get all fee records with fines
$query = "SELECT f.*, s.name as student_name, s.register_number, s.department, s.year_of_study
          FROM fees f
          JOIN students s ON f.student_id = s.student_id
          WHERE f.fine_amount > 0
          ORDER BY f.fine_amount DESC, f.due_date ASC";
$fines_result = mysqli_query($conn, $query);

// Process form submission for updating fine
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_fine'])) {
    $fee_id = mysqli_real_escape_string($conn, $_POST['fee_id']);
    $fine_amount = mysqli_real_escape_string($conn, $_POST['fine_amount']);
    
    // Update fine amount
    $update_query = "UPDATE fees SET fine_amount = $fine_amount WHERE fee_id = $fee_id";
    
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Fine updated successfully!";
        // Refresh fines data
        $fines_result = mysqli_query($conn, "SELECT f.*, s.name as student_name, s.register_number, s.department, s.year_of_study
                                            FROM fees f
                                            JOIN students s ON f.student_id = s.student_id
                                            WHERE f.fine_amount > 0
                                            ORDER BY f.fine_amount DESC, f.due_date ASC");
    } else {
        $error_message = "Error updating fine: " . mysqli_error($conn);
    }
}

// Process form submission for clearing fine
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_fine'])) {
    $fee_id = mysqli_real_escape_string($conn, $_POST['fee_id']);
    
    // Clear fine amount
    $update_query = "UPDATE fees SET fine_amount = 0 WHERE fee_id = $fee_id";
    
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Fine cleared successfully!";
        // Refresh fines data
        $fines_result = mysqli_query($conn, "SELECT f.*, s.name as student_name, s.register_number, s.department, s.year_of_study
                                            FROM fees f
                                            JOIN students s ON f.student_id = s.student_id
                                            WHERE f.fine_amount > 0
                                            ORDER BY f.fine_amount DESC, f.due_date ASC");
    } else {
        $error_message = "Error clearing fine: " . mysqli_error($conn);
    }
}

// Get fine statistics
$query = "SELECT 
          COUNT(fee_id) as total_records,
          SUM(fine_amount) as total_fine,
          MAX(fine_amount) as max_fine,
          AVG(fine_amount) as avg_fine,
          COUNT(DISTINCT student_id) as students_count
          FROM fees 
          WHERE fine_amount > 0";
$stats_result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($stats_result);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fines - College Management System</title>
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
                <li><a href="overdue.php"><i class="fas fa-exclamation-circle"></i> <span>Overdue Fees</span></a></li>
                <li><a href="manage_fines.php" class="active"><i class="fas fa-money-bill-wave"></i> <span>Manage Fines</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Manage Fines</h1>
            
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
                            <i class="fas fa-info-circle"></i> Fine Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-receipt fa-3x mb-3"></i>
                                        <h3><?php echo $stats['total_records'] ?: 0; ?></h3>
                                        <p>Records with Fines</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <h3><?php echo $stats['students_count'] ?: 0; ?></h3>
                                        <p>Students with Fines</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-money-bill-wave fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['total_fine'] ?: 0); ?></h3>
                                        <p>Total Fine Amount</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-arrow-up fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['max_fine'] ?: 0); ?></h3>
                                        <p>Highest Fine</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-calculator fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['avg_fine'] ?: 0, 2); ?></h3>
                                        <p>Average Fine</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> Search Fines
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
                    <i class="fas fa-list"></i> All Fines
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($fines_result) > 0): ?>
                        <div class="table-container">
                            <table class="table" id="fines-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Register No</th>
                                        <th>Department</th>
                                        <th>Year</th>
                                        <th>Fee Type</th>
                                        <th>Due Date</th>
                                        <th>Balance</th>
                                        <th>Fine Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($fine = mysqli_fetch_assoc($fines_result)): ?>
                                        <tr 
                                            data-dept="<?php echo $fine['department']; ?>"
                                            data-fee-type="<?php echo $fine['fee_type']; ?>"
                                        >
                                            <td><?php echo $fine['student_name']; ?></td>
                                            <td><?php echo $fine['register_number']; ?></td>
                                            <td><?php echo $fine['department']; ?></td>
                                            <td><?php echo $fine['year_of_study']; ?></td>
                                            <td><?php echo $fine['fee_type']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($fine['due_date'])); ?></td>
                                            <td>₹<?php echo number_format($fine['balance']); ?></td>
                                            <td>₹<?php echo number_format($fine['fine_amount']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $fine['status'] == 'Paid' ? 'success' : 
                                                        ($fine['status'] == 'Partially Paid' ? 'warning' : 
                                                            ($fine['status'] == 'Overdue' ? 'danger' : 'info')); 
                                                ?>">
                                                    <?php echo $fine['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm" onclick="openUpdateForm(<?php echo $fine['fee_id']; ?>, <?php echo $fine['fine_amount']; ?>)">Update</button>
                                                <form method="post" action="" style="display:inline;">
                                                    <input type="hidden" name="fee_id" value="<?php echo $fine['fee_id']; ?>">
                                                    <button type="submit" name="clear_fine" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to clear this fine?')">Clear</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No fees with fines found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Fine Policy
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4>Fine Calculation Policy</h4>
                            <ul>
                                <li>A fine of ₹25 is charged per day for late fee payments.</li>
                                <li>Fine is calculated from the day after the due date until the date of payment.</li>
                                <li>Fine can be manually adjusted or waived at the discretion of the accounts department.</li>
                                <li>Students must pay both the original fee amount and any applicable fines to receive a no due certificate.</li>
                            </ul>
                            
                            <h4 class="mt-4">Fine Adjustment Guidelines</h4>
                            <ul>
                                <li>Fines may be reduced for students with financial difficulties (with proper documentation).</li>
                                <li>Fines may be waived in cases of medical emergencies or other valid reasons.</li>
                                <li>All fine adjustments must be approved by the Accounts Officer.</li>
                            </ul>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-hand-holding-usd fa-5x mb-3 text-primary"></i>
                            <h4>Need help with fines?</h4>
                            <p>Contact the Accounts Office for assistance with fine calculation, policy questions, or special cases.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Fine Update Modal -->
            <div id="update-modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeUpdateForm()">&times;</span>
                    <h2>Update Fine Amount</h2>
                    <form method="post" action="">
                        <input type="hidden" id="fee_id" name="fee_id">
                        
                        <div class="form-group">
                            <label for="fine_amount">Fine Amount (₹)</label>
                            <input type="number" id="fine_amount" name="fine_amount" class="form-control" min="0" step="1" required>
                        </div>
                        
                        <div class="form-group mt-3">
                            <button type="submit" name="update_fine" class="btn btn-success">Update Fine</button>
                            <button type="button" class="btn" onclick="closeUpdateForm()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Search and filter functionality
        document.getElementById('search-input').addEventListener('keyup', filterFines);
        document.getElementById('dept-filter').addEventListener('change', filterFines);
        document.getElementById('fee-type-filter').addEventListener('change', filterFines);
        
        function filterFines() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const deptFilter = document.getElementById('dept-filter').value;
            const feeTypeFilter = document.getElementById('fee-type-filter').value;
            const rows = document.querySelectorAll('#fines-table tbody tr');
            
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
        
        // Modal functionality
        function openUpdateForm(feeId, fineAmount) {
            document.getElementById("fee_id").value = feeId;
            document.getElementById("fine_amount").value = fineAmount;
            document.getElementById("update-modal").style.display = "block";
        }
        
        function closeUpdateForm() {
            document.getElementById("update-modal").style.display = "none";
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById("update-modal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>