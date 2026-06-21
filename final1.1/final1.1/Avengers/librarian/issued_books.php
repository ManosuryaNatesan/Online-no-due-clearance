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

// Get issued books
$query = "SELECT r.*, s.name as student_name, s.register_number, s.department, s.year_of_study 
          FROM library_records r
          JOIN students s ON r.student_id = s.student_id
          WHERE r.status = 'Issued'
          ORDER BY r.due_date ASC";
$issued_books_result = mysqli_query($conn, $query);

// Get list of students with active books
$query = "SELECT s.student_id, s.name, s.register_number, s.department, s.year_of_study,
          COUNT(r.record_id) as book_count
          FROM students s
          JOIN library_records r ON s.student_id = r.student_id
          WHERE r.status = 'Issued'
          GROUP BY s.student_id
          ORDER BY s.department, s.year_of_study, s.name";
$students_result = mysqli_query($conn, $query);

// Get issued book statistics
$query = "SELECT COUNT(*) as total_issued,
          SUM(CASE WHEN due_date < CURRENT_DATE THEN 1 ELSE 0 END) as soon_due,
          COUNT(DISTINCT student_id) as students_count
          FROM library_records
          WHERE status = 'Issued'";
$stats_result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($stats_result);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issued Books - College Management System</title>
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
                <li><a href="issued_books.php" class="active"><i class="fas fa-bookmark"></i> <span>Issued Books</span></a></li>
                <li><a href="return_books.php"><i class="fas fa-undo"></i> <span>Return Books</span></a></li>
                <li><a href="manage_fines.php"><i class="fas fa-money-bill-wave"></i> <span>Manage Fines</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Issued Books</h1>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> Issued Books Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-bookmark fa-3x mb-3"></i>
                                        <h3><?php echo $stats['total_issued'] ?: 0; ?></h3>
                                        <p>Total Books Issued</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                        <h3><?php echo $stats['soon_due'] ?: 0; ?></h3>
                                        <p>Books Due Soon</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <h3><?php echo $stats['students_count'] ?: 0; ?></h3>
                                        <p>Students with Active Books</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> Search Issued Books
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Search by student name, register number, book ID or name">
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
                                <select id="due-filter" class="form-control">
                                    <option value="">All Books</option>
                                    <option value="due-soon">Due Soon (Within 3 Days)</option>
                                    <option value="overdue">Overdue</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-list"></i> Currently Issued Books
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($issued_books_result) > 0): ?>
                        <div class="table-container">
                            <table class="table" id="books-table">
                                <thead>
                                    <tr>
                                        <th>Book ID</th>
                                        <th>Book Name</th>
                                        <th>Student</th>
                                        <th>Department</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($book = mysqli_fetch_assoc($issued_books_result)): 
                                        $due_date = strtotime($book['due_date']);
                                        $current_date = strtotime(date('Y-m-d'));
                                        $days_remaining = round(($due_date - $current_date) / (60 * 60 * 24));
                                        
                                        $status_class = 'success';
                                        $status_text = 'On Time';
                                        
                                        if ($days_remaining < 0) {
                                            $status_class = 'danger';
                                            $status_text = 'Overdue by ' . abs($days_remaining) . ' days';
                                        } elseif ($days_remaining <= 3) {
                                            $status_class = 'warning';
                                            $status_text = 'Due in ' . $days_remaining . ' days';
                                        }
                                    ?>
                                        <tr 
                                            data-dept="<?php echo $book['department']; ?>"
                                            data-due="<?php echo ($days_remaining <= 3) ? 'due-soon' : (($days_remaining < 0) ? 'overdue' : ''); ?>"
                                        >
                                            <td><?php echo $book['book_id']; ?></td>
                                            <td><?php echo $book['book_name']; ?></td>
                                            <td><?php echo $book['student_name'] . ' (' . $book['register_number'] . ')'; ?></td>
                                            <td><?php echo $book['department']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($book['issue_date'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($book['due_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <!-- <td>
                                                <a href="return_book.php?id=<?php echo $book['record_id']; ?>" class="btn btn-sm">Return</a>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No books are currently issued.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-users"></i> Students with Issued Books
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
                                        <th>Books Issued</th>
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
                                            <td><?php echo $student['book_count']; ?></td>
                                            <!-- <td>
                                                <a href="student_books.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Books</a>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No students have books issued currently.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> Quick Links
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <a href="book_records.php" class="btn">Issue New Book</a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <a href="return_books.php" class="btn">Process Returns</a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <a href="export_issued.php" class="btn">Export Issued Books List</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
    
    <script>
        // Search and filter functionality
        document.getElementById('search-input').addEventListener('keyup', filterBooks);
        document.getElementById('dept-filter').addEventListener('change', filterBooks);
        document.getElementById('due-filter').addEventListener('change', filterBooks);
        
        function filterBooks() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const deptFilter = document.getElementById('dept-filter').value;
            const dueFilter = document.getElementById('due-filter').value;
            const rows = document.querySelectorAll('#books-table tbody tr');
            
            rows.forEach(row => {
                const bookId = row.cells[0].textContent.toLowerCase();
                const bookName = row.cells[1].textContent.toLowerCase();
                const student = row.cells[2].textContent.toLowerCase();
                const dept = row.getAttribute('data-dept');
                const dueStatus = row.getAttribute('data-due');
                
                // Check if row matches search and filters
                const matchesSearch = bookId.includes(searchValue) || 
                                    bookName.includes(searchValue) || 
                                    student.includes(searchValue);
                const matchesDept = deptFilter === '' || dept === deptFilter;
                const matchesDue = dueFilter === '' || dueStatus === dueFilter;
                
                row.style.display = (matchesSearch && matchesDept && matchesDue) ? '' : 'none';
            });
        }
    </script>
</body>
</html>