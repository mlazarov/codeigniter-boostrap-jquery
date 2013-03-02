
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` mediumint(6) unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `passwd` char(32) NOT NULL,
  `language` int(1) NOT NULL DEFAULT '1',
  `lastlogin` int(11) unsigned NOT NULL,
  `lastused` int(11) unsigned NOT NULL,
  `created_ip` varchar(60) NOT NULL,
  `created` int(11) unsigned NOT NULL,
  `updated` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_email` (`email`),
  KEY `lastused` (`lastused`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_activates`
--

CREATE TABLE IF NOT EXISTS `user_activates` (
  `user_id` mediumint(6) NOT NULL,
  `reg_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reg_code` varchar(20) CHARACTER SET cp1251 NOT NULL,
  `promo_credits` int(11) unsigned NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `reg_code` (`reg_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_autologins`
--

CREATE TABLE IF NOT EXISTS `user_autologins` (
  `user_id` mediumint(6) NOT NULL,
  `cookie_code` varchar(255) NOT NULL,
  `user_ip` varchar(15) NOT NULL,
  PRIMARY KEY (`user_id`,`cookie_code`,`user_ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(16) NOT NULL DEFAULT '0',
  `user_agent` varchar(255) NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `user_data` text NOT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
