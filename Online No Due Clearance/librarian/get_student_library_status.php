<?php
// This file provides AJAX support for the no_due approval page
session_start();
// Check if user is logged in and is a librarian
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once '../config/db_connect.php';

if (isset($_GET['id'])) {
    $student_id = (int) $_GET['id'];
    
    // Get active books count and total fine amount
    $query = "SELECT 
              COUNT(*) as active_books, 
              SUM(fine_amount) as total_fine
              FROM library_records 
              WHERE student_id = $student_id 
              AND (status = 'Issued' OR status = 'Overdue' OR fine_amount > 0)";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $data = mysqli_fetch_assoc($result);
        
        // Ensure numeric values
        $data['active_books'] = (int) $data['active_books'];
        $data['total_fine'] = (float) $data['total_fine'];
        
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to fetch data']);
    }
    
    mysqli_close($conn);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing student ID']);
}
?>
