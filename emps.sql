-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:4306
-- Generation Time: May 30, 2025 at 08:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `emps`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'HR'),
(2, 'Engineering'),
(3, 'Marketing'),
(4, 'Finance');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `ecode` text NOT NULL,
  `name` varchar(100) NOT NULL,
  `sex` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `ecode`, `name`, `sex`, `email`, `password`, `position`, `department_id`, `phone`, `profile_photo`, `joining_date`, `status`, `created_at`) VALUES
(1, 'e001', 'Alice Johnson', 2, 'alice.johnson@example.com', 'alice123', 'Software Engineer', 2, '123-456-7890', 'alice.jpg', '2023-01-15', 'Active', '2025-05-28 11:02:28'),
(2, 'e002', 'Bob Smith', 1, 'bob.smith@example.com', 'bob123', 'Frontend Developer', 2, '234-567-8901', 'bob.jpg', '2022-12-01', 'Active', '2025-05-28 11:02:28'),
(3, 'e003', 'Carol White', 2, 'carol.white@example.com', 'carol123', 'Marketing Manager', 3, '345-678-9012', 'carol.jpg', '2021-07-20', 'Active', '2025-05-28 11:02:28'),
(4, 'e004', 'David Brown', 1, 'david.brown@example.com', 'david123', 'Finance Analyst', 4, '456-789-0123', 'david.jpg', '2020-11-30', 'Active', '2025-05-28 11:02:28');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `log_time` datetime DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `employee_id`, `task_id`, `action`, `log_time`, `remarks`) VALUES
(5, 1, 2, 'Paused', '2025-05-28 22:27:13', 'DOING IT'),
(6, 1, 2, 'Updated subtask status to \'Completed\'', '2025-05-30 11:39:56', 'Subtask ID: 3'),
(7, 1, 2, 'Task Completed', '2025-05-30 11:41:16', NULL),
(8, 1, 2, 'Updated subtask status to \'Not Started\'', '2025-05-30 12:21:37', 'Subtask: Implement login API (ID: 3)'),
(9, 1, 2, 'Updated subtask status to \'In Progress\'', '2025-05-30 12:24:06', 'Subtask: Implement login API (ID: 3)'),
(10, 1, 2, 'Updated subtask status to \'In Progress\'', '2025-05-30 12:25:15', 'Subtask: Implement login API (ID: 3)'),
(11, 1, 2, 'Updated subtask status to \'In Progress\'', '2025-05-30 12:25:25', 'Subtask: Implement login API (ID: 3)'),
(12, 1, 2, 'Updated subtask status to \'In Progress\'', '2025-05-30 12:26:14', 'Subtask: Implement login API (ID: 3)'),
(13, 1, 3, 'Updated subtask status to \'Not Started\'', '2025-05-30 12:26:40', 'Subtask: Write test cases (ID: 4)'),
(14, 1, 3, 'Updated subtask status to \'Not Started\'', '2025-05-30 12:31:22', 'Subtask: Write test cases (ID: 4)');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subtasks`
--

CREATE TABLE `subtasks` (
  `id` int(11) NOT NULL,
  `parent_task_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `due_date` date DEFAULT NULL,
  `priority` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subtasks`
--

INSERT INTO `subtasks` (`id`, `parent_task_id`, `title`, `status`, `due_date`, `priority`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 1, 'Design database schema', 'Not Started', '2025-05-10', 'High', '2025-05-28 11:09:41', '2025-05-28 21:45:07', 1),
(2, 1, 'Create ER diagrams', 'Completed', '2025-05-12', 'Medium', '2025-05-28 11:09:41', '2025-05-28 21:45:13', 1),
(3, 2, 'Implement login API', 'In Progress', '2025-05-15', 'High', '2025-05-28 11:09:41', '2025-05-30 12:26:14', 1),
(4, 3, 'Write test cases', 'Not Started', '2025-05-18', 'Low', '2025-05-28 11:09:41', '2025-05-30 12:31:22', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task_id` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `client_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed') NOT NULL,
  `priority` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `created_at` datetime DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `task_id`, `title`, `client_name`, `description`, `department_id`, `status`, `priority`, `created_at`, `start_date`, `due_date`) VALUES
(1, '1001', 'Design Homepage', 'Client A', 'Create the homepage design mockup', 2, 'Not Started', 'High', '2025-05-28 11:02:28', NULL, '2025-06-10'),
(2, '1002', 'Develop Login Module', 'Client B', 'Implement login functionality', 2, 'In Progress', 'Medium', '2025-05-28 11:02:28', NULL, '2025-05-30'),
(3, '1003', 'Market Analysis', 'Client C', 'Perform competitor analysis', 3, 'In Progress', 'Low', '2025-05-28 11:02:28', NULL, '2025-05-20'),
(4, '1004', 'Prepare Financial Report', 'Client D', 'Q1 financial reporting', 4, 'In Progress', 'High', '2025-05-28 11:02:28', NULL, '2025-06-05');

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_assignments`
--

INSERT INTO `task_assignments` (`id`, `task_id`, `employee_id`) VALUES
(1, 1, 1),
(5, 2, 1),
(2, 2, 2),
(6, 3, 1),
(3, 3, 3),
(4, 4, 4);

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_comments`
--

INSERT INTO `task_comments` (`id`, `task_id`, `employee_id`, `comment`, `created_at`) VALUES
(1, 1, 1, 'Initial database schema looks good, moving to ER diagram.', '2025-05-10 10:30:00'),
(2, 1, 2, 'Added some indexes for optimization.', '2025-05-10 15:45:00'),
(3, 2, 3, 'Login API is almost done, need to add error handling.', '2025-05-12 12:00:00'),
(4, 3, 1, 'Test cases for login feature ready for review.', '2025-05-13 16:20:00'),
(5, 4, 2, 'Dashboard UI completed, needs integration with backend.', '2025-05-14 18:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `task_timesheets`
--

CREATE TABLE `task_timesheets` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date_worked` date NOT NULL,
  `hours_spent` decimal(5,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_timesheets`
--

INSERT INTO `task_timesheets` (`id`, `task_id`, `employee_id`, `date_worked`, `hours_spent`, `description`, `created_at`) VALUES
(1, 1, 1, '2025-05-10', 4.50, 'Designed database schema and ER diagrams', '2025-05-10 17:00:00'),
(2, 1, 2, '2025-05-11', 3.00, 'Reviewed schema and provided feedback', '2025-05-11 16:30:00'),
(3, 2, 3, '2025-05-12', 5.00, 'Implemented login API endpoints', '2025-05-12 19:15:00'),
(4, 3, 1, '2025-05-13', 2.50, 'Wrote unit tests for task management', '2025-05-13 14:45:00'),
(5, 4, 2, '2025-05-14', 6.00, 'Frontend UI implementation for dashboard', '2025-05-14 18:30:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `subtasks`
--
ALTER TABLE `subtasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_task_id` (`parent_task_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_id` (`task_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_id` (`task_id`,`employee_id`) USING BTREE,
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `task_timesheets`
--
ALTER TABLE `task_timesheets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subtasks`
--
ALTER TABLE `subtasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `task_timesheets`
--
ALTER TABLE `task_timesheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `subtasks`
--
ALTER TABLE `subtasks`
  ADD CONSTRAINT `subtasks_ibfk_1` FOREIGN KEY (`parent_task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_timesheets`
--
ALTER TABLE `task_timesheets`
  ADD CONSTRAINT `task_timesheets_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_timesheets_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
