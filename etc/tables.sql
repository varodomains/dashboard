CREATE TABLE `cards` (
 `id` varchar(100) DEFAULT NULL,
 `user` int(11) DEFAULT NULL,
 `brand` text DEFAULT NULL,
 `last4` varchar(4) DEFAULT NULL,
 `expiration` varchar(7) DEFAULT NULL,
 UNIQUE KEY `id` (`id`),
 KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4

CREATE TABLE `emails` (
  `ai` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `time` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`ai`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4

CREATE TABLE `invoices` (
 `ai` int(11) NOT NULL AUTO_INCREMENT,
 `id` varchar(16) DEFAULT NULL,
 `time` int(11) DEFAULT NULL,
 `user` int(11) DEFAULT NULL,
 `domain` varchar(255) DEFAULT NULL,
 `years` int(11) DEFAULT NULL,
 `type` varchar(10) DEFAULT NULL,
 `address` varchar(64) DEFAULT NULL,
 `amount` int(11) DEFAULT NULL,
 `expired` tinyint(1) NOT NULL DEFAULT 0,
 `paid` tinyint(1) NOT NULL DEFAULT 0,
 `override` tinyint(1) NOT NULL DEFAULT 0,
 PRIMARY KEY (`ai`),
 UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4

CREATE TABLE `log` (
  `ai` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `time` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`ai`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4

CREATE TABLE `notify` (
 `ai` int(11) NOT NULL AUTO_INCREMENT,
 `user` int(11) NOT NULL,
 `type` varchar(16) DEFAULT NULL,
 `name` varchar(100) DEFAULT NULL,
 `value` text DEFAULT NULL,
 `uuid` varchar(32) DEFAULT NULL,
 PRIMARY KEY (`ai`),
 UNIQUE KEY `uuid` (`uuid`),
 KEY `user` (`user`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4

CREATE TABLE `price` (
 `ai` int(11) NOT NULL AUTO_INCREMENT,
 `time` int(11) DEFAULT NULL,
 `price` double DEFAULT NULL,
 PRIMARY KEY (`ai`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4

CREATE TABLE `registrars` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `username` varchar(16) DEFAULT NULL,
 `password` text DEFAULT NULL,
 `active` tinyint(1) NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4

CREATE TABLE `reset` (
 `ai` int(11) NOT NULL AUTO_INCREMENT,
 `user` int(11) DEFAULT NULL,
 `code` varchar(32) DEFAULT NULL,
 `ip` varchar(15) DEFAULT NULL,
 `ua` varchar(500) DEFAULT NULL,
 `time` int(11) DEFAULT NULL,
 `used` tinyint(1) NOT NULL DEFAULT 0,
 PRIMARY KEY (`ai`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4

CREATE TABLE `sales` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user` int(11) DEFAULT NULL,
 `name` varchar(255) DEFAULT NULL,
 `tld` varchar(255) DEFAULT NULL,
 `type` varchar(10) DEFAULT NULL,
 `price` int(11) DEFAULT NULL,
 `total` int(11) DEFAULT NULL,
 `fee` int(11) DEFAULT NULL,
 `time` int(11) DEFAULT NULL,
 `registrar` varchar(16) DEFAULT NULL,
 `settled` tinyint(1) NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 KEY `tld` (`tld`),
 KEY `time` (`time`),
 KEY `registrar` (`registrar`),
 KEY `settled` (`settled`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4

CREATE TABLE `staked` (
 `tld` varchar(255) DEFAULT NULL,
 `uuid` varchar(64) DEFAULT NULL,
 `id` int(11) DEFAULT NULL,
 `owner` varchar(40) DEFAULT NULL,
 `price` int(11) DEFAULT NULL,
 `live` tinyint(1) NOT NULL DEFAULT 0,
 `featured` tinyint(1) NOT NULL DEFAULT 0,
 UNIQUE KEY `tld` (`tld`),
 UNIQUE KEY `uuid` (`uuid`),
 KEY `owner` (`owner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4

CREATE TABLE `users` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `email` varchar(320) DEFAULT NULL,
 `password` text DEFAULT NULL,
 `uuid` varchar(64) DEFAULT NULL,
 `token` varchar(16) DEFAULT NULL,
 `admin` tinyint(1) NOT NULL DEFAULT 0,
 `beta` tinyint(1) NOT NULL DEFAULT 0,
 `stripe` varchar(100) DEFAULT NULL,
 `totp` text DEFAULT NULL,
 `api` varchar(32) DEFAULT NULL,
 `theme` varchar(10) DEFAULT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `email` (`email`),
 UNIQUE KEY `token` (`token`),
 KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4
