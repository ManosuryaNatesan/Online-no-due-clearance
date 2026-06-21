<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

$student_id = $_SESSION['user_id'];

// Check if the student has an approved no due request
$query = "SELECT * FROM no_due_requests 
          WHERE student_id = $student_id 
          AND final_status = 'Approved'
          ORDER BY request_date DESC
          LIMIT 1";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    // No approved no due request found
    header("Location: no_due.php");
    exit();
}

$no_due_request = mysqli_fetch_assoc($result);

// Get student details
$query = "SELECT * FROM students WHERE student_id = $student_id";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);

// Get faculty details
$query = "SELECT * FROM faculty WHERE faculty_id = " . $no_due_request['faculty_id'];
$result = mysqli_query($conn, $query);
$faculty = mysqli_fetch_assoc($result);

// Get HOD details for the student's department
$query = "SELECT * FROM hod WHERE department = '" . $student['department'] . "'";
$result = mysqli_query($conn, $query);
$hod = mysqli_fetch_assoc($result);

// Get librarian details
$query = "SELECT * FROM librarian LIMIT 1";
$result = mysqli_query($conn, $query);
$librarian = mysqli_fetch_assoc($result);

// Get accountant details
$query = "SELECT * FROM accountant LIMIT 1";
$result = mysqli_query($conn, $query);
$accountant = mysqli_fetch_assoc($result);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Due Certificate - <?php echo $student['name']; ?></title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
        }
        .certificate {
            max-width: 800px;
            margin: 20px auto;
            padding: 40px;
            border: 2px solid #000;
            background-color: #fff;
        }
        .certificate-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .certificate-header img {
            width: 100px;
            margin-bottom: 10px;
        }
        .certificate-title {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .certificate-subtitle {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .certificate-content {
            line-height: 1.6;
            text-align: justify;
            margin-bottom: 30px;
        }
        .student-details {
            margin-bottom: 30px;
        }
        .student-details table {
            width: 100%;
        }
        .student-details td {
            padding: 5px;
        }
        .approval-section {
            margin-top: 50px;
        }
        .approval-section table {
            width: 100%;
        }
        .approval-box {
            text-align: center;
            width: 25%;
            vertical-align: top;
        }
        .signature-space {
            height: 25px;
            
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 0 auto;
        }
        .print-button {
            text-align: center;
            margin: 20px 0;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .certificate {
                border: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div class="container">
            <div class="print-button">
                <button onclick="window.print()" class="btn">Print Certificate</button>
                <a href="no_due.php" class="btn">Back to No Due Page</a>
            </div>
        </div>
    </div>

    <div class="certificate">
        <div class="certificate-header">
            <img src="../images/college_logo.jpeg" alt="College Logo">
            <div class="certificate-title">No Due Certificate</div>
            <div class="certificate-subtitle">College Management System</div>
        </div>

        <div class="certificate-content">
            <p>This is to certify that the following student has cleared all dues and obligations to the institution:</p>
        </div>

        <div class="student-details">
            <table>
                <tr>
                    <td width="30%"><strong>Name:</strong></td>
                    <td><?php echo $student['name']; ?></td>
                </tr>
                <tr>
                    <td><strong>Register Number:</strong></td>
                    <td><?php echo $student['register_number']; ?></td>
                </tr>
                <tr>
                    <td><strong>Department:</strong></td>
                    <td><?php echo $student['department']; ?></td>
                </tr>
                <tr>
                    <td><strong>Year of Study:</strong></td>
                    <td><?php echo $student['year_of_study']; ?></td>
                </tr>
                <tr>
                    <td><strong>Certificate Date:</strong></td>
                    <td><?php echo date('d-m-Y'); ?></td>
                </tr>
                <tr>
                    <td><strong>Reference Number:</strong></td>
                    <td>ND<?php echo str_pad($no_due_request['request_id'], 6, '0', STR_PAD_LEFT); ?></td>
                </tr>
            </table>
        </div>

        <div class="certificate-content">
            <p>The above student has no pending dues with respect to the following departments:</p>
            <ol>
                <li>Department of <?php echo $student['department']; ?></li>
                <li>Library Department</li>
                <li>Accounts Department</li>
            </ol>
            <p>This certificate is valid for all official purposes.</p>
        </div>

        <div class="approval-section">
            <table>
                <tr>
                    <td class="approval-box">
                        <div class="signature-space"><b>Approved</b></div>
                        <div class="signature-line"></div>
                        <p><strong>Faculty Advisor</strong><br><?php echo $faculty['name']; ?></p>
                        <p>Date: <?php echo date('d-m-Y', strtotime($no_due_request['faculty_approval_date'])); ?></p>
                    </td>
                    <td class="approval-box">
                        <div class="signature-space"><b>Approved</b></div>
                        <div class="signature-line"></div>
                        <p><strong>Librarian</strong><br><?php echo $librarian['name']; ?></p>
                        <p>Date: <?php echo date('d-m-Y', strtotime($no_due_request['librarian_approval_date'])); ?></p>
                    </td>
                    <td class="approval-box">
                        <div class="signature-space"><b>Approved</b></div>
                        <div class="signature-line"></div>
                        <p><strong>Accounts Officer</strong><br><?php echo $accountant['name']; ?></p>
                        <p>Date: <?php echo date('d-m-Y', strtotime($no_due_request['accountant_approval_date'])); ?></p>
                    </td>
                    <td class="approval-box">
                        <div class="signature-space"><b>Approved</b></div>
                        <div class="signature-line"></div>
                        <p><strong>Head of Department</strong><br><?php echo $hod['name']; ?></p>
                        <p>Date: <?php echo date('d-m-Y', strtotime($no_due_request['hod_approval_date'])); ?></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
