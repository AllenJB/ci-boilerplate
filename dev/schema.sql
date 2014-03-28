/* SQL schema will go here! */

CREATE TABLE `cron_locks` (
    `lockid` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `cron` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `locked` tinyint(1) unsigned NOT NULL,
    `dt_started` datetime NOT NULL,
    `dt_ended` datetime DEFAULT NULL,
    `file_rotation` enum('process','daily') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'process',
    `pid` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`lockid`),
    KEY `cron` (`cron`),
    KEY `locked` (`locked`),
    KEY `file_rotation` (`file_rotation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `cron_logs` (
    `logid` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `cron` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `dt_started` datetime NOT NULL,
    `dt_ended` datetime DEFAULT NULL,
    `failed` tinyint(1) unsigned NOT NULL,
    `message` text COLLATE utf8_unicode_ci,
    PRIMARY KEY (`logid`),
    KEY `failed` (`failed`),
    KEY `cron` (`cron`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
