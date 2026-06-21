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

// Process form submission for updating fine
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_fine'])) {
    $record_id = mysqli_real_escape_string($conn, $_POST['record_id']);
    $fine_amount = mysqli_real_escape_string($conn, $_POST['fine_amount']);
    
    // Update fine amount
    $update_query = "UPDATE library_records SET fine_amount = $fine_amount WHERE record_id = $record_id";
    
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Fine updated successfully!";
    } else {
        $error_message = "Error updating fine: " . mysqli_error($conn);
    }
}

// Process form submission for clearing fine
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_fine'])) {
    $record_id = mysqli_real_escape_string($conn, $_POST['record_id']);
    
    // Clear fine amount
    $update_query = "UPDATE library_records SET fine_amount = 0 WHERE record_id = $record_id";
    
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Fine cleared successfully!";
    } else {
        $error_message = "Error clearing fine: " . mysqli_error($conn);
    }
}

// Get all records with fines
$query = "SELECT r.*, s.name as student_name, s.register_number, s.department, s.year_of_study 
          FROM library_records r
          JOIN students s ON r.student_id = s.student_id
          WHERE r.fine_amount > 0
          ORDER BY r.fine_amount DESC";
$fines_result = mysqli_query($conn, $query);

// Get fine statistics
$query = "SELECT 
          COUNT(record_id) as total_records,
          SUM(fine_amount) as total_fine,
          MAX(fine_amount) as max_fine,
          AVG(fine_amount) as avg_fine
          FROM library_records 
          WHERE fine_amount > 0";
$stats_result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($stats_result);

// Get student-wise fine summary
$query = "SELECT s.student_id, s.name, s.register_number, s.department, s.year_of_study, 
          COUNT(r.record_id) as fine_records, SUM(r.fine_amount) as total_fine
          FROM students s
          JOIN library_records r ON s.student_id = r.student_id
          WHERE r.fine_amount > 0
          GROUP BY s.student_id
          ORDER BY SUM(r.fine_amount) DESC";
$students_result = mysqli_query($conn, $query);

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
                <h3>Library Portal</h3>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="book_records.php"><i class="fas fa-book"></i> <span>Book Records</span></a></li>
                <li><a href="issued_books.php"><i class="fas fa-bookmark"></i> <span>Issued Books</span></a></li>
                <li><a href="return_books.php"><i class="fas fa-undo"></i> <span>Return Books</span></a></li>
                <li><a href="manage_fines.php" class="active"><i class="fas fa-money-bill-wave"></i> <span>Manage Fines</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Manage Library Fines</h1>
            
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
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-receipt fa-3x mb-3"></i>
                                        <h3><?php echo $stats['total_records'] ?: 0; ?></h3>
                                        <p>Records with Fines</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-money-bill-wave fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['total_fine'] ?: 0, 2); ?></h3>
                                        <p>Total Fine Amount</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <i class="fas fa-arrow-up fa-3x mb-3"></i>
                                        <h3>₹<?php echo number_format($stats['max_fine'] ?: 0, 2); ?></h3>
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
                        <div class="col-md-8">
                            <div class="form-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Search by student name, register number, book ID or name">
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
                                        <th>Book ID</th>
                                        <th>Book Name</th>
                                        <th>Student</th>
                                        <th>Department</th>
                                        <th>Due Date</th>
                                        <th>Return Date</th>
                                        <th>Fine Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = mysqli_fetch_assoc($fines_result)): ?>
                                        <tr data-dept="<?php echo $record['department']; ?>">
                                            <td><?php echo $record['book_id']; ?></td>
                                            <td><?php echo $record['book_name']; ?></td>
                                            <td><?php echo $record['student_name'] . ' (' . $record['register_number'] . ')'; ?></td>
                                            <td><?php echo $record['department']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($record['due_date'])); ?></td>
                                            <td><?php echo $record['return_date'] ? date('d M Y', strtotime($record['return_date'])) : 'Not returned'; ?></td>
                                            <td>₹<?php echo $record['fine_amount']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $record['status'] == 'Issued' ? 'info' : 
                                                        ($record['status'] == 'Returned' ? 'success' : 'danger'); 
                                                ?>">
                                                    <?php echo $record['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm" onclick="openUpdateForm(<?php echo $record['record_id']; ?>, <?php echo $record['fine_amount']; ?>)">Update Fine</button>
                                                <form method="post" action="" style="display:inline;">
                                                    <input type="hidden" name="record_id" value="<?php echo $record['record_id']; ?>">
                                                    <button type="submit" name="clear_fine" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to clear this fine?')">Clear Fine</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No fines found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-users"></i> Student-wise Fine Summary
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($students_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Register No</th>
                                        <th>Department</th>
                                        <th>Year</th>
                                        <th>Records with Fines</th>
                                        <th>Total Fine</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                        <tr>
                                            <td><?php echo $student['name']; ?></td>
                                            <td><?php echo $student['register_number']; ?></td>
                                            <td><?php echo $student['department']; ?></td>
                                            <td><?php echo $student['year_of_study']; ?></td>
                                            <td><?php echo $student['fine_records']; ?></td>
                                            <td>₹<?php echo $student['total_fine']; ?></td>
                                            <!-- <td>
                                                <a href="student_fines.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Details</a>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No students with fines found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Fine Update Modal -->
            <div id="update-modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeUpdateForm()">&times;</span>
                    <h2>Update Fine Amount</h2>
                    <form method="post" action="">
                        <input type="hidden" id="record_id" name="record_id">
                        
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
        
        function filterFines() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const deptFilter = document.getElementById('dept-filter').value;
            const rows = document.querySelectorAll('#fines-table tbody tr');
            
            rows.forEach(row => {
                const bookId = row.cells[0].textContent.toLowerCase();
                const bookName = row.cells[1].textContent.toLowerCase();
                const student = row.cells[2].textContent.toLowerCase();
                const dept = row.getAttribute('data-dept');
                
                // Check if row matches search and dept filter
                const matchesSearch = bookId.includes(searchValue) || 
                                    bookName.includes(searchValue) || 
                                    student.includes(searchValue);
                const matchesDept = deptFilter === '' || dept === deptFilter;
                
                row.style.display = (matchesSearch && matchesDept) ? '' : 'none';
            });
        }
        
        // Modal functionality
        function openUpdateForm(recordId, fineAmount) {
            document.getElementById("record_id").value = recordId;
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