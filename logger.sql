SET NAMES utf8;
SET foreign_key_checks = 0;

DROP TABLE IF EXISTS `calls`;
CREATE TABLE `calls` (
  `id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `client_ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `application_ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `session_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `user_id` varchar(255) CHARACTER SET utf8 COLLATE 'utf8_bin' NOT NULL,
  `request_uri` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `request_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `database` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `created_at` datetime NOT NULL,
  `created_at_usec` int(11) NOT NULL,
  `call_time` int(11) DEFAULT NULL,
  `execution_time` int(11) NOT NULL,
  `query_time` int(11) NOT NULL,
  `query` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `json_error` int(11) NOT NULL,
  `response_size` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at_created_at_usec` (`created_at`,`created_at_usec`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;