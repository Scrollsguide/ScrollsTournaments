CREATE DATABASE IF NOT EXISTS `tournaments` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `tournaments`;

CREATE TABLE IF NOT EXISTS `roles` (
  `tournament` int(10) NOT NULL,
  `user` int(10) NOT NULL,
  `role` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`tournament`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `tournaments` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `url` varchar(50) NOT NULL,
  `date` int(10) NOT NULL,
  `regstate` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;
