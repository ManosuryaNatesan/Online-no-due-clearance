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
$query = "SELECT * FROM students WHERE department = '$department' ORDER BY year_of_study, name";
$students_result = mysqli_query($conn, $query);

// Process form submission for adding/updating marks
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_marks'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $exam_type = mysqli_real_escape_string($conn, $_POST['exam_type']);
    $marks = mysqli_real_escape_string($conn, $_POST['marks']);
    $max_marks = mysqli_real_escape_string($conn, $_POST['max_marks']);
    
    // Check if marks already exist for this student, subject and exam type
    $check_query = "SELECT * FROM internal_marks 
                   WHERE student_id = $student_id 
                   AND subject = '$subject' 
                   AND exam_type = '$exam_type'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing marks
        $update_query = "UPDATE internal_marks 
                        SET marks = $marks, max_marks = $max_marks, faculty_id = $faculty_id 
                        WHERE student_id = $student_id 
                        AND subject = '$subject' 
                        AND exam_type = '$exam_type'";
        
        if (mysqli_query($conn, $update_query)) {
            $success_message = "Marks updated successfully!";
        } else {
            $error_message = "Error updating marks: " . mysqli_error($conn);
        }
    } else {
        // Insert new marks
        $insert_query = "INSERT INTO internal_marks (student_id, faculty_id, subject, exam_type, marks, max_marks) 
                        VALUES ($student_id, $faculty_id, '$subject', '$exam_type', $marks, $max_marks)";
        
        if (mysqli_query($conn, $insert_query)) {
            $success_message = "Marks added successfully!";
        } else {
            $error_message = "Error adding marks: " . mysqli_error($conn);
        }
    }
}
// Get student details
$student_id = $_SESSION['user_id'];
$query = "SELECT * FROM students WHERE student_id = $student_id";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);

// Get internal marks grouped by subject
$query = "SELECT im.*, f.name as faculty_name 
          FROM internal_marks im 
          JOIN faculty f ON im.faculty_id = f.faculty_id 
          WHERE im.student_id = $student_id 
          ORDER BY im.subject, im.exam_type";
$marks_result = mysqli_query($conn, $query);

// Organize marks by subject and exam type
$marks_by_subject = [];
while ($mark = mysqli_fetch_assoc($marks_result)) {
    $subject = $mark['subject'];
    if (!isset($marks_by_subject[$subject])) {
        $marks_by_subject[$subject] = [
            'faculty_name' => $mark['faculty_name'],
            'exams' => []
        ];
    }
    $marks_by_subject[$subject]['exams'][$mark['exam_type']] = [
        'marks' => $mark['marks'],
        'max_marks' => $mark['max_marks'],
        'date' => $mark['created_at']
    ];
}
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $student_id_del = intval($_GET['student_id']);
    $subject_del = mysqli_real_escape_string($conn, $_GET['subject']);
    $exam_type_del = mysqli_real_escape_string($conn, $_GET['exam_type']);

    $delete_query = "DELETE FROM internal_marks WHERE student_id = $student_id_del AND subject = '$subject_del' AND exam_type = '$exam_type_del'";
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['success_message'] = "Marks record deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Error deleting marks: " . mysqli_error($conn);
    }
    header("Location: internal_marks.php");
    exit();
}
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $student_id_edit = intval($_GET['student_id']);
    $subject_edit = mysqli_real_escape_string($conn, $_GET['subject']);
    $exam_type_edit = mysqli_real_escape_string($conn, $_GET['exam_type']);

    $edit_query = "SELECT * FROM internal_marks WHERE student_id = $student_id_edit AND subject = '$subject_edit' AND exam_type = '$exam_type_edit'";
    $edit_result = mysqli_query($conn, $edit_query);
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_data = mysqli_fetch_assoc($edit_result);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_marks'])) {
    $edit_mode = isset($_POST['edit_mode']) && $_POST['edit_mode'] === '1';
    $student_id_post = intval($_POST['student_id']);
    $subject_post = mysqli_real_escape_string($conn, $_POST['subject']);
    $exam_type_post = mysqli_real_escape_string($conn, $_POST['exam_type']);
    $marks_post = intval($_POST['marks']);
    $max_marks_post = intval($_POST['max_marks']);

    if ($edit_mode) {
        // UPDATE
        $update_query = "UPDATE internal_marks 
                        SET marks = $marks_post, max_marks = $max_marks_post, faculty_id = $faculty_id 
                        WHERE student_id = $student_id_post 
                        AND subject = '$subject_post' 
                        AND exam_type = '$exam_type_post'";

        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success_message'] = "Marks updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating marks: " . mysqli_error($conn);
        }
    } else {
        // INSERT NEW MARK
        $insert_query = "INSERT INTO internal_marks (student_id, subject, exam_type, marks, max_marks, faculty_id)
                         VALUES ($student_id_post, '$subject_post', '$exam_type_post', $marks_post, $max_marks_post, $faculty_id)";
        if (mysqli_query($conn, $insert_query)) {
            $_SESSION['success_message'] = "Marks added successfully.";
        } else {
            $_SESSION['error_message'] = "Error adding marks: " . mysqli_error($conn);
        }
    }

    // Redirect
    header("Location: internal_marks.php");
    exit();
}
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Marks - College Management System</title>
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
                <li><a href="internal_marks.php" class="active"><i class="fas fa-chart-bar"></i> <span>Internal Marks</span></a></li>
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
            <h1 class="page-title">Manage Internal Marks</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> Update Internal Marks
                    <button class="btn btn-sm float-right" onclick="toggleMarksForm()">
                        <i class="fas fa-plus"></i> Add/Update Marks
                    </button>
                </div>
                <div class="card-body" id="marks-form" style="display: none;">
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
                                            echo "<option value='" . $student['student_id'] . "'>" . $student['name'] . " (" . $student['register_number'] . ") - Year " . $student['year_of_study'] . "</option>";
                                        }
                                        mysqli_data_seek($students_result, 0);
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="subject">Subject</label>
                                    <input type="text" id="subject" name="subject" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="exam_type">Exam Type</label>
                                    <select id="exam_type" name="exam_type" class="form-control" required>
                                        <option value="">Select Exam</option>
                                        <option value="Internal 1">Internal 1</option>
                                        <option value="Internal 2">Internal 2</option>
                                        <option value="Internal 3">Internal 3</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="marks">Marks Obtained</label>
                                    <input type="number" id="marks" name="marks" class="form-control" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_marks">Maximum Marks</label>
                                    <input type="number" id="max_marks" name="max_marks" class="form-control" min="1" value="100" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-3">
                            <button type="submit" name="update_marks" class="btn btn-success">Update Marks</button>
                            <button type="button" class="btn" onclick="toggleMarksForm()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-users"></i> Students
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($students_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Register No</th>
                                        <th>Year</th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                        <tr>
                                            <td><?php echo $student['name']; ?></td>
                                            <td><?php echo $student['register_number']; ?></td>
                                            <td><?php echo $student['year_of_study']; ?></td>
                                            <!-- <td>
                                                <a href="view_student_marks.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm">View Marks</a>
                                                <a href="update_marks.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-secondary">Update Marks</a>
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
            
    <div class="main-content">
            <h1 class="page-title">Internal Marks</h1>
            
             <!-- <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-graduate"></i> Student Information
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?php echo $student['name']; ?></p>
                            <p><strong>Register Number:</strong> <?php echo $student['register_number']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Department:</strong> <?php echo $student['department']; ?></p>
                            <p><strong>Year of Study:</strong> <?php echo $student['year_of_study']; ?></p>
                        </div>
                    </div>
                </div>
            </div> -->
            
            <?php if (count($marks_by_subject) > 0): ?>
                <?php foreach ($marks_by_subject as $subject => $data): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <i class="fas fa-book"></i> <?php echo $subject; ?> (Faculty: <?php echo $data['faculty_name']; ?>)
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Exam</th>
                                            <th>Marks Obtained</th>
                                            <th>Maximum Marks</th>
                                            <th>Percentage</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th> 
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        
                                        $exams = ['Internal 1', 'Internal 2', 'Internal 3'];
                                        foreach ($exams as $exam): 
                                            $exam_data = isset($data['exams'][$exam]) ? $data['exams'][$exam] : null;
                                            if ($exam_data):
                                                $marks = $exam_data['marks'];
                                                $max_marks = $exam_data['max_marks'];
                                                $percentage = ($marks / $max_marks) * 100;
                                        ?>
                                            <tr>
                                                <td><?php echo $exam; ?></td>
                                                <td><?php echo $marks; ?></td>
                                                <td><?php echo $max_marks; ?></td>
                                                <td><?php echo number_format($percentage, 2); ?>%</td>
                                                <td>
                                                    <?php if ($percentage >= 75): ?>
                                                        <span class="badge badge-success">Excellent</span>
                                                    <?php elseif ($percentage >= 60): ?>
                                                        <span class="badge badge-info">Good</span>
                                                    <?php elseif ($percentage >= 45): ?>
                                                        <span class="badge badge-warning">Average</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Needs Improvement</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($exam_data['date'])); ?></td>
                                                <td>
                                                   <a href="internal_marks.php?action=edit&student_id=<?php echo $student_id; ?>&subject=<?php echo urlencode($subject); ?>&exam_type=<?php echo urlencode($exam); ?>" class="btn btn-sm btn-primary">Edit</a>
                                                   <a href="internal_marks.php?action=delete&student_id=<?php echo $student_id; ?>&subject=<?php echo urlencode($subject); ?>&exam_type=<?php echo urlencode($exam); ?>" class="btn btn-sm btn-danger">Delete</a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td><?php echo $exam; ?></td>
                                                <td colspan="5">Marks not available yet</td>
                                            </tr>
                                        <?php endif; endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <p>No internal marks have been recorded yet.</p>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    <script>
        function toggleMarksForm() {
            var form = document.getElementById("marks-form");
            if (form.style.display === "none") {
                form.style.display = "block";
            } else {
                form.style.display = "none";
            }
        }
    </script>
</body>
</html>