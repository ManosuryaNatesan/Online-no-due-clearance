-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 07:36 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `college_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `accountant`
--

CREATE TABLE `accountant` (
  `accountant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'default_accountant.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `accountant`
--

INSERT INTO `accountant` (`accountant_id`, `name`, `email`, `mobile`, `password`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'Lakshmi Narayan', 'lakshmi@example.com', '9876543234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_accountant.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `name`, `email`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@example.com', '2025-05-16 04:28:24', '2025-05-16 04:28:24');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignment_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `subject` varchar(100) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `department` varchar(50) NOT NULL,
  `year_of_study` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assignment_id`, `title`, `description`, `subject`, `faculty_id`, `department`, `year_of_study`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 'Database Normalization', 'Design a database schema and normalize it to 3NF. Submit a report with all the steps.', 'Database Management Systems', 1, 'Computer Science', 3, '2025-05-31', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(2, 'Network Protocols', 'Implement a simple client-server application using TCP/IP protocol. Submit code and report.', 'Computer Networks', 1, 'Computer Science', 3, '2025-05-26', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(3, 'Circuit Design', 'Design a 4-bit ALU using logic gates. Submit circuit diagram and simulation results.', 'Digital Electronics', 3, 'Electronics', 3, '2025-06-05', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(4, 'Thermodynamics', 'Solve the given problems and submit a report with detailed solutions.', 'Engineering Thermodynamics', 5, 'Mechanical', 2, '2025-05-23', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(5, 'TEST', 'TEST', 'CSE', 1, 'Computer Science', 1, '2025-05-23', '2025-05-16 04:49:10', '2025-05-16 04:49:10'),
(6, 'TEST', 'TEST', 'CSE', 1, 'Computer Science', 3, '2025-05-23', '2025-05-16 04:49:44', '2025-05-16 04:49:44');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `submission_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'Submitted',
  `marks` float DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `assignment_submissions`
--

INSERT INTO `assignment_submissions` (`submission_id`, `assignment_id`, `student_id`, `file_path`, `submission_date`, `status`, `marks`, `remarks`) VALUES
(1, 1, 1, 'assignment_1_1.pdf', '2025-05-10 18:30:00', 'Submitted', 85, 'Good work!'),
(2, 1, 2, 'assignment_1_2.pdf', '2025-05-12 18:30:00', 'Submitted', 92, 'Excellent work!'),
(3, 2, 1, 'assignment_2_1.pdf', '2025-05-13 18:30:00', 'Submitted', NULL, NULL),
(4, 3, 5, 'assignment_3_5.pdf', '2025-05-11 18:30:00', 'Submitted', 78, 'Good effort');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(10) DEFAULT 'Present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `subject`, `faculty_id`, `date`, `status`) VALUES
(1, 1, 'Database Management Systems', 1, '2025-05-11', 'Present'),
(2, 1, 'Database Management Systems', 1, '2025-05-12', 'Present'),
(3, 1, 'Database Management Systems', 1, '2025-05-13', 'Absent'),
(4, 2, 'Database Management Systems', 1, '2025-05-11', 'Present'),
(5, 2, 'Database Management Systems', 1, '2025-05-12', 'Present'),
(6, 2, 'Database Management Systems', 1, '2025-05-13', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(150) NOT NULL,
  `publisher` varchar(150) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `available` int(11) NOT NULL DEFAULT 1,
  `shelf_no` varchar(20) DEFAULT NULL,
  `added_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `publisher`, `isbn`, `category`, `quantity`, `available`, `shelf_no`, `added_date`, `price`) VALUES
('CS-101', 'Database Systems Concepts', 'Abraham Silberschatz', 'McGraw-Hill', '9780073523323', 'Computer Science', 5, 4, 'A1', '2025-05-16 04:28:11', '599.00'),
('CS-102', 'Computer Networks', 'Andrew S. Tanenbaum', 'Pearson', '9780132126953', 'Computer Science', 3, 2, 'A1', '2025-05-16 04:28:11', '699.00'),
('CS-103', 'Operating System Concepts', 'Silberschatz et al', 'Wiley', '9781118063330', 'Computer Science', 4, 3, 'A2', '2025-05-16 04:28:11', '649.00'),
('CS-104', 'Design Patterns', 'Erich Gamma', 'Addison-Wesley', '9780201633610', 'Computer Science', 2, 1, 'A2', '2025-05-16 04:28:11', '549.00'),
('CS-105', 'Data Mining', 'Han and Kamber', 'Morgan Kaufmann', '9780123814791', 'Computer Science', 3, 2, 'A3', '2025-05-16 04:28:11', '799.00'),
('CS-106', 'Programming in C++', 'Bjarne Stroustrup', 'Addison-Wesley', '9780321563842', 'Computer Science', 5, 4, 'A3', '2025-05-16 04:28:11', '499.00'),
('EC-101', 'Digital Circuit Design', 'Morris Mano', 'Pearson', '9780131989245', 'Electronics', 3, 2, 'B1', '2025-05-16 04:28:11', '549.00'),
('EC-102', 'Electronic Devices', 'Thomas Floyd', 'Pearson', '9780132429733', 'Electronics', 4, 3, 'B1', '2025-05-16 04:28:11', '599.00'),
('ME-101', 'Thermodynamics', 'Yunus Cengel', 'McGraw-Hill', '9780073398174', 'Mechanical', 3, 2, 'C1', '2025-05-16 04:28:11', '649.00');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `certificate_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `student_id` int(11) NOT NULL,
  `department` varchar(50) NOT NULL,
  `file_name` text DEFAULT NULL,
  `file_path` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`certificate_id`, `title`, `description`, `student_id`, `department`, `file_name`, `file_path`, `created_at`, `updated_at`) VALUES
(7, 'Paper Presentation', 'Paper Presentation', 1, 'Computer Science', 'sample.pdf', 'certificate_1_1747372782.pdf', '2025-05-16 05:19:42', '2025-05-16 05:19:42');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `department` varchar(50) NOT NULL,
  `designation` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'default_faculty.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `name`, `email`, `mobile`, `department`, `designation`, `password`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'Dr. Suresh Kumar', 'suresh@example.com', '9876543220', 'Computer Science', 'Associate Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(2, 'Dr. Anita Desai', 'anita@example.com', '9876543221', 'Computer Science', 'Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(3, 'Dr. Ramesh Gupta', 'ramesh@example.com', '9876543222', 'Electronics', 'Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(4, 'Dr. Geeta Sharma', 'geeta@example.com', '9876543223', 'Electronics', 'Assistant Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(5, 'Dr. Mohan Rao', 'mohan@example.com', '9876543224', 'Mechanical', 'Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(6, 'Dr. Radha Krishna', 'radha@example.com', '9876543225', 'Mechanical', 'Associate Professor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_faculty.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11');

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `fee_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_type` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `payment_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Unpaid',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`fee_id`, `student_id`, `fee_type`, `total_amount`, `amount_paid`, `balance`, `due_date`, `payment_date`, `status`, `fine_amount`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 'Tuition', '80000.00', '80000.00', '0.00', '2025-03-17', '2025-03-22', 'Paid', '0.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(2, 1, 'Transport', '15000.00', '15000.00', '0.00', '2025-03-17', '2025-03-19', 'Paid', '0.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(3, 1, 'Exam', '5000.00', '5000.00', '0.00', '2025-04-16', '2025-04-21', 'Paid', '0.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(4, 2, 'Tuition', '80000.00', '80000.00', '0.00', '2025-03-17', '2025-03-27', 'Paid', '0.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(5, 2, 'Transport', '15000.00', '15000.00', '0.00', '2025-03-17', '2025-03-17', 'Paid', '0.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(6, 2, 'Exam', '5000.00', '0.00', '5000.00', '2025-04-16', NULL, 'Overdue', '250.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(7, 3, 'Tuition', '75000.00', '50000.00', '25000.00', '2025-03-17', '2025-04-01', 'Partially Paid', '0.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(8, 3, 'Transport', '15000.00', '15000.00', '0.00', '2025-03-17', '2025-03-27', 'Paid', '0.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(9, 3, 'Exam', '5000.00', '0.00', '5000.00', '2025-04-16', NULL, 'Overdue', '275.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(10, 5, 'Tuition', '80000.00', '80000.00', '0.00', '2025-03-17', '2025-03-19', 'Paid', '0.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(11, 5, 'Transport', '15000.00', '10000.00', '5000.00', '2025-03-17', '2025-04-01', 'Partially Paid', '0.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(12, 7, 'Tuition', '75000.00', '75000.00', '0.00', '2025-03-17', '2025-03-17', 'Paid', '0.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(13, 7, 'Transport', '15000.00', '0.00', '15000.00', '2025-03-17', NULL, 'Overdue', '625.00', NULL, '2025-05-16 04:28:24', '2025-05-16 04:28:24');

-- --------------------------------------------------------

--
-- Table structure for table `fee_types`
--

CREATE TABLE `fee_types` (
  `fee_type_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `fee_types`
--

INSERT INTO `fee_types` (`fee_type_id`, `name`, `description`, `created_at`) VALUES
(1, 'Tuition', 'Regular tuition fees', '2025-05-16 04:28:11'),
(2, 'Transport', 'Bus transportation fees', '2025-05-16 04:28:11'),
(3, 'Exam', 'Examination fees', '2025-05-16 04:28:11'),
(4, 'Hostel', 'Hostel accommodation fees', '2025-05-16 04:28:11'),
(5, 'Library', 'Library fees', '2025-05-16 04:28:11');

-- --------------------------------------------------------

--
-- Table structure for table `hod`
--

CREATE TABLE `hod` (
  `hod_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `department` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'default_hod.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `hod`
--

INSERT INTO `hod` (`hod_id`, `name`, `email`, `mobile`, `department`, `password`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'Dr. Dinesh Verma', 'dinesh@example.com', '9876543230', 'Computer Science', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_hod.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(2, 'Dr. Smita Patel', 'smita@example.com', '9876543231', 'Electronics', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_hod.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(3, 'Dr. Prakash Nair', 'prakash@example.com', '9876543232', 'Mechanical', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_hod.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11');

-- --------------------------------------------------------

--
-- Table structure for table `internal_marks`
--

CREATE TABLE `internal_marks` (
  `mark_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `exam_type` varchar(50) NOT NULL,
  `marks` float NOT NULL,
  `max_marks` float NOT NULL DEFAULT 100,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `internal_marks`
--

INSERT INTO `internal_marks` (`mark_id`, `student_id`, `faculty_id`, `subject`, `exam_type`, `marks`, `max_marks`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Database Management Systems', 'Internal 1', 42, 50, 'Good performance', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(2, 1, 1, 'Database Management Systems', 'Internal 2', 45, 50, 'Excellent performance', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(3, 1, 1, 'Computer Networks', 'Internal 1', 38, 50, 'Above average', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(4, 1, 1, 'Computer Networks', 'Internal 2', 40, 50, 'Good performance', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(5, 2, 1, 'Database Management Systems', 'Internal 1', 48, 50, 'Outstanding', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(6, 2, 1, 'Database Management Systems', 'Internal 2', 47, 50, 'Excellent', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(7, 2, 1, 'Computer Networks', 'Internal 1', 44, 50, 'Very good', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(8, 2, 1, 'Computer Networks', 'Internal 2', 42, 50, 'Good', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(9, 3, 2, 'Programming Fundamentals', 'Internal 1', 35, 50, 'Average performance', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(10, 3, 2, 'Programming Fundamentals', 'Internal 2', 42, 50, 'Improved performance', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(11, 5, 3, 'Digital Electronics', 'Internal 1', 40, 50, 'Good', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(12, 5, 3, 'Digital Electronics', 'Internal 2', 44, 50, 'Very good', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(13, 7, 5, 'Engineering Thermodynamics', 'Internal 1', 32, 50, 'Needs improvement', '2025-05-16 04:28:24', '2025-05-16 04:28:24');

-- --------------------------------------------------------

--
-- Table structure for table `librarian`
--

CREATE TABLE `librarian` (
  `librarian_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'default_librarian.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `librarian`
--

INSERT INTO `librarian` (`librarian_id`, `name`, `email`, `mobile`, `password`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'Kiran Reddy', 'kiran@example.com', '9876543233', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_librarian.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11');

-- --------------------------------------------------------

--
-- Table structure for table `library_records`
--

CREATE TABLE `library_records` (
  `record_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `book_id` varchar(50) NOT NULL,
  `book_name` varchar(255) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Issued',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `library_records`
--

INSERT INTO `library_records` (`record_id`, `student_id`, `book_id`, `book_name`, `issue_date`, `due_date`, `return_date`, `status`, `fine_amount`, `created_at`, `updated_at`) VALUES
(1, 1, 'CS-101', 'Database Systems Concepts', '2025-04-16', '2025-05-01', '2025-05-04', 'Returned', '0.00', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(2, 1, 'CS-102', 'Computer Networks', '2025-04-26', '2025-05-11', '2025-05-14', 'Returned', '0.00', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(3, 1, 'CS-103', 'Operating System Concepts', '2025-05-06', '2025-05-21', NULL, 'Issued', '0.00', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(4, 2, 'CS-104', 'Design Patterns', '2025-04-06', '2025-04-21', '2025-04-26', 'Returned', '0.00', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(5, 2, 'CS-105', 'Data Mining', '2025-05-01', '2025-05-16', NULL, 'Issued', '0.00', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(6, 3, 'CS-106', 'Programming in C++', '2025-04-21', '2025-05-06', NULL, 'Overdue', '50.00', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(7, 5, 'EC-101', 'Digital Circuit Design', '2025-04-26', '2025-05-11', NULL, 'Overdue', '25.00', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(8, 5, 'EC-102', 'Electronic Devices', '2025-05-06', '2025-05-21', NULL, 'Issued', '0.00', '2025-05-16 04:28:24', '2025-05-16 04:28:24'),
(9, 7, 'ME-101', 'Thermodynamics', '2025-05-11', '2025-05-26', NULL, 'Issued', '0.00', '2025-05-16 04:28:24', '2025-05-16 04:28:24');

-- --------------------------------------------------------

--
-- Table structure for table `no_due_requests`
--

CREATE TABLE `no_due_requests` (
  `request_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `faculty_approval` varchar(20) DEFAULT 'Pending',
  `faculty_id` int(11) DEFAULT NULL,
  `faculty_remarks` text DEFAULT NULL,
  `faculty_approval_date` datetime DEFAULT NULL,
  `librarian_approval` varchar(20) DEFAULT 'Pending',
  `librarian_id` int(11) DEFAULT NULL,
  `librarian_remarks` text DEFAULT NULL,
  `librarian_approval_date` datetime DEFAULT NULL,
  `accountant_approval` varchar(20) DEFAULT 'Pending',
  `accountant_id` int(11) DEFAULT NULL,
  `accountant_remarks` text DEFAULT NULL,
  `accountant_approval_date` datetime DEFAULT NULL,
  `hod_approval` varchar(20) DEFAULT 'Pending',
  `hod_id` int(11) DEFAULT NULL,
  `hod_remarks` text DEFAULT NULL,
  `hod_approval_date` datetime DEFAULT NULL,
  `final_status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `no_due_requests`
--

INSERT INTO `no_due_requests` (`request_id`, `student_id`, `request_date`, `faculty_approval`, `faculty_id`, `faculty_remarks`, `faculty_approval_date`, `librarian_approval`, `librarian_id`, `librarian_remarks`, `librarian_approval_date`, `accountant_approval`, `accountant_id`, `accountant_remarks`, `accountant_approval_date`, `hod_approval`, `hod_id`, `hod_remarks`, `hod_approval_date`, `final_status`) VALUES
(1, 2, '2025-04-30 18:30:00', 'Approved', 1, 'All assignments submitted', '2025-05-02 00:00:00', 'Approved', 1, 'All books returned', '2025-05-03 00:00:00', 'Rejected', 1, 'Exam fee is pending', '2025-05-04 00:00:00', 'Pending', 1, NULL, NULL, 'Rejected'),
(2, 4, '2025-05-05 18:30:00', 'Approved', 1, 'All assignments submitted', '2025-05-07 00:00:00', 'Approved', 1, 'All books returned', '2025-05-08 00:00:00', 'Approved', 1, 'All fees paid', '2025-05-09 00:00:00', 'Pending', 1, NULL, NULL, 'Pending'),
(3, 8, '2025-04-25 18:30:00', 'Approved', 5, 'All assignments submitted', '2025-04-27 00:00:00', 'Approved', 1, 'All books returned', '2025-04-28 00:00:00', 'Approved', 1, 'All fees paid', '2025-04-29 00:00:00', 'Approved', 3, NULL, '2025-05-01 00:00:00', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `payment_records`
--

CREATE TABLE `payment_records` (
  `payment_id` int(11) NOT NULL,
  `fee_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_mode` varchar(50) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payment_records`
--

INSERT INTO `payment_records` (`payment_id`, `fee_id`, `amount`, `payment_date`, `payment_mode`, `transaction_id`, `remarks`) VALUES
(1, 1, '80000.00', '2025-03-21 18:30:00', 'Online', 'TXN123456', 'Full payment'),
(2, 2, '15000.00', '2025-03-18 18:30:00', 'Online', 'TXN123457', 'Full payment'),
(3, 3, '5000.00', '2025-04-20 18:30:00', 'Cash', NULL, 'Full payment'),
(4, 4, '80000.00', '2025-03-26 18:30:00', 'Online', 'TXN123458', 'Full payment'),
(5, 5, '15000.00', '2025-03-16 18:30:00', 'Online', 'TXN123459', 'Full payment'),
(6, 7, '50000.00', '2025-03-31 18:30:00', 'Online', 'TXN123460', 'Partial payment'),
(7, 8, '15000.00', '2025-03-26 18:30:00', 'Cash', NULL, 'Full payment'),
(8, 10, '80000.00', '2025-03-18 18:30:00', 'Online', 'TXN123461', 'Full payment'),
(9, 11, '10000.00', '2025-03-31 18:30:00', 'Cash', NULL, 'Partial payment'),
(10, 12, '75000.00', '2025-03-16 18:30:00', 'Online', 'TXN123462', 'Full payment');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `register_number` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `department` varchar(50) NOT NULL,
  `year_of_study` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'default_student.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `register_number`, `email`, `mobile`, `gender`, `department`, `year_of_study`, `password`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'Rahul Sharma', 'CS2021001', 'rahul@example.com', '9876543210', 'Male', 'Computer Science', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(2, 'Priya Singh', 'CS2021002', 'priya@example.com', '9876543211', 'Female', 'Computer Science', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(3, 'Amit Kumar', 'CS2022003', 'amit@example.com', '9876543212', 'Male', 'Computer Science', 2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(4, 'Sunita Patel', 'CS2022004', 'sunita@example.com', '9876543213', 'Female', 'Computer Science', 2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(5, 'Vijay Reddy', 'EC2021005', 'vijay@example.com', '9876543214', 'Male', 'Electronics', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(6, 'Aisha Khan', 'EC2021006', 'aisha@example.com', '9876543215', 'Female', 'Electronics', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(7, 'Rajesh Kumar', 'ME2022007', 'rajesh@example.com', '9876543216', 'Male', 'Mechanical', 2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11'),
(8, 'Meena Verma', 'ME2022008', 'meena@example.com', '9876543217', 'Female', 'Mechanical', 2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default_student.jpg', '2025-05-16 04:28:11', '2025-05-16 04:28:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accountant`
--
ALTER TABLE `accountant`
  ADD PRIMARY KEY (`accountant_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`certificate_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`fee_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `fee_types`
--
ALTER TABLE `fee_types`
  ADD PRIMARY KEY (`fee_type_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `hod`
--
ALTER TABLE `hod`
  ADD PRIMARY KEY (`hod_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `department` (`department`);

--
-- Indexes for table `internal_marks`
--
ALTER TABLE `internal_marks`
  ADD PRIMARY KEY (`mark_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `librarian`
--
ALTER TABLE `librarian`
  ADD PRIMARY KEY (`librarian_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `library_records`
--
ALTER TABLE `library_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `no_due_requests`
--
ALTER TABLE `no_due_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `librarian_id` (`librarian_id`),
  ADD KEY `accountant_id` (`accountant_id`),
  ADD KEY `hod_id` (`hod_id`);

--
-- Indexes for table `payment_records`
--
ALTER TABLE `payment_records`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fee_id` (`fee_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `register_number` (`register_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accountant`
--
ALTER TABLE `accountant`
  MODIFY `accountant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `fee_types`
--
ALTER TABLE `fee_types`
  MODIFY `fee_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hod`
--
ALTER TABLE `hod`
  MODIFY `hod_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `internal_marks`
--
ALTER TABLE `internal_marks`
  MODIFY `mark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `librarian`
--
ALTER TABLE `librarian`
  MODIFY `librarian_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `library_records`
--
ALTER TABLE `library_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `no_due_requests`
--
ALTER TABLE `no_due_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_records`
--
ALTER TABLE `payment_records`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `assignment_submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `internal_marks`
--
ALTER TABLE `internal_marks`
  ADD CONSTRAINT `internal_marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `internal_marks_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `library_records`
--
ALTER TABLE `library_records`
  ADD CONSTRAINT `library_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `library_records_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `no_due_requests`
--
ALTER TABLE `no_due_requests`
  ADD CONSTRAINT `no_due_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `no_due_requests_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `no_due_requests_ibfk_3` FOREIGN KEY (`librarian_id`) REFERENCES `librarian` (`librarian_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `no_due_requests_ibfk_4` FOREIGN KEY (`accountant_id`) REFERENCES `accountant` (`accountant_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `no_due_requests_ibfk_5` FOREIGN KEY (`hod_id`) REFERENCES `hod` (`hod_id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_records`
--
ALTER TABLE `payment_records`
  ADD CONSTRAINT `payment_records_ibfk_1` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`fee_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
