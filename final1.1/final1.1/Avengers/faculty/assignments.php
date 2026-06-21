<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db_connect.php';

$faculty_id = $_SESSION['user_id'];

// Handle assignment deletion
if (isset($_GET['delete_assignment'])) {
    $assignment_id = intval($_GET['delete_assignment']);

    // Delete all related submissions first
    $del_submissions_query = "DELETE FROM assignment_submissions WHERE assignment_id = $assignment_id";
    mysqli_query($conn, $del_submissions_query);

    // Delete assignment
    $del_assignment_query = "DELETE FROM assignments WHERE assignment_id = $assignment_id AND faculty_id = $faculty_id";
    if (mysqli_query($conn, $del_assignment_query)) {
        header("Location: assignments.php?message=assignment_deleted");
        exit();
    } else {
        header("Location: assignments.php?error=delete_failed");
        exit();
    }
}

// Handle assignment creation (Post/Redirect/Get to prevent duplicates)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_assignment'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $year = intval($_POST['year_of_study']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);

    $insert_query = "
        INSERT INTO assignments (title, description, subject, faculty_id, department, year_of_study, due_date)
        VALUES ('$title', '$description', '$subject', $faculty_id, '$department', $year, '$due_date')
    ";

    if (mysqli_query($conn, $insert_query)) {
        // Redirect to avoid duplicate insert on refresh
        header("Location: assignments.php?message=assignment_created");
        exit();
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// Fetch assignments after any operations
$assignments_query = "
    SELECT a.*, COUNT(s.submission_id) AS submission_count
    FROM assignments a
    LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id
    WHERE a.faculty_id = $faculty_id
    GROUP BY a.assignment_id
    ORDER BY a.due_date DESC
";
$assignments_result = mysqli_query($conn, $assignments_query);

// Fetch student submissions
$submissions_query = "
    SELECT s.submission_id, s.file_path, s.submission_date,
           st.name AS student_name, st.register_number,
           a.title, a.subject, a.due_date,
           f.name AS faculty_name
    FROM assignment_submissions s
    JOIN students st ON s.student_id = st.student_id
    JOIN assignments a ON s.assignment_id = a.assignment_id
    JOIN faculty f ON a.faculty_id = f.faculty_id
    WHERE a.faculty_id = $faculty_id
    ORDER BY s.submission_date DESC
";
$submissions_result = mysqli_query($conn, $submissions_query);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Assignments</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div class="dashboard">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../images/college_logo.jpeg" alt="College Logo">
            <h3>Faculty Portal</h3>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="assignments.php" class="active"><i class="fas fa-tasks"></i> Assignments</a></li>
            <li><a href="internal_marks.php"><i class="fas fa-chart-bar"></i> Internal Marks</a></li>
            <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="library.php"><i class="fas fa-book"></i> Library</a></li>
            <li><a href="fees.php"><i class="fas fa-money-bill-wave"></i> Fees</a></li>
            <li><a href="certificates.php"><i class="fas fa-certificate"></i> Certificates</a></li>
            <li><a href="no_due.php"><i class="fas fa-clipboard-check"></i> No Due Approvals</a></li>
        </ul>
        <div class="logout">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1 class="page-title">Manage Assignments</h1>

        <?php 
        if (isset($_GET['message'])) {
            if ($_GET['message'] == 'assignment_created') {
                echo "<div class='alert alert-success'>Assignment created successfully!</div>";
            } elseif ($_GET['message'] == 'assignment_deleted') {
                echo "<div class='alert alert-success'>Assignment deleted successfully.</div>";
            }
        }
        if (isset($_GET['error'])) {
            if ($_GET['error'] == 'delete_failed') {
                echo "<div class='alert alert-danger'>Error deleting assignment.</div>";
            }
        }
        if (isset($error_message)) {
            echo "<div class='alert alert-danger'>$error_message</div>";
        }
        ?>

        <!-- Assignment Creation Form -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i> Create Assignment
                <button class="btn btn-sm float-right" onclick="toggleCreateForm()">
                    <i class="fas fa-plus"></i> Create
                </button>
            </div>
            <div class="card-body" id="create-form" style="display: none;">
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                        <div class="col-md-6"><label>Subject</label><input type="text" name="subject" class="form-control" required></div>
                    </div>
                    <div><label>Description</label><textarea name="description" class="form-control" rows="4" required></textarea></div>
                    <div class="row">
                        <div class="col-md-4">
                            <label>Department</label>
                            <select name="department" class="form-control" required>
                                <option value="">Select</option>
                                <option>Computer Science</option><option>Electronics</option>
                                <option>Mechanical</option><option>Civil</option><option>Electrical</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Year</label>
                            <select name="year_of_study" class="form-control" required>
                                <option value="">Select</option><option>1</option><option>2</option><option>3</option><option>4</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Due Date</label><input type="date" name="due_date" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" name="create_assignment" class="btn btn-success mt-3">Create</button>
                </form>
            </div>
        </div>

       <!-- Assignments List -->
<div class="card mt-4">
    <div class="card-header"><i class="fas fa-list"></i> My Assignments</div>
    <div class="card-body">
        <?php if (mysqli_num_rows($assignments_result) > 0): ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Class</th>
                    <th>Due Date</th>
                    <th>Submissions</th>
                    <th>Status</th> <!-- Separate status -->
                    <th>Action</th> <!-- Separate action -->
                </tr>
                </thead>
                <tbody>
                <?php while ($a = mysqli_fetch_assoc($assignments_result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['title']) ?></td>
                        <td><?= htmlspecialchars($a['subject']) ?></td>
                        <td><?= htmlspecialchars($a['department']) ?> - Year <?= htmlspecialchars($a['year_of_study']) ?></td>
                        <td><?= date('d M Y', strtotime($a['due_date'])) ?></td>
                        <td><?= $a['submission_count'] ?></td>
                        <td>
                            <?= strtotime($a['due_date']) < strtotime(date('Y-m-d'))
                                ? "<span class='badge badge-danger'>Past Due</span>"
                                : "<span class='badge badge-success'>Active</span>" ?>
                        </td>
                        <td>
                            <a href="assignments.php?delete_assignment=<?= $a['assignment_id'] ?>"
                               class="btn btn-sm btn-danger">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No assignments created yet.</p>
        <?php endif; ?>
    </div>
</div>
        <!-- Student Submissions Table -->
        <div class="card mt-4">
            <div class="card-header"><i class="fas fa-file-upload"></i> Student Submitted Assignments</div>
            <div class="card-body">
                <?php if (mysqli_num_rows($submissions_result) > 0): ?>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Student Name</th><th>Register No.</th><th>Title</th><th>Subject</th><th>Faculty</th><th>Due Date</th><th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($s = mysqli_fetch_assoc($submissions_result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['student_name']) ?></td>
                                <td><?= htmlspecialchars($s['register_number']) ?></td>
                                <td><?= htmlspecialchars($s['title']) ?></td>
                                <td><?= htmlspecialchars($s['subject']) ?></td>
                                <td><?= htmlspecialchars($s['faculty_name']) ?></td>
                                <td><?= date('d M Y', strtotime($s['due_date'])) ?></td>
                                <td>
                                    <a href="../uploads/assignments/<?= htmlspecialchars($s['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary">View</a>
                                    <a href="delete_submission.php?id=<?= $s['submission_id'] ?>" class="btn btn-sm btn-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No student submissions available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleCreateForm() {
        var f = document.getElementById("create-form");
        f.style.display = f.style.display === "none" ? "block" : "none";
    }
</script>
</body>
</html>
