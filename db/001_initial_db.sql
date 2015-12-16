/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `appconfig` */

DROP TABLE IF EXISTS `appconfig`;

CREATE TABLE `appconfig` (
  `configID` int(11) NOT NULL AUTO_INCREMENT,
  `configName` varchar(45) DEFAULT NULL,
  `configValue` text,
  `configDescription` varchar(45) DEFAULT NULL,
  `configIsNumeric` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`configID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `appconfig` */

/*Table structure for table `geocode_cached` */

DROP TABLE IF EXISTS `geocode_cached`;

CREATE TABLE `geocode_cached` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `longitude` double NOT NULL,
  `latitude` double NOT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country_iso` varchar(3) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `found_longitude` double DEFAULT NULL,
  `found_latitude` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index2` (`longitude`,`latitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `geocode_cached` */

/*Table structure for table `geocode_loaded` */

DROP TABLE IF EXISTS `geocode_loaded`;

CREATE TABLE `geocode_loaded` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `country_iso` varchar(3) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `postal_code` varchar(64) DEFAULT NULL,
  `load_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `country_iso` (`country_iso`,`city`),
  KEY `country_iso_2` (`country_iso`,`postal_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `geocode_loaded` */

/*Table structure for table `movie` */

DROP TABLE IF EXISTS `movie`;

CREATE TABLE `movie` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `genre` varchar(45) DEFAULT NULL,
  `user_rating` float DEFAULT NULL,
  `poster_url` varchar(255) DEFAULT NULL,
  `rated` varchar(15) DEFAULT NULL,
  `critic_rating` float DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `runtime` int(11) DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `movie` */

/*Table structure for table `showtime` */

DROP TABLE IF EXISTS `showtime`;

CREATE TABLE `showtime` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `show_date` date NOT NULL,
  `show_time` time DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `theatre_id` int(11) NOT NULL,
  `movie_id` bigint(20) NOT NULL,
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `type` varchar(15) DEFAULT NULL,
  `redirects` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `show_date` (`show_date`,`show_time`,`theatre_id`,`movie_id`,`type`),
  KEY `fk_showtime_movie1_idx` (`movie_id`),
  KEY `fk_showtime_theatre1_idx` (`theatre_id`),
  CONSTRAINT `fk_showtime_movie1` FOREIGN KEY (`movie_id`) REFERENCES `movie` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_showtime_theatre1` FOREIGN KEY (`theatre_id`) REFERENCES `theatre` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `showtime` */

/*Table structure for table `theatre` */

DROP TABLE IF EXISTS `theatre`;

CREATE TABLE `theatre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `theatre` */

/*Table structure for table `theatre_nearby` */

DROP TABLE IF EXISTS `theatre_nearby`;

CREATE TABLE `theatre_nearby` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `theatre_id` int(11) NOT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `city` varchar(45) DEFAULT NULL,
  `country_iso` varchar(3) DEFAULT NULL,
  `country` varchar(45) DEFAULT NULL,
  `distance_m` double DEFAULT NULL,
  `distance_mi` double DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_theatre_nearby_theatre1_idx` (`theatre_id`),
  CONSTRAINT `fk_theatre_nearby_theatre1` FOREIGN KEY (`theatre_id`) REFERENCES `theatre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `theatre_nearby` */

/*Table structure for table `user_device` */

DROP TABLE IF EXISTS `user_device`;

CREATE TABLE `user_device` (
  `id` bigint(20) NOT NULL,
  `secret_key` varchar(128) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `device_uuid` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `user_device` */

/*Table structure for table `user_device_req` */

DROP TABLE IF EXISTS `user_device_req`;

CREATE TABLE `user_device_req` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip_address` bigint(20) NOT NULL,
  `req_type` varchar(25) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_device_id` bigint(20) DEFAULT NULL,
  `request_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_device_id` (`user_device_id`,`request_id`),
  CONSTRAINT `fk_user_device_req_user_device` FOREIGN KEY (`user_device_id`) REFERENCES `user_device` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `user_device_req` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
