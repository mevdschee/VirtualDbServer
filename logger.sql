SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = '+02:00';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `queries`;
CREATE TABLE `queries` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `database` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `created_at` datetime NOT NULL,
  `created_at_msec` int(11) NOT NULL,
  `request_id` bigint(20) NOT NULL,
  `call_time` int(11) DEFAULT NULL,
  `execution_time` int(11) NOT NULL,
  `query_time` int(11) NOT NULL,
  `query` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `json_error` int(11) NOT NULL,
  `response_size` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at_created_at_usec` (`created_at`,`created_at_msec`),
  KEY `request_id` (`request_id`),
  KEY `database` (`database`),
  CONSTRAINT `queries_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `requests`;
CREATE TABLE `requests` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `database` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `created_at` datetime NOT NULL,
  `created_at_msec` int(11) NOT NULL,
  `session_id` bigint(20) NOT NULL,
  `call_time` int(11) DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `request_uri` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at_created_at_msec` (`created_at`,`created_at_msec`),
  KEY `session_id` (`session_id`),
  KEY `database` (`database`),
  CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `servers`;
CREATE TABLE `servers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `database` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `server_ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `database` (`database`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `database` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `client_ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `server_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `database` (`database`),
  KEY `server_id` (`server_id`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
