<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../index.php");
    exit();
}
require_once '../config/db_connect.php';

if (isset($_GET['id'])) {
    $submission_id = intval($_GET['id']);
    $result = mysqli_query($conn, "SELECT file_path FROM assignment_submissions WHERE submission_id = $submission_id");
    if ($data = mysqli_fetch_assoc($result)) {
        $file_path = '../uploads/' . $data['file_path'];
        mysqli_query($conn, "DELETE FROM assignment_submissions WHERE submission_id = $submission_id");
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        header("Location: assignments.php?deleted=1");
    } else {
        header("Location: assignments.php?error=notfound");
    }
} else {
    header("Location: assignments.php?error=invalid");
}
?>
