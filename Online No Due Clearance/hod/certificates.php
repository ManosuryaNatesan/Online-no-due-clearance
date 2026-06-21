<?php
session_start();
// Check if user is logged in and is a faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

// Get faculty details
$hod_id = $_SESSION['user_id'];
$query = "SELECT * FROM hod WHERE hod_id = $hod_id";
$result = mysqli_query($conn, $query);
$hod = mysqli_fetch_assoc($result);
// Get assignments created by this faculty
$query = "SELECT a.*
          FROM certificates a 
          WHERE a.department = '".$hod['department']."'
          GROUP BY a.certificate_id
          ORDER BY a.created_at DESC";
$certificates_result = mysqli_query($conn, $query);
// Process form submission for new assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_certificate'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    //$subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    //$year_of_study = mysqli_real_escape_string($conn, $_POST['year_of_study']);
    //$due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    
    
    // Check if file was uploaded
    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] == 0) {
        // Create directory if it doesn't exist
        $upload_dir = "../uploads/certificates/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = $_FILES['certificate_file']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $new_filename = "certificate_" . $student_id . "_" . time() . "." . $filetype;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['certificate_file']['tmp_name'], $upload_path)) {
            // Insert submission record
            $insert_query = "INSERT INTO certificates (title, description, student_id, department, file_name, file_path) 
                     VALUES ('$title', '$description', $student_id, '$department', '$filename', '$new_filename')";
            if (mysqli_query($conn, $insert_query)) {
                $success_message = "Certificate submitted successfully!";
                $certificates_result = mysqli_query($conn, "SELECT a.*
                                        FROM certificates a 
                                        WHERE a.student_id = $student_id
                                        GROUP BY a.certificate_id
                                        ORDER BY a.created_at DESC");
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
            <h1 class="page-title">Manage Certificates</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <!-- <div class="card-header">
                            <i class="fas fa-plus-circle"></i> Create New Certificate
                            <button class="btn btn-sm float-right" onclick="toggleCreateForm()">
                                <i class="fas fa-plus"></i> Create
                            </button>
                        </div> -->
                        <div class="card-body" id="create-form" style="display: none;">
                            <form method="post" action="" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="title">Certificate Title</label>
                                            <input type="text" id="title" name="title" class="form-control" required>
                                        </div>
                                    </div>
                                    <!-- <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="subject">Subject</label>
                                            <input type="text" id="subject" name="subject" class="form-control" required>
                                        </div>
                                    </div> -->
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="department">Department</label>
                                            <select id="department" name="department" class="form-control" required>
                                                <option value="">Select Department</option>
                                                <option value="Computer Science">Computer Science</option>
                                                <option value="Electronics">Electronics</option>
                                                <option value="Mechanical">Mechanical</option>
                                                <option value="Civil">Civil</option>
                                                <option value="Electrical">Electrical</option>
                                            </select>
                                        </div>
                                    </div>
<!--                                     
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="due_date">Date</label>
                                            <input type="date" id="due_date" name="due_date" class="form-control" required>
                                        </div>
                                    </div> -->
                                    <div class="form-group">
                                        <label for="certificate_file">Upload File (PDF, DOC, DOCX, ZIP)</label>
                                        <input type="file" id="certificate_file" name="certificate_file" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="form-group mt-3">
                                    <button type="submit" name="create_certificate" class="btn btn-success">Create Certificate</button>
                                    <button type="button" class="btn" onclick="toggleCreateForm()">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-list"></i> Student Certificates
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($certificates_result) > 0): ?>
                        <div class="table-container">
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
                                            <td><?php echo $assignment['title']; ?></td>
                                            <td><?php echo $assignment['department'] ?></td>
                                            <td><?php echo $assignment['file_name']; ?></td>
                                            <td><a target="_blank" href="../uploads/certificates/<?php echo $assignment['file_path']; ?>" class="btn btn-success">View</a></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
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
            if (form.style.display === "none") {
                form.style.display = "block";
            } else {
                form.style.display = "none";
            }
        }
    </script>
</body>
</html>