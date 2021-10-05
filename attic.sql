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


-- Dumping database structure for attic
CREATE DATABASE IF NOT EXISTS `attic` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci */;
USE `attic`;

-- Dumping structure for table attic.accounts
CREATE TABLE IF NOT EXISTS `accounts` (
  `account_id` binary(16) NOT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(320) COLLATE utf8_unicode_ci NOT NULL,
  `password` binary(32) NOT NULL,
  `admin` tinyint(4) NOT NULL DEFAULT 0,
  `disabled` tinyint(4) NOT NULL DEFAULT 0,
  `reason` varchar(500) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `max_space` bigint(20) unsigned NOT NULL,
  `current_space` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`account_id`) USING BTREE,
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `email` (`email`),
  KEY `email_password` (`email`,`password`),
  KEY `disabled` (`disabled`),
  KEY `max_space` (`max_space`),
  KEY `current_sapce` (`current_space`),
  KEY `admin` (`admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table attic.accounts: ~2 rows (approximately)
DELETE FROM `accounts`;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` (`account_id`, `name`, `email`, `password`, `admin`, `disabled`, `reason`, `max_space`, `current_space`) VALUES
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, 'DEFAULT', 'admin@attic.com', _binary 0x7e7900fb7c922567f9b6f025cb68e378cb67046b40230ac5263684a0e21dc318, 1, 0, '', 10737418240, 30480),
	(_binary 0x11ec18d51ff77e08bdb900d8614e2cfe, 'test_account', 'attic1@badikus.com', _binary 0x7e7900fb7c922567f9b6f025cb68e378cb67046b40230ac5263684a0e21dc318, 0, 0, '', 10737418240, 4096);
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;

-- Dumping structure for table attic.config
CREATE TABLE IF NOT EXISTS `config` (
  `single_row` enum('') COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `salt` varchar(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `session_timeout` int(10) unsigned NOT NULL COMMENT 'min',
  `invitation_token_timeout` int(10) unsigned NOT NULL COMMENT 'min',
  `password_email_token_timeout` int(10) unsigned NOT NULL COMMENT 'min',
  `max_space` bigint(20) unsigned NOT NULL COMMENT 'bytes',
  `max_depth` int(10) unsigned NOT NULL COMMENT 'max folders depth',
  PRIMARY KEY (`single_row`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table attic.config: ~1 rows (approximately)
DELETE FROM `config`;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` (`single_row`, `salt`, `session_timeout`, `invitation_token_timeout`, `password_email_token_timeout`, `max_space`, `max_depth`) VALUES
	('', 'wofTxTGAGxsuW5sR', 1440, 4320, 60, 10737418240, 1000);
/*!40000 ALTER TABLE `config` ENABLE KEYS */;

-- Dumping structure for table attic.files
CREATE TABLE IF NOT EXISTS `files` (
  `account_id` binary(16) NOT NULL,
  `folder_id` binary(16) NOT NULL,
  `file_id` binary(16) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `public` tinyint(4) NOT NULL DEFAULT 0,
  `size` bigint(20) unsigned NOT NULL,
  `current_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `description` varchar(500) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT utc_timestamp(),
  PRIMARY KEY (`file_id`) USING BTREE,
  UNIQUE KEY `folder_id_name` (`folder_id`,`name`) USING BTREE,
  KEY `public` (`public`),
  KEY `size` (`size`),
  KEY `current_size` (`current_size`),
  KEY `FK_files_accounts` (`account_id`) USING BTREE,
  FULLTEXT KEY `name_description` (`name`,`description`),
  CONSTRAINT `FK_files_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_files_folders` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`folder_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table attic.files: ~0 rows (approximately)
DELETE FROM `files`;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
INSERT INTO `files` (`account_id`, `folder_id`, `file_id`, `name`, `public`, `size`, `current_size`, `description`, `created`) VALUES
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x11ec15a2dc554fd7bdb900d8614e2cfe, _binary 0x11ec15ff884a366fbdb900d8614e2cfe, 'file1', 0, 10000, 0, '', '2021-09-15 08:32:58');
/*!40000 ALTER TABLE `files` ENABLE KEYS */;

-- Dumping structure for table attic.folders
CREATE TABLE IF NOT EXISTS `folders` (
  `account_id` binary(16) NOT NULL,
  `folder_id` binary(16) NOT NULL,
  `parent_id` binary(16) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`folder_id`) USING BTREE,
  UNIQUE KEY `parent_id_name` (`parent_id`,`name`) USING BTREE,
  KEY `FK_folders_accounts` (`account_id`) USING BTREE,
  CONSTRAINT `FK_folders_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_folders_folders` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`folder_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table attic.folders: ~4 rows (approximately)
DELETE FROM `folders`;
/*!40000 ALTER TABLE `folders` DISABLE KEYS */;
INSERT INTO `folders` (`account_id`, `folder_id`, `parent_id`, `name`) VALUES
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x11ec15a2dc554fd7bdb900d8614e2cfe, NULL, 'New Folder'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x11ec15dfd923a89dbdb900d8614e2cfe, _binary 0x11ec15a2dc554fd7bdb900d8614e2cfe, 'folder1'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x11ec15ef97b79e62bdb900d8614e2cfe, _binary 0x11ec15a2dc554fd7bdb900d8614e2cfe, 'folder2'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x11ec15ef9de75b0ebdb900d8614e2cfe, _binary 0x11ec15a2dc554fd7bdb900d8614e2cfe, 'folder3'),
	(_binary 0x11ec18d51ff77e08bdb900d8614e2cfe, _binary 0x11ec18d51ff77e0cbdb900d8614e2cfe, NULL, 'New Folder');
/*!40000 ALTER TABLE `folders` ENABLE KEYS */;

-- Dumping structure for table attic.local_shares
CREATE TABLE IF NOT EXISTS `local_shares` (
  `account_id` binary(16) NOT NULL,
  `share_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rcpt` varchar(320) COLLATE utf8_unicode_ci NOT NULL,
  `token` binary(32) NOT NULL,
  UNIQUE KEY `token` (`token`),
  UNIQUE KEY `share_id` (`share_id`) USING BTREE,
  UNIQUE KEY `account_id_rcpt` (`account_id`,`rcpt`),
  CONSTRAINT `FK_local_shares_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table attic.local_shares: ~2 rows (approximately)
DELETE FROM `local_shares`;
/*!40000 ALTER TABLE `local_shares` DISABLE KEYS */;
INSERT INTO `local_shares` (`account_id`, `share_id`, `rcpt`, `token`) VALUES
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, 55, 'test_account@attic.badikus.com', _binary 0x243e2f85b4936109a01d4175b1378799696648c159b51bee7d97484322b08e39),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, 56, 'default@attic.badikus.com', _binary 0x412c82faf7bf0ec02e1b7ea782c224a14c7ca18344a3ae7341ed1951ae413709);
/*!40000 ALTER TABLE `local_shares` ENABLE KEYS */;

-- Dumping structure for table attic.local_shares_assocs
CREATE TABLE IF NOT EXISTS `local_shares_assocs` (
  `file_id` binary(16) NOT NULL,
  `share_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `file_id_share_id` (`file_id`,`share_id`),
  KEY `FK_local_shares_assocs_files` (`file_id`) USING BTREE,
  KEY `FK_local_shares_assocs_local_shares` (`share_id`) USING BTREE,
  CONSTRAINT `FK_local_shares_assocs_files` FOREIGN KEY (`file_id`) REFERENCES `files` (`file_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_local_shares_assocs_local_shares` FOREIGN KEY (`share_id`) REFERENCES `local_shares` (`share_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table attic.local_shares_assocs: ~1 rows (approximately)
DELETE FROM `local_shares_assocs`;
/*!40000 ALTER TABLE `local_shares_assocs` DISABLE KEYS */;
INSERT INTO `local_shares_assocs` (`file_id`, `share_id`) VALUES
	(_binary 0x11ec15ff884a366fbdb900d8614e2cfe, 55),
	(_binary 0x11ec15ff884a366fbdb900d8614e2cfe, 56);
/*!40000 ALTER TABLE `local_shares_assocs` ENABLE KEYS */;

-- Dumping structure for table attic.remote_shares
CREATE TABLE IF NOT EXISTS `remote_shares` (
  `account_id` binary(16) NOT NULL,
  `sender` varchar(320) COLLATE utf8_unicode_ci NOT NULL,
  `token` binary(32) NOT NULL,
  UNIQUE KEY `token` (`token`),
  UNIQUE KEY `account_id_sender` (`account_id`,`sender`),
  CONSTRAINT `FK_remote_shares_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table attic.remote_shares: ~2 rows (approximately)
DELETE FROM `remote_shares`;
/*!40000 ALTER TABLE `remote_shares` DISABLE KEYS */;
INSERT INTO `remote_shares` (`account_id`, `sender`, `token`) VALUES
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, 'DEFAULT@attic.badkius.com', _binary 0x412c82faf7bf0ec02e1b7ea782c224a14c7ca18344a3ae7341ed1951ae413709),
	(_binary 0x11ec18d51ff77e08bdb900d8614e2cfe, 'DEFAULT@attic.badkius.com', _binary 0x243e2f85b4936109a01d4175b1378799696648c159b51bee7d97484322b08e39);
/*!40000 ALTER TABLE `remote_shares` ENABLE KEYS */;

-- Dumping structure for table attic.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `account_id` binary(16) NOT NULL,
  `session_id` binary(32) NOT NULL,
  `updated` datetime NOT NULL DEFAULT utc_timestamp(),
  UNIQUE KEY `token` (`session_id`) USING BTREE,
  KEY `updated` (`updated`),
  KEY `FK_sessions_accounts` (`account_id`) USING BTREE,
  CONSTRAINT `FK_sessions_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table attic.sessions: ~22 rows (approximately)
DELETE FROM `sessions`;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` (`account_id`, `session_id`, `updated`) VALUES
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x06ad1147871e4cc568c902e5d6ae2bfad6384bda1891a5895869c178c651dbda, '2021-09-17 22:31:19'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x0d64729e10e4e3755b13fa625b7fae8955792729c1e2a89d1160cfb7d2b004a1, '2021-09-17 22:08:02'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x22c0942dab6e254daf1bed25f9ef3594bc0445aa2e40573f3b182987e55942c7, '2021-09-17 22:13:43'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x34e4b3bee0fb481f5e585d4419ad6684426c668ed7306c5daec929dce76d0967, '2021-09-17 22:09:16'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x365ea7c8426769cd039acca4de0a63c7ea23f5bb4e758c78b8f4ff396200cf70, '2021-09-18 00:21:23'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x58aa4f55381f68d62f8f304d5d0fe071193d88b51f3a9f1b6135a095af651c3e, '2021-09-14 21:29:52'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x7fbe293ab7aeb7b711c6df033549d16d69044d5201b0297dd98a0a0c39145b7b, '2021-09-17 22:11:25'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x8d9cbb81bb976533f5a0dfd0f835c6bb19e9a62e47effa4e1fea82b088d3ed1e, '2021-09-17 22:12:22'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x95894ee7dfab0250b1580919f8545b684139856ddf8848bee8161a473a372dc6, '2021-09-17 22:12:02'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0x959285c63ceeef646fa3c815efa18e2d21a23178315799363d76bf571f64ac46, '2021-09-17 22:32:38'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xa25c7c6918a0e99c8be7e8ef911cedd7e344ae6f6badcfc1ec980152a88861a8, '2021-09-17 22:16:03'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xaa9cae6bb622db5dd87ea2d67fbe16bb4fe7f08c09f86adc18756b3b98a52cda, '2021-09-17 22:16:40'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xafb2b0ea7e405503ec0c25b907c0a5ef060847438d9398fe85e7a704694ad13b, '2021-09-17 22:07:13'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xb5f5c8a514c91f2ea71acd9f8d31ad183f1d1e132b080cc9de537694e75228ee, '2021-09-17 23:07:22'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xb7937bf5c5ed9555baf2f42e429c8445fb5e75a06da2eb4a8fa529f3f9820297, '2021-09-17 22:12:33'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xc71266106fc4a6643cc1e2dab04fa5ee5c6c84fd1c3b3af3c50c3d10caf0e73f, '2021-09-17 22:09:40'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xc84c72db5541a233e5552ceb5582631c5c64244e0edf45282cc4e8fc4028ca54, '2021-09-17 22:10:06'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xca4bf4ede9080af3bbdeeab62d4cdba2ec22262bb61b0a9609e00b6a6cbdd1d5, '2021-09-17 22:06:29'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xee73894c36a9d4eeeb62ca7f93640a7aa04eb1f8d0efaaf6aea538ebef745889, '2021-09-17 22:31:00'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xf4068ca22b6d43ce824d4b04946cb759fb9a632b61f2c990f3f451f47ed56011, '2021-09-17 22:11:49'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xfa8b944ff27aa15404ed10ecb17648048bea943544407f46924b22c0949f1578, '2021-09-17 22:08:30'),
	(_binary 0x11ec15a2dc554efbbdb900d8614e2cfe, _binary 0xffdf97724b16c56bbf64c045c35b79957eef45d24622b7eca382e20cfb3d036d, '2021-09-17 22:20:34');
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;

-- Dumping structure for table attic.tokens
CREATE TABLE IF NOT EXISTS `tokens` (
  `account_id` binary(16) NOT NULL,
  `token` binary(32) NOT NULL,
  `type` tinyint(4) NOT NULL COMMENT '1 = INVITATION,\r\n2 = PASSWORD,\r\n3 = EMAIL',
  `email` varchar(320) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT utc_timestamp(),
  UNIQUE KEY `token_type` (`token`,`type`),
  KEY `created` (`created`),
  KEY `FK_tokens_accounts` (`account_id`) USING BTREE,
  CONSTRAINT `FK_tokens_accounts` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table attic.tokens: ~0 rows (approximately)
DELETE FROM `tokens`;
/*!40000 ALTER TABLE `tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `tokens` ENABLE KEYS */;

-- Dumping structure for view attic.v_files
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_files` (
	`file_id` BINARY(16) NOT NULL,
	`name` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci',
	`public` TINYINT(4) NOT NULL,
	`size` BIGINT(20) UNSIGNED NOT NULL,
	`current_size` BIGINT(20) UNSIGNED NOT NULL,
	`description` VARCHAR(500) NOT NULL COLLATE 'utf8_unicode_ci',
	`folder_id` BINARY(16) NOT NULL,
	`folder_name` VARCHAR(255) NULL COLLATE 'utf8_unicode_ci',
	`account_id` BINARY(16) NOT NULL,
	`account_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci'
) ENGINE=MyISAM;

-- Dumping structure for view attic.v_folders
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_folders` (
	`account_id` BINARY(16) NOT NULL,
	`account_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci',
	`parent_id` BINARY(16) NULL,
	`parent_name` VARCHAR(255) NULL COLLATE 'utf8_unicode_ci',
	`folder_id` BINARY(16) NOT NULL,
	`name` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci'
) ENGINE=MyISAM;

-- Dumping structure for view attic.v_local_shares
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_local_shares` (
	`account_id` BINARY(16) NOT NULL,
	`account_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci',
	`share_id` INT(10) UNSIGNED NOT NULL,
	`rcpt` VARCHAR(320) NOT NULL COLLATE 'utf8_unicode_ci',
	`token` BINARY(32) NOT NULL
) ENGINE=MyISAM;

-- Dumping structure for view attic.v_remote_shares
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_remote_shares` (
	`account_id` BINARY(16) NOT NULL,
	`account_name` VARCHAR(64) NULL COLLATE 'utf8_unicode_ci',
	`sender` VARCHAR(320) NOT NULL COLLATE 'utf8_unicode_ci',
	`token` BINARY(32) NOT NULL
) ENGINE=MyISAM;

-- Dumping structure for view attic.v_sessions
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_sessions` (
	`account_id` BINARY(16) NOT NULL,
	`session_id` BINARY(32) NOT NULL,
	`admin` TINYINT(4) NULL,
	`disabled` TINYINT(4) NULL,
	`reason` VARCHAR(500) NULL COLLATE 'utf8_unicode_ci'
) ENGINE=MyISAM;

-- Dumping structure for view attic.v_tokens
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `v_tokens` (
	`account_id` BINARY(16) NOT NULL,
	`token` BINARY(32) NOT NULL,
	`type` TINYINT(4) NOT NULL COMMENT '1 = INVITATION,\r\n2 = PASSWORD,\r\n3 = EMAIL',
	`email` VARCHAR(320) NULL COLLATE 'utf8_unicode_ci',
	`admin` TINYINT(4) NULL,
	`disabled` TINYINT(4) NULL,
	`reason` VARCHAR(500) NULL COLLATE 'utf8_unicode_ci'
) ENGINE=MyISAM;

-- Dumping structure for procedure attic.accept_sharing
DELIMITER //
CREATE PROCEDURE `accept_sharing`(
	IN `_sender` VARCHAR(320),
	IN `_rcpt` VARCHAR(320),
	IN `_token` BINARY(32)
)
    MODIFIES SQL DATA
    DETERMINISTIC
BEGIN
	DECLARE _account_name_ VARCHAR(64) DEFAULT SUBSTRING_INDEX(_rcpt, '@', 1);
	DECLARE _account_id_ BINARY(16) DEFAULT (SELECT `account_id` FROM `accounts` WHERE `name` = _account_name_);
	
	IF _account_id_ IS NULL THEN
		CALL throw('Invalid remote account_id');
	END IF;
	
	IF EXISTS(SELECT 0 FROM `remote_shares` WHERE `account_id` = _account_id_ AND `sender` = _sender COLLATE UTF8_UNICODE_CI) THEN 
		UPDATE `remote_shares` SET `token` = _token WHERE `account_id` = _account_id_ AND `sender` = _sender COLLATE UTF8_UNICODE_CI;
	ELSE
		INSERT INTO `remote_shares`(`account_id`, `sender`, `token`) VALUES (_account_id_, _sender, _token);
	END IF;
END//
DELIMITER ;

-- Dumping structure for procedure attic.check_space
DELIMITER //
CREATE PROCEDURE `check_space`(
	IN `_account_id` BINARY(16),
	IN `_file_size` BIGINT UNSIGNED
)
    MODIFIES SQL DATA
    DETERMINISTIC
BEGIN
	DECLARE _current_space_ BIGINT UNSIGNED;
	DECLARE _max_space_ BIGINT UNSIGNED;
	
	SELECT `current_space`, `max_space` 
		INTO _current_space_, _max_space_
		FROM `accounts` 
	WHERE `account_id` = _account_id;
	
	/* Increment used space by 1 block = 4096 Bytes */
	SET _current_space_ = _current_space_ + 4096;
	
	IF _file_size IS NOT NULL THEN
		SET _current_space_ = _current_space_ + _file_size;
	END IF;
	
	IF _current_space_ > _max_space_ THEN
		CALL throw('Max space reached');
	END IF;
END//
DELIMITER ;

-- Dumping structure for procedure attic.create_sharing
DELIMITER //
CREATE PROCEDURE `create_sharing`(
	IN `_sender` VARCHAR(320),
	IN `_rcpt` VARCHAR(320)
)
    MODIFIES SQL DATA
    DETERMINISTIC
BEGIN
	/* return existing share id or create new one */
	DECLARE _account_name_ VARCHAR(64) DEFAULT SUBSTRING_INDEX(_sender, '@', 1);
	DECLARE _account_id_ BINARY(16) DEFAULT (SELECT `account_id` FROM `accounts` WHERE `name` = _account_name_);
	DECLARE _share_id_ INT UNSIGNED;
	DECLARE _token_ VARCHAR(64);
	DECLARE _new_ TINYINT DEFAULT 0;
	
	IF _account_id_ IS NULL THEN
		CALL throw('Invalid local account_id');
	END IF;
	
	
	SELECT `share_id`, bin_to_token(`token`)
		INTO _share_id_, _token_
		FROM `local_shares`
	WHERE `rcpt` = _rcpt COLLATE utf8_unicode_ci;
		
	IF _share_id_ IS NULL THEN
		SET _token_ = create_token();
		INSERT INTO `local_shares`(`account_id`, `rcpt`, `token`)
		VALUES (_account_id_, _rcpt, token_to_bin(_token_));
		SET _share_id_ = LAST_INSERT_ID();
		SET _new_ = 1;
	END IF;
	
	SELECT 
		_new_ AS `new_share`,
		_share_id_ AS `share_id`,
		_token_ AS `share_token`;
END//
DELIMITER ;

-- Dumping structure for procedure attic.create_v_files
DELIMITER //
CREATE PROCEDURE `create_v_files`()
BEGIN
	CREATE OR REPLACE VIEW `v_files` AS
	SELECT
		`files`.`account_id`,
		`accounts`.`name` AS `account_name`,
		`files`.`folder_id`,
		`folders`.`name` AS `folder_name`,
		`files`.`file_id`,
		`files`.`name`,
		`files`.`public`,
		`files`.`size`,
		`files`.`current_size`,
		`files`.`description`
	FROM `files`
	LEFT OUTER JOIN `folders` ON `files`.`folder_id` = `folders`.`folder_id`
	LEFT OUTER JOIN `accounts` ON `files`.`account_id` = `accounts`.`account_id`;
END//
DELIMITER ;

-- Dumping structure for procedure attic.create_v_folders
DELIMITER //
CREATE PROCEDURE `create_v_folders`()
BEGIN
	CREATE OR REPLACE VIEW `v_folders` AS
	SELECT
		`folders`.`account_id`,
		`accounts`.`name` AS `account_name`,
		`folders`.`parent_id`,
		`a_folders`.`name` AS `parent_name`,
		`folders`.`folder_id`,
		`folders`.`name`
	FROM `folders`
	LEFT OUTER JOIN `folders` AS `a_folders` ON `folders`.`parent_id` = `a_folders`.`folder_id`
	LEFT OUTER JOIN `accounts` ON `folders`.`account_id` = `accounts`.`account_id`;
END//
DELIMITER ;

-- Dumping structure for procedure attic.create_v_local_shares
DELIMITER //
CREATE PROCEDURE `create_v_local_shares`()
BEGIN
	CREATE OR REPLACE VIEW `v_local_shares` AS
	SELECT
		`local_shares`.`account_id`,
		`accounts`.`name` AS `account_name`,
		`local_shares`.`share_id`,
		`local_shares`.`rcpt`,
		`local_shares`.`token`
	FROM `local_shares`
	LEFT OUTER JOIN `accounts` ON `accounts`.`account_id` = `local_shares`.`account_id`;
END//
DELIMITER ;

-- Dumping structure for procedure attic.create_v_remote_shares
DELIMITER //
CREATE PROCEDURE `create_v_remote_shares`()
BEGIN
	CREATE OR REPLACE VIEW `v_remote_shares` AS
	SELECT
		`remote_shares`.`account_id`,
		`accounts`.`name` AS `account_name`,
		`remote_shares`.`sender`,
		`remote_shares`.`token`
	FROM `remote_shares`
	LEFT OUTER JOIN `accounts` ON `accounts`.`account_id` = `remote_shares`.`account_id`;
END//
DELIMITER ;

-- Dumping structure for procedure attic.create_v_sessions
DELIMITER //
CREATE PROCEDURE `create_v_sessions`()
BEGIN
	CREATE OR REPLACE VIEW `v_sessions` AS
	SELECT
		`sessions`.`account_id`,
		`sessions`.`session_id`,
		`accounts`.`admin`,
		`accounts`.`disabled`,
		`accounts`.`reason`
	FROM `sessions`
	LEFT OUTER JOIN `accounts` ON `accounts`.`account_id` = `sessions`.`account_id`;
END//
DELIMITER ;

-- Dumping structure for procedure attic.create_v_tokens
DELIMITER //
CREATE PROCEDURE `create_v_tokens`()
BEGIN
	CREATE OR REPLACE VIEW `v_tokens` AS
	SELECT
		`tokens`.`account_id`,
		`tokens`.`token`,
		`tokens`.`type`,
		`tokens`.`email`,
		`accounts`.`admin`,
		`accounts`.`disabled`,
		`accounts`.`reason`
	FROM `tokens`
	LEFT OUTER JOIN `accounts` ON `accounts`.`account_id` = `tokens`.`account_id`;
END//
DELIMITER ;

-- Dumping structure for procedure attic.dec_space
DELIMITER //
CREATE PROCEDURE `dec_space`(
	IN `_account_id` BINARY(16),
	IN `_file_size` BIGINT UNSIGNED
)
    MODIFIES SQL DATA
    DETERMINISTIC
BEGIN
	DECLARE _dec_ BIGINT UNSIGNED DEFAULT 4096; /* Decrement used space by 1 block = 4096 Bytes */
	
	IF _file_size IS NOT NULL THEN
		SET _dec_ = _dec_ + _file_size; /* Decrement used space by _file_size + block = 4096 Bytes */
	END IF;
		
	UPDATE `accounts` SET `current_space` = IF(`current_space` < _dec_, 0, `current_space` - _dec_)	WHERE `account_id` = _account_id;
END//
DELIMITER ;

-- Dumping structure for procedure attic.inc_space
DELIMITER //
CREATE PROCEDURE `inc_space`(
	IN `_account_id` BINARY(16),
	IN `_file_size` BIGINT UNSIGNED
)
    MODIFIES SQL DATA
    DETERMINISTIC
BEGIN
	DECLARE _inc_ BIGINT UNSIGNED DEFAULT 4096; /* Increment used space by 1 block = 4096 Bytes */
	
	IF _file_size IS NOT NULL THEN
		SET _inc_ = _inc_ + _file_size; /* Increment used space by _file_size + block = 4096 Bytes */
	END IF;
		
	UPDATE `accounts` SET `current_space` = `current_space` + _inc_	WHERE `account_id` = _account_id;
END//
DELIMITER ;

-- Dumping structure for procedure attic.internal_reset_db
DELIMITER //
CREATE PROCEDURE `internal_reset_db`()
    MODIFIES SQL DATA
BEGIN
	DECLARE _account_id_ BINARY(16) DEFAULT uuid_to_bin(UUID());
	DECLARE _folder_id_ BINARY(16) DEFAULT uuid_to_bin(UUID());
	
	DELETE FROM `accounts`;
	DELETE FROM `config`;
	DELETE FROM `local_shares`;
	DELETE FROM `remote_shares`;
	ALTER TABLE `accounts` AUTO_INCREMENT = 1;
	ALTER TABLE `local_shares` AUTO_INCREMENT = 1;
	
	INSERT INTO `config`(
		`salt`,
		`session_timeout`,
		`invitation_token_timeout`,
		`password_email_token_timeout`,
		`max_space`,
		`max_depth`
	) VALUES (
		random_string(16),
		1440,
		4320,
		60,
		10737418240,
		1000
	);
	
	INSERT INTO `accounts`(
		`account_id`,
		`name`,
		`email`,
		`password`,
		`admin`,
		`max_space`
	) VALUES (
		_account_id_,
		'DEFAULT',
		'admin@attic.com',
		encrypt_string('Admin0'),
		1,
		(SELECT `max_space` FROM `config`)
	);
	
	INSERT INTO `folders`(
		`account_id`,
		`parent_id`,
		`folder_id`,
		`name`
	) VALUES (
		`_account_id_`,
		NULL,
		_folder_id_,
		'New Folder'
	);
END//
DELIMITER ;

-- Dumping structure for procedure attic.throw
DELIMITER //
CREATE PROCEDURE `throw`(
	IN `_msg` VARCHAR(128)
)
BEGIN
	SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = _msg;
END//
DELIMITER ;

-- Dumping structure for function attic.bin_to_token
DELIMITER //
CREATE FUNCTION `bin_to_token`(`_bin` BINARY(32)
) RETURNS varchar(64) CHARSET utf8 COLLATE utf8_unicode_ci
    NO SQL
    DETERMINISTIC
BEGIN
	RETURN UPPER(HEX(_bin));
END//
DELIMITER ;

-- Dumping structure for function attic.bin_to_uuid
DELIMITER //
CREATE FUNCTION `bin_to_uuid`(`_bin` BINARY(16)
) RETURNS varchar(36) CHARSET utf8 COLLATE utf8_unicode_ci
    NO SQL
    DETERMINISTIC
BEGIN
	DECLARE _result_ VARCHAR(36) DEFAULT
     (LCASE(CONCAT_WS('-',
         HEX(SUBSTR(_bin,  5, 4)),
         HEX(SUBSTR(_bin,  3, 2)),
         HEX(SUBSTR(_bin,  1, 2)),
         HEX(SUBSTR(_bin,  9, 2)),
         HEX(SUBSTR(_bin, 11))
		)));
	IF _result_ = '' THEN
		RETURN NULL;
	END IF;
	RETURN _result_;
END//
DELIMITER ;

-- Dumping structure for function attic.create_token
DELIMITER //
CREATE FUNCTION `create_token`() RETURNS varchar(64) CHARSET utf8 COLLATE utf8_unicode_ci
    NO SQL
BEGIN
	RETURN UPPER(SHA2(CONCAT(NOW(), `random_string`(32), UUID()), 256));
END//
DELIMITER ;

-- Dumping structure for function attic.encrypt_string
DELIMITER //
CREATE FUNCTION `encrypt_string`(`_string` TEXT
) RETURNS binary(32)
    NO SQL
BEGIN
	RETURN UNHEX(SHA2(CONCAT((SELECT `salt` FROM `config`), _string), 256));
END//
DELIMITER ;

-- Dumping structure for function attic.random_string
DELIMITER //
CREATE FUNCTION `random_string`(`_length` INT UNSIGNED
) RETURNS text CHARSET utf8 COLLATE utf8_unicode_ci
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

-- Dumping structure for function attic.token_to_bin
DELIMITER //
CREATE FUNCTION `token_to_bin`(`_token` VARCHAR(64)
) RETURNS binary(32)
    NO SQL
    DETERMINISTIC
BEGIN
	RETURN UNHEX(_token);
END//
DELIMITER ;

-- Dumping structure for function attic.uuid_to_bin
DELIMITER //
CREATE FUNCTION `uuid_to_bin`(`_uuid` VARCHAR(36)
) RETURNS binary(16)
    NO SQL
    DETERMINISTIC
BEGIN
    RETURN
        UNHEX(CONCAT(
            SUBSTR(_uuid, 15, 4),
            SUBSTR(_uuid, 10, 4),
            SUBSTR(_uuid,  1, 8),
            SUBSTR(_uuid, 20, 4),
            SUBSTR(_uuid, 25) ));

END//
DELIMITER ;

-- Dumping structure for trigger attic.files_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `files_after_delete` AFTER DELETE ON `files` FOR EACH ROW BEGIN
	CALL dec_space(OLD.`account_id`, OLD.`size`);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic.files_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `files_after_insert` AFTER INSERT ON `files` FOR EACH ROW BEGIN
	CALL inc_space(NEW.account_id, NEW.`size`);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic.files_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `files_before_insert` BEFORE INSERT ON `files` FOR EACH ROW BEGIN
	CALL check_space(NEW.account_id, NEW.`size`);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic.folders_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `folders_after_delete` AFTER DELETE ON `folders` FOR EACH ROW BEGIN
	CALL dec_space(OLD.account_id, NULL);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic.folders_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `folders_after_insert` AFTER INSERT ON `folders` FOR EACH ROW BEGIN
	CALL inc_space(NEW.account_id, NULL);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic.folders_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `folders_before_insert` BEFORE INSERT ON `folders` FOR EACH ROW BEGIN
	DECLARE _parent_id_ BINARY(16) DEFAULT NEW.`parent_id`;
	DECLARE _depth_ INT UNSIGNED DEFAULT 0;
	DECLARE _max_depth_ INT UNSIGNED DEFAULT (SELECT `max_depth` FROM `config`);

	IF _parent_id_ IS NULL THEN
		/* if parent is null, check if folder with the same name already exists */
		IF EXISTS(SELECT 0 FROM `folders` WHERE `account_id` = NEW.`account_id` AND `parent_id` IS NULL AND `name` = NEW.`name`) THEN
			CALL throw('Folder with the same name already exists');
		END IF;
	END IF;
		
	/* check depth and recursion */
	WHILE _depth_ < _max_depth_ AND _parent_id_ IS NOT NULL DO
		SET _parent_id_ = (SELECT `parent_id` FROM `folders` WHERE `folder_id` = _parent_id_ LOCK IN SHARE MODE);
		SET _depth_ = _depth_ + 1;
	END WHILE;
	
	IF (_depth_ >= _max_depth_) THEN
		CALL throw('Depth limit reached');
	END IF;
	
	CALL check_space(NEW.account_id, NULL);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger attic.folders_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `folders_before_update` BEFORE UPDATE ON `folders` FOR EACH ROW BEGIN
	DECLARE _parent_id_ BINARY(16) DEFAULT NEW.`parent_id`;
	DECLARE _depth_ INT UNSIGNED DEFAULT 0;
	DECLARE _max_depth_ INT UNSIGNED DEFAULT (SELECT `max_depth` FROM `config`);

	IF _parent_id_ IS NULL THEN
		/* if parent is null, check if folder with the same name already exists */
		IF EXISTS(SELECT 0 FROM `folders` WHERE `account_id` = NEW.`account_id` AND `parent_id` IS NULL AND `name` = NEW.`name`) THEN
			CALL throw('Folder with the same name already exists');
		END IF;
	END IF;

	/* check for depth and recursion */
	WHILE _depth_ < _max_depth_ AND _parent_id_ IS NOT NULL DO
		IF _parent_id_ = NEW.`folder_id` THEN
			CALL throw('Recursion detected');
		END IF;
		SET _parent_id_ = (SELECT `parent_id` FROM `folders` WHERE `folder_id` = _parent_id_ LOCK IN SHARE MODE);
		SET _depth_ = _depth_ + 1;
	END WHILE;
	
	IF (_depth_ >= _max_depth_) THEN
		CALL throw('Depth limit reached');
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for view attic.v_files
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_files`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_files` AS select `files`.`file_id` AS `file_id`,`files`.`name` AS `name`,`files`.`public` AS `public`,`files`.`size` AS `size`,`files`.`current_size` AS `current_size`,`files`.`description` AS `description`,`files`.`folder_id` AS `folder_id`,`folders`.`name` AS `folder_name`,`files`.`account_id` AS `account_id`,`accounts`.`name` AS `account_name` from ((`files` left join `folders` on(`files`.`folder_id` = `folders`.`folder_id`)) left join `accounts` on(`files`.`account_id` = `accounts`.`account_id`));

-- Dumping structure for view attic.v_folders
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_folders`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_folders` AS select `folders`.`account_id` AS `account_id`,`accounts`.`name` AS `account_name`,`folders`.`parent_id` AS `parent_id`,`a_folders`.`name` AS `parent_name`,`folders`.`folder_id` AS `folder_id`,`folders`.`name` AS `name` from ((`folders` left join `folders` `a_folders` on(`folders`.`parent_id` = `a_folders`.`folder_id`)) left join `accounts` on(`folders`.`account_id` = `accounts`.`account_id`));

-- Dumping structure for view attic.v_local_shares
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_local_shares`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_local_shares` AS select `local_shares`.`account_id` AS `account_id`,`accounts`.`name` AS `account_name`,`local_shares`.`share_id` AS `share_id`,`local_shares`.`rcpt` AS `rcpt`,`local_shares`.`token` AS `token` from (`local_shares` left join `accounts` on(`accounts`.`account_id` = `local_shares`.`account_id`));

-- Dumping structure for view attic.v_remote_shares
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_remote_shares`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_remote_shares` AS select `remote_shares`.`account_id` AS `account_id`,`accounts`.`name` AS `account_name`,`remote_shares`.`sender` AS `sender`,`remote_shares`.`token` AS `token` from (`remote_shares` left join `accounts` on(`accounts`.`account_id` = `remote_shares`.`account_id`));

-- Dumping structure for view attic.v_sessions
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_sessions`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_sessions` AS select `sessions`.`account_id` AS `account_id`,`sessions`.`session_id` AS `session_id`,`accounts`.`admin` AS `admin`,`accounts`.`disabled` AS `disabled`,`accounts`.`reason` AS `reason` from (`sessions` left join `accounts` on(`accounts`.`account_id` = `sessions`.`account_id`));

-- Dumping structure for view attic.v_tokens
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `v_tokens`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_tokens` AS select `tokens`.`account_id` AS `account_id`,`tokens`.`token` AS `token`,`tokens`.`type` AS `type`,`tokens`.`email` AS `email`,`accounts`.`admin` AS `admin`,`accounts`.`disabled` AS `disabled`,`accounts`.`reason` AS `reason` from (`tokens` left join `accounts` on(`accounts`.`account_id` = `tokens`.`account_id`));

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
