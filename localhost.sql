-- Adminer 4.2.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE `yelp_crawler` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `yelp_crawler`;

DROP TABLE IF EXISTS `yelp_options`;
CREATE TABLE `yelp_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `option_name` text NOT NULL,
  `option_value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `yelp_options` (`id`, `option_name`, `option_value`) VALUES
(1,	'current_count',	'59'),
(2,	'url',	'aHR0cHM6Ly93d3cueWVscC5jb20vc2VhcmNoP2ZpbmRfZGVzYz1SZXN0YXVyYW50cyZmaW5kX2xvYz1TYW4rRnJhbmNpc2NvJTJDK0NBJm5zPTE=');

-- 2016-11-29 16:26:26
