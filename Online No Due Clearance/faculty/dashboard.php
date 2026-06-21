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

// Get pending assignments
$query = "SELECT a.assignment_id, a.title, a.subject, a.due_date, a.department, a.year_of_study,
          COUNT(s.submission_id) as submission_count
          FROM assignments a
          LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id
          WHERE a.faculty_id = $faculty_id
          GROUP BY a.assignment_id
          ORDER BY a.due_date DESC";
$assignments_result = mysqli_query($conn, $query);

// Get pending no due requests for approval
$query = "SELECT r.request_id, s.name as student_name, s.register_number, r.request_date, r.faculty_approval
          FROM no_due_requests r
          JOIN students s ON r.student_id = s.student_id
          WHERE s.department = '{$faculty['department']}'
          AND r.faculty_approval = 'Pending'
          ORDER BY r.request_date ASC";
$no_due_result = mysqli_query($conn, $query);

// Get students who need internal marks update
$query = "SELECT s.student_id, s.name, s.register_number
          FROM students s
          WHERE s.department = '{$faculty['department']}'
          ORDER BY s.year_of_study, s.name";
$students_result = mysqli_query($conn, $query);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - College Management System</title>
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
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
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
            <h1 class="page-title">Faculty Dashboard</h1>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-user"></i> Faculty Information
                        </div>
                        <div class="card-body">
                            <div class="profile-card">
                                <img src="../images/<?php echo $faculty['profile_image']; ?>" alt="Profile Image" class="profile-image">
                                <h3><?php echo $faculty['name']; ?></h3>
                                <p>Department: <?php echo $faculty['department']; ?></p>
                                <p>Designation: <?php echo $faculty['designation']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clipboard-check"></i> No Due Requests
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($no_due_result) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Student Name</th>
                                                <th>Register No</th>
                                                <th>Request Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($request = mysqli_fetch_assoc($no_due_result)): ?>
                                                <tr>
                                                    <td><?php echo $request['student_name']; ?></td>
                                                    <td><?php echo $request['register_number']; ?></td>
                                                    <td><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                                                    <td>
                                                        <a href="no_due.php?id=<?php echo $request['request_id']; ?>" class="btn btn-success">Review</a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No pending requests for approval.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-tasks"></i> Assignments
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($assignments_result) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Subject</th>
                                                <th>Class</th>
                                                <th>Submissions</th>
                                                <th>Due Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($assignment = mysqli_fetch_assoc($assignments_result)): ?>
                                                <tr>
                                                    <td><?php echo $assignment['title']; ?></td>
                                                    <td><?php echo $assignment['subject']; ?></td>
                                                    <td><?php echo $assignment['department'] . ' Year ' . $assignment['year_of_study']; ?></td>
                                                    <td><?php echo $assignment['submission_count']; ?></td>
                                                    <td><?php echo date('d M Y', strtotime($assignment['due_date'])); ?></td>
                                                    <td>
                                                        <a href="assignments.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn">View</a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No assignments created yet.</p>
                            <?php endif; ?>
                            <div class="text-center mt-3">
                                <a href="assignments.php" class="btn">Create New Assignment</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i> Internal Marks Update
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($students_result) > 0): ?>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Student Name</th>
                                                <th>Register No</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                                <tr>
                                                    <td><?php echo $student['name']; ?></td>
                                                    <td><?php echo $student['register_number']; ?></td>
                                                    <td>
                                                        <a href="internal_marks.php?id=<?php echo $student['student_id']; ?>" class="btn">Update Marks</a>
                                                    </td>
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
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-bullhorn"></i> Quick Links
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-tasks fa-3x mb-3"></i>
                                        <h4>Manage Assignments</h4>
                                        <p>Create, view, and evaluate student assignments</p>
                                        <a href="assignments.php" class="btn">Go to Assignments</a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                        <h4>Internal Marks</h4>
                                        <p>Update student marks for internal exams</p>
                                        <a href="internal_marks.php" class="btn">Manage Marks</a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-clipboard-check fa-3x mb-3"></i>
                                        <h4>No Due Approvals</h4>
                                        <p>Review and approve student no due requests</p>
                                        <a href="no_due.php" class="btn">View Requests</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
