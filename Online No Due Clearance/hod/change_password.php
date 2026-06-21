<?php
session_start();
// Check if user is logged in and is a HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

$hod_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All fields are required";
    } else if ($new_password != $confirm_password) {
        $error_message = "New password and confirm password do not match";
    } else {
        // Verify current password
        $query = "SELECT password FROM hod WHERE hod_id = $hod_id";
        $result = mysqli_query($conn, $query);
        $hod = mysqli_fetch_assoc($result);
        
        if (password_verify($current_password, $hod['password'])) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in database
            $update_query = "UPDATE hod SET password = '$hashed_password' WHERE hod_id = $hod_id";
            
            if (mysqli_query($conn, $update_query)) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Error changing password: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Current password is incorrect";
        }
    }
}

// Redirect back to profile page with message
if (isset($success_message)) {
    $_SESSION['success_message'] = $success_message;
    header("Location: profile.php");
    exit();
} else if (isset($error_message)) {
    $_SESSION['error_message'] = $error_message;
    header("Location: profile.php");
    exit();
}

mysqli_close($conn);
?>