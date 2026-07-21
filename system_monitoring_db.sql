-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 04, 2026 at 09:48 AM
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
-- Database: `system_monitoring_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `micei_system_monitoring`
--

CREATE TABLE `micei_system_monitoring` (
  `id` int(11) NOT NULL,
  `identification_number` varchar(100) DEFAULT NULL,
  `date_recorded` date NOT NULL,
  `transaction_date` date NOT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `dealer` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `user_name` varchar(150) DEFAULT NULL,
  `invoice_reference` varchar(150) DEFAULT NULL,
  `payment_reference` varchar(150) DEFAULT NULL,
  `client_name` varchar(200) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `approved_by` varchar(150) DEFAULT NULL,
  `processed_type` varchar(100) DEFAULT NULL,
  `processed_by` varchar(150) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `incident_report_image_path` varchar(255) DEFAULT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `system_admin` varchar(150) DEFAULT NULL,
  `ticket` varchar(150) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `offense` varchar(150) DEFAULT NULL,
  `disciplinary_action` varchar(100) DEFAULT NULL,
  `action_taken` varchar(100) DEFAULT NULL,
  `memo_printed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `micei_system_monitoring`
--

INSERT INTO `micei_system_monitoring` (`id`, `identification_number`, `date_recorded`, `transaction_date`, `branch`, `dealer`, `department`, `module`, `user_name`, `invoice_reference`, `payment_reference`, `client_name`, `amount`, `reason`, `approved_by`, `processed_type`, `processed_by`, `remarks`, `incident_report_image_path`, `classification`, `system_admin`, `ticket`, `status`, `offense`, `disciplinary_action`, `action_taken`, `created_at`) VALUES
(3, '000003', '2026-05-20', '2026-05-20', 'GSC', '', 'Service', 'PMIS', 'QUEENIE PALAS', 'TN941897', '', 'JELYN PEARL MANUEL PASCA', NULL, 'EDIT STOCK NUMBER', '', '', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-05-22 01:38:48'),
(4, '000004', '2026-05-20', '2026-05-20', 'KID', NULL, 'Service', 'CSMS', 'EDSON PANES', 'RO#7639', '', 'DEBIE g. Barrios', NULL, 'Void RO - wrong input of RO number', '', 'Cancellation', 'ITA', '', NULL, 'User error', '', '', 'Voided', '', NULL, NULL, '2026-05-22 01:45:22'),
(5, '000005', '2026-05-20', '2026-05-08', 'GLA', NULL, 'Accounting', 'CMIS', 'GERLYN TEJADA', 'OR#279611', '', 'Maria izza saniel samulde', NULL, 'Cancelled due to wrong data entry on amount.', 'RGR', 'Cancellation', 'LBA', '', NULL, 'User error', '', '', 'Cancelled', 'Incident report', NULL, NULL, '2026-05-22 01:55:46'),
(6, '000006', '2026-05-19', '2026-05-19', 'GSC', NULL, 'Service', 'CSMS', 'Marvin jay ruiz', 'SI# 144896', '', 'Meralco industrial engineering services corporation', NULL, 'Wrong billed customer', 'RGR', 'Cancellation', 'ITA', '', NULL, 'User error', '', '', 'Cancelled', '', NULL, NULL, '2026-05-22 02:46:33'),
(7, '000007', '2026-05-18', '2026-05-18', 'GSC', NULL, 'Parts', 'PMIS', 'REDIN MONTILLANO', 'ARR-002725', '', 'MMPC', NULL, 'EDIT COST', '', 'Unposting', 'ITA', '', NULL, 'User error', '', '', 'Unposted', '', NULL, NULL, '2026-05-22 05:27:11'),
(8, '000008', '2026-05-22', '2026-05-23', 'GSC', NULL, 'Service', 'CSMS', 'COLEEN CAYE ALMANZOR', 'RO 212481', '', 'FREDDIE JR. MALINAO', NULL, 'UNPRINTED PROFORMA', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-05-23 00:14:41'),
(9, '000009', '2026-05-23', '2026-05-04', 'GSC', NULL, 'Service', 'CSMS', 'MELHAM ACOP', 'RO211910', '', 'JUROMEY KRIS BARABAT', NULL, 'EDIT LABOR DETAILS', '', 'Unposting', 'ITA', '', NULL, 'Data correction', '', '', 'Unposted', '', NULL, NULL, '2026-05-23 05:28:22'),
(10, '000010', '2026-05-25', '2026-05-05', 'GSC', 'MGSC', 'Service', 'CSMS', 'VHYNSE MIEL CADAVOS', '211381', '', 'MANUEL PRADO MARGES', 8989.76, 'EDIT RO NUMBER', 'RGR', 'Cancellation, Unposting', 'ITA', 'SI NUMBER IS ENCODED AS THE RO NUMBER', NULL, 'User error', '', '', 'Cancelled, Unposted', '', NULL, NULL, '2026-05-25 02:54:20'),
(11, '000011', '2026-05-25', '2026-05-25', 'GSC', 'MGSC', 'Accounting', 'PMIS', 'RACHELLE GADORES', '123456', '', 'SEAN LAPICEROS', NULL, 'TEST', '', 'Data correction', 'ITA', 'TEST ONLY', NULL, 'User error', '', '', 'Cancelled', '', NULL, NULL, '2026-05-25 03:13:51'),
(12, '000012', '2026-05-25', '2026-05-25', 'GSC', 'MGSC', 'Accounting', 'SMIS', 'RACHELLE GADORES', '12345', '', 'SEAN LAPICEROS', NULL, 'TEST', '', 'Data correction', 'ITA', 'TEST ONLY', NULL, 'User error', '', '', 'Cancelled', '', NULL, NULL, '2026-05-25 03:14:39'),
(13, '000013', '2026-05-25', '2026-05-25', 'GSC', 'MGSC', '', 'CSMS', 'RACHELLE GADORES', '123', '', '', NULL, 'TEST', '', 'Data correction', 'ITA', 'TEST ONLY', NULL, 'Others', '', '', 'Cancelled', 'Vocal Memo', 'Vocal Memo', 'Vocal Memo', '2026-05-25 03:15:18'),
(14, '000014', '2026-05-25', '2026-05-25', 'GSC', '', '', 'All Modules', 'RACHELLE GADORES', '1234', '', 'SEAN', NULL, 'TEST', '', 'Data correction', 'ITA', 'TEST ONLY', NULL, 'Others', '', '', 'Cancelled', 'Vocal Memo', 'Vocal Memo', 'Vocal Memo', '2026-05-25 03:33:36'),
(15, '000015', '2026-05-25', '2026-05-25', 'GSC', 'NGSC', 'Accounting', 'CMIS', 'SEAN GREY LAPICEROS', '41234', '', 'RACHELLE GADORES', NULL, 'TEST', '', 'Unposting', 'ITA', 'TEST ONLY', NULL, 'User error', '', '', 'Unposted', '', NULL, NULL, '2026-05-25 06:48:28'),
(16, '000016', '2026-05-25', '2026-05-25', 'KID', 'MKC', 'Sales', 'All Modules', 'APRIL JOY GEROLAGA', 'INVOICE 357', '', 'ALIBAI KUSAIN', NULL, 'MODIFY MUNICIPAL ADDRESS', '', 'Others', 'ITA', '', NULL, 'Others', '', '3874', 'Done', '', NULL, NULL, '2026-05-26 00:16:31'),
(17, '000017', '2026-05-26', '2026-05-26', 'GSC', 'MGSC', 'Accounting', 'CMIS', 'KATRENA CILLO', 'OR 280035', '', 'BRIGADA MASS MEDIA CORPORATION', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-05-26 02:50:30'),
(18, '000018', '2026-05-28', '2026-05-28', 'GSC', 'MGSC', 'Sales', 'All Modules', 'DIWANA GRANCHO', '', '', 'JOCELYN COLLADO', NULL, 'EDIT CUSTOMER FILE', '', 'Others', 'ITA', '', NULL, 'Others', '', '', 'Done', '', NULL, NULL, '2026-05-28 03:10:25'),
(19, '000019', '2026-05-28', '2026-05-28', 'GSC', 'MGSC', 'Accounting', 'All Modules', 'RACHELLE GADORES', '313123', '', 'SEAN LAPICEROS', NULL, 'ERROR', '', 'Cancellation, Unposting, Data correction', 'ITA', '', 'uploads/incident_reports/mitsubishi/000019-20260528_051759.jpg', 'User error', '', '', 'Cancelled, Unposted', 'Written Memo', 'Written Memo', 'Written Memo', '2026-05-28 03:17:59'),
(20, '000020', '2026-05-28', '2026-05-28', 'GSC', 'MKC', 'Parts', 'PMIS', 'PANES, EDSON L.', 'RIV265(ACC)', '', 'DERWESA S KADIR', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-05-28 05:34:58'),
(21, '000021', '2026-05-28', '2026-05-28', 'GSC', 'MGSC', 'Service', 'CSMS', 'JEMBOLEE GALANG', '212514', '', 'LEONIDES VILLAMOR TOMAS', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-05-28 06:41:55'),
(22, '000022', '2026-05-29', '2026-04-28', 'GSC', 'MGSC', 'Service', 'CSMS', 'ROGEIO DARDO', 'RO 211532', '', 'DONALD LOUIE MONTECLARO', NULL, 'EDIT', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-05-29 03:30:40'),
(23, '000023', '2026-05-30', '2026-05-30', 'GSC', 'MKC', 'CNC', 'CMIS', 'RACHEL PASQUIL', 'CR 18088', '', 'ROBERT FORES', NULL, 'POSTED ON MKC INSTEAD OF MGSC', '', '', 'ITA', '', NULL, 'User error', '', '', 'Unposted', '', NULL, NULL, '2026-05-30 00:38:02'),
(24, '000024', '2026-05-30', '2026-05-30', 'KID', 'MKC', 'Parts', 'PMIS', 'JANESSA KITONG', 'RIV 2360', '', 'FAROUK S ROMANCAP', NULL, 'WRONG PART NUMBER', '', 'Cancellation, Unposting, Data correction', '', 'PRS 100821', NULL, 'User error', '', '', 'Cancelled, Unposted', '', NULL, NULL, '2026-05-30 05:22:05'),
(25, '000025', '2026-05-30', '2026-05-30', 'KID', 'MKC', 'Parts', 'PMIS', 'JANESA KITONG', 'PSI-89933 | MSI - 93803', '', 'KENT BRYLLE PROLOGO CATIPAY', NULL, 'EDIT PRICE FOR OMS', '', 'Unposting', 'ITA', '', NULL, 'User error', '', '', 'Unposted', '', NULL, NULL, '2026-05-30 06:01:56'),
(26, '000026', '2026-06-01', '2026-05-30', 'GSC', 'MGSC', 'Accounting', 'CMIS', 'JASPER LLENA', '280161', '', 'BJ AMAHAN', NULL, 'UNPOST', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-01 00:00:28'),
(27, '000027', '2026-06-01', '2026-05-30', 'GSC', 'MGSC', 'Accounting', 'CMIS', 'JASPER LLENA', '47573', '', 'AMOR PRIME BUILDERS INC', NULL, 'UNPOST', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-01 01:02:59'),
(28, '000028', '2026-06-01', '2026-06-01', 'GSC', 'MGSC', 'Parts', 'PMIS', 'REDIN MONTILLA', '2754', '', '', NULL, 'CHANGE VENDOR', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-01 05:44:51'),
(29, '000029', '2026-06-02', '2026-06-01', 'GSC', 'MGSC', 'Service', 'CSMS', 'CERILO CORSIT III', 'SI 142291 & 145174 | RO 205059', '', 'JUDHILAN FOODS CORPORATION', NULL, 'WRONG AMOUNT', 'RGR', 'Cancellation, Unposting, Data correction', 'ITA', 'UNPOST RO, CANCEL INVOICES', 'uploads/incident_reports/mitsubishi/000029-20260602_034340.jpg', 'User error', '', '', 'Cancelled, Unposted', '', NULL, NULL, '2026-06-02 01:43:40'),
(30, '000030', '2026-06-02', '2026-05-19', 'GSC', 'MGSC', 'Service', 'CSMS', 'CERILO CORSIT III', 'RO 211550', '', 'JELLIE MOLLINO PFEIFFER', NULL, 'ACCIDENTAL POSTING', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-02 03:20:37'),
(31, '000031', '2026-06-02', '2026-06-01', 'GSC', 'NGSC', 'CNC', 'CMIS', 'JUDIE ANN DE LEON', 'CR 6090', '', 'ARLEE LU SENOBAGO', NULL, 'EDIT NAME', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-02 05:14:05'),
(32, '000032', '2026-06-02', '2026-05-29', 'GSC', 'MGSC', 'CNC', 'CMIS', 'JUDIE ANN DE LEON', 'OR 279585', '', 'MARIA CHARINA SALAZAR CRUS', NULL, 'EDIT TAGGING AND REMARKS', '', 'Unposting', 'ITA', '', NULL, 'User error', '', '', 'Unposted', '', NULL, NULL, '2026-06-02 05:46:44'),
(33, '000033', '2026-06-02', '2026-05-29', 'GSC', 'MGSC', 'Accounting', 'AMIS', 'ALYSSA GATION', 'CRJ OR 279590', '', 'PACIFIC UNION INSURANCE COMPANY', NULL, '', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-02 07:57:38'),
(34, '000034', '2026-06-02', '2026-06-02', 'GSC', 'NGSC', 'Service', 'CSMS', 'VANCE ALINSASAGUIN', 'RO 17126', '', 'MARK LOU SABANO', NULL, 'WRONG TAGGING', '', 'Unposting', 'ITA', '', NULL, 'User error', '', '', 'Unposted', '', NULL, NULL, '2026-06-02 08:12:24'),
(35, '000035', '2026-06-03', '2026-06-03', 'GLA', 'MGSC', 'Service', 'CMIS', 'MARVIN JAY RUIZ', 'RO 212337', '', 'MICEI', NULL, 'WRONG TAGGING', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-03 02:38:25'),
(36, '000036', '2026-06-03', '2026-06-03', 'KID', 'MKC', 'Service', 'CSMS', 'EDSON PANES', 'INV MAT- 93112', '', 'MELBA R. TOQUERO', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-03 06:27:04'),
(37, '000037', '2026-06-04', '2026-06-04', 'KID', 'MKC', 'Parts', 'PMIS', 'EDSON PANES', 'RIV 93119', '', 'LORNA ANTIPUESTO MALICUBAN', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-04 02:25:51'),
(38, '000038', '2026-06-04', '2026-06-04', 'KID', 'MKC', 'Parts', 'PMIS', 'EDSON PANES', 'RIV 89974', '', 'ZENAIDA B MAC', NULL, 'EDIT TAGGING', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-04 07:55:33'),
(39, '000039', '2026-06-05', '2026-06-05', 'KID', 'MKC', 'Service', 'PMIS', 'EDSON PANES', 'RIV89978 | RS101154', '', 'VICENTE Y. CURTIZ', NULL, 'WRONG ORDER', '', 'Cancellation, Unposting', 'ITA', '', NULL, 'Others', '', '', 'Cancelled, Unposted', '', NULL, NULL, '2026-06-05 06:32:00'),
(40, '000040', '2026-06-10', '2026-06-10', 'KID', 'MKC', 'Service', 'PMIS', 'QUEENIE PALAS', 'RIV 212306', '', 'AMOR MANGIDAYOS MANGAWANG', NULL, 'DOUBLE ENTRY DUE TO POWER OUTAGE', '', 'Cancellation', '', '', NULL, 'Others', '', '', 'Cancelled', '', NULL, NULL, '2026-06-11 06:01:21'),
(41, '000041', '2026-06-10', '2026-06-10', 'KID', 'MKC', 'Service', 'PMIS', 'QUEENIE PALAS', 'PRS 942893', '', 'AMOR MANGIDAYOS MANGAWANG', NULL, 'DOUBLE ENTRY DUE TO POWER OUTAGE', '', 'Unposting', '', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-11 06:02:15'),
(42, '000042', '2026-06-19', '2026-06-19', 'GSC', 'MKC', 'CNC', 'CSMS', 'RACHEL PASQUIL', '', '', '', NULL, '', '', 'Others', 'ITA', 'REQUEST ACCESS CSMS - MKC - SYSTEM | BILLING SYSTEM BROWSER', NULL, 'Others', '', '', '', '', NULL, NULL, '2026-06-19 06:32:52'),
(43, '000043', '2026-06-19', '2026-06-19', 'GSC', 'MGSC', 'BRP', 'CSMS', 'JEMBOLEE GALANG', 'RIV 145802', '', 'GENIA GENOSA', NULL, 'WRONG TAGGING', 'RGR', 'Cancellation, Data correction', '', '', NULL, 'Others', '', '', '', '', NULL, NULL, '2026-06-19 08:11:56'),
(44, '000044', '2026-06-20', '2026-05-25', 'GSC', 'MGSC', 'BRP', 'All Modules', 'LENIE DURAN', 'RO 211981', '', 'MICEI', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-20 05:42:13'),
(45, '000045', '2026-06-24', '2026-06-23', 'KID', 'MKC', 'Service', 'CSMS', 'JORDAN ASHLEY GALLOS', 'RO7823 RIV704', '', '', NULL, 'WRONG TAGGING BY PDI', 'RGR', 'Cancellation, Void', 'ITA', '', NULL, 'User error', '', '', 'Cancelled, Voided', '', NULL, NULL, '2026-06-24 02:43:56'),
(46, '000046', '2026-06-26', '2026-06-26', 'GSC', 'MGSC', 'Service', 'CSMS', 'MELHAM ACOP', 'SI 146105', '', '', NULL, 'CHANGE BILLING NAME', 'RGR', 'Cancellation', 'ITA', '', NULL, 'Others', '', '', 'Cancelled', '', NULL, NULL, '2026-06-26 01:21:19'),
(47, '000047', '2026-06-26', '2026-06-25', 'GSC', 'MGSC', 'BRP', 'CSMS', 'LENIE MAE DURAN', 'RR 108045 PO 29867', '', 'ARNOLD PIAMONTE', NULL, 'WRONG AMOUNT', 'RGR', 'Cancellation', 'ITA', '', NULL, 'User error', '', '', 'Cancelled', '', NULL, NULL, '2026-06-26 02:58:33'),
(48, '000048', '2026-06-12', '2026-06-11', 'GLA', 'MGSC', 'Parts', 'PMIS', 'HAZEL YLAGAN', 'MRIV 212602', '', 'Y\' SSER AL JALIL DIKA SIMBOL', NULL, 'PRICING SHOULD BE FREE PMS AND ENGINE TREATMEN HAS BEEN CANCELLED DUE TO CUSTOMER ASSUME THAT IT IS INCLUDED IN THE PACKAGE.', 'RGR', 'Cancellation', 'ITA', '', NULL, 'Others', '', '', 'Cancelled', '', NULL, NULL, '2026-06-26 05:59:03'),
(49, '000049', '2026-06-12', '2026-06-11', '<Br /> <b>Warning</b>: undefined variable $fixedbranch in <b>c:\\xampp\\htdocs\\system_monitoring\\inclu', 'MGSC', 'Parts', 'PMIS', 'HAZEL YLAGAN', 'PRIV #212319', '', 'Y\' SSER AL JALIL DIKA SIMBOL', NULL, 'PRICING SHOULD BE FREE PMS AND ENGINE TREATMEN HAS BEEN CANCELLED DUE TO CUSTOMER ASSUME THAT IT IS INCLUDED IN THE PACKAGE.', 'RGR', 'Cancellation', 'ITA', '', NULL, 'Others', '', '', 'Cancelled', '', NULL, NULL, '2026-06-26 06:01:43'),
(50, '000050', '2026-06-02', '2026-06-02', 'KID', '', 'CNC', 'AMIS', 'RACHEL PASQUIL', 'GJ#4824', '', '', NULL, 'ADJUSTMENT ON AR BANK FAO CBS FOR SHORT PAYMENTS', '', 'Others', 'LBA', '', NULL, 'Others', '', '', 'Done', '', NULL, NULL, '2026-06-26 06:06:48'),
(51, '000051', '2026-06-05', '2026-06-05', 'KID', 'MKC', 'Parts', 'PMIS', 'EDSON PANES', 'RIV89978 | RS101154', '', 'VICENTE CURTIZ', NULL, 'WRONG ORDER', 'RGR', 'Cancellation, Unposting', 'ITA', '', NULL, 'Others', '', '', 'Cancelled, Unposted', '', NULL, NULL, '2026-06-26 06:35:36'),
(52, '000052', '2026-06-04', '2026-06-04', 'KID', 'MKC', 'Service', 'CSMS', 'EDSON PANES', 'RIV 89974', '', 'ZENAIDA B MAC', NULL, 'EDSON PANES', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-26 06:42:07'),
(53, '000053', '2026-06-04', '2026-06-04', 'KID', 'MKC', 'Service', 'CSMS', 'EDSON PANES', 'RIV 93119', '', 'LORNA ANTIPUESTO MALICUBAN', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-26 06:43:16'),
(54, '000054', '2026-06-03', '2026-06-03', 'GSC', 'MGSC', '', 'All Modules', 'MELHAM ACOP', 'RO 212719', '', 'MAXINE GAIL VALENCIA', NULL, 'ADD DISCOUNT', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-26 07:05:24'),
(55, '000055', '2026-06-03', '2026-06-03', 'KID', 'MKC', 'Service', 'CSMS', 'EDSON PANES', 'MRR 9311O', '', '', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-26 07:07:03'),
(56, '000056', '2026-06-26', '2026-06-26', 'KID', 'MKC', 'Parts', 'PMIS', 'JANESSA KITONG', 'MRS 101400', '', 'EVANGELINE DOMINGO CAMINO', NULL, 'EDIT QUANTITY', 'RGR', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-26 07:10:28'),
(57, '000057', '2026-06-26', '2026-06-25', 'GSC', 'MGSC', 'Parts', 'PMIS', 'QUEENIE PALAS', 'PRIV 63491', '', 'DOLE PHILIPPINES', NULL, 'WRONG PART QUANTITY', 'RGR', 'Cancellation', 'ITA', '', NULL, 'User error', '', '', 'Cancelled', '', NULL, NULL, '2026-06-26 07:25:41'),
(58, '000058', '2026-06-26', '2026-06-26', 'GSC', 'MGSC', 'Parts', 'PMIS', 'CYRIL BARTIANA', '63888', '018518', 'JOEY PERAMAM JR.', 5116.61, 'WRONG PART NUMBER', '', 'Cancellation', '', 'CANCEL PSI63888', 'uploads/incident_reports/mitsubishi/000058-20260626_101220.jpg', 'Others', '', '', 'Cancelled', '', NULL, NULL, '2026-06-26 08:12:20'),
(59, '000059', '2026-06-27', '2026-06-25', 'GSC', 'MGSC', 'BRP', 'CSMS', 'LENIE MAE DURAN', 'RO 212951', '', 'MICEI', NULL, 'CHANGE TAGGING', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-27 01:01:16'),
(60, '000060', '2026-06-27', '2026-06-27', 'KID', 'MKC', 'Service', 'CSMS', 'ROMMEL HERNANDO', 'RI 7867', '', 'FREEDE MARK JOHN DEMILLO', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-27 03:08:57'),
(61, '000061', '2026-06-29', '2026-06-29', 'KID', 'MKC', 'Parts', 'PMIS', 'EDSON PANES', 'RS 101450', '', 'SANNY ALPAS SAPA', NULL, 'EDIT', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', '', '', NULL, NULL, '2026-06-29 03:54:00'),
(62, '000062', '2026-06-30', '2026-06-30', '', '', '', 'All Modules', 'EDSON PANES', 'MRIV# 093307', '', 'PANTASIA FOOD SOLUTIONS INC.', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', '', '', NULL, NULL, '2026-07-01 05:51:39'),
(63, '000063', '2026-06-30', '2026-06-30', 'KID', 'MKC', 'Parts', 'PMIS', 'EDSON PANES', 'PRIV#090168', '', 'PANTASIA FOOD SOLUTIONS INC.', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-07-01 05:52:11'),
(64, '000064', '2026-06-03', '2026-06-03', 'KID', 'MKC', 'Parts', 'PMIS', 'EDSON PANES', 'MRIV 93112', '', '', NULL, 'EDIT PRICE', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-07-01 05:53:07'),
(65, '000065', '2026-06-02', '2026-06-02', 'GSC', 'MGSC', 'CNC', 'AMIS', 'RACHEL PASQUIL', '', '', '', NULL, 'ADJUSTMENT ON AR BANK FAO CBS FOR SHORT PAYMENTS', 'NGR', 'Adjustment', 'LBA', 'GJ#17285', NULL, 'Others', '', '', '', '', NULL, NULL, '2026-07-01 05:54:28'),
(66, '000066', '2026-06-02', '2026-06-02', 'KID', 'MKC', 'CNC', 'AMIS', 'RACHEL PASQUIL', '', '', '', NULL, 'ADJUSTMENT ON AR BANK FAO CBS FOR SHORT PAYMENTS', 'NGR', 'Adjustment', 'LBA', 'GJ#4824', NULL, 'Others', '', '', '', '', NULL, NULL, '2026-07-01 05:55:08'),
(67, '000067', '2026-06-02', '2026-06-02', 'GSC', 'MGSC', 'CNC', 'CMIS', 'JUDIE ANN DE LEON', 'CR 6090', '', 'ARLEE LU SENOBAGO', NULL, 'EDIT NAME', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-07-01 05:57:04'),
(68, '000068', '2026-06-02', '2026-06-01', 'GSC', 'MGSC', 'Service', 'CSMS', 'CERILO CORSIT III', 'SI 142291 & 145174 | RO 205059', '', 'JUDPHILAN FOODS CORPORATION', NULL, 'WRONG AMOUNT', 'RGR', 'Cancellation', 'ITA', '', NULL, 'User error', '', '', 'Cancelled', '', NULL, NULL, '2026-07-01 05:59:58'),
(69, '000069', '2026-06-01', '2026-06-01', 'GSC', '', 'Parts', 'PMIS', 'REDIN MONTILLANO', 'A-RR 2754', '', '', NULL, 'CHANGE VENDOR', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-07-01 06:01:16'),
(70, '000070', '2026-05-30', '2026-05-30', 'KID', 'MKC', 'Parts', 'PMIS', 'JANESSA KITONG', 'RIV 2360 |PRS 100821', '', '\"FAROUK S ROMANCAP \"', NULL, 'WRONG PART NUMBER', 'RGR', 'Cancellation, Unposting', 'ITA', '', NULL, 'User error', '', '', 'Cancelled, Unposted', '', NULL, NULL, '2026-07-02 00:36:50'),
(71, '000071', '2026-05-30', '2026-05-30', 'GSC', 'MGSC', 'CNC', 'CMIS', 'RACHEL PASQUIL', 'CR 18088', '', 'ROBERT FORES', NULL, 'POSTED ON MKC INSTEAD OF MGSC', '', 'Unposting', 'ITA', '', NULL, 'User error', '', '', 'Unposted', '', NULL, NULL, '2026-07-02 00:37:59'),
(72, '000072', '2026-05-29', '2026-05-29', 'GSC', 'MGSC', 'Service', 'CSMS', 'ROGEIO DARDO', 'RO 211532', '', 'DONALD LOUIE MONTECLARO', NULL, 'EDIT TAGGING TO CUSTOMER', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-07-02 00:39:52'),
(73, '000073', '2026-05-25', '2026-05-05', 'GSC', 'MGSC', 'Service', 'CSMS', 'VHYNSE MIEL CADAVOS', 'RO211381', '', 'MANUEL PRADO MARGES', NULL, 'EDIT RO NUMBER', 'RGR', 'Cancellation, Unposting', 'ITA', '', NULL, 'User error', '', '', 'Cancelled, Unposted', '', NULL, NULL, '2026-07-02 00:41:25'),
(74, '000074', '2026-05-23', '2026-05-04', 'GSC', 'MGSC', 'Service', 'CSMS', 'MELHAM ACOP', 'RO211910', '', 'JUROMEY KRIS BARABAT', NULL, 'EDIT LABOR DETAILS', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-07-02 00:43:36'),
(75, '000075', '2026-05-23', '2026-05-23', 'GSC', 'MGSC', 'Service', 'CSMS', 'COLEEN CAYE ALMANZOR', 'RO 212481', '', 'FREDDIE JR. MALINAO', NULL, 'UNPRINTED PROFORMA', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-07-02 00:45:16'),
(76, '000076', '2026-05-20', '2026-05-20', 'KID', 'MKC', 'Service', 'CSMS', 'EDSON PANES', 'RO#7639', '', 'DEBIE G. BARRIOS', NULL, 'WRONG INPUT OF RO NUMBER', 'RGR', 'Cancellation, Unposting', 'ITA', '', NULL, 'User error', '', '', 'Cancelled, Unposted', '', NULL, NULL, '2026-07-02 00:48:20'),
(78, '000078', '2026-07-02', '2026-07-02', 'KID', 'MKC', 'Parts', 'PMIS', 'EDSON PANES', '90190', '', '', NULL, 'EDIT PRICE PMPP', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-07-02 05:26:44'),
(79, '000079', '2026-07-02', '2026-06-30', 'KID', 'MKC', 'Service', 'CSMS', 'ROMMEL HERNANDO', 'RO 7891 SI 7463', '', 'KIRK JAY AHMED ANTATICO KHAN', NULL, 'WRONG PRINTING', 'RGR', 'Cancellation', 'ITA', '', NULL, 'User error', '', '', 'Cancelled', '', NULL, NULL, '2026-07-02 08:23:04'),
(80, '000080', '2026-07-03', '2026-07-03', 'KID', 'MKC', 'Service', 'PMIS', 'JANESSA KITONG', 'RR 1091', '', 'UNITOP GENERAL MERCHANDISE INC.', NULL, 'EDIT REMARKS', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-07-03 01:12:10'),
(81, '000081', '2026-07-03', '2026-07-03', 'GSC', 'MGSC', 'Service', 'CSMS', 'FLORDELYN CAROD', '213396', '', 'ARTHUR LEGISLADOR', NULL, 'EDIT PRICE', '', 'Unposting', '', '', NULL, 'Others', '', '', '', '', NULL, NULL, '2026-07-03 07:46:39');

-- --------------------------------------------------------

--
-- Table structure for table `micei_ticket_monitoring`
--

CREATE TABLE `micei_ticket_monitoring` (
  `id` int(11) NOT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `dealer` varchar(100) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `ticket_number` varchar(150) NOT NULL,
  `ticket_description` text DEFAULT NULL,
  `date_created` date NOT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `ticket_status` varchar(100) NOT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `micei_ticket_monitoring`
--

INSERT INTO `micei_ticket_monitoring` (`id`, `branch`, `dealer`, `module`, `ticket_number`, `ticket_description`, `date_created`, `created_by`, `ticket_status`, `resolved_at`, `created_at`) VALUES
(1, 'GSC', 'MGSC', 'All Modules', '3836', 'REQUESTING FOR REVIEW VEHICLE INVENTORY REPORT', '2026-05-14', 'IT', 'Resolved', '2026-05-23 14:31:55', '2026-05-23 06:24:57'),
(2, 'GSC', 'MGSC', 'All Modules', '3854', 'Requesting for inclusion of all accounts in the CDJ Reports Account Detail by Payee.', '2026-05-19', 'LBA', 'Resolved', '2026-06-04 16:03:27', '2026-05-23 06:49:45'),
(3, 'GSC', 'MGSC', 'AMIS', '#3874', 'REQUEST TO CORRECT MUNICIPALITY ADDRESS | CUSTOMER MASTER FILE', '2026-05-26', 'IT - CHEL', 'Resolved', '2026-05-26 10:33:46', '2026-05-26 00:03:55'),
(4, 'GSC', 'MGSC', 'PMIS', '#4032', 'The vendor on the RR is different from the PO', '2026-07-02', 'IT DEPARTMENT', 'Open', NULL, '2026-07-03 00:36:30');

-- --------------------------------------------------------

--
-- Table structure for table `ntr_system_monitoring`
--

CREATE TABLE `ntr_system_monitoring` (
  `id` int(11) NOT NULL,
  `identification_number` varchar(100) DEFAULT NULL,
  `date_recorded` date NOT NULL,
  `transaction_date` date NOT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `dealer` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `user_name` varchar(150) DEFAULT NULL,
  `invoice_reference` varchar(150) DEFAULT NULL,
  `payment_reference` varchar(150) DEFAULT NULL,
  `client_name` varchar(200) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `approved_by` varchar(150) DEFAULT NULL,
  `processed_type` varchar(100) DEFAULT NULL,
  `processed_by` varchar(150) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `incident_report_image_path` varchar(255) DEFAULT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `system_admin` varchar(150) DEFAULT NULL,
  `ticket` varchar(150) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `offense` varchar(150) DEFAULT NULL,
  `disciplinary_action` varchar(100) DEFAULT NULL,
  `action_taken` varchar(100) DEFAULT NULL,
  `memo_printed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ntr_system_monitoring`
--

INSERT INTO `ntr_system_monitoring` (`id`, `identification_number`, `date_recorded`, `transaction_date`, `branch`, `dealer`, `department`, `module`, `user_name`, `invoice_reference`, `payment_reference`, `client_name`, `amount`, `reason`, `approved_by`, `processed_type`, `processed_by`, `remarks`, `incident_report_image_path`, `classification`, `system_admin`, `ticket`, `status`, `offense`, `disciplinary_action`, `action_taken`, `created_at`) VALUES
(1, '000001', '2026-06-03', '2026-06-03', 'GSC', 'NGSC', 'Sales', 'SMIS', 'JOSIE SIRAD', 'PO 224', '', '', NULL, 'WRONG COLOR', '', 'Unposting', 'ITA', '', NULL, 'User error', '', '', 'Unposted', '', NULL, NULL, '2026-06-03 01:55:56'),
(2, '000002', '2026-06-06', '2026-06-06', 'GSC', 'NGSC', 'Service', 'CSMS', 'VANCE JOSHUA ALINSASAGUIN', 'RO 17137 SI 31314', '', 'ANNETTE AZARCON', NULL, 'WRONG TAGGING', 'RGR', 'Cancellation, Unposting, Data correction', 'ITA', '', NULL, 'User error', '', '', 'Cancelled, Unposted', '', NULL, NULL, '2026-06-06 00:31:02'),
(3, '000003', '2026-06-26', '2024-04-04', 'GSC', 'NGSC', 'Service', 'CSMS', 'CLYDEE MAE FACTORAN', 'RO 14671', '', 'RONNIE DAYON', NULL, 'OPEN RO', 'NGR', 'Void', 'ITA', '', NULL, 'Others', '', '', 'Voided', '', NULL, NULL, '2026-06-26 06:15:01'),
(4, '000004', '2026-06-26', '2024-07-31', 'GSC', 'NGSC', 'Service', 'CSMS', 'CLYDEE MAE FACTORAN', 'RO 15094', '', 'SAMUEL ELEVADO', NULL, 'OPEN RO', 'NGR', 'Void', 'ITA', '', NULL, 'Others', '', '', 'Voided', '', NULL, NULL, '2026-06-26 06:17:07'),
(5, '000005', '2026-06-26', '2024-09-25', 'GSC', 'NGSC', 'Service', 'CSMS', 'CLYDEE MAE FACTORAN', 'RO 15522', '', 'REX ACDAL', NULL, 'OPEN RO', 'NGR', 'Void', 'ITA', '', NULL, 'Others', '', '', 'Voided', '', NULL, NULL, '2026-06-26 06:18:17'),
(6, '000006', '2026-06-26', '2024-04-04', 'GSC', 'NGSC', 'Service', 'CSMS', 'CLYDEE MAE FACTORAN', 'RO 13788', '', 'LEONARD REYES', NULL, 'OPEN RO', 'NGR', 'Void', 'ITA', '', NULL, 'Others', '', '', 'Voided', '', NULL, NULL, '2026-06-26 06:19:36'),
(7, '000007', '2026-06-26', '2026-06-26', 'GSC', 'NGSC', 'Service', 'CSMS', 'VANCE ALINSASAGUIN', 'RO 17183', '', 'NTR', NULL, 'EDIT TAGGIN', '', 'Unposting', 'ITA', '', NULL, 'Others', '', '', 'Unposted', '', NULL, NULL, '2026-06-26 08:14:36'),
(8, '000008', '2026-06-30', '2026-06-30', 'GSC', 'NGSC', 'Service', 'All Modules', 'CHARRY OTOY', 'SI# 17194', '', 'GOOD LIFE MEMORIAL CHAPEL CO.', NULL, 'WRONG TAGGING', 'RGR', 'Cancellation', 'ITA', '', NULL, 'User error', '', '', 'Cancelled', '', NULL, NULL, '2026-07-02 00:31:30'),
(9, '000009', '2026-06-30', '2026-06-30', 'GSC', 'NGSC', 'Service', 'CSMS', 'CHARRY OTOY', 'SI# 0318', '', 'GOOD LIFE MEMORIAL CHAPEL CO.', NULL, 'WRONG TAGGING', 'RGR', 'Cancellation', 'ITA', '', NULL, 'User error', '', '', 'Cancelled', '', NULL, NULL, '2026-07-02 00:32:44'),
(10, '000010', '2026-06-30', '2026-06-30', 'GSC', 'NGSC', 'Service', 'CSMS', 'CHARRY OTOY', 'SI# 0319', '', 'GOOD LIFE MEMORIAL CHAPEL CO.', NULL, 'WRONG TAGGING', 'RGR', 'Cancellation, Unposting', 'ITA', '', NULL, 'User error', '', '', 'Cancelled', '', NULL, NULL, '2026-07-02 00:34:09'),
(11, '000011', '2026-06-06', '2026-06-06', 'GSC', 'NGSC', 'Service', 'CSMS', 'VANCE JOSHUA ALINSASAGUIN', 'RO 17137 SI 31314', '', 'ANNETTE AZARCON', NULL, 'WRONG TAGGING', 'RGR', 'Cancellation, Unposting', 'ITA', '', NULL, 'User error', '', '', 'Cancelled, Unposted', '', NULL, NULL, '2026-07-02 00:35:31'),
(12, '000012', '2026-07-04', '2026-07-04', 'GLA', 'NGSC', 'Service', 'CSMS', 'MARVIN JAY RUIZ', '', '', '', NULL, 'REQUEST ACCESS CSMS', '', 'Others', '', 'CSMS - SYSTEM - INPUT BILLING DISCOUNT', NULL, 'Others', '', '', 'Done', '', NULL, NULL, '2026-07-04 01:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `ntr_ticket_monitoring`
--

CREATE TABLE `ntr_ticket_monitoring` (
  `id` int(11) NOT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `dealer` varchar(100) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `ticket_number` varchar(150) NOT NULL,
  `ticket_description` text DEFAULT NULL,
  `date_created` date NOT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `ticket_status` varchar(100) NOT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ntr_ticket_monitoring`
--

INSERT INTO `ntr_ticket_monitoring` (`id`, `branch`, `dealer`, `module`, `ticket_number`, `ticket_description`, `date_created`, `created_by`, `ticket_status`, `resolved_at`, `created_at`) VALUES
(1, 'GSC', 'NGSC', 'CSMS', '#4009', 'We have several voided ROs today in the CSMS of dealer NGSC but upon checking the voided RO report no Repair Order is included.', '2026-06-26', 'LAILENE AMPARADO', 'Open', NULL, '2026-06-26 07:09:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `micei_system_monitoring`
--
ALTER TABLE `micei_system_monitoring`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `micei_ticket_monitoring`
--
ALTER TABLE `micei_ticket_monitoring`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ntr_system_monitoring`
--
ALTER TABLE `ntr_system_monitoring`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ntr_ticket_monitoring`
--
ALTER TABLE `ntr_ticket_monitoring`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `micei_system_monitoring`
--
ALTER TABLE `micei_system_monitoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `micei_ticket_monitoring`
--
ALTER TABLE `micei_ticket_monitoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ntr_system_monitoring`
--
ALTER TABLE `ntr_system_monitoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ntr_ticket_monitoring`
--
ALTER TABLE `ntr_ticket_monitoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
