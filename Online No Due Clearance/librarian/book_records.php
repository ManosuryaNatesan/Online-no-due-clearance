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

// Get all book records
$query = "SELECT r.*, s.name as student_name, s.register_number, s.department, s.year_of_study 
          FROM library_records r
          JOIN students s ON r.student_id = s.student_id
          ORDER BY r.issue_date DESC";
$records_result = mysqli_query($conn, $query);

// Get book statistics
$query = "SELECT 
          COUNT(*) as total_records,
          SUM(CASE WHEN status = 'Issued' THEN 1 ELSE 0 END) as issued_books,
          SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned_books,
          SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue_books,
          SUM(fine_amount) as total_fine
          FROM library_records";
$stats_result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($stats_result);

// Process form submission for adding new book record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_record'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $book_id = mysqli_real_escape_string($conn, $_POST['book_id']);
    $book_name = mysqli_real_escape_string($conn, $_POST['book_name']);
    $issue_date = mysqli_real_escape_string($conn, $_POST['issue_date']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    
    // Insert new record
    $insert_query = "INSERT INTO library_records (student_id, book_id, book_name, issue_date, due_date, status) 
                     VALUES ($student_id, '$book_id', '$book_name', '$issue_date', '$due_date', 'Issued')";
    
    if (mysqli_query($conn, $insert_query)) {
        $success_message = "Book issued successfully!";
        // Refresh records
        $records_result = mysqli_query($conn, "SELECT r.*, s.name as student_name, s.register_number, s.department, s.year_of_study 
                                              FROM library_records r
                                              JOIN students s ON r.student_id = s.student_id
                                              ORDER BY r.issue_date DESC");
        // Refresh stats
        $stats_result = mysqli_query($conn, "SELECT 
                                            COUNT(*) as total_records,
                                            SUM(CASE WHEN status = 'Issued' THEN 1 ELSE 0 END) as issued_books,
                                            SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned_books,
                                            SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue_books,
                                            SUM(fine_amount) as total_fine
                                            FROM library_records");
        $stats = mysqli_fetch_assoc($stats_result);
    } else {
        $error_message = "Error issuing book: " . mysqli_error($conn);
    }
}

// Get all students for dropdown
$query = "SELECT student_id, name, register_number, department FROM students ORDER BY name";
$students_result = mysqli_query($conn, $query);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Records - College Management System</title>
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
                <li><a href="book_records.php" class="active"><i class="fas fa-book"></i> <span>Book Records</span></a></li>
                <li><a href="issued_books.php"><i class="fas fa-bookmark"></i> <span>Issued Books</span></a></li>
                <li><a href="return_books.php"><i class="fas fa-undo"></i> <span>Return Books</span></a></li>
                <li><a href="manage_fines.php"><i class="fas fa-money-bill-wave"></i> <span>Manage Fines</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Book Records</h1>
            
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
                            <i class="fas fa-info-circle"></i> Library Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <i class="fas fa-book fa-3x mb-3"></i>
                                        <h3><?php echo $stats['total_records'] ?: 0; ?></h3>
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
            
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-plus-circle"></i> Issue New Book
                            <button class="btn btn-sm float-right" onclick="toggleIssuanceForm()">
                                <i class="fas fa-plus"></i> Issue Book
                            </button>
                        </div>
                        <div class="card-body" id="issuance-form" style="display: none;">
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
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="book_id">Book ID</label>
                                            <input type="text" id="book_id" name="book_id" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="book_name">Book Name</label>
                                    <input type="text" id="book_name" name="book_name" class="form-control" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="issue_date">Issue Date</label>
                                            <input type="date" id="issue_date" name="issue_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="due_date">Due Date</label>
                                            <input type="date" id="due_date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group mt-3">
                                    <button type="submit" name="add_record" class="btn btn-success">Issue Book</button>
                                    <button type="button" class="btn" onclick="toggleIssuanceForm()">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> Search Records
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
                                <select id="status-filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Issued">Issued</option>
                                    <option value="Returned">Returned</option>
                                    <option value="Overdue">Overdue</option>
                                </select>
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
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-list"></i> All Book Records
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($records_result) > 0): ?>
                        <div class="table-container">
                            <table class="table" id="records-table">
                                <thead>
                                    <tr>
                                        <th>Book ID</th>
                                        <th>Book Name</th>
                                        <th>Student</th>
                                        <th>Department</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Return Date</th>
                                        <th>Fine</th>
                                        <th>Status</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = mysqli_fetch_assoc($records_result)): ?>
                                        <tr data-status="<?php echo $record['status']; ?>" data-dept="<?php echo $record['department']; ?>">
                                            <td><?php echo $record['book_id']; ?></td>
                                            <td><?php echo $record['book_name']; ?></td>
                                            <td><?php echo $record['student_name'] . ' (' . $record['register_number'] . ')'; ?></td>
                                            <td><?php echo $record['department']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($record['issue_date'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($record['due_date'])); ?></td>
                                            <td><?php echo $record['return_date'] ? date('d M Y', strtotime($record['return_date'])) : '-'; ?></td>
                                            <td><?php echo $record['fine_amount'] > 0 ? '₹' . $record['fine_amount'] : '-'; ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $record['status'] == 'Issued' ? 'info' : 
                                                        ($record['status'] == 'Returned' ? 'success' : 'danger'); 
                                                ?>">
                                                    <?php echo $record['status']; ?>
                                                </span>
                                            </td>
                                            <!-- <td>
                                                <?php if ($record['status'] != 'Returned'): ?>
                                                    <a href="return_book.php?id=<?php echo $record['record_id']; ?>" class="btn btn-sm">Return</a>
                                                <?php else: ?>
                                                    <a href="view_record.php?id=<?php echo $record['record_id']; ?>" class="btn btn-sm">View</a>
                                                <?php endif; ?>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No book records found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-circle"></i> Overdue Books
                        </div>
                        <div class="card-body">
                            <p>Click the button below to see all overdue books and manage fines:</p>
                            <a href="overdue_books.php" class="btn">View Overdue Books</a>
                        </div>
                    </div>
                </div>
                
                <!-- <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-file-export"></i> Export Records
                        </div>
                        <div class="card-body">
                            <form action="export_records.php" method="post">
                                <div class="form-group">
                                    <label for="export_status">Status</label>
                                    <select id="export_status" name="export_status" class="form-control">
                                        <option value="">All Records</option>
                                        <option value="Issued">Issued</option>
                                        <option value="Returned">Returned</option>
                                        <option value="Overdue">Overdue</option>
                                    </select>
                                </div>
                                <div class="form-group mt-3">
                                    <button type="submit" name="export" class="btn">Export to CSV</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div> -->
            </div> -->
        </div>
    </div>
    
    <script>
        function toggleIssuanceForm() {
            var form = document.getElementById("issuance-form");
            if (form.style.display === "none") {
                form.style.display = "block";
            } else {
                form.style.display = "none";
            }
        }
        
        // Search and filter functionality
        document.getElementById('search-input').addEventListener('keyup', filterRecords);
        document.getElementById('status-filter').addEventListener('change', filterRecords);
        document.getElementById('dept-filter').addEventListener('change', filterRecords);
        
        function filterRecords() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;
            const deptFilter = document.getElementById('dept-filter').value;
            const rows = document.querySelectorAll('#records-table tbody tr');
            
            rows.forEach(row => {
                const bookId = row.cells[0].textContent.toLowerCase();
                const bookName = row.cells[1].textContent.toLowerCase();
                const student = row.cells[2].textContent.toLowerCase();
                const status = row.getAttribute('data-status');
                const dept = row.getAttribute('data-dept');
                
                // Check if row matches search and filters
                const matchesSearch = bookId.includes(searchValue) || 
                                    bookName.includes(searchValue) || 
                                    student.includes(searchValue);
                const matchesStatus = statusFilter === '' || status === statusFilter;
                const matchesDept = deptFilter === '' || dept === deptFilter;
                
                row.style.display = (matchesSearch && matchesStatus && matchesDept) ? '' : 'none';
            });
        }
    </script>
</body>
</html>