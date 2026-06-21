# College Management System

A comprehensive web-based college management system built with PHP and MySQL, featuring role-based access control and streamlined no-due certificate process.

## Features

### Student Dashboard
- Profile Management: View, edit personal details
- Assignments: Submit assignments to faculty members based on subjects
- Internal Marks: View marks for three internal exams
- Library: Track book issues, returns, and fines
- Fees Due: View and track fee payments and dues
- No Due Application: Request clearance from faculty and download approved forms

### Faculty Dashboard
- Assignments Management: Create, view, and grade student assignments
- Internal Marks: Update and assign marks for students
- Student Management: View student details
- Library and Fees: View student library and fee records
- No Due Approval: Approve or reject student clearance requests

### HOD Dashboard
- Student & Faculty Management: View, edit, and manage department data
- Department Reports: Access department statistics and reports
- Library & Fees: View department-wide library and fee records
- Final No Due Approval: Provide final clearance after faculty, library, and accounts approval

### Librarian Dashboard
- Book Records: Manage book inventory and transactions
- Issue & Return: Process book issues and returns
- Fine Management: Track and update fines for late returns
- No Due Approval: Verify library clearance for students

### Accountant Dashboard
- Fee Management: Track tuition, transport, and exam fees
- Payment Records: Record and manage student payments
- Fine Calculation: Apply fines for overdue payments
- No Due Approval: Verify accounts clearance for students

## System Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)

## Installation
1. Clone this repository to your web server
2. Create a database named 'college_management'
3. Import the database/setup.sql file to set up the database schema
4. Update the database credentials in config/db_connect.php
5. Access the application through your web server

## Demo Credentials
- **Student:** ravi@student.com / password123
- **Faculty:** rajesh@faculty.com / password123
- **HOD:** vikram@hod.com / password123
- **Librarian:** shalini@library.com / password123
- **Accountant:** prakash@accounts.com / password123

## Features In Detail

### No Due Certificate Process
1. Student submits a No Due request
2. Faculty advisor reviews and approves/rejects
3. Library verifies book return status and approves/rejects
4. Accounts verifies fee payment status and approves/rejects
5. HOD provides final approval
6. Student downloads the No Due certificate upon approval

### Fine Calculation
- Library: ₹5 per day for overdue books
- Fees: ₹25 per day for overdue fee payments

## Directory Structure
- `/config` - Database configuration files
- `/database` - Database schema and setup files
- `/student` - Student dashboard and related files
- `/faculty` - Faculty dashboard and related files
- `/hod` - HOD dashboard and related files
- `/librarian` - Librarian dashboard and related files
- `/accountant` - Accountant dashboard and related files
- `/styles` - CSS stylesheets
- `/images` - Image assets
