DROP TABLE IF EXISTS `event`;

CREATE TABLE `event` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_date` timestamp DEFAULT NULL,
  `location` text,
  `action` varchar(255) NOT NULL,
  `fare` int(11),
  `price_cap` int(11),
  `balance` int(11),
  `note` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_time_event` (`creation_date`, `action`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
