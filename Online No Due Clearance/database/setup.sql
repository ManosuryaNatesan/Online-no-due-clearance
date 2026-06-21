-- College Management System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS college_management;
USE college_management;

-- Students table
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    register_number VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    department VARCHAR(50) NOT NULL,
    year_of_study INT NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default_student.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Faculty table
CREATE TABLE IF NOT EXISTS faculty (
    faculty_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    department VARCHAR(50) NOT NULL,
    designation VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default_faculty.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- HOD (Head of Department) table
CREATE TABLE IF NOT EXISTS hod (
    hod_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    department VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default_hod.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Librarian table
CREATE TABLE IF NOT EXISTS librarian (
    librarian_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default_librarian.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Accountant table
CREATE TABLE IF NOT EXISTS accountant (
    accountant_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default_accountant.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Assignments table
CREATE TABLE IF NOT EXISTS assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    faculty_id INT NOT NULL,
    department VARCHAR(50) NOT NULL,
    year_of_study INT NOT NULL,
    due_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE
);

-- Assignment submissions table
CREATE TABLE IF NOT EXISTS assignment_submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'Submitted',
    marks FLOAT DEFAULT NULL,
    remarks TEXT,
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Internal marks table
CREATE TABLE IF NOT EXISTS internal_marks (
    mark_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    faculty_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    exam_type VARCHAR(50) NOT NULL,
    marks FLOAT NOT NULL,
    max_marks FLOAT NOT NULL DEFAULT 100,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE
);

-- Books table
CREATE TABLE IF NOT EXISTS books (
    book_id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) NOT NULL,
    publisher VARCHAR(150) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    category VARCHAR(50) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    available INT NOT NULL DEFAULT 1,
    shelf_no VARCHAR(20),
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    price DECIMAL(10, 2)
);

-- Library records table
CREATE TABLE IF NOT EXISTS library_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    book_id VARCHAR(50) NOT NULL,
    book_name VARCHAR(255) NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Issued', -- Issued, Returned, Overdue
    fine_amount DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
);

-- Fees types table
CREATE TABLE IF NOT EXISTS fee_types (
    fee_type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fees table
CREATE TABLE IF NOT EXISTS fees (
    fee_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type VARCHAR(50) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(10, 2) NOT NULL,
    due_date DATE NOT NULL,
    payment_date DATE DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Unpaid', -- Unpaid, Partially Paid, Paid, Overdue
    fine_amount DECIMAL(10, 2) DEFAULT 0.00,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Payment records table
CREATE TABLE IF NOT EXISTS payment_records (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    fee_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_mode VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100),
    remarks TEXT,
    FOREIGN KEY (fee_id) REFERENCES fees(fee_id) ON DELETE CASCADE
);

-- No Due Requests table
CREATE TABLE IF NOT EXISTS no_due_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    faculty_approval VARCHAR(20) DEFAULT 'Pending', -- Pending, Approved, Rejected
    faculty_id INT DEFAULT NULL,
    faculty_remarks TEXT,
    faculty_approval_date TIMESTAMP DEFAULT NULL,
    librarian_approval VARCHAR(20) DEFAULT 'Pending', -- Pending, Approved, Rejected
    librarian_id INT DEFAULT NULL,
    librarian_remarks TEXT,
    librarian_approval_date TIMESTAMP DEFAULT NULL,
    accountant_approval VARCHAR(20) DEFAULT 'Pending', -- Pending, Approved, Rejected
    accountant_id INT DEFAULT NULL,
    accountant_remarks TEXT,
    accountant_approval_date TIMESTAMP DEFAULT NULL,
    hod_approval VARCHAR(20) DEFAULT 'Pending', -- Pending, Approved, Rejected
    hod_id INT DEFAULT NULL,
    hod_remarks TEXT,
    hod_approval_date TIMESTAMP DEFAULT NULL,
    final_status VARCHAR(20) DEFAULT 'Pending', -- Pending, Approved, Rejected
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE SET NULL,
    FOREIGN KEY (librarian_id) REFERENCES librarian(librarian_id) ON DELETE SET NULL,
    FOREIGN KEY (accountant_id) REFERENCES accountant(accountant_id) ON DELETE SET NULL,
    FOREIGN KEY (hod_id) REFERENCES hod(hod_id) ON DELETE SET NULL
);

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    faculty_id INT NOT NULL,
    date DATE NOT NULL,
    status VARCHAR(10) DEFAULT 'Present', -- Present, Absent
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE
);

-- Insert sample data for testing

-- Insert fee types
INSERT INTO fee_types (name, description) VALUES 
('Tuition', 'Regular tuition fees'),
('Transport', 'Bus transportation fees'),
('Exam', 'Examination fees'),
('Hostel', 'Hostel accommodation fees'),
('Library', 'Library fees');

-- Insert books
INSERT INTO books (book_id, title, author, publisher, isbn, category, quantity, available, shelf_no, price)
VALUES 
('CS-101', 'Database Systems Concepts', 'Abraham Silberschatz', 'McGraw-Hill', '9780073523323', 'Computer Science', 5, 4, 'A1', 599.00),
('CS-102', 'Computer Networks', 'Andrew S. Tanenbaum', 'Pearson', '9780132126953', 'Computer Science', 3, 2, 'A1', 699.00),
('CS-103', 'Operating System Concepts', 'Silberschatz et al', 'Wiley', '9781118063330', 'Computer Science', 4, 3, 'A2', 649.00),
('CS-104', 'Design Patterns', 'Erich Gamma', 'Addison-Wesley', '9780201633610', 'Computer Science', 2, 1, 'A2', 549.00),
('CS-105', 'Data Mining', 'Han and Kamber', 'Morgan Kaufmann', '9780123814791', 'Computer Science', 3, 2, 'A3', 799.00),
('CS-106', 'Programming in C++', 'Bjarne Stroustrup', 'Addison-Wesley', '9780321563842', 'Computer Science', 5, 4, 'A3', 499.00),
('EC-101', 'Digital Circuit Design', 'Morris Mano', 'Pearson', '9780131989245', 'Electronics', 3, 2, 'B1', 549.00),
('EC-102', 'Electronic Devices', 'Thomas Floyd', 'Pearson', '9780132429733', 'Electronics', 4, 3, 'B1', 599.00),
('ME-101', 'Thermodynamics', 'Yunus Cengel', 'McGraw-Hill', '9780073398174', 'Mechanical', 3, 2, 'C1', 649.00);

-- Insert students
INSERT INTO students (name, register_number, email, mobile, gender, department, year_of_study, password, profile_image)
VALUES 
('Rahul Sharma', 'CS2021001', 'rahul@example.com', '9876543210', 'Male', 'Computer Science', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg'),
('Priya Singh', 'CS2021002', 'priya@example.com', '9876543211', 'Female', 'Computer Science', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg'),
('Amit Kumar', 'CS2022003', 'amit@example.com', '9876543212', 'Male', 'Computer Science', 2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg'),
('Sunita Patel', 'CS2022004', 'sunita@example.com', '9876543213', 'Female', 'Computer Science', 2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg'),
('Vijay Reddy', 'EC2021005', 'vijay@example.com', '9876543214', 'Male', 'Electronics', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg'),
('Aisha Khan', 'EC2021006', 'aisha@example.com', '9876543215', 'Female', 'Electronics', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg'),
('Rajesh Kumar', 'ME2022007', 'rajesh@example.com', '9876543216', 'Male', 'Mechanical', 2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg'),
('Meena Verma', 'ME2022008', 'meena@example.com', '9876543217', 'Female', 'Mechanical', 2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg');

-- Insert faculty
INSERT INTO faculty (name, email, mobile, department, designation, password, profile_image)
VALUES 
('Dr. Suresh Kumar', 'suresh@example.com', '9876543220', 'Computer Science', 'Associate Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg'),
('Dr. Anita Desai', 'anita@example.com', '9876543221', 'Computer Science', 'Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg'),
('Dr. Ramesh Gupta', 'ramesh@example.com', '9876543222', 'Electronics', 'Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg'),
('Dr. Geeta Sharma', 'geeta@example.com', '9876543223', 'Electronics', 'Assistant Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg'),
('Dr. Mohan Rao', 'mohan@example.com', '9876543224', 'Mechanical', 'Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg'),
('Dr. Radha Krishna', 'radha@example.com', '9876543225', 'Mechanical', 'Associate Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg');

-- Insert HODs
INSERT INTO hod (name, email, mobile, department, password, profile_image)
VALUES 
('Dr. Dinesh Verma', 'dinesh@example.com', '9876543230', 'Computer Science', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_hod.jpg'),
('Dr. Smita Patel', 'smita@example.com', '9876543231', 'Electronics', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_hod.jpg'),
('Dr. Prakash Nair', 'prakash@example.com', '9876543232', 'Mechanical', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_hod.jpg');

-- Insert librarian
INSERT INTO librarian (name, email, mobile, password, profile_image)
VALUES 
('Kiran Reddy', 'kiran@example.com', '9876543233', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_librarian.jpg');

-- Insert accountant
INSERT INTO accountant (name, email, mobile, password, profile_image)
VALUES 
('Lakshmi Narayan', 'lakshmi@example.com', '9876543234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_accountant.jpg');

-- Insert assignments
INSERT INTO assignments (title, description, subject, faculty_id, department, year_of_study, due_date)
VALUES 
('Database Normalization', 'Design a database schema and normalize it to 3NF. Submit a report with all the steps.', 'Database Management Systems', 1, 'Computer Science', 3, DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY)),
('Network Protocols', 'Implement a simple client-server application using TCP/IP protocol. Submit code and report.', 'Computer Networks', 1, 'Computer Science', 3, DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY)),
('Circuit Design', 'Design a 4-bit ALU using logic gates. Submit circuit diagram and simulation results.', 'Digital Electronics', 3, 'Electronics', 3, DATE_ADD(CURRENT_DATE, INTERVAL 20 DAY)),
('Thermodynamics', 'Solve the given problems and submit a report with detailed solutions.', 'Engineering Thermodynamics', 5, 'Mechanical', 2, DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY));

-- Insert assignment submissions
INSERT INTO assignment_submissions (assignment_id, student_id, file_path, submission_date, status, marks, remarks)
VALUES 
(1, 1, 'assignment_1_1.pdf', DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 'Submitted', 85, 'Good work!'),
(1, 2, 'assignment_1_2.pdf', DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), 'Submitted', 92, 'Excellent work!'),
(2, 1, 'assignment_2_1.pdf', DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), 'Submitted', NULL, NULL),
(3, 5, 'assignment_3_5.pdf', DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), 'Submitted', 78, 'Good effort');

-- Insert internal marks
INSERT INTO internal_marks (student_id, faculty_id, subject, exam_type, marks, max_marks, remarks)
VALUES 
(1, 1, 'Database Management Systems', 'Internal 1', 42, 50, 'Good performance'),
(1, 1, 'Database Management Systems', 'Internal 2', 45, 50, 'Excellent performance'),
(1, 1, 'Computer Networks', 'Internal 1', 38, 50, 'Above average'),
(1, 1, 'Computer Networks', 'Internal 2', 40, 50, 'Good performance'),
(2, 1, 'Database Management Systems', 'Internal 1', 48, 50, 'Outstanding'),
(2, 1, 'Database Management Systems', 'Internal 2', 47, 50, 'Excellent'),
(2, 1, 'Computer Networks', 'Internal 1', 44, 50, 'Very good'),
(2, 1, 'Computer Networks', 'Internal 2', 42, 50, 'Good'),
(3, 2, 'Programming Fundamentals', 'Internal 1', 35, 50, 'Average performance'),
(3, 2, 'Programming Fundamentals', 'Internal 2', 42, 50, 'Improved performance'),
(5, 3, 'Digital Electronics', 'Internal 1', 40, 50, 'Good'),
(5, 3, 'Digital Electronics', 'Internal 2', 44, 50, 'Very good'),
(7, 5, 'Engineering Thermodynamics', 'Internal 1', 32, 50, 'Needs improvement');

-- Insert attendance records
INSERT INTO attendance (student_id, subject, faculty_id, date, status)
VALUES
(1, 'Database Management Systems', 1, DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 'Present'),
(1, 'Database Management Systems', 1, DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), 'Present'),
(1, 'Database Management Systems', 1, DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), 'Absent'),
(2, 'Database Management Systems', 1, DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 'Present'),
(2, 'Database Management Systems', 1, DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), 'Present'),
(2, 'Database Management Systems', 1, DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), 'Present');

-- Insert library records
INSERT INTO library_records (student_id, book_id, book_name, issue_date, due_date, return_date, status, fine_amount)
VALUES 
(1, 'CS-101', 'Database Systems Concepts', DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 15 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY), 'Returned', 0),
(1, 'CS-102', 'Computer Networks', DATE_SUB(CURRENT_DATE, INTERVAL 20 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), 'Returned', 0),
(1, 'CS-103', 'Operating System Concepts', DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), NULL, 'Issued', 0),
(2, 'CS-104', 'Design Patterns', DATE_SUB(CURRENT_DATE, INTERVAL 40 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 25 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 20 DAY), 'Returned', 0),
(2, 'CS-105', 'Data Mining', DATE_SUB(CURRENT_DATE, INTERVAL 15 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 0 DAY), NULL, 'Issued', 0),
(3, 'CS-106', 'Programming in C++', DATE_SUB(CURRENT_DATE, INTERVAL 25 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), NULL, 'Overdue', 50), -- 10 days * ₹5
(5, 'EC-101', 'Digital Circuit Design', DATE_SUB(CURRENT_DATE, INTERVAL 20 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), NULL, 'Overdue', 25), -- 5 days * ₹5
(5, 'EC-102', 'Electronic Devices', DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), NULL, 'Issued', 0),
(7, 'ME-101', 'Thermodynamics', DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY), NULL, 'Issued', 0);

-- Insert fees
INSERT INTO fees (student_id, fee_type, total_amount, amount_paid, balance, due_date, payment_date, status, fine_amount)
VALUES 
(1, 'Tuition', 80000, 80000, 0, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 55 DAY), 'Paid', 0),
(1, 'Transport', 15000, 15000, 0, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 58 DAY), 'Paid', 0),
(1, 'Exam', 5000, 5000, 0, DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 25 DAY), 'Paid', 0),
(2, 'Tuition', 80000, 80000, 0, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 50 DAY), 'Paid', 0),
(2, 'Transport', 15000, 15000, 0, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), 'Paid', 0),
(2, 'Exam', 5000, 0, 5000, DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY), NULL, 'Overdue', 250), -- 10 days * ₹25
(3, 'Tuition', 75000, 50000, 25000, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 45 DAY), 'Partially Paid', 0),
(3, 'Transport', 15000, 15000, 0, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 50 DAY), 'Paid', 0),
(3, 'Exam', 5000, 0, 5000, DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY), NULL, 'Overdue', 275), -- 11 days * ₹25
(5, 'Tuition', 80000, 80000, 0, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 58 DAY), 'Paid', 0),
(5, 'Transport', 15000, 10000, 5000, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 45 DAY), 'Partially Paid', 0),
(7, 'Tuition', 75000, 75000, 0, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), 'Paid', 0),
(7, 'Transport', 15000, 0, 15000, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), NULL, 'Overdue', 625); -- 25 days * ₹25

-- Insert payment records
INSERT INTO payment_records (fee_id, amount, payment_date, payment_mode, transaction_id, remarks)
VALUES
(1, 80000, DATE_SUB(CURRENT_DATE, INTERVAL 55 DAY), 'Online', 'TXN123456', 'Full payment'),
(2, 15000, DATE_SUB(CURRENT_DATE, INTERVAL 58 DAY), 'Online', 'TXN123457', 'Full payment'),
(3, 5000, DATE_SUB(CURRENT_DATE, INTERVAL 25 DAY), 'Cash', NULL, 'Full payment'),
(4, 80000, DATE_SUB(CURRENT_DATE, INTERVAL 50 DAY), 'Online', 'TXN123458', 'Full payment'),
(5, 15000, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), 'Online', 'TXN123459', 'Full payment'),
(7, 50000, DATE_SUB(CURRENT_DATE, INTERVAL 45 DAY), 'Online', 'TXN123460', 'Partial payment'),
(8, 15000, DATE_SUB(CURRENT_DATE, INTERVAL 50 DAY), 'Cash', NULL, 'Full payment'),
(10, 80000, DATE_SUB(CURRENT_DATE, INTERVAL 58 DAY), 'Online', 'TXN123461', 'Full payment'),
(11, 10000, DATE_SUB(CURRENT_DATE, INTERVAL 45 DAY), 'Cash', NULL, 'Partial payment'),
(12, 75000, DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY), 'Online', 'TXN123462', 'Full payment');

-- Insert no due requests
INSERT INTO no_due_requests (student_id, request_date, faculty_approval, faculty_id, faculty_remarks, faculty_approval_date, librarian_approval, librarian_id, librarian_remarks, librarian_approval_date, accountant_approval, accountant_id, accountant_remarks, accountant_approval_date, hod_approval, hod_id, hod_approval_date, final_status)
VALUES 
(2, DATE_SUB(CURRENT_DATE, INTERVAL 15 DAY), 'Approved', 1, 'All assignments submitted', DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY), 'Approved', 1, 'All books returned', DATE_SUB(CURRENT_DATE, INTERVAL 13 DAY), 'Rejected', 1, 'Exam fee is pending', DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY), 'Pending', 1, NULL, 'Rejected'),
(4, DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), 'Approved', 1, 'All assignments submitted', DATE_SUB(CURRENT_DATE, INTERVAL 9 DAY), 'Approved', 1, 'All books returned', DATE_SUB(CURRENT_DATE, INTERVAL 8 DAY), 'Approved', 1, 'All fees paid', DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), 'Pending', 1, NULL, 'Pending'),
(8, DATE_SUB(CURRENT_DATE, INTERVAL 20 DAY), 'Approved', 5, 'All assignments submitted', DATE_SUB(CURRENT_DATE, INTERVAL 19 DAY), 'Approved', 1, 'All books returned', DATE_SUB(CURRENT_DATE, INTERVAL 18 DAY), 'Approved', 1, 'All fees paid', DATE_SUB(CURRENT_DATE, INTERVAL 17 DAY), 'Approved', 3, DATE_SUB(CURRENT_DATE, INTERVAL 15 DAY), 'Approved');

-- Create an admin user
CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO admin (username, password, name, email)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@example.com');
