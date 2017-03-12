-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 08, 2017 at 09:09 PM
-- Server version: 5.7.17-0ubuntu0.16.04.1
-- PHP Version: 5.6.30-7+deb.sury.org~xenial+1

-- The following code contains immuniations schedules from a pediatrician office
-- This can be imported using phpmyadmin.  If the immunization schedule doesn't exist, this will create one.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
--
--

-- --------------------------------------------------------

--
-- Table structure for table `immunizations_schedules`
--
#ifNotTable immunizations_schedules 
CREATE TABLE `immunizations_schedules` (
  `id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `age` int(11) NOT NULL,
  `age_max` int(11) DEFAULT NULL,
  `frequency` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
#EndIf
--
-- Dumping data for table `immunizations_schedules`
--

INSERT INTO `immunizations_schedules` (`id`, `description`, `age`, `age_max`, `frequency`) VALUES
(1, 'Newborn', 0, NULL, NULL),
(2, '2 Months', 2, NULL, NULL),
(3, '4 Months', 4, NULL, NULL),
(4, '6 Months', 6, NULL, NULL),
(5, '9 Months', 9, NULL, NULL),
(6, '12 Months', 12, NULL, NULL),
(7, '15 Months', 15, NULL, NULL),
(8, '18 Months', 18, NULL, NULL),
(9, '4 Years', 48, NULL, NULL),
(10, '11 Years', 132, NULL, NULL),
(11, '15 Years', 160, NULL, NULL),
(12, 'Influenza', 36, 216, 'annual'),
(13, '3 Years', 36, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `immunizations_schedules`
--
ALTER TABLE `immunizations_schedules`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `immunizations_schedules`
--
ALTER TABLE `immunizations_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

--
-- Table structure for table `immunizations_schedules_codes`
--
#ifNotTable immunizations_schedules_codes
CREATE TABLE `immunizations_schedules_codes` (
  `id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(45) DEFAULT NULL,
  `cvx_code` varchar(45) DEFAULT NULL,
  `proc_codes` varchar(45) DEFAULT NULL,
  `justify_codes` varchar(45) DEFAULT NULL,
  `default_site` varchar(45) DEFAULT NULL,
  `comments` varchar(45) DEFAULT NULL,
  `drug_route` varchar(2) DEFAULT 'TD'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
#EndIF

--
-- Dumping data for table `immunizations_schedules_codes`
--

INSERT INTO `immunizations_schedules_codes` (`id`, `description`, `manufacturer`, `cvx_code`, `proc_codes`, `justify_codes`, `default_site`, `comments`, `drug_route`) VALUES
(0, 'MMRV', 'MSD', '94', 'CPT4:90710', 'ICD10:Z23', 'SQ', 'MMRV, Measles, Mumps, Rubella, and varicella ', 'SC'),
(1, 'Hepatitis B', 'GSK', '8', 'CPT4:90744', 'ICD10:Z23', 'RT', 'Newborns', 'IM'),
(2, 'Pentacel', 'PMC', '120', 'CPT4:90698', 'ICD10:Z23', 'RT', NULL, 'IM'),
(3, 'Hepatitis B', 'GSK', '8', 'CPT4:90744', 'ICD10:Z23', 'RT', NULL, 'IM'),
(4, 'Prevnar 13', 'PFI', '133', 'CPT4:90670', 'ICD10:Z23', 'LT', NULL, 'IM'),
(5, 'Rotateq', 'MSD', '116', 'CPT4:90680', 'ICD10:Z23', 'PO', NULL, 'PO'),
(6, 'DTaP', 'PMC', '20', 'CPT4:90700', 'ICD10:Z23', 'RT', NULL, 'IM'),
(7, 'IPV', 'PMC', '10', 'CPT4:90713', 'ICD10:Z23', 'RT', '(IPOL?)', 'IM'),
(8, 'Influenza(Preservative Free)', 'PMC', '140', 'CPT4:90655', 'ICD10:Z23', 'LT', '(Fluzone?)(6 months)', 'IM'),
(9, 'HIB', 'MSD', '47', 'CPT4:90648', 'ICD10:Z23', 'LT', '(HibTiter?/Wyeth)(LT 9 Mo)', 'IM'),
(10, 'MMR', 'MSD', '03', 'CPT4:90707', 'ICD10:Z23', 'RA', '(Merck?)', 'SC'),
(11, 'Varicella', 'MSD', '21', 'CPT4:90716', 'ICD10:Z23', 'LA', '(Merck?/Varivax?)', 'SC'),
(12, 'Hepatitis A', 'GSK', '83', 'CPT4:90633', 'ICD10:Z23', 'RT', 'RT 12 Months', 'IM'),
(13, 'HIB', 'MSD', '47', 'CPT4:90648', 'ICD10:Z23', 'RT', '(RT 15 months)', 'IM'),
(14, 'Hepatitis A', 'GSK', '83', 'CPT4:90633', 'ICD10:Z23', 'RD', 'RD 18 Months', 'IM'),
(15, 'Kinrix', 'SKB', '130', 'CPT4:90696', 'ICD10:Z23', 'RT', NULL, 'IM'),
(16, 'Menactra', 'PMC', '114', 'CPT4:90734', 'ICD10:Z23', 'RD', NULL, 'IM'),
(17, 'Adacel-TDaP', 'PMC', '115', 'CPT4:90715', 'ICD10:Z23', 'LD', NULL, 'IM'),
(18, 'Gardasil', 'MSD', '62', 'CPT4:90649', 'ICD10:Z23', 'RD', NULL, 'IM'),
(19, 'Influenza', 'PMC', '141', 'CPT4:90658', 'ICD10:Z23', 'RD', 'Annual Influenza Age 3-18', 'IM'),
(20, 'FluMist', 'MED', '149', 'CPT4:90660', 'ICD10:Z23', 'NASAL', 'FluMist Age 3-18', 'NS'),
(21, 'Gardasil 9', 'MSD', '165', 'CPT4:90651', 'ICD10:Z23', 'RD', 'Human Papillomavirus 9-valent Vaccine, Recomb', 'IM'),
(22, 'ProQuad', 'MSD', '94', 'CPT4:90710', 'ICD10:Z23', '', 'MMRV, Measles, Mumps, Rubella, and varicella ', 'SC'),
(23, 'MMRV', 'MSD', '94', 'CPT4:90710', 'ICD10:Z23', 'SQ', 'MMRV, Measles, Mumps, Rubella, and varicella ', 'SC');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `immunizations_schedules_codes`
--
ALTER TABLE `immunizations_schedules_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);


--
-- Table structure for table `immunizations_schedules_options`
--

#IfNotTable immunizations_schedules_options
CREATE TABLE `immunizations_schedules_options` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `code_id` int(11) DEFAULT NULL,
  `seq` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
#endIf

--
-- Dumping data for table `immunizations_schedules_options`
--

INSERT INTO `immunizations_schedules_options` (`id`, `schedule_id`, `code_id`, `seq`) VALUES
(0, 13, 20, 10),
(1, 1, 1, 10),
(2, 2, 2, 10),
(3, 2, 3, 20),
(4, 2, 4, 30),
(5, 2, 5, 40),
(6, 3, 2, 10),
(7, 3, 3, 20),
(8, 3, 4, 30),
(9, 3, 5, 40),
(10, 4, 6, 10),
(11, 4, 7, 20),
(12, 4, 4, 30),
(13, 4, 8, 40),
(14, 4, 5, 50),
(15, 4, 2, 60),
(16, 5, 3, 10),
(17, 5, 9, 20),
(18, 5, 8, 30),
(19, 6, 10, 10),
(20, 6, 11, 20),
(21, 6, 12, 30),
(22, 6, 8, 40),
(23, 7, 6, 10),
(24, 7, 13, 20),
(25, 7, 4, 30),
(26, 8, 14, 10),
(27, 9, 6, 10),
(28, 9, 7, 20),
(29, 9, 15, 30),
(30, 9, 11, 40),
(31, 9, 10, 50),
(32, 10, 16, 10),
(33, 10, 17, 20),
(34, 10, 18, 30),
(35, 11, 16, 10),
(36, 12, 19, 10),
(37, 2, 6, 50),
(38, 2, 7, 60),
(39, 2, 9, 70),
(40, 3, 6, 50),
(41, 3, 7, 60),
(42, 3, 9, 70),
(43, 4, 9, 70),
(44, 12, 20, 20),
(45, 9, 20, 60),
(46, 10, 20, 40),
(47, 11, 20, 20),
(48, 7, 8, 40),
(49, 8, 8, 20),
(50, 13, 8, 20),
(51, 13, 19, 30),
(52, 9, 19, 60),
(53, 10, 19, 50),
(54, 11, 19, 30),
(56, 10, 21, 12),
(57, 6, 22, 50),
(58, 9, 22, 50);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `immunizations_schedules_options`
--
ALTER TABLE `immunizations_schedules_options`
  ADD PRIMARY KEY (`id`);


-- columns for immunizations

#IfNotColumnType immunizations administered_date datetime
ALTER TABLE `immunizations`
  MODIFY COLUMN administered_date datetime DEFAULT NULL;
#EndIf

#IfMissingColumn immunizations amount_administered
ALTER TABLE `immunizations`
  ADD COLUMN `amount_administered` int(11) DEFAULT NULL;
#EndIf

#IfMissingColumn immunizations amount_administered_unit
ALTER TABLE `immunizations`
  ADD COLUMN `amount_administered_unit` varchar(50) DEFAULT NULL;
#EndIf

#IfMissingColumn immunizations expiration_date
ALTER TABLE `immunizations`
  ADD COLUMN `expiration_date` date DEFAULT NULL;
#EndIf

#IfMissingColumn immunizations route
ALTER TABLE `immunizations`
  ADD COLUMN `route` varchar(100) DEFAULT NULL;
#EndIf

#IfMissingColumn immunizations administration_site
ALTER TABLE `immunizations`
  ADD COLUMN `administration_site` varchar(100) DEFAULT NULL;
#EndIf

#IfMissingColumn immunizations added_erroneously
ALTER TABLE `immunizations`
  ADD COLUMN `added_erroneously` tinyint(1) NOT NULL DEFAULT '0';
#EndIf

#IfMissingColumn immunizations historical
ALTER TABLE `immunizations`
ADD COLUMN `historical` tinyint(2) NOT NULL DEFAULT '00';
#EndIf

#IfMissingColumn immunizations vfc
ALTER TABLE `immunizations`
ADD COLUMN `vfc` varchar(100) not null DEFAULT 'V01';
#EndIf

#IfMissingColumn immunizations submitted
ALTER TABLE `immunizations`
ADD COLUMN `submitted` varchar(1) not null DEFAULT '0' COMMENT '0 = not submitted, 1 = submitted and passed, F = submitted and failed';
#EndIf