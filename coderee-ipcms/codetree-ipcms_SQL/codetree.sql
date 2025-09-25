-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2025 at 01:20 PM
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
-- Database: `codetree`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `company`, `email`, `phone`, `address`, `website`, `status`, `created_at`) VALUES
(1, 'Alice Johnson', 'TechCorp', 'alice@techcorp.com', '+1 555 123 4567', '123 Tech Street, NY', 'www.techcorp.com', 'Active', '2025-09-13 22:58:18'),
(2, 'Bob Smith', 'InnoSoft', 'bob@innosoft.com', '+1 555 234 5678', '45 Innovation Ave, LA', 'www.innosoft.io', 'Inactive', '2025-09-13 22:58:18'),
(3, 'Sani', 'B2B Ltd', 'sani@gmail.com', '01987654321', 'Uttara, Sector 5', 'App Development', 'Inactive', '2025-09-24 17:50:57'),
(4, 'sam', NULL, 'sa@gmail.com', '01987654321', NULL, NULL, 'Active', '2025-09-25 02:28:57');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Pending','Partial','Paid') DEFAULT 'Pending',
  `paid_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `project_id`, `invoice_number`, `amount`, `issue_date`, `due_date`, `status`, `paid_amount`) VALUES
(1, 3, '1001', 3.00, '2025-04-01', '2025-05-01', 'Paid', 3.00),
(2, 2, '1002', 400.00, '2025-04-10', '2025-05-10', 'Paid', 400.00),
(3, 1, '1003', 5.00, '2025-04-15', '2025-05-15', 'Pending', 0.00),
(4, 3, '1004', 3000.00, '2025-09-24', '2025-10-24', 'Partial', 1500.00);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_type` enum('Team','Employee','Client') DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_type`, `receiver_id`, `message`, `sent_at`) VALUES
(1, 3, 'Team', 2, 'hi', '2025-09-13 23:43:23'),
(2, 3, 'Team', 1, 'hi', '2025-09-13 23:43:59'),
(3, 3, 'Client', 1, 'hello', '2025-09-24 13:26:38'),
(4, 3, 'Team', 1, 'To the conference hall at 9 Am', '2025-09-24 13:27:49'),
(5, 3, 'Team', 2, 'The UI segment is completed', '2025-09-24 13:28:46'),
(6, 3, 'Client', 2, 'Is the material available now?', '2025-09-24 13:29:52'),
(9, 4, 'Employee', 1, 'Bring The file.', '2025-09-25 03:46:58'),
(10, 4, 'Team', 2, 'Back to work.', '2025-09-25 03:57:57'),
(11, 4, 'Client', 1, 'The segment is ready for review.', '2025-09-25 05:08:25'),
(12, 4, 'Client', 4, 'We need more raw materials.', '2025-09-25 05:09:08');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(8, NULL, 'Notification', 'Director Board Meeting at 8 AM', 0, '2025-09-25 10:51:53'),
(9, NULL, 'Reminder', 'Team meeting tomorrow at 10 AM.', 0, '2025-09-25 11:18:57');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `team_lead` varchar(100) DEFAULT NULL,
  `members` text DEFAULT NULL,
  `client` varchar(100) DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `assigned_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `description`, `team_lead`, `members`, `client`, `budget`, `progress`, `assigned_date`, `due_date`, `status`, `created_at`) VALUES
(1, 'Website Redesign', 'A complete redesign of website', 'Ahmed Shakil', 'John Doe,Sara Ali,Sam', 'Globes Corp', 5000.00, 100, '2025-04-01', '2025-06-01', 'Completed', '2025-09-13 22:58:18'),
(2, 'Mobile App', 'Developing a mobile application', 'Michel Brown', 'Clara Lee,Bob Smith,Sam', 'Acme Inc', 8000.00, 40, '2025-03-15', '2025-07-01', 'In Progress', '2025-09-13 22:58:18'),
(3, 'E-commerce Website Launch', 'Launch a fully functional e-commerce website with payment gateway integration.', 'Michael Scott', 'Pam Beesly, Jim Halpert, Dwight Schrute', 'Dunder Mifflin Inc.', 20000.00, 10, '2025-09-24', '2025-10-03', 'Not Started', '2025-09-24 12:30:59');

-- --------------------------------------------------------

--
-- Table structure for table `quick_messages`
--

CREATE TABLE `quick_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_type` enum('Team','Employee','Client','All') DEFAULT 'All',
  `receiver_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quick_messages`
--

INSERT INTO `quick_messages` (`id`, `sender_id`, `receiver_type`, `receiver_id`, `message`, `sent_at`) VALUES
(2, 3, 'All', NULL, 'Team A meeting at 8 Am', '2025-09-24 09:17:01'),
(3, 4, 'All', NULL, 'B2B project client asked for an meeting at 2.30PM.', '2025-09-25 04:08:11'),
(4, 4, 'Team', NULL, 'The segment needs debugging.', '2025-09-25 05:24:35'),
(5, 3, 'All', NULL, 'Launch Hour is reduced to 20 mintues.', '2025-09-25 07:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `leader` varchar(100) DEFAULT NULL,
  `members` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`, `leader`, `members`, `created_at`) VALUES
(1, 'Team A', 'Ahmed Shakil', '[\"John Doe\",\"Sara Ali\"]', '2025-09-13 22:58:18'),
(2, 'Team B', 'Michel Brown', '[\"Clara\",\"Bob\",\"Smith\"]', '2025-09-13 22:58:18'),
(3, 'Team C', 'Michael Scott', '[\"Lona\",\"Ellen\",\"Jack\"]', '2025-09-24 16:48:31');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(100) NOT NULL,
  `skills` text DEFAULT NULL,
  `availability` enum('Available','Busy','Part-time') DEFAULT 'Available',
  `workload` int(11) DEFAULT 0,
  `projects` text DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `name`, `email`, `role`, `skills`, `availability`, `workload`, `projects`, `team_id`, `created_at`) VALUES
(1, 'Shaikat Akash', 'shaikatakash@gmail.com', 'Software Designer', 'UI/UX, Figma, React', 'Available', 40, 'Library Scheduler,E-commerce Portal', 1, '2025-09-24 14:53:06'),
(2, 'Ashikur Rahaman', 'ashikurrahaman@gmail.com', 'Frontend Developer', 'HTML, CSS, JavaScript', 'Busy', 80, 'Inventory System,Portfolio Website', 2, '2025-09-24 14:53:06'),
(3, 'Sami Khan', 'samikhan@gmail.com', 'Project Manager', 'Agile, Scrum, Communication', 'Part-time', 60, 'Smart Library,Pharmacy App', 1, '2025-09-24 14:53:06'),
(4, 'Samia', 'sam@gmail.com', 'Front-end Developer', 'Html,CSS,Javascript,Bootsrap', 'Available', 90, 'Levi Techno inc. UI,Bitly Dashboard', 3, '2025-09-24 16:38:00'),
(9, 'Sam', 'sa@gmail.com', 'Back-end Developer', 'PHP,python,C,C++', 'Busy', 30, 'Library Scheduler,E-commerce Portal', 3, '2025-09-25 02:37:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','User') DEFAULT 'User',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@codetree.com', '01987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', '2025-09-13 22:58:18'),
(2, 'alice', 'alice@company.com', '01234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2025-09-13 22:58:18'),
(3, 'Samia', 'sae@gmail.com', '01987345672', '$2y$10$F38ysQlZR.yU9M8VMQDzZeztTEYFB4oegFtm.SvMaJnIgn/myynRu', 'Admin', '2025-09-13 23:08:32'),
(4, 'Sam', 'sa@gmail.com', '01712345670', '$2y$10$SWeJiXZNLGfGaOEro6FBxOIucDGwA0V1THrq1ubYL5kXW/Fgv3gjC', 'User', '2025-09-14 00:08:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quick_messages`
--
ALTER TABLE `quick_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quick_messages`
--
ALTER TABLE `quick_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quick_messages`
--
ALTER TABLE `quick_messages`
  ADD CONSTRAINT `quick_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
