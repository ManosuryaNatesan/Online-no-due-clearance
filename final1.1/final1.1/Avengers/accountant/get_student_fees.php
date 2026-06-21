<?php
// This file provides AJAX support for the fee payments page
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
    
    // Get all fees for student with balance > 0
    $query = "SELECT fee_id, fee_type, total_amount, amount, balance, due_date, fine_amount, status
              FROM fees 
              WHERE student_id = $student_id 
              AND balance > 0
              ORDER BY due_date ASC";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $fees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Format dates for better readability
            $row['due_date'] = date('d M Y', strtotime($row['due_date']));
            $fees[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($fees);
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