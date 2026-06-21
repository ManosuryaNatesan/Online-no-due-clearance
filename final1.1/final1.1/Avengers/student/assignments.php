<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get student details
$student_id = $_SESSION['user_id'];
$query = "SELECT * FROM students WHERE student_id = $student_id";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);

// Get all available assignments for the student's department and year
$dept = $student['department'];
$year = $student['year_of_study'];

$query_available = "SELECT a.*, f.name as faculty_name
                    FROM assignments a
                    JOIN faculty f ON a.faculty_id = f.faculty_id
                    WHERE a.department = '$dept' AND a.year_of_study = $year
                    ORDER BY a.due_date ASC";
$assignments_result = mysqli_query($conn, $query_available);

// Get submitted assignments with file path
$query_submitted = "SELECT s.*, a.title, a.subject, s.file_path AS submitted_file_path
                    FROM assignment_submissions s
                    JOIN assignments a ON s.assignment_id = a.assignment_id
                    WHERE s.student_id = $student_id";
$submissions_result = mysqli_query($conn, $query_submitted);

// Process assignment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_assignment'])) {
    $assignment_id = mysqli_real_escape_string($conn, $_POST['assignment_id']);

    // Check if file was uploaded
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == 0) {
        // Create directory if it doesn't exist
        $upload_dir = "../uploads/assignments/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = $_FILES['assignment_file']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $new_filename = "assignment_" . $student_id . "_" . $assignment_id . "_" . time() . "." . $filetype;
        $upload_path = $upload_dir . $new_filename;

        // Check if assignment is already submitted
        $check_query = "SELECT * FROM assignment_submissions WHERE student_id = $student_id AND assignment_id = $assignment_id";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "You have already submitted this assignment.";
        } else {
            // Check if assignment is past due date
            $due_date_query = "SELECT due_date FROM assignments WHERE assignment_id = $assignment_id";
            $due_date_result = mysqli_query($conn, $due_date_query);
            $due_date = mysqli_fetch_assoc($due_date_result)['due_date'];

            $status = 'Submitted';
            if (strtotime($due_date) < strtotime(date('Y-m-d'))) {
                $status = 'Late';
            }

            if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $upload_path)) {
                // Insert submission record
                $insert_query = "INSERT INTO assignment_submissions (assignment_id, student_id, file_path, status)
                                 VALUES ($assignment_id, $student_id, '$new_filename', '$status')";

                if (mysqli_query($conn, $insert_query)) {
                    $success_message = "Assignment submitted successfully!";
                    // Refresh submissions
                    $submissions_result = mysqli_query($conn, $query_submitted);
                    mysqli_data_seek($assignments_result, 0); // Reset pointer for available assignments
                } else {
                    $error_message = "Error submitting assignment: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Error uploading file.";
            }
        }
    } else {
        $error_message = "Please select a file to upload.";
    }
}

// Handle delete submission request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_submission_id'])) {
    $delete_id = intval($_POST['delete_submission_id']);

    // Get file path to delete from server
    $file_query = "SELECT file_path FROM assignment_submissions WHERE submission_id = $delete_id AND student_id = $student_id";
    $file_result = mysqli_query($conn, $file_query);
    if ($file = mysqli_fetch_assoc($file_result)) {
        $file_path = "../uploads/assignments/" . $file['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Delete from database
    $delete_query = "DELETE FROM assignment_submissions WHERE submission_id = $delete_id AND student_id = $student_id";
    if (mysqli_query($conn, $delete_query)) {
        $success_message = "Submission deleted successfully!";
        // Refresh submissions
        $submissions_result = mysqli_query($conn, $query_submitted);
        mysqli_data_seek($assignments_result, 0); // Reset pointer for available assignments
    } else {
        $error_message = "Error deleting submission: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - College Management System</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/college_logo.jpeg" alt="College Logo">
                <h3>Student Portal</h3>
            </div>

            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="assignments.php" class="active"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
                <li><a href="internal_marks.php"><i class="fas fa-chart-bar"></i> <span>Internal Marks</span></a></li>
                <li><a href="library.php"><i class="fas fa-book"></i> <span>Library</span></a></li>
                <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> <span>Fees Due</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-certificate"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Application</span></a></li>
            </ul>

            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>

        <div class="main-content">
            <h1 class="page-title">Assignments</h1>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clipboard-list"></i> Available Assignments
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($assignments_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Subject</th>
                                        <th>Faculty</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($assignment = mysqli_fetch_assoc($assignments_result)):
                                        $assignment_id = $assignment['assignment_id'];
                                        $submitted = false;
                                        $submission_status = '';

                                        // Check if the assignment is already submitted
                                        mysqli_data_seek($submissions_result, 0);
                                        while ($submission = mysqli_fetch_assoc($submissions_result)) {
                                            if ($submission['assignment_id'] == $assignment_id) {
                                                $submitted = true;
                                                $submission_status = $submission['status'];
                                                break;
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo $assignment['title']; ?></td>
                                            <td><?php echo $assignment['subject']; ?></td>
                                            <td><?php echo $assignment['faculty_name']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($assignment['due_date'])); ?></td>
                                            <td>
                                                <?php if ($submitted): ?>
                                                    <span class="badge badge-<?php echo $submission_status == 'Submitted' ? 'success' : ($submission_status == 'Late' ? 'warning' : 'info'); ?>">
                                                        <?php echo $submission_status; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Not Submitted</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!$submitted): ?>
                                                    <button class="btn btn-primary btn-sm" onclick="openSubmitForm(<?php echo $assignment_id; ?>, '<?php echo $assignment['title']; ?>')">Submit</button>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-sm" disabled>Submitted</button>
                                                <?php endif; ?>
                                                </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No assignments available for your department and year.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-tasks"></i> My Submissions
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($submissions_result) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Assignment</th>
                                        <th>Subject</th>
                                        <th>Submitted Date</th>
                                        <th>Status</th>
                                        <th>Marks</th>
                                        <th>Remarks</th>
                                        <th>View</th>
                                        <th>Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    mysqli_data_seek($submissions_result, 0);
                                    while ($submission = mysqli_fetch_assoc($submissions_result)):
                                    ?>
                                        <tr>
                                            <td><?php echo $submission['title']; ?></td>
                                            <td><?php echo $submission['subject']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($submission['submission_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $submission['status'] == 'Submitted' ? 'success' : ($submission['status'] == 'Late' ? 'warning' : 'info'); ?>">
                                                    <?php echo $submission['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $submission['marks'] ? $submission['marks'] : 'Not evaluated'; ?></td>
                                            <td><?php echo $submission['remarks'] ? $submission['remarks'] : '-'; ?></td>
                                            <td>
                                                <?php if (!empty($submission['submitted_file_path'])): ?>
                                                    <a target="_blank" href="../uploads/assignments/<?php echo $submission['submitted_file_path']; ?>" class="btn btn-success btn-sm" >View</a>
                                                <?php else: ?>
                                                    <span class="text-muted">No File</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="post" action="">
                                                    <input type="hidden" name="delete_submission_id" value="<?php echo $submission['submission_id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>You haven't submitted any assignments yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="submission-modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeSubmitForm()">&times;</span>
                    <h2>Submit Assignment</h2>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" id="assignment_id" name="assignment_id" value="">

                        <div class="form-group">
                            <label for="assignment_title">Assignment Title</label>
                            <input type="text" id="assignment_title" class="form-control" readonly>
                        </div>

                        <div class="form-group">
                            <label for="assignment_file">Upload File (PDF, DOC, DOCX, ZIP)</label>
                            <input type="file" id="assignment_file" name="assignment_file" class="form-control" required>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" name="submit_assignment" class="btn btn-success">Submit Assignment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        function openSubmitForm(assignmentId, title) {
            document.getElementById("assignment_id").value = assignmentId;
            document.getElementById("assignment_title").value = title;
            document.getElementById("submission-modal").style.display = "block";
        }

        function closeSubmitForm() {
            document.getElementById("submission-modal").style.display = "none";
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById("submission-modal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>