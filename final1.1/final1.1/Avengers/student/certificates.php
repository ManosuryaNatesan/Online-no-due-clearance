<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

$student_id = $_SESSION['user_id'];

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_certificate_id'])) {
    $delete_id = intval($_POST['delete_certificate_id']);

    // Get file path to delete from server
    $file_query = "SELECT file_path FROM certificates WHERE certificate_id = $delete_id AND student_id = $student_id";
    $file_result = mysqli_query($conn, $file_query);
    if ($file = mysqli_fetch_assoc($file_result)) {
        $file_path = "../uploads/certificates/" . $file['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Delete from database
    $delete_query = "DELETE FROM certificates WHERE certificate_id = $delete_id AND student_id = $student_id";
    mysqli_query($conn, $delete_query);
}

// Get student details
$query = "SELECT * FROM students WHERE student_id = $student_id";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);

// Get certificates
$query = "SELECT a.* FROM certificates a WHERE a.student_id = $student_id GROUP BY a.certificate_id ORDER BY a.created_at DESC";
$certificates_result = mysqli_query($conn, $query);

// Handle certificate submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_certificate'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);

    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] == 0) {
        $upload_dir = "../uploads/certificates/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = $_FILES['certificate_file']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $new_filename = "certificate_" . $student_id . "_" . time() . "." . $filetype;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['certificate_file']['tmp_name'], $upload_path)) {
            $insert_query = "INSERT INTO certificates (title, description, student_id, department, file_name, file_path) 
                             VALUES ('$title', '$description', $student_id, '$department', '$filename', '$new_filename')";
            if (mysqli_query($conn, $insert_query)) {
                $success_message = "Certificate submitted successfully!";
                $certificates_result = mysqli_query($conn, "SELECT a.* FROM certificates a WHERE a.student_id = $student_id GROUP BY a.certificate_id ORDER BY a.created_at DESC");
            } else {
                $error_message = "Error submitting certificate: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Error uploading file.";
        }
    } else {
        $error_message = "Please select a file to upload.";
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Certificates - College Management System</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/college_logo.jpeg" alt="College Logo">
                <h3>Student Portal</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="assignments.php"><i class="fas fa-tasks"></i> Assignments</a></li>
                <li><a href="internal_marks.php"><i class="fas fa-chart-bar"></i> Internal Marks</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="library.php"><i class="fas fa-book"></i> Library</a></li>
                <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> Fees</a></li>
                <li><a href="certificates.php" class="active"><i class="fas fa-certificate"></i> Certificates</a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> No Due Approvals</a></li>
            </ul>
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="main-content">
            <h1 class="page-title">Manage Certificates</h1>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> Create New Certificate
                    <button class="btn btn-sm float-right" onclick="toggleCreateForm()">
                        <i class="fas fa-plus"></i> Create
                    </button>
                </div>
                <div class="card-body" id="create-form" style="display: none;">
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Certificate Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select name="department" class="form-control" required>
                                <option value="">Select Department</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Electronics">Electronics</option>
                                <option value="Mechanical">Mechanical</option>
                                <option value="Civil">Civil</option>
                                <option value="Electrical">Electrical</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="certificate_file">Upload File</label>
                            <input type="file" name="certificate_file" class="form-control" required>
                        </div>
                        <div class="form-group mt-3">
                            <button type="submit" name="create_certificate" class="btn btn-success">Create Certificate</button>
                            <button type="button" class="btn" onclick="toggleCreateForm()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-list"></i> My Certificates
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($certificates_result) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Class</th>
                                    <th>File Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($assignment = mysqli_fetch_assoc($certificates_result)): ?>
                                    <tr>
                                        <td><?= $assignment['title']; ?></td>
                                        <td><?= $assignment['department']; ?></td>
                                        <td><?= $assignment['file_name']; ?></td>
                                        <td>
                                            <a target="_blank" href="../uploads/certificates/<?= $assignment['file_path']; ?>" class="btn btn-success">View</a>
                                            <form method="post" action="" style="display:inline;">
                                             <input type="hidden" name="delete_certificate_id" value="<?= $assignment['certificate_id']; ?>">
                                              <button type="submit" class="btn btn-danger" style="padding: 12px 18px; border-radius: 4px;">
                                                Delete
                                             </button>
                                               </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No Certificates found. Create a new Certificate to get started.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleCreateForm() {
            var form = document.getElementById("create-form");
            form.style.display = form.style.display === "none" ? "block" : "none";
        }
    </script>
</body>
</html>
