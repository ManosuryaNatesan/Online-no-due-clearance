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
                <h3>Student Portal</h3>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
                <li><a href="internal_marks.php" class="active"><i class="fas fa-chart-bar"></i> <span>Internal Marks</span></a></li>
                <li><a href="library.php"><i class="fas fa-book"></i> <span>Library</span></a></li>
                <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> <span>Fees Due</span></a></li>
                <li><a href="certificates.php"><i class="fas fa-money-bill-wave"></i> <span>Certificates</span></a></li>
                <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> <span>No Due Application</span></a></li>
            </ul>
            
            <div class="logout">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Internal Marks</h1>
            
            <div class="card">
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
            </div>
            
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
</body>
</html>
