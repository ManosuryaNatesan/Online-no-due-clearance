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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    
    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = "hod_" . $hod_id . "_" . time() . "." . $filetype;
            $upload_path = "../images/" . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Update profile with new image
                $update_query = "UPDATE hod SET name = '$name', email = '$email', mobile = '$mobile', profile_image = '$new_filename' WHERE hod_id = $hod_id";
            }
        }
    } else {
        // Update profile without changing image
        $update_query = "UPDATE hod SET name = '$name', email = '$email', mobile = '$mobile' WHERE hod_id = $hod_id";
    }
    
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Profile updated successfully!";
        // Refresh HOD data
        $result = mysqli_query($conn, $query);
        $hod = mysqli_fetch_assoc($result);
    } else {
        $error_message = "Error updating profile: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Profile - College Management System</title>
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
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> <span>Profile</span></a></li>
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
            <h1 class="page-title">My Profile</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-user"></i> Profile Image
                        </div>
                        <div class="card-body text-center">
                            <img src="../images/<?php echo $hod['profile_image']; ?>" alt="Profile Image" class="profile-image mb-3">
                            <h3><?php echo $hod['name']; ?></h3>
                            <p>Head of Department - <?php echo $hod['department']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-edit"></i> Edit Profile
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" class="form-control" value="<?php echo $hod['name']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?php echo $hod['email']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="mobile">Mobile Number</label>
                                    <input type="text" id="mobile" name="mobile" class="form-control" value="<?php echo $hod['mobile']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="profile_image">Profile Image</label>
                                    <input type="file" id="profile_image" name="profile_image" class="form-control">
                                    <small class="text-muted">Leave empty to keep current image</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <input type="text" id="department" class="form-control" value="<?php echo $hod['department']; ?>" readonly>
                                    <small class="text-muted">Department cannot be changed</small>
                                </div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-success">Update Profile</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-key"></i> Change Password
                </div>
                <div class="card-body">
                    <form action="change_password.php" method="post">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <button type="submit" class="btn">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>