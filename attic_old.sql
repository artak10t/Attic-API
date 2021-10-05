-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.5.12-MariaDB-log - MariaDB Server
-- Server OS:                    Linux
-- HeidiSQL Version:             11.3.0.6295
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for attic_db
CREATE DATABASE IF NOT EXISTS `attic_db` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_bin */;
USE `attic_db`;

-- Dumping structure for table attic_db.accounts
CREATE TABLE IF NOT EXISTS `accounts` (
  `account_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(320) COLLATE utf8_bin NOT NULL,
  `password_bin` binary(32) NOT NULL,
  `enabled` tinyint(4) NOT NULL,
  `activated` tinyint(4) NOT NULL,
  `access` tinyint(4) NOT NULL COMMENT '0 = PRIVATE,\r\n1 = AUTHORIZED\r\n2 = PUBLIC',
  `privileges` tinyint(4) NOT NULL COMMENT '0 = ADMINISTRATOR,\r\n1 = MODERATOR,\r\n2 = USER',
  `max_space` bigint(20) unsigned NOT NULL,
  `current_space` bigint(20) unsigned NOT NULL DEFAULT 0,
  `max_attics_count` int(10) unsigned NOT NULL,
  `current_attics_count` int(10) unsigned NOT NULL DEFAULT 0,
  `max_folders_count` int(10) unsigned NOT NULL,
  `current_folders_count` int(10) unsigned NOT NULL DEFAULT 0,
  `max_files_count` int(10) unsigned NOT NULL,
  `current_files_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT utc_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `delete_on` datetime DEFAULT NULL,
  `contact_email` varchar(320) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_phone` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `email` (`email`),
  KEY `email_password_bin` (`email`,`password_bin`),
  KEY `enabled_activated_access` (`enabled`,`activated`,`access`),
  KEY `privileges` (`privileges`),
  KEY `max_space` (`max_space`),
  KEY `current_space` (`current_space`),
  KEY `max_attics_count` (`max_attics_count`),
  KEY `created` (`created`),
  KEY `last_login` (`last_login`),
  KEY `delete_on` (`delete_on`),
  KEY `current_attcs_count` (`current_attics_count`) USING BTREE,
  FULLTEXT KEY `contacts` (`contact_email`,`contact_phone`,`description`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.accounts: ~1 rows (approximately)
DELETE FROM `accounts`;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` (`account_id`, `name`, `email`, `password_bin`, `enabled`, `activated`, `access`, `privileges`, `max_space`, `current_space`, `max_attics_count`, `current_attics_count`, `max_folders_count`, `current_folders_count`, `max_files_count`, `current_files_count`, `created`, `last_login`, `delete_on`, `contact_email`, `contact_phone`, `description`) VALUES
	(1, 'Default Admin', 'admin@attic.com', _binary 0xa1301f20a5abec76e72b330301f73d55352d20ac779aecb7e961e3b6c72bc297, 1, 1, 0, 0, 10737418240, 0, 10, 0, 1000, 0, 1000000, 0, '2021-09-12 03:46:15', '2021-10-03 02:48:54', NULL, '', '', '');
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;

-- Dumping structure for table attic_db.attics
CREATE TABLE IF NOT EXISTS `attics` (
  `attic_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(10) unsigned NOT NULL,
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint(4) NOT NULL,
  `access` tinyint(4) NOT NULL COMMENT '0 = PRIVATE,\r\n1 = NODE,\r\n2 = PUBLIC',
  `description` varchar(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`attic_id`),
  UNIQUE KEY `account_id_name` (`account_id`,`name`),
  KEY `enabled_access` (`enabled`,`access`),
  FULLTEXT KEY `description` (`description`),
  CONSTRAINT `FK_attics_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.attics: ~0 rows (approximately)
DELETE FROM `attics`;
/*!40000 ALTER TABLE `attics` DISABLE KEYS */;
/*!40000 ALTER TABLE `attics` ENABLE KEYS */;

-- Dumping structure for table attic_db.comments
CREATE TABLE IF NOT EXISTS `comments` (
  `file_id` int(10) unsigned NOT NULL,
  `from_id` int(10) unsigned DEFAULT NULL,
  `from` varchar(64) COLLATE utf8_bin NOT NULL,
  `comment` varchar(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL DEFAULT utc_timestamp(),
  KEY `FK_comments_files` (`file_id`),
  KEY `FK_comments_accounts` (`from_id`),
  KEY `from` (`from`),
  KEY `created` (`created`),
  FULLTEXT KEY `comment` (`comment`),
  CONSTRAINT `FK_comments_accounts` FOREIGN KEY (`from_id`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `FK_comments_files` FOREIGN KEY (`file_id`) REFERENCES `files` (`file_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.comments: ~0 rows (approximately)
DELETE FROM `comments`;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;

-- Dumping structure for table attic_db.files
CREATE TABLE IF NOT EXISTS `files` (
  `file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attic_id` int(10) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `folder_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `enabled` tinyint(4) NOT NULL,
  `access` tinyint(4) NOT NULL COMMENT '0 = PRIVATE,\r\n1 = NODE,\r\n2 = PUBLIC',
  `comments_on` tinyint(4) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `current_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT utc_timestamp(),
  `updated` datetime NOT NULL DEFAULT utc_timestamp(),
  `weight` int(10) unsigned NOT NULL DEFAULT 0,
  `description` varchar(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`file_id`),
  UNIQUE KEY `folder_id_name` (`folder_id`,`name`),
  KEY `enabled_access` (`enabled`,`access`),
  KEY `comments_on` (`comments_on`),
  KEY `size` (`size`),
  KEY `current_size` (`current_size`),
  KEY `created` (`created`),
  KEY `updated` (`updated`),
  KEY `weight` (`weight`),
  KEY `FK_files_attics` (`attic_id`),
  KEY `FK_files_accounts` (`account_id`),
  FULLTEXT KEY `description` (`description`),
  CONSTRAINT `FK_files_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_files_attics` FOREIGN KEY (`attic_id`) REFERENCES `attics` (`attic_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_files_folders` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`folder_id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.files: ~0 rows (approximately)
DELETE FROM `files`;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
/*!40000 ALTER TABLE `files` ENABLE KEYS */;

-- Dumping structure for table attic_db.folders
CREATE TABLE IF NOT EXISTS `folders` (
  `folder_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attic_id` int(10) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`folder_id`),
  UNIQUE KEY `parent_id_name` (`parent_id`,`name`),
  KEY `FK_folders_attics` (`attic_id`),
  KEY `FK_folders_accounts` (`account_id`),
  CONSTRAINT `FK_folders_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_folders_attics` FOREIGN KEY (`attic_id`) REFERENCES `attics` (`attic_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_folders_folders` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`folder_id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.folders: ~0 rows (approximately)
DELETE FROM `folders`;
/*!40000 ALTER TABLE `folders` DISABLE KEYS */;
/*!40000 ALTER TABLE `folders` ENABLE KEYS */;

-- Dumping structure for table attic_db.logs
CREATE TABLE IF NOT EXISTS `logs` (
  `account_id` int(10) unsigned NOT NULL,
  `operation` varchar(32) COLLATE utf8_bin NOT NULL,
  `msg` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL DEFAULT utc_timestamp(),
  KEY `FK__accounts` (`account_id`),
  CONSTRAINT `FK__accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.logs: ~1 rows (approximately)
DELETE FROM `logs`;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` (`account_id`, `operation`, `msg`, `created`) VALUES
	(1, 'ACCOUNT INSERT', 'name = Default Admin\nemail = admin@attic.com\npassword = *\nenabled = 1\nactivated = 1\naccess = 0\nprivileges = 0\nmax_space = 10737418240\nmax_attics_count = 10\ncontact_email = \ncontact_phone = \ndescription = \ndelete_on = NULL', '2021-09-12 03:46:15');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;

-- Dumping structure for table attic_db.node
CREATE TABLE IF NOT EXISTS `node` (
  `single_row_keeper` enum('') COLLATE utf8_bin NOT NULL DEFAULT '',
  `salt` varchar(16) COLLATE utf8_bin NOT NULL,
  `fqdn` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `registration_mode` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 = PRIVATE,\r\n1 = INVITATION,\r\n2 = PUBLIC',
  `sessions_timeout` int(10) unsigned NOT NULL COMMENT 'min',
  `activation_invitation_tokens_timeout` int(10) unsigned NOT NULL COMMENT 'min',
  `password_email_tokens_timeout` int(10) unsigned NOT NULL COMMENT 'min',
  `account_expire_delete_timeout` int(10) unsigned NOT NULL COMMENT 'min',
  `max_space` bigint(20) unsigned NOT NULL COMMENT 'Bytes',
  `max_attics_count` int(10) unsigned NOT NULL,
  `max_folders_count` int(10) unsigned NOT NULL,
  `max_files_count` int(10) unsigned NOT NULL,
  `max_depth` int(10) unsigned NOT NULL,
  `storage_path` varchar(4096) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`single_row_keeper`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.node: ~1 rows (approximately)
DELETE FROM `node`;
/*!40000 ALTER TABLE `node` DISABLE KEYS */;
INSERT INTO `node` (`single_row_keeper`, `salt`, `fqdn`, `registration_mode`, `sessions_timeout`, `activation_invitation_tokens_timeout`, `password_email_tokens_timeout`, `account_expire_delete_timeout`, `max_space`, `max_attics_count`, `max_folders_count`, `max_files_count`, `max_depth`, `storage_path`) VALUES
	('', 'vSGBPclWvx0f3oCN', 'attic.com', 0, 1440, 4320, 60, 14400, 10737418240, 10, 1000, 1000000, 100, '/opt/data/attics/');
/*!40000 ALTER TABLE `node` ENABLE KEYS */;

-- Dumping structure for table attic_db.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `session_id` binary(32) NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `updated` datetime NOT NULL DEFAULT utc_timestamp(),
  UNIQUE KEY `session_id_account_id` (`session_id`,`account_id`) USING BTREE,
  KEY `updated` (`updated`) USING BTREE,
  KEY `FK_sessions_accounts` (`account_id`) USING BTREE,
  CONSTRAINT `FK_sessions_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.sessions: ~1 rows (approximately)
DELETE FROM `sessions`;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` (`session_id`, `account_id`, `updated`) VALUES
	(_binary 0x3f4f26e27ddb2af7702f0084cbcc4475cabb031cc681ecb9924b5beaef1dfba9, 1, '2021-10-03 02:48:54');
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;

-- Dumping structure for table attic_db.tags
CREATE TABLE IF NOT EXISTS `tags` (
  `tag_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(16) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.tags: ~0 rows (approximately)
DELETE FROM `tags`;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;

-- Dumping structure for table attic_db.tag_relations
CREATE TABLE IF NOT EXISTS `tag_relations` (
  `tag_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  `folder_id` int(10) unsigned DEFAULT NULL,
  `attic_id` int(10) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `tag_id_file_id` (`tag_id`,`file_id`),
  KEY `FK_tag_relations_files` (`file_id`),
  KEY `FK_tag_relations_files_3` (`account_id`),
  KEY `FK_tag_relations_files_2` (`attic_id`),
  KEY `FK_tag_relations_files_4` (`folder_id`),
  CONSTRAINT `FK_tag_relations_files` FOREIGN KEY (`file_id`) REFERENCES `files` (`file_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_tag_relations_files_2` FOREIGN KEY (`attic_id`) REFERENCES `files` (`attic_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tag_relations_files_3` FOREIGN KEY (`account_id`) REFERENCES `files` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tag_relations_files_4` FOREIGN KEY (`folder_id`) REFERENCES `files` (`folder_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tag_relations_tags` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.tag_relations: ~0 rows (approximately)
DELETE FROM `tag_relations`;
/*!40000 ALTER TABLE `tag_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag_relations` ENABLE KEYS */;

-- Dumping structure for table attic_db.tag_weight_accounts
CREATE TABLE IF NOT EXISTS `tag_weight_accounts` (
  `tag_id` int(10) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `weight` int(10) unsigned NOT NULL,
  UNIQUE KEY `tag_id_account_id` (`tag_id`,`account_id`),
  KEY `FK_tag_weight_accounts_accounts` (`account_id`),
  KEY `weight` (`weight`),
  CONSTRAINT `FK_tag_weight_accounts_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_tag_weight_accounts_tags` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.tag_weight_accounts: ~0 rows (approximately)
DELETE FROM `tag_weight_accounts`;
/*!40000 ALTER TABLE `tag_weight_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag_weight_accounts` ENABLE KEYS */;

-- Dumping structure for table attic_db.tag_weight_attics
CREATE TABLE IF NOT EXISTS `tag_weight_attics` (
  `tag_id` int(10) unsigned NOT NULL,
  `attic_id` int(10) unsigned NOT NULL,
  `weight` int(10) unsigned NOT NULL,
  UNIQUE KEY `tag_id_attic_id` (`tag_id`,`attic_id`),
  KEY `FK_tag_weight_attics_attics` (`attic_id`),
  KEY `weight` (`weight`),
  CONSTRAINT `FK_tag_weight_attics_attics` FOREIGN KEY (`attic_id`) REFERENCES `attics` (`attic_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_tag_weight_attics_tags` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.tag_weight_attics: ~0 rows (approximately)
DELETE FROM `tag_weight_attics`;
/*!40000 ALTER TABLE `tag_weight_attics` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag_weight_attics` ENABLE KEYS */;

-- Dumping structure for table attic_db.tag_weight_global
CREATE TABLE IF NOT EXISTS `tag_weight_global` (
  `tag_id` int(10) unsigned NOT NULL,
  `weight` int(10) unsigned NOT NULL,
  UNIQUE KEY `tag_id` (`tag_id`),
  KEY `weight` (`weight`),
  CONSTRAINT `FK__tags` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.tag_weight_global: ~0 rows (approximately)
DELETE FROM `tag_weight_global`;
/*!40000 ALTER TABLE `tag_weight_global` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag_weight_global` ENABLE KEYS */;

-- Dumping structure for table attic_db.tokens
CREATE TABLE IF NOT EXISTS `tokens` (
  `account_id` int(10) unsigned NOT NULL,
  `token_bin` binary(32) NOT NULL,
  `token_type` tinyint(4) NOT NULL COMMENT '0 = ACTIVATION,\r\n1 = INVITATION,\r\n2 = PASSWORD,\r\n3 = EMAIL',
  `created` datetime NOT NULL DEFAULT utc_timestamp(),
  UNIQUE KEY `token_bin_token_type` (`token_bin`,`token_type`) USING BTREE,
  KEY `created` (`created`) USING BTREE,
  KEY `FK_tokens_accounts` (`account_id`) USING BTREE,
  CONSTRAINT `FK_account_id` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table attic_db.tokens: ~0 rows (approximately)
DELETE FROM `tokens`;
/*!40000 ALTER TABLE `tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `tokens` ENABLE KEYS */;

-- Dumping structure for view attic_db.v_files
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_files` (
	`file_id` INT(10) UNSIGNED NOT NULL,
	`file_name` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
	`file_enabled` TINYINT(4) NOT NULL,
	`file_access` TINYINT(4) NOT NULL COMMENT '0 = PRIVATE,\r\n1 = NODE,\r\n2 = PUBLIC',
	`file_comments_on` TINYINT(4) NOT NULL,
	`file_size` BIGINT(20) UNSIGNED NOT NULL,
	`file_created` DATETIME NOT NULL,
	`file_updated` DATETIME NOT NULL,
	`file_weight` INT(10) UNSIGNED NOT NULL,
	`file_description` VARCHAR(500) NOT NULL COLLATE 'utf8_unicode_ci',
	`folder_id` INT(10) UNSIGNED NULL,
	`folder_name` VARCHAR(255) NULL COLLATE 'utf8_bin',
	`attic_id` INT(10) UNSIGNED NOT NULL,
	`attic_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci',
	`attic_enabled` TINYINT(4) NULL,
	`attic_access` TINYINT(4) NULL COMMENT '0 = PRIVATE,\r\n1 = NODE,\r\n2 = PUBLIC',
	`account_id` INT(10) UNSIGNED NOT NULL,
	`account_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci',
	`account_enabled` TINYINT(4) NULL,
	`account_access` TINYINT(4) NULL COMMENT '0 = PRIVATE,\r\n1 = AUTHORIZED\r\n2 = PUBLIC'
) ENGINE=MyISAM;

-- Dumping structure for view attic_db.v_tag_relations_accounts
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_tag_relations_accounts` (
	`tag` VARCHAR(16) NULL COLLATE 'utf8_bin',
	`weight` INT(10) UNSIGNED NULL,
	`file_id` INT(10) UNSIGNED NOT NULL,
	`file_name` VARCHAR(255) NULL COLLATE 'utf8_bin',
	`file_enabled` TINYINT(4) NULL,
	`file_access` TINYINT(4) NULL COMMENT '0 = PRIVATE,\r\n1 = NODE,\r\n2 = PUBLIC',
	`file_comments_on` TINYINT(4) NULL,
	`file_size` BIGINT(20) UNSIGNED NULL,
	`file_created` DATETIME NULL,
	`file_updated` DATETIME NULL,
	`file_weight` INT(10) UNSIGNED NULL,
	`file_description` VARCHAR(500) NULL COLLATE 'utf8_unicode_ci',
	`folder_id` INT(10) UNSIGNED NULL,
	`folder_name` VARCHAR(255) NULL COLLATE 'utf8_bin',
	`attic_id` INT(10) UNSIGNED NOT NULL,
	`attic_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci',
	`attic_enabled` TINYINT(4) NULL,
	`attic_access` TINYINT(4) NULL COMMENT '0 = PRIVATE,\r\n1 = NODE,\r\n2 = PUBLIC',
	`account_id` INT(10) UNSIGNED NOT NULL,
	`account_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci',
	`account_enabled` TINYINT(4) NULL,
	`account_access` TINYINT(4) NULL COMMENT '0 = PRIVATE,\r\n1 = AUTHORIZED\r\n2 = PUBLIC'
) ENGINE=MyISAM;

-- Dumping structure for view attic_db.v_tag_relations_attics
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_tag_relations_attics` (
	`tag` VARCHAR(16) NULL COLLATE 'utf8_bin',
	`weight` INT(10) UNSIGNED NULL,
	`file_id` INT(10) UNSIGNED NOT NULL,
	`file_name` VARCHAR(255) NULL COLLATE 'utf8_bin',
	`file_enabled` TINYINT(4) NULL,
	`file_access` TINYINT(4) NULL COMMENT '0 = PRIVATE,\r\n1 = NODE,\r\n2 = PUBLIC',
	`file_comments_on` TINYINT(4) NULL,
	`file_size` BIGINT(20) UNSIGNED NULL,
	`file_created` DATETIME NULL,
	`file_updated` DATETIME NULL,
	`file_weight` INT(10) UNSIGNED NULL,
	`file_description` VARCHAR(500) NULL COLLATE 'utf8_unicode_ci',
	`folder_id` INT(10) UNSIGNED NULL,
	`folder_name` VARCHAR(255) NULL COLLATE 'utf8_bin',
	`attic_id` INT(10) UNSIGNED NOT NULL,
	`attic_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci',
	`attic_enabled` TINYINT(4) NULL,
	`attic_access` TINYINT(4) NULL COMMENT '0 = PRIVATE,\r\n1 = NODE,\r\n2 = PUBLIC',
	`account_id` INT(10) UNSIGNED NOT NULL,
	`account_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci',
	`account_enabled` TINYINT(4) NULL,
	`account_access` TINYINT(4) NULL COMMENT '0 = PRIVATE,\r\n1 = AUTHORIZED\r\n2 = PUBLIC'
) ENGINE=MyISAM;

-- Dumping structure for view attic_db.v_tag_relations_global
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_tag_relations_global` (
	`tag` VARCHAR(16) NULL COLLATE 'utf8_bin',
	`weight` INT(10) UNSIGNED NULL,
	`file_id` INT(10) UNSIGNED NOT NULL,
	`file_name` VARCHAR(255) NULL COLLATE 'utf8_bin',
	`file_enabled` TINYINT(4) NULL,
	`file_access` TINYINT(4) NULL COMMENT '0 = PRIVATE,\r\n1 = NODE,\r\n2 = PUBLIC',
	`file_comments_on` TINYINT(4) NULL,
	`file_size` BIGINT(20) UNSIGNED NULL,
	`file_created` DATETIME NULL,
	`file_updated` DATETIME NULL,
	`file_weight` INT(10) UNSIGNED NULL,
	`file_description` VARCHAR(500) NULL COLLATE 'utf8_unicode_ci',
	`folder_id` INT(10) UNSIGNED NULL,
	`folder_name` VARCHAR(255) NULL COLLATE 'utf8_bin',
	`attic_id` INT(10) UNSIGNED NOT NULL,
	`attic_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci',
	`attic_enabled` TINYINT(4) NULL,
	`attic_access` TINYINT(4) NULL COMMENT '0 = PRIVATE,\r\n1 = NODE,\r\n2 = PUBLIC',
	`account_id` INT(10) UNSIGNED NOT NULL,
	`account_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci',
	`account_enabled` TINYINT(4) NULL,
	`account_access` TINYINT(4) NULL COMMENT '0 = PRIVATE,\r\n1 = AUTHORIZED\r\n2 = PUBLIC'
) ENGINE=MyISAM;

-- Dumping structure for view attic_db.v_tag_weight_accounts
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_tag_weight_accounts` (
	`tag` VARCHAR(16) NULL COLLATE 'utf8_bin',
	`account_id` INT(10) UNSIGNED NOT NULL,
	`weight` INT(10) UNSIGNED NOT NULL
) ENGINE=MyISAM;

-- Dumping structure for view attic_db.v_tag_weight_attics
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_tag_weight_attics` (
	`tag` VARCHAR(16) NULL COLLATE 'utf8_bin',
	`attic_id` INT(10) UNSIGNED NOT NULL,
	`weight` INT(10) UNSIGNED NOT NULL
) ENGINE=MyISAM;

-- Dumping structure for view attic_db.v_tag_weight_global
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_tag_weight_global` (
	`tag` VARCHAR(16) NULL COLLATE 'utf8_bin',
	`weight` INT(10) UNSIGNED NOT NULL
) ENGINE=MyISAM;

-- Dumping structure for procedure attic_db.create_v_files
DELIMITER //
CREATE PROCEDURE `create_v_files`()
    READS SQL DATA
    DETERMINISTIC
BEGIN
	CREATE OR REPLACE VIEW `v_files` AS
	SELECT
		`files`.`file_id`,
		`files`.`name` AS `file_name`, 
		`files`.`enabled` AS `file_enabled`, 
		`files`.`access` AS `file_access`, 
		`files`.`comments_on` AS `file_comments_on`, 
		`files`.`size` AS `file_size`, 
		`files`.`created` AS `file_created`, 
		`files`.`updated` AS `file_updated`, 
		`files`.`weight` AS `file_weight`, 
		`files`.`description` AS `file_description`, 

		`files`.`folder_id`,
		`folders`.`name` AS `folder_name`,

		`files`.`attic_id`,
		`attics`.`name` AS `attic_name`,
		`attics`.`enabled` AS `attic_enabled`, 
		`attics`.`access` AS `attic_access`, 

		`files`.`account_id`,
		`accounts`.`name` AS `account_name`,
		`accounts`.`enabled` AS `account_enabled`, 
		`accounts`.`access` AS `account_access`

	FROM `files`
	LEFT OUTER JOIN `folders` ON `files`.`folder_id` = `folders`.`folder_id`
	LEFT OUTER JOIN `attics` ON `files`.`attic_id` = `attics`.`attic_id`
	LEFT OUTER JOIN `accounts` ON `files`.`account_id` = `accounts`.`account_id`;
END//
DELIMITER ;

-- Dumping structure for procedure attic_db.create_v_tag_relations_accounts
DELIMITER //
CREATE PROCEDURE `create_v_tag_relations_accounts`()
    READS SQL DATA
    DETERMINISTIC
BEGIN
	CREATE OR REPLACE VIEW `v_tag_relations_accounts` AS
	SELECT
		`tags`.`tag`,
		`tag_weight_accounts`.`weight`,

		`tag_relations`.`file_id`,
		`files`.`name` AS `file_name`, 
		`files`.`enabled` AS `file_enabled`, 
		`files`.`access` AS `file_access`, 
		`files`.`comments_on` AS `file_comments_on`, 
		`files`.`size` AS `file_size`, 
		`files`.`created` AS `file_created`, 
		`files`.`updated` AS `file_updated`, 
		`files`.`weight` AS `file_weight`, 
		`files`.`description` AS `file_description`, 

		`tag_relations`.`folder_id`,
		`folders`.`name` AS `folder_name`,

		`tag_relations`.`attic_id`,
		`attics`.`name` AS `attic_name`,
		`attics`.`enabled` AS `attic_enabled`, 
		`attics`.`access` AS `attic_access`, 

		`tag_relations`.`account_id`,
		`accounts`.`name` AS `account_name`,
		`accounts`.`enabled` AS `account_enabled`, 
		`accounts`.`access` AS `account_access` 

	FROM `tag_relations`
	LEFT OUTER JOIN `tags` ON `tag_relations`.`tag_id` = `tags`.`tag_id`
	LEFT OUTER JOIN `files` ON `tag_relations`.`file_id` = `files`.`file_id` 
	LEFT OUTER JOIN `folders` ON `tag_relations`.`folder_id` = `folders`.`folder_id`
	LEFT OUTER JOIN `attics` ON `tag_relations`.`attic_id` = `attics`.`attic_id`
	LEFT OUTER JOIN `accounts` ON `tag_relations`.`account_id` = `accounts`.`account_id`
	LEFT OUTER JOIN `tag_weight_accounts` ON 
		`tag_relations`.`tag_id` = `tag_weight_accounts`.`tag_id` AND `tag_relations`.`account_id` = `tag_weight_accounts`.`account_id`;
END//
DELIMITER ;

-- Dumping structure for procedure attic_db.create_v_tag_relations_attics
DELIMITER //
CREATE PROCEDURE `create_v_tag_relations_attics`()
    READS SQL DATA
    DETERMINISTIC
BEGIN
	CREATE OR REPLACE VIEW `v_tag_relations_attics` AS
	SELECT
		`tags`.`tag`,
		`tag_weight_attics`.`weight`,

		`tag_relations`.`file_id`,
		`files`.`name` AS `file_name`, 
		`files`.`enabled` AS `file_enabled`, 
		`files`.`access` AS `file_access`, 
		`files`.`comments_on` AS `file_comments_on`, 
		`files`.`size` AS `file_size`, 
		`files`.`created` AS `file_created`, 
		`files`.`updated` AS `file_updated`, 
		`files`.`weight` AS `file_weight`, 
		`files`.`description` AS `file_description`, 

		`tag_relations`.`folder_id`,
		`folders`.`name` AS `folder_name`,

		`tag_relations`.`attic_id`,
		`attics`.`name` AS `attic_name`,
		`attics`.`enabled` AS `attic_enabled`, 
		`attics`.`access` AS `attic_access`, 

		`tag_relations`.`account_id`,
		`accounts`.`name` AS `account_name`,
		`accounts`.`enabled` AS `account_enabled`, 
		`accounts`.`access` AS `account_access` 

	FROM `tag_relations`
	LEFT OUTER JOIN `tags` ON `tag_relations`.`tag_id` = `tags`.`tag_id`
	LEFT OUTER JOIN `files` ON `tag_relations`.`file_id` = `files`.`file_id` 
	LEFT OUTER JOIN `folders` ON `tag_relations`.`folder_id` = `folders`.`folder_id`
	LEFT OUTER JOIN `attics` ON `tag_relations`.`attic_id` = `attics`.`attic_id`
	LEFT OUTER JOIN `accounts` ON `tag_relations`.`account_id` = `accounts`.`account_id`
	LEFT OUTER JOIN `tag_weight_attics` ON 
		`tag_relations`.`tag_id` = `tag_weight_attics`.`tag_id` AND `tag_relations`.`attic_id` = `tag_weight_attics`.`attic_id`; 
END//
DELIMITER ;

-- Dumping structure for procedure attic_db.create_v_tag_relations_global
DELIMITER //
CREATE PROCEDURE `create_v_tag_relations_global`()
    READS SQL DATA
    DETERMINISTIC
BEGIN
	CREATE OR REPLACE VIEW `v_tag_relations_global` AS
	SELECT
		`tags`.`tag`,
		`tag_weight_global`.`weight`,

		`tag_relations`.`file_id`,
		`files`.`name` AS `file_name`, 
		`files`.`enabled` AS `file_enabled`, 
		`files`.`access` AS `file_access`, 
		`files`.`comments_on` AS `file_comments_on`, 
		`files`.`size` AS `file_size`, 
		`files`.`created` AS `file_created`, 
		`files`.`updated` AS `file_updated`, 
		`files`.`weight` AS `file_weight`, 
		`files`.`description` AS `file_description`, 

		`tag_relations`.`folder_id`,
		`folders`.`name` AS `folder_name`,

		`tag_relations`.`attic_id`,
		`attics`.`name` AS `attic_name`,
		`attics`.`enabled` AS `attic_enabled`, 
		`attics`.`access` AS `attic_access`, 

		`tag_relations`.`account_id`,
		`accounts`.`name` AS `account_name`,
		`accounts`.`enabled` AS `account_enabled`, 
		`accounts`.`access` AS `account_access` 

	FROM `tag_relations`
	LEFT OUTER JOIN `tags` ON `tag_relations`.`tag_id` = `tags`.`tag_id`
	LEFT OUTER JOIN `files` ON `tag_relations`.`file_id` = `files`.`file_id` 
	LEFT OUTER JOIN `folders` ON `tag_relations`.`folder_id` = `folders`.`folder_id`
	LEFT OUTER JOIN `attics` ON `tag_relations`.`attic_id` = `attics`.`attic_id`
	LEFT OUTER JOIN `accounts` ON `tag_relations`.`account_id` = `accounts`.`account_id`
	LEFT OUTER JOIN `tag_weight_global` ON 
		`tag_relations`.`tag_id` = `tag_weight_global`.`tag_id`; 
END//
DELIMITER ;

-- Dumping structure for procedure attic_db.create_v_tag_weight_accounts
DELIMITER //
CREATE PROCEDURE `create_v_tag_weight_accounts`()
    READS SQL DATA
    DETERMINISTIC
BEGIN
	CREATE OR REPLACE VIEW `v_tag_weight_accounts` AS
	SELECT
		`tags`.`tag`,
		`tag_weight_accounts`.`account_id`,
		`tag_weight_accounts`.`weight`
	FROM `tag_weight_accounts`
	LEFT OUTER JOIN `tags` ON 
		`tag_weight_accounts`.`tag_id` = `tags`.`tag_id`;
END//
DELIMITER ;

-- Dumping structure for procedure attic_db.create_v_tag_weight_attics
DELIMITER //
CREATE PROCEDURE `create_v_tag_weight_attics`()
    READS SQL DATA
    DETERMINISTIC
BEGIN
	CREATE OR REPLACE VIEW `v_tag_weight_attics` AS
	SELECT
		`tags`.`tag`,
		`tag_weight_attics`.`attic_id`,
		`tag_weight_attics`.`weight`
	FROM `tag_weight_attics`
	LEFT OUTER JOIN `tags` ON 
		`tag_weight_attics`.`tag_id` = `tags`.`tag_id`;
END//
DELIMITER ;

-- Dumping structure for procedure attic_db.create_v_tag_weight_global
DELIMITER //
CREATE PROCEDURE `create_v_tag_weight_global`()
    READS SQL DATA
    DETERMINISTIC
BEGIN
	CREATE OR REPLACE VIEW `v_tag_weight_global` AS
	SELECT
		`tags`.`tag`,
		`tag_weight_global`.`weight`
	FROM `tag_weight_global`
	LEFT OUTER JOIN `tags` ON 
		`tag_weight_global`.`tag_id` = `tags`.`tag_id`;
END//
DELIMITER ;

-- Dumping structure for procedure attic_db.extract_tag
DELIMITER //
CREATE PROCEDURE `extract_tag`(
	INOUT `_str_` TEXT,
	IN `_allowed_len` INT,
	OUT `tag_` TEXT
)
BEGIN
	DECLARE _i_ INT DEFAULT 1;
	DECLARE _ch_ CHAR(1);
	DECLARE _next_ch_ CHAR(1);
	DECLARE _word_ TEXT DEFAULT '';
	DECLARE _len_ INT DEFAULT CHAR_LENGTH(_str_);
	
	DECLARE _stop_chars_ TEXT DEFAULT ' #/`*()-+={}[]|<>?,.:;\'\"\%\b\n\r\t\z\\';

	label1: BEGIN
		WHILE _i_ < _len_ DO
			SET _ch_ = SUBSTRING(_str_, _i_, 1);
			SET _next_ch_ = SUBSTRING(_str_, _i_ + 1, 1);
			IF _ch_ = '#' AND NOT INSTR(_stop_chars_, _next_ch_) THEN
				SET _i_ = _i_ + 1;
				WHILE _i_ <= _len_ DO
					SET _ch_ = SUBSTRING(_str_, _i_, 1);
					IF NOT INSTR(_stop_chars_, _ch_) THEN
						SET _word_ = CONCAT(_word_, _ch_);
						IF CHAR_LENGTH(_word_) = _allowed_len THEN
							LEAVE label1;
						END IF;
					ELSE
						LEAVE label1;
					END IF;
					SET _i_ = _i_ + 1;
				END WHILE;
			ELSE
				SET _i_ = _i_ + 1;
			END IF;
		END WHILE;
	END label1;

	SET _str_ =  SUBSTRING(_str_, _i_ + 1);
	IF CHAR_LENGTH(_str_) = 0 THEN
		SET _str_ = NULL;
	END IF;
	
	IF CHAR_LENGTH(_word_) = 0 THEN
		SET tag_ = NULL;
	ELSE
		SET tag_ = _word_;
	END IF;
END//
DELIMITER ;

-- Dumping structure for procedure attic_db.generate_tags
DELIMITER //
CREATE PROCEDURE `generate_tags`(
	IN `_file_id` BIGINT UNSIGNED,
	IN `_text` TEXT
)
    MODIFIES SQL DATA
BEGIN
	DECLARE _tag_ VARCHAR(16);
	DECLARE _tag_id_ INT UNSIGNED;
	DECLARE _folder_id_ INT UNSIGNED;
	DECLARE _attic_id_ INT UNSIGNED;
	DECLARE _account_id_ INT UNSIGNED;
	
	SELECT `folder_id`, `attic_id`, `account_id`
		INTO _folder_id_, _attic_id_, _account_id_
		FROM `files`
	WHERE `file_id` = _file_id;
	
	DELETE FROM `tag_relations` WHERE `file_id` = _file_id;
	
	WHILE CHAR_LENGTH(_text) IS NOT NULL DO
		CALL extract_tag(_text, 16, _tag_);
		IF _tag_ IS NOT NULL THEN
			SET _tag_id_ = (SELECT `tag_id` FROM `tags` WHERE `tag` = _tag_);
			IF _tag_id_ IS NULL THEN
				INSERT INTO `tags`(`tag`) VALUES (_tag_); 
				SET _tag_id_ = LAST_INSERT_ID();
			END IF; 
			
			INSERT IGNORE INTO `tag_weight_global`(
				`tag_id`,
				`weight`
			) VALUES (
				_tag_id_,
				0
			);
			
			INSERT IGNORE INTO `tag_weight_accounts`(
				`tag_id`,
				`account_id`,
				`weight`
			) VALUES (
				_tag_id_,
				_account_id_,
				0
			);
			
			INSERT IGNORE INTO `tag_weight_attics`(
				`tag_id`,
				`attic_id`,
				`weight`
			) VALUES (
				_tag_id_,
				_attic_id_,
				0
			);
			
			INSERT IGNORE INTO `tag_relations`(
				`tag_id`,
				`file_id`,
				`folder_id`,
				`attic_id`,
				`account_id`
			) VALUES (
				_tag_id_,
				_file_id,
				_folder_id_,
				_attic_id_,
				_account_id_
			);
		END IF;
		
	END WHILE;
END//
DELIMITER ;

-- Dumping structure for procedure attic_db.internal_reset_db
DELIMITER //
CREATE PROCEDURE `internal_reset_db`()
BEGIN
/* 
	!!! WARNING !!! 

	This will reset database to default
*/

	DECLARE _salt_ VARCHAR(16) DEFAULT random_string(16);
	DECLARE _account_name_ VARCHAR(64) DEFAULT 'Default Admin';
	DECLARE _password_ VARCHAR(32) DEFAULT 'Admin0';
	DECLARE _fqdn_ VARCHAR(255) DEFAULT 'attic.com';
	DECLARE _email_ VARCHAR(320) DEFAULT CONCAT('admin@', _fqdn_);

	DELETE FROM `node`;
	INSERT INTO `node`(
		`salt`,
		`fqdn`,
		`registration_mode`,
		`sessions_timeout`,
		`activation_invitation_tokens_timeout`,
		`password_email_tokens_timeout`,
		`account_expire_delete_timeout`,
		`max_space`,
		`max_attics_count`,
		`max_folders_count`,
		`max_files_count`,
		`max_depth`,
		`storage_path`
	) VALUES (
		_salt_,
		_fqdn_,
		0,
		1440,
		4320,
		60,
		14400,
		10737418240,
		10,
		1000,
		1000000,
		100,
		'/opt/data/attics/'
	);
	
	DELETE FROM `accounts`;
	DELETE FROM `tags`;
	DELETE FROM `sessions`;
	ALTER TABLE `accounts`	AUTO_INCREMENT = 1;
	ALTER TABLE `attics`	AUTO_INCREMENT = 1;
	ALTER TABLE `folders`	AUTO_INCREMENT = 1;
	ALTER TABLE `files`	AUTO_INCREMENT = 1;
	ALTER TABLE `tags` AUTO_INCREMENT = 1;
	
	INSERT INTO `accounts`(
		`name`,
		`email`,
		`password_bin`,
		`enabled`,
		`activated`,
		`access`,
		`privileges`,
		`max_space`,
		`max_attics_count`,
		`max_folders_count`,
		`max_files_count`
	) VALUES (
		_account_name_,
		_email_,
		encrypt_string(_password_),
		1,
		1,
		0,
		0,
		(SELECT `max_space` FROM `node`),
		(SELECT `max_attics_count` FROM `node`),
		(SELECT `max_folders_count` FROM `node`),
		(SELECT `max_files_count` FROM `node`)
	);

END//
DELIMITER ;

-- Dumping structure for function attic_db.bin_to_token
DELIMITER //
CREATE FUNCTION `bin_to_token`(`_bin` BINARY(32)
) RETURNS varchar(64) CHARSET utf8 COLLATE utf8_bin
    NO SQL
    DETERMINISTIC
BEGIN
	RETURN UPPER(HEX(_bin));
END//
DELIMITER ;

-- Dumping structure for function attic_db.create_token
DELIMITER //
CREATE FUNCTION `create_token`() RETURNS varchar(64) CHARSET utf8 COLLATE utf8_bin
    NO SQL
BEGIN
	RETURN UPPER(SHA2(CONCAT(NOW(), `random_string`(32), UUID()), 256));
END//
DELIMITER ;

-- Dumping structure for function attic_db.encrypt_string
DELIMITER //
CREATE FUNCTION `encrypt_string`(`_string` TEXT
) RETURNS binary(32)
    NO SQL
BEGIN
	RETURN UNHEX(SHA2(CONCAT((SELECT `salt` FROM `node`), _string), 256));
END//
DELIMITER ;

-- Dumping structure for function attic_db.random_string
DELIMITER //
CREATE FUNCTION `random_string`(`_length` INT UNSIGNED
) RETURNS text CHARSET utf8 COLLATE utf8_bin
    NO SQL
BEGIN
	DECLARE _i_ TINYINT UNSIGNED DEFAULT 0;
	DECLARE _result_ TEXT DEFAULT '';

	WHILE (_i_ < _length) DO
		SET _result_ = CONCAT(
			_result_,
			SUBSTRING('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
			FLOOR(RAND() * 62) + 1, 1)
		);
		SET _i_ = _i_ + 1;
	END WHILE;

	RETURN _result_;
END//
DELIMITER ;

-- Dumping structure for function attic_db.token_to_bin
DELIMITER //
CREATE FUNCTION `token_to_bin`(`_token` VARCHAR(64)
) RETURNS binary(32)
    NO SQL
    DETERMINISTIC
BEGIN
	RETURN UNHEX(_token);
END//
DELIMITER ;

-- Dumping structure for function attic_db.verify_file_create
DELIMITER //
CREATE FUNCTION `verify_file_create`(`_account_id` INT UNSIGNED,
	`_attic_id` INT UNSIGNED,
	`_folder_id` INT UNSIGNED
) RETURNS tinyint(4)
    READS SQL DATA
    DETERMINISTIC
BEGIN
	DECLARE _id_ INT UNSIGNED;
	DECLARE _current_files_count_ INT UNSIGNED;
	DECLARE _max_files_count_ INT UNSIGNED;
	
	/* check if account exists */
	/*
	SELECT `max_files_count`, `current_files_count` 
		INTO _max_files_count_, _current_files_count_
		FROM `accounts` 
	WHERE `account_id` = _account_id LOCK IN SHARE MODE;
	IF _max_files_count_ IS NULL THEN
		RETURN 1;
	END IF;
	*/
	/* Check if max_files_count not reached */
	/*
	IF _current_files_count_ >= _max_files_count_ THEN
		RETURN 2;
	END IF;
	*/
	/* check if attic exists */
	SET _id_ = (SELECT `account_id` FROM `attics` WHERE `attic_id` = _attic_id LOCK IN SHARE MODE);
	IF _id_ IS NULL THEN
		RETURN 3;
	END IF;
	
	/* check if attic belongs to provided account */
	/*
	IF _account_id <> _id_ THEN
		RETURN 4;
	END IF;
	*/
	IF _folder_id IS NOT NULL THEN
		/* check if parent folder exists */
		IF NOT EXISTS(SELECT 0 FROM `folders` WHERE `folder_id` = _folder_id LOCK IN SHARE MODE) THEN
			RETURN 5;
		END IF;
		
		/* check if parent folder belongs to provided attic */
		/*
		SET _id_ = (SELECT `attic_id` FROM `folders` WHERE `folder_id` = _folder_id LOCK IN SHARE MODE);
		IF _attic_id <> _id_ THEN
			RETURN 6;
		END IF;
		*/
	END IF;
		
	RETURN 0;
END//
DELIMITER ;

-- Dumping structure for function attic_db.verify_file_delete
DELIMITER //
CREATE FUNCTION `verify_file_delete`(`_account_id` INT UNSIGNED,
	`_file_id` INT UNSIGNED
) RETURNS tinyint(4)
    READS SQL DATA
    DETERMINISTIC
BEGIN
	DECLARE _id_ INT UNSIGNED;
	
	/* check if file exists */
	SET _id_ = (SELECT `account_id` FROM `files` WHERE `file_id` = _file_id LOCK IN SHARE MODE); 
	IF _id_ IS NULL THEN
		RETURN 1;
	END IF;

	/* check if account exists */
	IF NOT EXISTS(SELECT 0 FROM `accounts` WHERE `account_id` = _account_id LOCK IN SHARE MODE) THEN
		RETURN 2;
	END IF;
	
	/* check if file belongs to provided account */
	IF _account_id <> _id_ THEN
		RETURN 3;
	END IF;

	RETURN 0;
END//
DELIMITER ;

-- Dumping structure for function attic_db.verify_file_move
DELIMITER //
CREATE FUNCTION `verify_file_move`(`_account_id` INT UNSIGNED,
	`_file_id` INT UNSIGNED,
	`_parent_id` INT UNSIGNED
) RETURNS tinyint(4)
    READS SQL DATA
    DETERMINISTIC
BEGIN
	DECLARE _id1_ INT UNSIGNED;
	DECLARE _id2_ INT UNSIGNED;
	
	/* check if file exists */
	SET _id1_ = (SELECT `account_id` FROM `files` WHERE `file_id` = _file_id LOCK IN SHARE MODE); 
	IF _id1_ IS NULL THEN
		RETURN 1;
	END IF;
	
	/* check if file belongs to provided account */
	IF _id1_ <> _account_id THEN
		RETURN 2;
	END IF;
	
	/* if parent is null, allow to move to root of source attic */
	IF _parent_id IS NULL THEN
		RETURN 0;
	END IF;
	
	/* check if target folder exists */
	SET _id1_ = (SELECT `account_id` FROM `folders` WHERE `folder_id` = _parent_id LOCK IN SHARE MODE); 
	IF _id1_ IS NULL THEN
		RETURN 3;
	END IF;

	/* check if target folder belongs to the provided account */
	IF _id1_ <> _account_id THEN
		RETURN 4;
	END IF;

	/* check if source file and target folder belong to the same attic */
	SET _id1_ = (SELECT `attic_id` FROM `files` WHERE `file_id` = _file_id); 
	SET _id2_ = (SELECT `attic_id` FROM `folders` WHERE `folder_id` = _parent_id); 
	IF _id1_ <> _id2_ THEN
		RETURN 5;
	END IF;
	
	RETURN 0;
END//
DELIMITER ;

-- Dumping structure for function attic_db.verify_folder_create
DELIMITER //
CREATE FUNCTION `verify_folder_create`(`_account_id` INT UNSIGNED,
	`_attic_id` INT UNSIGNED,
	`_parent_id` INT UNSIGNED
) RETURNS tinyint(4)
    READS SQL DATA
    DETERMINISTIC
BEGIN
	DECLARE _id_ INT UNSIGNED;
	DECLARE _depth_ INT UNSIGNED DEFAULT 1;
	DECLARE _max_depth_ INT UNSIGNED;
	DECLARE _current_folders_count_ INT UNSIGNED;
	DECLARE _max_folders_count_ INT UNSIGNED;
	
	/* check if account exists */
	/*
	SELECT `max_folders_count`, `current_folders_count` 
		INTO _max_folders_count_, _current_folders_count_
		FROM `accounts` 
	WHERE `account_id` = _account_id LOCK IN SHARE MODE;
	IF _max_folders_count_ IS NULL THEN
		RETURN 1;
	END IF;
	*/
	
	/* Check if max_folders_count not reached */
	/*
	IF _current_folders_count_ >= _max_folders_count_ THEN
		RETURN 2;
	END IF;
	*/
	
	/* check if attic exists */
	SET _id_ = (SELECT `account_id` FROM `attics` WHERE `attic_id` = _attic_id LOCK IN SHARE MODE);
	IF _id_ IS NULL THEN
		RETURN 3;
	END IF;
	
	
	/* check if attic belongs to provided account */
	IF _account_id <> _id_ THEN
		RETURN 4;
	END IF;
	
	IF _parent_id IS NOT NULL THEN
		/* check if parent folder exists */
		IF NOT EXISTS(SELECT 0 FROM `folders` WHERE `folder_id` = _parent_id LOCK IN SHARE MODE) THEN
			RETURN 5;
		END IF;
		
		/* check if parent folder belongs to provided attic */
		SET _id_ = (SELECT `attic_id` FROM `folders` WHERE `folder_id` = _parent_id LOCK IN SHARE MODE);
		IF _attic_id <> _id_ THEN
			RETURN 6;
		END IF;
	END IF;
		
	/* check max depth */
	SET _max_depth_ = (SELECT `max_depth` FROM `node`);
	WHILE _parent_id IS NOT NULL DO
		SET _parent_id = (SELECT `parent_id` FROM `folders` WHERE `folder_id` = _parent_id LOCK IN SHARE MODE);
		SET _depth_ = _depth_ + 1;
	END WHILE;
	IF (_depth_ >= _max_depth_) THEN
			RETURN 7;
	END IF;

	RETURN 0;
END//
DELIMITER ;

-- Dumping structure for function attic_db.verify_folder_delete
DELIMITER //
CREATE FUNCTION `verify_folder_delete`(`_account_id` INT UNSIGNED,
	`_folder_id` INT UNSIGNED
) RETURNS tinyint(4)
    READS SQL DATA
    DETERMINISTIC
BEGIN
	/*NO NEED TO USE*/
	DECLARE _id_ INT UNSIGNED;
	
	/* check if folder exists */
	SET _id_ = (SELECT `account_id` FROM `folders` WHERE `folder_id` = _folder_id LOCK IN SHARE MODE); 
	IF _id_ IS NULL THEN
		RETURN 1;
	END IF;

	/* check if account exists */
	/*
	IF NOT EXISTS(SELECT 0 FROM `accounts` WHERE `account_id` = _account_id LOCK IN SHARE MODE) THEN
		RETURN 2;
	END IF;
	*/
	
	/* check if folder belongs to provided account */
	/*
	IF _account_id <> _id_ THEN
		RETURN 3;
	END IF;
	*/
	
	/* Check if folder is empty */
	IF EXISTS(SELECT 0 FROM `files` WHERE `parent_id` = _folder_id LIMIT 1) THEN
			RETURN 4;
	END IF;
		
	RETURN 0;
END//
DELIMITER ;

-- Dumping structure for function attic_db.verify_folder_move
DELIMITER //
CREATE FUNCTION `verify_folder_move`(`_account_id` INT UNSIGNED,
	`_folder_id` INT UNSIGNED,
	`_parent_id` INT UNSIGNED
) RETURNS tinyint(4)
    READS SQL DATA
    DETERMINISTIC
BEGIN
	DECLARE _id1_ INT UNSIGNED;
	DECLARE _id2_ INT UNSIGNED;
	DECLARE _depth_ INT UNSIGNED DEFAULT 1;
	DECLARE _max_depth_ INT UNSIGNED;
	
	/* check if source folder exists */
	/*
	SET _id1_ = (SELECT `account_id` FROM `folders` WHERE `folder_id` = _folder_id LOCK IN SHARE MODE); 
	IF _id1_ IS NULL THEN
		RETURN 1;
	END IF;
	*/
	
	/* check if source folder belongs to provided account */
	/*
	IF _id1_ <> _account_id THEN
		RETURN 2;
	END IF;
	*/
	
	/* if parent is null, allow to move to root of source attic */
	/*
	IF _parent_id IS NULL THEN
		RETURN 0;
	END IF;
	*/
	
	/* check if parent folder exists */
	IF _parent_id IS NOT NULL THEN
		SET _id1_ = (SELECT `account_id` FROM `folders` WHERE `folder_id` = _parent_id LOCK IN SHARE MODE); 
		IF _id1_ IS NULL THEN
			RETURN 3;
		END IF;
	END IF;

	
	/* check if target folder belongs to the provided account */
	/*
	IF _id1_ <> _account_id THEN
		RETURN 4;
	END IF;
	*/
	
	/* check if source and target folders belong to the same attic */
	SET _id1_ = (SELECT `attic_id` FROM `folders` WHERE `folder_id` = _folder_id); 
	SET _id2_ = (SELECT `attic_id` FROM `folders` WHERE `folder_id` = _parent_id); 
	IF _id1_ <> _id2_ THEN
		RETURN 5;
	END IF;
	
	/* check recursion */
	WHILE _parent_id IS NOT NULL DO
		IF _parent_id = _folder_id THEN
			RETURN 6;
		END IF;
		SET _parent_id = (SELECT `parent_id` FROM `folders` WHERE `folder_id` = _parent_id LOCK IN SHARE MODE);
		SET _depth_ = _depth_ + 1;
	END WHILE;
	
	/* check max depth*/
	SET _max_depth_ = (SELECT `max_depth` FROM `node`);
	IF (_depth_ >= _max_depth_) THEN
			RETURN 7;
	END IF;
	
	RETURN 0;
END//
DELIMITER ;

-- Dumping structure for trigger attic_db.accounts_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `accounts_after_insert` AFTER INSERT ON `accounts` FOR EACH ROW BEGIN
	INSERT INTO `logs` (`account_id`, `operation`, `msg`) VALUES (
		NEW.`account_id`,
		'ACCOUNT INSERT',
		CONCAT(
		'name = ', NEW.`name`, '\n',
		'email = ', NEW.`email`, '\n',
		'password = *\n',
		'enabled = ', NEW.`enabled`, '\n',
		'activated = ', NEW.`activated`, '\n',
		'access = ', NEW.`access`, '\n',
		'privileges = ', NEW.`privileges`, '\n',
		'max_space = ', NEW.`max_space`, '\n',
		'max_attics_count = ', NEW.`max_attics_count`, '\n',
		'contact_email = ', NEW.`contact_email`, '\n',
		'contact_phone = ', NEW.`contact_phone`, '\n',
		'description = ', NEW.`description`, '\n',
		'delete_on = ', IFNULL(NEW.`delete_on`, 'NULL')
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.accounts_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `accounts_after_update` AFTER UPDATE ON `accounts` FOR EACH ROW BEGIN
	DECLARE _txt_ TEXT DEFAULT '';
	
	IF OLD.`name` <> NEW.`name` THEN
		SET _txt_ = CONCAT(_txt_,
			'old name = ', OLD.`name`, '\n',
			'new name = ', NEW.`name`, '\n');
	END IF;

	IF OLD.`email` <> NEW.`email` THEN
		SET _txt_ = CONCAT(_txt_,
			'old email = ', OLD.`email`, '\n',
			'new email = ', NEW.`email`, '\n');
	END IF;

	IF OLD.`password_bin` <> NEW.`password_bin` THEN
		SET _txt_ = CONCAT(_txt_,
			'password changed\n');
	END IF;

	IF OLD.`enabled` <> NEW.`enabled` THEN
		SET _txt_ = CONCAT(_txt_,
			'old enabled = ', OLD.`enabled`, '\n',
			'new enabled = ', NEW.`enabled`, '\n');
	END IF;

	IF OLD.`activated` <> NEW.`activated` THEN
		SET _txt_ = CONCAT(_txt_,
			'old activated = ', OLD.`activated`, '\n',
			'new activated = ', NEW.`activated`, '\n');
	END IF;

	IF OLD.`access` <> NEW.`access` THEN
		SET _txt_ = CONCAT(_txt_,
			'old activated = ', OLD.`activated`, '\n',
			'new activated = ', NEW.`activated`, '\n');
	END IF;

	IF OLD.`privileges` <> NEW.`privileges` THEN
		SET _txt_ = CONCAT(_txt_,
			'old privileges = ', OLD.`privileges`, '\n',
			'new privileges = ', NEW.`privileges`, '\n');
	END IF;

	IF OLD.`max_space` <> NEW.`max_space` THEN
		SET _txt_ = CONCAT(_txt_,
			'old max_space = ', OLD.`max_space`, '\n',
			'new max_space = ', NEW.`max_space`, '\n');
	END IF;

	IF OLD.`max_attics_count` <> NEW.`max_attics_count` THEN
		SET _txt_ = CONCAT(_txt_,
			'old max_attics_count = ', OLD.`max_attics_count`, '\n',
			'new max_attics_count = ', NEW.`max_attics_count`, '\n');
	END IF;

	IF OLD.`contact_email` <> NEW.`contact_email` THEN
		SET _txt_ = CONCAT(_txt_,
			'old contact_email = ', OLD.`contact_email`, '\n',
			'new contact_email = ', NEW.`contact_email`, '\n');
	END IF;

	IF OLD.`description` <> NEW.`description` THEN
		SET _txt_ = CONCAT(_txt_,
			'old description = ', OLD.`description`, '\n',
			'new description = ', NEW.`description`, '\n');
	END IF;

	IF OLD.`delete_on` <> NEW.`delete_on` THEN
		SET _txt_ = CONCAT(_txt_,
			'old delete_on = ', IFNULL(OLD.`delete_on`, 'NULL'), '\n',
			'new delete_on = ', IFNULL(NEW.`delete_on`, 'NULL'), '\n');
	END IF;

	IF _txt_ <> '' THEN
		INSERT INTO `logs` (
			`account_id`, 
			`operation`, 
			`msg`
		) VALUES (
			NEW.`account_id`,
			'ACCOUNT UPDATE',
			_txt_
		);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.attics_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `attics_after_insert` AFTER INSERT ON `attics` FOR EACH ROW BEGIN
	/* Increment attics count */
	UPDATE `accounts` SET 
		`current_attics_count` = `current_attics_count` + 1
	WHERE `account_id` = NEW.`account_id`;
	
	INSERT INTO `logs` (`account_id`, `operation`, `msg`) VALUES (
		NEW.`account_id`,
		'ATTIC INSERT',
		CONCAT(
		'name = ', NEW.`name`, '\n',
		'enabled = ', NEW.`enabled`, '\n',
		'access = ', NEW.`access`, '\n',
		'description = ', NEW.`description`
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.attics_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `attics_after_update` AFTER UPDATE ON `attics` FOR EACH ROW BEGIN
	DECLARE _txt_ TEXT DEFAULT '';
	
	IF NEW.`name` <> OLD.`name` THEN
		SET _txt_ = CONCAT(_txt_,
			'old name = ', OLD.`name`, '\n',
			'new name = ', NEW.`name`, '\n');
	END IF;
	
	IF NEW.`enabled` <> OLD.`enabled` THEN
		SET _txt_ = CONCAT(_txt_,
			'old enabled = ', OLD.`enabled`, '\n',
			'new enabled = ', NEW.`enabled`, '\n');
	END IF;
	
	IF NEW.`access` <> OLD.`access` THEN
		SET _txt_ = CONCAT(_txt_,
			'old access = ', OLD.`access`, '\n',
			'new access = ', NEW.`access`, '\n');
	END IF;
	
	IF NEW.`description` <> OLD.`description` THEN
		SET _txt_ = CONCAT(_txt_,
			'old description = ', OLD.`description`, '\n',
			'new description = ', NEW.`description`, '\n');
	END IF;
	
	IF _txt_ <> '' THEN
		INSERT INTO `logs` (
			`account_id`, 
			`operation`, 
			`msg`
		) VALUES (
			NEW.`account_id`,
			'ATTIC UPDATE',
			_txt_
		);
	END IF;	

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.attics_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `attics_before_delete` BEFORE DELETE ON `attics` FOR EACH ROW BEGIN
	/* Decrement attics count */
	UPDATE `accounts` SET 
		`current_attics_count` = `current_attics_count` - 1
	WHERE `account_id` = OLD.`account_id`;

	INSERT INTO `logs` (`account_id`, `operation`, `msg`) VALUES (
		OLD.`account_id`,
		'ATTIC DELETE',
		CONCAT(
		'name = ', OLD.`name`, '\n',
		'enabled = ', OLD.`enabled`, '\n',
		'access = ', OLD.`access`, '\n',
		'description = ', OLD.`description`
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.files_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `files_after_delete` AFTER DELETE ON `files` FOR EACH ROW BEGIN
	CALL generate_tags(OLD.`file_id`, NULL);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.files_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `files_after_insert` AFTER INSERT ON `files` FOR EACH ROW BEGIN
	/* Increment files count */
	UPDATE `accounts` SET 
		`current_files_count` = `current_files_count` + 1
	WHERE `account_id` = NEW.`account_id`;

	CALL generate_tags(NEW.`file_id`, NEW.`description`);

	INSERT INTO `logs` (`account_id`, `operation`, `msg`) VALUES (
		NEW.`account_id`,
		'FILE INSERT',
		CONCAT(
		'folder_id = ', IFNULL(NEW.`folder_id`, 'NULL'), '\n',
		'attic_id = ', NEW.`attic_id`, '\n',
		'name = ', NEW.`name`, '\n',
		'size = ', NEW.`size`, '\n',
		'enabled = ', NEW.`enabled`, '\n',
		'access = ', NEW.`access`, '\n',
		'comments_on = ', NEW.`comments_on`, '\n',
		'description = ', NEW.`description`
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.files_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `files_after_update` AFTER UPDATE ON `files` FOR EACH ROW BEGIN
	DECLARE _txt_ TEXT DEFAULT '';
	
	IF NEW.`description` <> OLD.`description` OR
		NEW.`attic_id` <> OLD.`attic_id` OR
		NEW.`account_id` <> OLD.`account_id`
	THEN
		CALL generate_tags(NEW.`file_id`, NEW.`description`);
	END IF;

	IF NEW.`folder_id` <> OLD.`folder_id` THEN
		SET _txt_ = CONCAT(_txt_,
			'old folder_id = ', IFNULL(OLD.`folder_id`, 'NULL'), '\n',
			'new folder_id = ', IFNULL(NEW.`folder_id`, 'NULL'), '\n');
	END IF;
	
	IF NEW.`attic_id` <> OLD.`attic_id` THEN
		SET _txt_ = CONCAT(_txt_,
			'old attic_id = ', OLD.`attic_id`, '\n',
			'new attic_id = ', NEW.`attic_id`, '\n');
	END IF;

	IF NEW.`name` <> OLD.`name` THEN
		SET _txt_ = CONCAT(_txt_,
			'old name = ', OLD.`name`, '\n',
			'new name = ', NEW.`name`, '\n');
	END IF;

	IF NEW.`enabled` <> OLD.`enabled` THEN
		SET _txt_ = CONCAT(_txt_,
			'old enabled = ', OLD.`enabled`, '\n',
			'new enabled = ', NEW.`enabled`, '\n');
	END IF;
	
	IF NEW.`access` <> OLD.`access` THEN
		SET _txt_ = CONCAT(_txt_,
			'old access = ', OLD.`access`, '\n',
			'new access = ', NEW.`access`, '\n');
	END IF;
	
	IF NEW.`comments_on` <> OLD.`comments_on` THEN
		SET _txt_ = CONCAT(_txt_,
			'old comments_on = ', OLD.`comments_on`, '\n',
			'new comments_on = ', NEW.`comments_on`, '\n');
	END IF;
	
	IF NEW.`description` <> OLD.`description` THEN
		SET _txt_ = CONCAT(_txt_,
			'old description = ', OLD.`description`, '\n',
			'new description = ', NEW.`description`, '\n');
	END IF;
	
	IF _txt_ <> '' THEN
		INSERT INTO `logs` (
			`account_id`, 
			`operation`, 
			`msg`
		) VALUES (
			(SELECT `account_id` FROM `v_files` WHERE `file_id` = NEW.`file_id`),
			'FILE UPDATE',
			_txt_
		);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.files_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `files_before_delete` BEFORE DELETE ON `files` FOR EACH ROW BEGIN
	/* Decrement files count */
	UPDATE `accounts` SET 
		`current_files_count` = `current_files_count` - 1
	WHERE `account_id` = OLD.`account_id`;

	INSERT INTO `logs` (`account_id`, `operation`, `msg`) VALUES (
		OLD.`account_id`,
		'FILE DELETE',
		CONCAT(
		'folder_id = ', IFNULL(OLD.`folder_id`, 'NULL'), '\n',
		'attic_id = ', OLD.`attic_id`, '\n',
		'name = ', OLD.`name`, '\n',
		'size = ', OLD.`size`, '\n',
		'enabled = ', OLD.`enabled`, '\n',
		'access = ', OLD.`access`, '\n',
		'comments_on = ', OLD.`comments_on`, '\n',
		'description = ', OLD.`description`
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.folders_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `folders_after_insert` AFTER INSERT ON `folders` FOR EACH ROW BEGIN
	/* Increment folders count */
	UPDATE `accounts` SET 
		`current_folders_count` = `current_folders_count` + 1
	WHERE `account_id` = NEW.`account_id`;

	INSERT INTO `logs` (`account_id`, `operation`, `msg`) VALUES (
		NEW.`account_id`,
		'FOLDER INSERT',
		CONCAT(
		'attic_id = ', NEW.`attic_id`, '\n',
		'account_id = ', NEW.`account_id`, '\n',
		'parent_id = ', IFNULL(NEW.`parent_id`, 'NULL'), '\n',
		'name = ', NEW.`name`
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.folders_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `folders_after_update` AFTER UPDATE ON `folders` FOR EACH ROW BEGIN
	DECLARE _txt_ TEXT DEFAULT '';
	
	IF OLD.`attic_id` <> NEW.`attic_id` THEN
		SET _txt_ = CONCAT(_txt_,
			'old attic_id = ', OLD.`attic_id`, '\n',
			'new attic_id = ', NEW.`attic_id`, '\n');
	END IF;

	IF OLD.`parent_id` <> NEW.`parent_id` THEN
		SET _txt_ = CONCAT(_txt_,
			'old parent_id = ', IFNULL(OLD.`parent_id`, 'NULL'), '\n',
			'new parent_id = ', IFNULL(NEW.`parent_id`, 'NULL'), '\n');
	END IF;

	IF OLD.`name` <> NEW.`name` THEN
		SET _txt_ = CONCAT(_txt_,
			'old name = ', OLD.`name`, '\n',
			'new name = ', NEW.`name`, '\n');
	END IF;

	IF _txt_ <> '' THEN
		INSERT INTO `logs` (
			`account_id`, 
			`operation`, 
			`msg`
		) VALUES (
			NEW.`account_id`,
			'FOLDER UPDATE',
			_txt_
		);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.folders_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `folders_before_delete` BEFORE DELETE ON `folders` FOR EACH ROW BEGIN
	/* Decrement folders count */
	UPDATE `accounts` SET 
		`current_folders_count` = `current_folders_count` - 1
	WHERE `account_id` = OLD.`account_id`;

	INSERT INTO `logs` (`account_id`, `operation`, `msg`) VALUES (
		OLD.`account_id`,
		'FOLDER DELETE',
		CONCAT(
		'attic_id = ', OLD.`attic_id`, '\n',
		'account_id = ', OLD.`account_id`, '\n',
		'parent_id = ', IFNULL(OLD.`parent_id`, ''), '\n',
		'name = ', OLD.`name`
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.tag_relations_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `tag_relations_after_delete` AFTER DELETE ON `tag_relations` FOR EACH ROW BEGIN
	UPDATE `tag_weight_global` 
		SET `weight` = `weight` - 1
		WHERE `tag_id` = OLD.tag_id;	

	UPDATE `tag_weight_accounts` 
		SET `weight` = `weight` - 1
		WHERE `tag_id` = OLD.`tag_id` AND `account_id` = OLD.`account_id`;	

	UPDATE `tag_weight_attics` 
		SET `weight` = `weight` - 1
		WHERE `tag_id` = OLD.`tag_id` AND `attic_id` = OLD.`attic_id`;	
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic_db.tag_relations_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `tag_relations_after_insert` AFTER INSERT ON `tag_relations` FOR EACH ROW BEGIN
	UPDATE `tag_weight_global` 
		SET `weight` = `weight` + 1
		WHERE `tag_id` = NEW.tag_id;	

	UPDATE `tag_weight_accounts` 
		SET `weight` = `weight` + 1
		WHERE `tag_id` = NEW.`tag_id` AND `account_id` = NEW.`account_id`;	

	UPDATE `tag_weight_attics` 
		SET `weight` = `weight` + 1
		WHERE `tag_id` = NEW.`tag_id` AND `attic_id` = NEW.`attic_id`;	
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for view attic_db.v_files
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_files`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_files` AS select `files`.`file_id` AS `file_id`,`files`.`name` AS `file_name`,`files`.`enabled` AS `file_enabled`,`files`.`access` AS `file_access`,`files`.`comments_on` AS `file_comments_on`,`files`.`size` AS `file_size`,`files`.`created` AS `file_created`,`files`.`updated` AS `file_updated`,`files`.`weight` AS `file_weight`,`files`.`description` AS `file_description`,`files`.`folder_id` AS `folder_id`,`folders`.`name` AS `folder_name`,`files`.`attic_id` AS `attic_id`,`attics`.`name` AS `attic_name`,`attics`.`enabled` AS `attic_enabled`,`attics`.`access` AS `attic_access`,`files`.`account_id` AS `account_id`,`accounts`.`name` AS `account_name`,`accounts`.`enabled` AS `account_enabled`,`accounts`.`access` AS `account_access` from (((`files` left join `folders` on(`files`.`folder_id` = `folders`.`folder_id`)) left join `attics` on(`files`.`attic_id` = `attics`.`attic_id`)) left join `accounts` on(`files`.`account_id` = `accounts`.`account_id`));

-- Dumping structure for view attic_db.v_tag_relations_accounts
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_tag_relations_accounts`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_tag_relations_accounts` AS select `tags`.`tag` AS `tag`,`tag_weight_accounts`.`weight` AS `weight`,`tag_relations`.`file_id` AS `file_id`,`files`.`name` AS `file_name`,`files`.`enabled` AS `file_enabled`,`files`.`access` AS `file_access`,`files`.`comments_on` AS `file_comments_on`,`files`.`size` AS `file_size`,`files`.`created` AS `file_created`,`files`.`updated` AS `file_updated`,`files`.`weight` AS `file_weight`,`files`.`description` AS `file_description`,`tag_relations`.`folder_id` AS `folder_id`,`folders`.`name` AS `folder_name`,`tag_relations`.`attic_id` AS `attic_id`,`attics`.`name` AS `attic_name`,`attics`.`enabled` AS `attic_enabled`,`attics`.`access` AS `attic_access`,`tag_relations`.`account_id` AS `account_id`,`accounts`.`name` AS `account_name`,`accounts`.`enabled` AS `account_enabled`,`accounts`.`access` AS `account_access` from ((((((`tag_relations` left join `tags` on(`tag_relations`.`tag_id` = `tags`.`tag_id`)) left join `files` on(`tag_relations`.`file_id` = `files`.`file_id`)) left join `folders` on(`tag_relations`.`folder_id` = `folders`.`folder_id`)) left join `attics` on(`tag_relations`.`attic_id` = `attics`.`attic_id`)) left join `accounts` on(`tag_relations`.`account_id` = `accounts`.`account_id`)) left join `tag_weight_accounts` on(`tag_relations`.`tag_id` = `tag_weight_accounts`.`tag_id` and `tag_relations`.`account_id` = `tag_weight_accounts`.`account_id`));

-- Dumping structure for view attic_db.v_tag_relations_attics
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_tag_relations_attics`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_tag_relations_attics` AS select `tags`.`tag` AS `tag`,`tag_weight_attics`.`weight` AS `weight`,`tag_relations`.`file_id` AS `file_id`,`files`.`name` AS `file_name`,`files`.`enabled` AS `file_enabled`,`files`.`access` AS `file_access`,`files`.`comments_on` AS `file_comments_on`,`files`.`size` AS `file_size`,`files`.`created` AS `file_created`,`files`.`updated` AS `file_updated`,`files`.`weight` AS `file_weight`,`files`.`description` AS `file_description`,`tag_relations`.`folder_id` AS `folder_id`,`folders`.`name` AS `folder_name`,`tag_relations`.`attic_id` AS `attic_id`,`attics`.`name` AS `attic_name`,`attics`.`enabled` AS `attic_enabled`,`attics`.`access` AS `attic_access`,`tag_relations`.`account_id` AS `account_id`,`accounts`.`name` AS `account_name`,`accounts`.`enabled` AS `account_enabled`,`accounts`.`access` AS `account_access` from ((((((`tag_relations` left join `tags` on(`tag_relations`.`tag_id` = `tags`.`tag_id`)) left join `files` on(`tag_relations`.`file_id` = `files`.`file_id`)) left join `folders` on(`tag_relations`.`folder_id` = `folders`.`folder_id`)) left join `attics` on(`tag_relations`.`attic_id` = `attics`.`attic_id`)) left join `accounts` on(`tag_relations`.`account_id` = `accounts`.`account_id`)) left join `tag_weight_attics` on(`tag_relations`.`tag_id` = `tag_weight_attics`.`tag_id` and `tag_relations`.`attic_id` = `tag_weight_attics`.`attic_id`));

-- Dumping structure for view attic_db.v_tag_relations_global
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_tag_relations_global`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_tag_relations_global` AS select `tags`.`tag` AS `tag`,`tag_weight_global`.`weight` AS `weight`,`tag_relations`.`file_id` AS `file_id`,`files`.`name` AS `file_name`,`files`.`enabled` AS `file_enabled`,`files`.`access` AS `file_access`,`files`.`comments_on` AS `file_comments_on`,`files`.`size` AS `file_size`,`files`.`created` AS `file_created`,`files`.`updated` AS `file_updated`,`files`.`weight` AS `file_weight`,`files`.`description` AS `file_description`,`tag_relations`.`folder_id` AS `folder_id`,`folders`.`name` AS `folder_name`,`tag_relations`.`attic_id` AS `attic_id`,`attics`.`name` AS `attic_name`,`attics`.`enabled` AS `attic_enabled`,`attics`.`access` AS `attic_access`,`tag_relations`.`account_id` AS `account_id`,`accounts`.`name` AS `account_name`,`accounts`.`enabled` AS `account_enabled`,`accounts`.`access` AS `account_access` from ((((((`tag_relations` left join `tags` on(`tag_relations`.`tag_id` = `tags`.`tag_id`)) left join `files` on(`tag_relations`.`file_id` = `files`.`file_id`)) left join `folders` on(`tag_relations`.`folder_id` = `folders`.`folder_id`)) left join `attics` on(`tag_relations`.`attic_id` = `attics`.`attic_id`)) left join `accounts` on(`tag_relations`.`account_id` = `accounts`.`account_id`)) left join `tag_weight_global` on(`tag_relations`.`tag_id` = `tag_weight_global`.`tag_id`));

-- Dumping structure for view attic_db.v_tag_weight_accounts
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_tag_weight_accounts`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_tag_weight_accounts` AS select `tags`.`tag` AS `tag`,`tag_weight_accounts`.`account_id` AS `account_id`,`tag_weight_accounts`.`weight` AS `weight` from (`tag_weight_accounts` left join `tags` on(`tag_weight_accounts`.`tag_id` = `tags`.`tag_id`));

-- Dumping structure for view attic_db.v_tag_weight_attics
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_tag_weight_attics`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_tag_weight_attics` AS select `tags`.`tag` AS `tag`,`tag_weight_attics`.`attic_id` AS `attic_id`,`tag_weight_attics`.`weight` AS `weight` from (`tag_weight_attics` left join `tags` on(`tag_weight_attics`.`tag_id` = `tags`.`tag_id`));

-- Dumping structure for view attic_db.v_tag_weight_global
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_tag_weight_global`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_tag_weight_global` AS select `tags`.`tag` AS `tag`,`tag_weight_global`.`weight` AS `weight` from (`tag_weight_global` left join `tags` on(`tag_weight_global`.`tag_id` = `tags`.`tag_id`));

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
