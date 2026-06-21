<?php
session_start();
// Check if user is logged in and is a HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get HOD details
$hod_id = $_SESSION['user_id'];
$query = "SELECT * FROM hod WHERE hod_id = $hod_id";
$result = mysqli_query($conn, $query);
$hod = mysqli_fetch_assoc($result);

// Get the department that this HOD oversees
$department = $hod['department'];

// Get assignments for the department
$query = "SELECT a.*, f.name as faculty_name, COUNT(s.submission_id) as submission_count 
          FROM assignments a 
          LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id
          JOIN faculty f ON a.faculty_id = f.faculty_id
          WHERE a.department = '$department'
          GROUP BY a.assignment_id
          ORDER BY a.due_date DESC";
$assignments_result = mysqli_query($conn, $query);

// Get faculty members in the department
$query = "SELECT faculty_id, name, designation FROM faculty WHERE department = '$department' ORDER BY name";
$faculty_result = mysqli_query($conn, $query);

// Process form submission for new assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_assignment'])) {
    $faculty_id = mysqli_real_escape_string($conn, $_POST['faculty_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $year_of_study = mysqli_real_escape_string($conn, $_POST['year_of_study']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    
    $insert_query = "INSERT INTO assignments (title, description, subject, faculty_id, department, year_of_study, due_date) 
                     VALUES ('$title', '$description', '$subject', $faculty_id, '$department', $year_of_study, '$due_date')";
    
    if (mysqli_query($conn, $insert_query)) {
        $success_message = "Assignment created successfully!";
        // Refresh assignments
        $assignments_result = mysqli_query($conn, "SELECT a.*, f.name as faculty_name, COUNT(s.submission_id) as submission_count 
                                                   FROM assignments a 
                                                   LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id
                                                   JOIN faculty f ON a.faculty_id = f.faculty_id
                                                   WHERE a.department = '$department'
                                                   GROUP BY a.assignment_id
                                                   ORDER BY a.due_date DESC");
    } else {
        $error_message = "Error creating assignment: " . mysqli_error($conn);
    }
}

// Get assignment statistics
$query = "SELECT 
          COUNT(*) as total_assignments,
          SUM(CASE WHEN CURRENT_DATE > due_date THEN 1 ELSE 0 END) as past_due,
          COUNT(DISTINCT faculty_id) as faculty_count
          FROM assignments
          WHERE department = '$department'";
$stats_result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($stats_result);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Assignments - College Management System</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/college_logo.jpeg" alt="College Logo">
                <h3>HOD Portal</h3>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="assignments.php" class="active"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
                <li><a href="internal_marks.php"><i class="fas fa-chart-bar"></i> <span>Internal Marks</span></a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
                <li><a href="library.php"><i class="fas fa-book"></i> <span>Library</span></a></li>
                <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> <span>Fees</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Approvals</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Department Assignments</h1>
            
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
                            <i class="fas fa-info-circle"></i> Assignment Statistics
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-tasks fa-3x mb-3"></i>
                                        <h3><?php echo $stats['total_assignments'] ?: 0; ?></h3>
                                        <p>Total Assignments</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                        <h3><?php echo $stats['past_due'] ?: 0; ?></h3>
                                        <p>Past Due Date</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-chalkboard-teacher fa-3x mb-3"></i>
                                        <h3><?php echo $stats['faculty_count'] ?: 0; ?></h3>
                                        <p>Faculty Involved</p>
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
                            <i class="fas fa-plus-circle"></i> Create New Assignment
                            <button class="btn btn-sm float-right" onclick="toggleCreateForm()">
                                <i class="fas fa-plus"></i> Create
                            </button>
                        </div>
                        <div class="card-body" id="create-form" style="display: none;">
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="title">Assignment Title</label>
                                            <input type="text" id="title" name="title" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="subject">Subject</label>
                                            <input type="text" id="subject" name="subject" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="faculty_id">Assign To Faculty</label>
                                            <select id="faculty_id" name="faculty_id" class="form-control" required>
                                                <option value="">Select Faculty</option>
                                                <?php
                                                mysqli_data_seek($faculty_result, 0);
                                                while ($faculty = mysqli_fetch_assoc($faculty_result)) {
                                                    echo "<option value='" . $faculty['faculty_id'] . "'>" . $faculty['name'] . " (" . $faculty['designation'] . ")</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="year_of_study">Year of Study</label>
                                            <select id="year_of_study" name="year_of_study" class="form-control" required>
                                                <option value="">Select Year</option>
                                                <option value="1">1st Year</option>
                                                <option value="2">2nd Year</option>
                                                <option value="3">3rd Year</option>
                                                <option value="4">4th Year</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="due_date">Due Date</label>
                                            <input type="date" id="due_date" name="due_date" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group mt-3">
                                    <button type="submit" name="create_assignment" class="btn btn-success">Create Assignment</button>
                                    <button type="button" class="btn" onclick="toggleCreateForm()">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-search"></i> Search Assignments
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Search by title, subject or faculty">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <select id="year-filter" class="form-control">
                                    <option value="">All Years</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <select id="status-filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="due">Past Due</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-list"></i> Department Assignments
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($assignments_result) > 0): ?>
                        <div class="table-container">
                            <table class="table" id="assignments-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Subject</th>
                                        <th>Faculty</th>
                                        <th>Year of Study</th>
                                        <th>Due Date</th>
                                        <th>Submissions</th>
                                        <th>Status</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($assignment = mysqli_fetch_assoc($assignments_result)): 
                                        $due_date = strtotime($assignment['due_date']);
                                        $current_date = strtotime(date('Y-m-d'));
                                        $status = ($current_date > $due_date) ? 'due' : 'active';
                                    ?>
                                        <tr data-year="<?php echo $assignment['year_of_study']; ?>" data-status="<?php echo $status; ?>">
                                            <td><?php echo $assignment['title']; ?></td>
                                            <td><?php echo $assignment['subject']; ?></td>
                                            <td><?php echo $assignment['faculty_name']; ?></td>
                                            <td><?php echo $assignment['year_of_study']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($assignment['due_date'])); ?></td>
                                            <td><?php echo $assignment['submission_count']; ?></td>
                                            <td>
                                                <?php if ($status == 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Past Due</span>
                                                <?php endif; ?>
                                            </td>
                                            <!-- <td>
                                                <a href="view_assignment.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn btn-sm">View Details</a>
                                            </td> -->
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No assignments found for your department.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleCreateForm() {
            var form = document.getElementById("create-form");
            if (form.style.display === "none") {
                form.style.display = "block";
            } else {
                form.style.display = "none";
            }
        }
        
        // Search and filter functionality
        document.getElementById('search-input').addEventListener('keyup', filterAssignments);
        document.getElementById('year-filter').addEventListener('change', filterAssignments);
        document.getElementById('status-filter').addEventListener('change', filterAssignments);
        
        function filterAssignments() {
            const searchValue = document.getElementById('search-input').value.toLowerCase();
            const yearFilter = document.getElementById('year-filter').value;
            const statusFilter = document.getElementById('status-filter').value;
            const rows = document.querySelectorAll('#assignments-table tbody tr');
            
            rows.forEach(row => {
                const title = row.cells[0].textContent.toLowerCase();
                const subject = row.cells[1].textContent.toLowerCase();
                const faculty = row.cells[2].textContent.toLowerCase();
                const year = row.getAttribute('data-year');
                const status = row.getAttribute('data-status');
                
                // Check if row matches search and filters
                const matchesSearch = title.includes(searchValue) || 
                                    subject.includes(searchValue) || 
                                    faculty.includes(searchValue);
                const matchesYear = yearFilter === '' || year === yearFilter;
                const matchesStatus = statusFilter === '' || status === statusFilter;
                
                row.style.display = (matchesSearch && matchesYear && matchesStatus) ? '' : 'none';
            });
        }
    </script>
</body>
</html>