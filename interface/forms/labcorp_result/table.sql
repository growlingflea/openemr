--
-- Table structure for table `form_labcorp_batch`
--

CREATE TABLE IF NOT EXISTS `form_labcorp_batch` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `pid` bigint(20) NOT NULL,
  `user` varchar(255) NOT NULL,
  `groupname` varchar(255) NOT NULL,
  `authorized` tinyint(4) DEFAULT NULL,
  `activity` tinyint(4) DEFAULT NULL,
  `order_number` varchar(60) NOT NULL,
  `order_datetime` datetime DEFAULT NULL,
  `facility` varchar(255) NOT NULL,
  `provider_id` varchar(255) NOT NULL,
  `provider_npi` varchar(255) NOT NULL,
  `pat_dob` date DEFAULT NULL,
  `pat_first` varchar(255) NOT NULL,
  `pat_middle` varchar(255) NOT NULL,
  `pat_last` varchar(255) NOT NULL,
  `lab_number` varchar(60) NOT NULL,
  `lab_status` varchar(20) NOT NULL,
  `result_output` longtext,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `form_labcorp_result`
--

CREATE TABLE IF NOT EXISTS `form_labcorp_result` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `pid` bigint(20) NOT NULL,
  `user` varchar(255) NOT NULL,
  `groupname` varchar(255) NOT NULL,
  `authorized` tinyint(4) DEFAULT NULL,
  `activity` tinyint(4) DEFAULT NULL,
  `status` varchar(16) DEFAULT NULL,
  `priority` varchar(16) DEFAULT NULL,
  `request_id` bigint(20) NOT NULL,
  `request_control` varchar(255) DEFAULT NULL,
  `request_facility` varchar(255) NOT NULL,
  `request_order` varchar(255) DEFAULT NULL,
  `request_pid` varchar(255) DEFAULT NULL,
  `request_pubpid` varchar(25) NOT NULL,
  `request_pat_first` varchar(255) DEFAULT NULL,
  `request_pat_middle` varchar(255) DEFAULT NULL,
  `request_pat_last` varchar(255) DEFAULT NULL,
  `request_DOB` date DEFAULT NULL,
  `request_provider` varchar(255) NOT NULL,
  `request_npi` varchar(255) DEFAULT NULL,
  `specimen_datetime` datetime DEFAULT NULL,
  `received_datetime` datetime DEFAULT NULL,
  `result_datetime` datetime DEFAULT NULL,
  `result_abnormal` varchar(255) DEFAULT NULL,
  `result_handling` varchar(255) DEFAULT NULL,
  `reviewed_datetime` datetime DEFAULT NULL,
  `reviewed_id` varchar(255) DEFAULT NULL,
  `notified_datetime` datetime DEFAULT NULL,
  `notified_id` varchar(255) DEFAULT NULL,
  `notified_person` varchar(255) DEFAULT NULL,
  `result_notes` text,
  `lab_received` date DEFAULT NULL,
  `lab_number` varchar(255) DEFAULT NULL,
  `lab_status` varchar(255) DEFAULT NULL,
  `lab_notes` text,
  `document_id` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `form_labcorp_result_item`
--

CREATE TABLE IF NOT EXISTS `form_labcorp_result_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) NOT NULL,
  `sequence` int(16) NOT NULL,
  `date` datetime NOT NULL,
  `pid` bigint(20) NOT NULL,
  `activity` tinyint(1) NOT NULL DEFAULT '1',
  `test_code` varchar(255) NOT NULL,
  `test_text` varchar(255) NOT NULL,
  `test_type` char(1) NOT NULL COMMENT 'G=Relex, A=Added',
  `parent_code` varchar(25) NOT NULL,
  `observation_status` varchar(255) DEFAULT NULL,
  `observation_loinc` varchar(255) NOT NULL,
  `observation_label` varchar(255) DEFAULT NULL,
  `observation_type` varchar(255) DEFAULT NULL,
  `observation_value` varchar(255) DEFAULT NULL,
  `observation_units` varchar(255) DEFAULT NULL,
  `observation_range` varchar(255) DEFAULT NULL,
  `observation_abnormal` varchar(255) DEFAULT NULL,
  `observation_notes` text,
  `producer_id` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `form_labcorp_result_lab`
--

CREATE TABLE IF NOT EXISTS `form_labcorp_result_lab` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) NOT NULL,
  `sequence` int(16) NOT NULL,
  `date` datetime NOT NULL,
  `pid` bigint(20) NOT NULL,
  `user` varchar(255) NOT NULL,
  `activity` tinyint(4) DEFAULT NULL,
  `status` varchar(16) DEFAULT NULL,
  `code` varchar(5) NOT NULL,
  `name` varchar(100) NOT NULL,
  `street` varchar(100) NOT NULL,
  `street2` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` char(2) NOT NULL,
  `zip` varchar(12) NOT NULL,
  `phone` varchar(25) NOT NULL,
  `director` varchar(100) NOT NULL,
  `clia` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
