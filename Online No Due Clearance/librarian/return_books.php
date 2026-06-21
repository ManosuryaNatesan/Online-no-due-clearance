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

// Process return if book ID is provided
if (isset($_GET['id'])) {
    $record_id = $_GET['id'];
    
    // Get the book record
    $query = "SELECT * FROM library_records WHERE record_id = $record_id";
    $record_result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($record_result) > 0) {
        $record = mysqli_fetch_assoc($record_result);
        
        if ($record['status'] == 'Returned') {
            $error_message = "This book has already been returned.";
        } else {
            // Calculate fine if book is overdue
            $due_date = strtotime($record['due_date']);
            $current_date = strtotime(date('Y-m-d'));
            $days_overdue = max(0, floor(($current_date - $due_date) / (60 * 60 * 24)));
            $fine_amount = 0;
            
            if ($days_overdue > 0) {
                $fine_amount = $days_overdue * 5; // ₹5 per day
            }
            
            // Update the record as returned
            $update_query = "UPDATE library_records 
                            SET status = 'Returned', 
                                return_date = CURRENT_DATE, 
                                fine_amount = $fine_amount 
                            WHERE record_id = $record_id";
            
            if (mysqli_query($conn, $update_query)) {
                $success_message = "Book returned successfully! " . ($fine_amount > 0 ? "Fine amount: ₹$fine_amount" : "No fine.");
            } else {
                $error_message = "Error processing return: " . mysqli_error($conn);
            }
        }
    } else {
        $error_message = "Invalid book record.";
    }
}

// Get issued books
$query = "SELECT r.*, s.name as student_name, s.register_number, s.department, s.year_of_study 
          FROM library_records r
          JOIN students s ON r.student_id = s.student_id
          WHERE r.status IN ('Issued', 'Overdue')
          ORDER BY r.due_date ASC";
$issued_books_result = mysqli_query($conn, $query);

// Process form submission for returning book by ID
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_book'])) {
    $book_id = mysqli_real_escape_string($conn, $_POST['book_id']);
    
    // Find the book record
    $query = "SELECT * FROM library_records WHERE book_id = '$book_id' AND status IN ('Issued', 'Overdue')";
    $record_result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($record_result) > 0) {
        $record = mysqli_fetch_assoc($record_result);
        $record_id = $record['record_id'];
        
        // Calculate fine if book is overdue
        $due_date = strtotime($record['due_date']);
        $current_date = strtotime(date('Y-m-d'));
        $days_overdue = max(0, floor(($current_date - $due_date) / (60 * 60 * 24)));
        $fine_amount = 0;
        
        if ($days_overdue > 0) {
            $fine_amount = $days_overdue * 5; // ₹5 per day
        }
        
        // Update the record as returned
        $update_query = "UPDATE library_records 
                        SET status = 'Returned', 
                            return_date = CURRENT_DATE, 
                            fine_amount = $fine_amount 
                        WHERE record_id = $record_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success_message = "Book returned successfully! " . ($fine_amount > 0 ? "Fine amount: ₹$fine_amount" : "No fine.");
            // Refresh issued books
            $issued_books_result = mysqli_query($conn, "SELECT r.*, s.name as student_name, s.register_number, s.department, s.year_of_study 
                                                      FROM library_records r
                                                      JOIN students s ON r.student_id = s.student_id
                                                      WHERE r.status IN ('Issued', 'Overdue')
                                                      ORDER BY r.due_date ASC");
        } else {
            $error_message = "Error processing return: " . mysqli_error($conn);
        }
    } else {
        $error_message = "No issued book found with this ID.";
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Books - College Management System</title>
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
                <li><a href="return_books.php" class="active"><i class="fas fa-undo"></i> <span>Return Books</span></a></li>
                <li><a href="manage_fines.php"><i class="fas fa-money-bill-wave"></i> <span>Manage Fines</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Return Books</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-undo"></i> Process Book Return
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="book_id">Enter Book ID</label>
                                    <input type="text" id="book_id" name="book_id" class="form-control" placeholder="Enter book ID to process return" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" name="return_book" class="btn btn-success btn-block">Process Return</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> Fine is calculated at the rate of ₹5 per day after the due date.
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
                                <select id="status-filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Issued">Issued</option>
                                    <option value="Overdue">Overdue</option>
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
                                        <th>Fine (If returned today)</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($book = mysqli_fetch_assoc($issued_books_result)): 
                                        $due_date = strtotime($book['due_date']);
                                        $current_date = strtotime(date('Y-m-d'));
                                        $days_remaining = round(($due_date - $current_date) / (60 * 60 * 24));
                                        $days_overdue = max(0, -$days_remaining);
                                        $potential_fine = $days_overdue * 5;
                                        
                                        $status = 'Issued';
                                        $status_class = 'info';
                                        
                                        if ($days_remaining < 0) {
                                            $status = 'Overdue';
                                            $status_class = 'danger';
                                        }
                                    ?>
                                        <tr 
                                            data-dept="<?php echo $book['department']; ?>"
                                            data-status="<?php echo $status; ?>"
                                        >
                                            <td><?php echo $book['book_id']; ?></td>
                                            <td><?php echo $book['book_name']; ?></td>
                                            <td><?php echo $book['student_name'] . ' (' . $book['register_number'] . ')'; ?></td>
                                            <td><?php echo $book['department']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($book['issue_date'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($book['due_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $status_class; ?>">
                                                    <?php echo $status; ?>
                                                    <?php if ($days_overdue > 0): ?>
                                                        (<?php echo $days_overdue; ?> days)
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($potential_fine > 0): ?>
                                                    <span class="badge badge-warning">₹<?php echo $potential_fine; ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">No Fine</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="return_books.php?id=<?php echo $book['record_id']; ?>" class="btn btn-sm">Return</a>
                                            </td>
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
                    <i class="fas fa-info-circle"></i> Return Process Information
                </div>
                <div class="card-body">
                    <h4>Return Process Steps:</h4>
                    <ol>
                        <li>Check the physical condition of the returned book</li>
                        <li>Scan or enter the book ID in the system</li>
                        <li>Verify the details of the book and the borrower</li>
                        <li>Process the return in the system</li>
                        <li>Inform the student about any fine (if applicable)</li>
                        <li>Return the book to the library shelf</li>
                    </ol>
                    
                    <h4 class="mt-4">Fine Calculation:</h4>
                    <p>A fine of ₹5 per day is charged for books returned after the due date.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Search and filter functionality
        document.getElementById('search-input').addEventListener('keyup', filterBooks);
        document.getElementById('dept-filter').addEventListener('change', filterBooks);
        document.getElementById('status-filter').addEventListener('change', filterBooks);
        
        function filterBooks() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const deptFilter = document.getElementById('dept-filter').value;
            const statusFilter = document.getElementById('status-filter').value;
            const rows = document.querySelectorAll('#books-table tbody tr');
            
            rows.forEach(row => {
                const bookId = row.cells[0].textContent.toLowerCase();
                const bookName = row.cells[1].textContent.toLowerCase();
                const student = row.cells[2].textContent.toLowerCase();
                const dept = row.getAttribute('data-dept');
                const status = row.getAttribute('data-status');
                
                // Check if row matches search and filters
                const matchesSearch = bookId.includes(searchValue) || 
                                    bookName.includes(searchValue) || 
                                    student.includes(searchValue);
                const matchesDept = deptFilter === '' || dept === deptFilter;
                const matchesStatus = statusFilter === '' || status === statusFilter;
                
                row.style.display = (matchesSearch && matchesDept && matchesStatus) ? '' : 'none';
            });
        }
    </script>
</body>
</html>