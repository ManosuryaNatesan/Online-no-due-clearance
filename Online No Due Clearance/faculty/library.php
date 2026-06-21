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

// Get all students in the faculty's department
$department = $faculty['department'];
$query = "SELECT s.*, COUNT(l.record_id) as books_issued, 
          SUM(CASE WHEN l.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_books,
          SUM(l.fine_amount) as total_fine
          FROM students s
          LEFT JOIN library_records l ON s.student_id = l.student_id
          WHERE s.department = '$department'
          GROUP BY s.student_id
          ORDER BY s.year_of_study, s.name";
$students_result = mysqli_query($conn, $query);

// Get department library statistics
$query = "SELECT 
          COUNT(l.record_id) as total_books,
          SUM(CASE WHEN l.status = 'Issued' THEN 1 ELSE 0 END) as issued_books,
          SUM(CASE WHEN l.status = 'Returned' THEN 1 ELSE 0 END) as returned_books,
          SUM(CASE WHEN l.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_books,
          SUM(l.fine_amount) as total_fine
          FROM library_records l
          JOIN students s ON l.student_id = s.student_id
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
    <title>Library Records - College Management System</title>
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
                <li><a href="library.php" class="active"><i class="fas fa-book"></i> <span>Library</span></a></li>
                <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> <span>Fees</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Library Records</h1>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> Department Library Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-book fa-3x mb-3"></i>
                                        <h3><?php echo $stats['total_books'] ?: 0; ?></h3>
                                        <p>Total Transactions</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-bookmark fa-3x mb-3"></i>
                                        <h3><?php echo $stats['issued_books'] ?: 0; ?></h3>
                                        <p>Currently Issued</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-undo fa-3x mb-3"></i>
                                        <h3><?php echo $stats['returned_books'] ?: 0; ?></h3>
                                        <p>Returned Books</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                        <h3><?php echo $stats['overdue_books'] ?: 0; ?></h3>
                                        <p>Overdue Books</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-money-bill-wave fa-3x mb-3"></i>
                                        <h3>₹<?php echo $stats['total_fine'] ?: 0; ?></h3>
                                        <p>Total Fine Amount</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> Search Students
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Search by name or register number">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <select id="year-filter" class="form-control">
                                    <option value="">All Years</option>
                                    <option value="1">Year 1</option>
                                    <option value="2">Year 2</option>
                                    <option value="3">Year 3</option>
                                    <option value="4">Year 4</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-list"></i> Students Library Status
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($students_result) > 0): ?>
                        <div class="table-container">
                            <table class="table" id="students-table">
                                <thead>
                                    <tr>
                                        <th>Register No</th>
                                        <th>Name</th>
                                        <th>Year</th>
                                        <th>Books Issued</th>
                                        <th>Overdue Books</th>
                                        <th>Fine Amount</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                        <tr data-year="<?php echo $student['year_of_study']; ?>">
                                            <td><?php echo $student['register_number']; ?></td>
                                            <td><?php echo $student['name']; ?></td>
                                            <td><?php echo $student['year_of_study']; ?></td>
                                            <td><?php echo $student['books_issued'] ?: 0; ?></td>
                                            <td>
                                                <?php if ($student['overdue_books'] > 0): ?>
                                                    <span class="badge badge-danger"><?php echo $student['overdue_books']; ?></span>
                                                <?php else: ?>
                                                    0
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['total_fine'] > 0): ?>
                                                    <span class="badge badge-warning">₹<?php echo $student['total_fine']; ?></span>
                                                <?php else: ?>
                                                    ₹0
                                                <?php endif; ?>
                                            </td>
                                            <!-- <td>
                                                <a href="view_library_records.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Records</a>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No students found in your department.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-exclamation-circle"></i> Overdue Library Items
                </div>
                <div class="card-body">
                    <p>Click the button below to see all overdue library items for students in your department:</p>
                    <a href="overdue_items.php" class="btn">View Overdue Items</a>
                </div>
            </div> -->
        </div>
    </div>
    
    <script>
        // Search functionality
        document.getElementById('search-input').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#students-table tbody tr');
            const yearFilter = document.getElementById('year-filter').value;
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const regNo = row.cells[0].textContent.toLowerCase();
                const year = row.getAttribute('data-year');
                
                // Check if row matches both search and year filter
                const matchesSearch = name.includes(searchValue) || regNo.includes(searchValue);
                const matchesYear = yearFilter === '' || year === yearFilter;
                
                row.style.display = (matchesSearch && matchesYear) ? '' : 'none';
            });
        });
        
        // Year filter functionality
        document.getElementById('year-filter').addEventListener('change', function() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('#students-table tbody tr');
            const yearFilter = this.value;
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const regNo = row.cells[0].textContent.toLowerCase();
                const year = row.getAttribute('data-year');
                
                // Check if row matches both search and year filter
                const matchesSearch = searchValue === '' || name.includes(searchValue) || regNo.includes(searchValue);
                const matchesYear = yearFilter === '' || year === yearFilter;
                
                row.style.display = (matchesSearch && matchesYear) ? '' : 'none';
            });
        });
    </script>
</body>
</html>