<?php
// This file provides AJAX support for the no_due approval page
session_start();
// Check if user is logged in and is an accountant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once '../config/db_connect.php';

if (isset($_GET['id'])) {
    $student_id = (int) $_GET['id'];
    
    // Get unpaid fees count and total fine amount
    $query = "SELECT 
              COUNT(*) as unpaid_fees, 
              SUM(fine_amount) as total_fine
              FROM fees 
              WHERE student_id = $student_id 
              AND (status IN ('Unpaid', 'Partially Paid', 'Overdue') OR fine_amount > 0)";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $data = mysqli_fetch_assoc($result);
        
        // Ensure numeric values
        $data['unpaid_fees'] = (int) $data['unpaid_fees'];
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
