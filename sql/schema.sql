-- -----------------------------------------------------------------------------
-- netprovider — canonical database schema (structure only, no data)
--
-- Source of truth for:
--   * initial database setup (see README / docs/USER_GUIDE.md)
--   * integration tests (default NP_IT_SCHEMA; see tests/Integration/README.md)
--   * Phinx migration 001 (db/migrations/*_initial_schema.php loads this file)
--
-- Derived from the final production dump (MySQL 8.0), with these deliberate
-- deltas vs. that dump:
--   * all customer/operational data stripped (DDL only)
--   * per-table AUTO_INCREMENT offsets removed (every table starts clean)
--   * charset normalised utf8mb3 -> utf8mb4 / utf8mb4_czech_ci
--     (the deprecated `session`.`SE_sessionid` ASCII column is intentionally kept)
--   * the dead `configuration` table (no code reference) is not included
--
-- Regenerate from a fresh dump: see sql/README.md.
--
-- Load under a relaxed sql_mode so the legacy '0000-00-00' zero-dates the app
-- stores for open-ended HasCharge / unrealised ChargeEntry rows are accepted.
-- -----------------------------------------------------------------------------

SET NAMES utf8mb4;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `bankaccount`;
CREATE TABLE `bankaccount` (
  `BA_bankaccountid` int NOT NULL AUTO_INCREMENT,
  `BA_bankname` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `BA_accountname` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `BA_accountnumber` bigint NOT NULL,
  `BA_banknumber` decimal(4,0) unsigned zerofill NOT NULL,
  `BA_iban` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `BA_currency` varchar(10) COLLATE utf8mb4_czech_ci NOT NULL,
  `BA_startbalance` decimal(11,2) NOT NULL,
  `BA_income` decimal(11,2) NOT NULL DEFAULT '0.00',
  `BA_expenses` decimal(11,2) NOT NULL DEFAULT '0.00',
  `BA_includedcharges` decimal(11,2) NOT NULL DEFAULT '0.00',
  `BA_balance` decimal(11,2) NOT NULL DEFAULT '0.00',
  `BA_blockedbalance` decimal(11,2) NOT NULL DEFAULT '0.00',
  `BA_datasource` int NOT NULL DEFAULT '0',
  `BA_datasourcetype` int NOT NULL DEFAULT '0',
  `BA_emailserver` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `BA_emailusername` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `BA_emailpassword` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `BA_emailsender` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `BA_emailsubject` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  PRIMARY KEY (`BA_bankaccountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci COMMENT='Bank account';

DROP TABLE IF EXISTS `bankaccountentry`;
CREATE TABLE `bankaccountentry` (
  `BE_bankaccountentryid` int NOT NULL AUTO_INCREMENT,
  `BE_bankaccountid` int NOT NULL,
  `BE_personaccountentryid` int DEFAULT NULL,
  `BE_datetime` datetime NOT NULL,
  `BE_note` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `BE_comment` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `BE_accountname` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `BE_accountnumber` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `BE_banknumber` decimal(4,0) unsigned zerofill NOT NULL,
  `BE_writeoff_date` date NOT NULL,
  `BE_typeoftransaction` int NOT NULL DEFAULT '0',
  `BE_variablesymbol` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  `BE_constantsymbol` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  `BE_specificsymbol` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  `BE_amount` decimal(11,2) NOT NULL,
  `BE_charge` decimal(11,2) NOT NULL,
  `BE_message` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `BE_status` int NOT NULL DEFAULT '0',
  `BE_identifycode` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`BE_bankaccountentryid`),
  KEY `BE_bankaccountid` (`BE_bankaccountid`),
  KEY `BE_personaccountentryid` (`BE_personaccountentryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `charge`;
CREATE TABLE `charge` (
  `CH_chargeid` int NOT NULL AUTO_INCREMENT,
  `CH_name` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `CH_description` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `CH_period` int DEFAULT NULL,
  `CH_vat` decimal(10,2) DEFAULT NULL,
  `CH_baseamount` decimal(10,2) DEFAULT NULL,
  `CH_amount` decimal(10,2) DEFAULT NULL,
  `CH_currency` varchar(10) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `CH_tolerance` int DEFAULT NULL,
  `CH_writeoffoffset` int NOT NULL DEFAULT '0',
  `CH_type` int DEFAULT NULL,
  `CH_priority` int NOT NULL DEFAULT '0',
  `CH_internetid` int DEFAULT NULL,
  PRIMARY KEY (`CH_chargeid`),
  KEY `CH_internetid` (`CH_internetid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `chargeentry`;
CREATE TABLE `chargeentry` (
  `CE_chargeentryid` int NOT NULL AUTO_INCREMENT,
  `CE_haschargeid` int NOT NULL,
  `CE_period_date` date NOT NULL,
  `CE_writeoffoffset` int NOT NULL DEFAULT '0',
  `CE_realize_date` date NOT NULL,
  `CE_overdue` int NOT NULL,
  `CE_vat` decimal(10,2) DEFAULT NULL,
  `CE_baseamount` decimal(10,2) DEFAULT NULL,
  `CE_amount` decimal(10,2) NOT NULL,
  `CE_currency` varchar(10) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `CE_status` int NOT NULL,
  PRIMARY KEY (`CE_chargeentryid`),
  KEY `CE_haschargeid` (`CE_haschargeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `emaillist`;
CREATE TABLE `emaillist` (
  `EL_emaillistid` int NOT NULL AUTO_INCREMENT,
  `EL_bankaccountid` int NOT NULL,
  `EL_name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `EL_currency` varchar(10) COLLATE utf8mb4_czech_ci NOT NULL,
  `EL_year` int NOT NULL,
  `EL_no` int NOT NULL,
  `EL_datefrom` datetime NOT NULL,
  `EL_dateto` datetime NOT NULL,
  `EL_list` mediumblob NOT NULL,
  `EL_listtype` int NOT NULL,
  `EL_entrycount` int NOT NULL DEFAULT '0',
  `EL_status` int NOT NULL,
  PRIMARY KEY (`EL_emaillistid`),
  KEY `EL_bankaccountid` (`EL_bankaccountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `group`;
CREATE TABLE `group` (
  `GR_groupid` int NOT NULL AUTO_INCREMENT,
  `GR_name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  `GR_acl` int NOT NULL DEFAULT '0',
  `GR_level` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`GR_groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci COMMENT='group list';

DROP TABLE IF EXISTS `handleevent`;
CREATE TABLE `handleevent` (
  `HE_handleeventid` int NOT NULL AUTO_INCREMENT,
  `HE_type` tinyint unsigned NOT NULL,
  `HE_status` tinyint(1) NOT NULL,
  `HE_name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `HE_notifypersonid` int DEFAULT NULL,
  `HE_notifydaysbeforeturnoff` tinyint NOT NULL,
  `HE_emailsubject` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `HE_templatepath` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `HE_description` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  PRIMARY KEY (`HE_handleeventid`),
  KEY `HE_notifypersonid` (`HE_notifypersonid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `hascharge`;
CREATE TABLE `hascharge` (
  `HC_haschargeid` int NOT NULL AUTO_INCREMENT,
  `HC_chargeid` int NOT NULL,
  `HC_personid` int NOT NULL,
  `HC_datestart` date NOT NULL,
  `HC_dateend` date NOT NULL,
  `HC_status` int NOT NULL,
  `HC_actualstate` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`HC_haschargeid`),
  KEY `HC_chargeid` (`HC_chargeid`),
  KEY `HC_personid` (`HC_personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `internet`;
CREATE TABLE `internet` (
  `IN_internetid` int NOT NULL AUTO_INCREMENT,
  `IN_name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `IN_description` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `IN_dnl_rate` int NOT NULL,
  `IN_dnl_ceil` int NOT NULL,
  `IN_upl_rate` int NOT NULL,
  `IN_upl_ceil` int NOT NULL,
  `IN_prio` int NOT NULL,
  PRIMARY KEY (`IN_internetid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `ip`;
CREATE TABLE `ip` (
  `IP_ipid` int NOT NULL AUTO_INCREMENT,
  `IP_networkid` int NOT NULL DEFAULT '0',
  `IP_personid` int NOT NULL DEFAULT '0',
  `IP_address` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  `IP_dns` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`IP_ipid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `ipaccount`;
CREATE TABLE `ipaccount` (
  `IA_ipaccountid` int NOT NULL AUTO_INCREMENT,
  `IA_ipid` int NOT NULL,
  `IA_datetime` datetime NOT NULL,
  `IA_bytes_in` bigint NOT NULL,
  `IA_packets_in` bigint NOT NULL,
  `IA_bytes_out` bigint NOT NULL,
  `IA_packets_out` bigint NOT NULL,
  PRIMARY KEY (`IA_ipaccountid`),
  KEY `IA_ipid` (`IA_ipid`),
  KEY `IA_datetime` (`IA_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `ipaccountabs`;
CREATE TABLE `ipaccountabs` (
  `IB_ipaccountabsid` int NOT NULL AUTO_INCREMENT,
  `IB_ipid` int NOT NULL,
  `IB_bytes_in` bigint NOT NULL,
  `IB_packets_in` bigint NOT NULL,
  `IB_bytes_out` bigint NOT NULL,
  `IB_packets_out` bigint NOT NULL,
  PRIMARY KEY (`IB_ipaccountabsid`),
  UNIQUE KEY `IA_ipid` (`IB_ipid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `LO_logid` int NOT NULL AUTO_INCREMENT,
  `LO_personid` int NOT NULL DEFAULT '0',
  `LO_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LO_log` text COLLATE utf8mb4_czech_ci NOT NULL,
  `LO_level` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`LO_logid`),
  KEY `LO_personid` (`LO_personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `message`;
CREATE TABLE `message` (
  `ME_messageid` int NOT NULL AUTO_INCREMENT,
  `ME_personid` int NOT NULL,
  `ME_datetime` datetime NOT NULL,
  `ME_subject` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `ME_body` text COLLATE utf8mb4_czech_ci NOT NULL,
  `ME_status` int NOT NULL,
  PRIMARY KEY (`ME_messageid`),
  KEY `ME_personid` (`ME_personid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `messageattachment`;
CREATE TABLE `messageattachment` (
  `MA_messageattachmentid` int NOT NULL AUTO_INCREMENT,
  `MA_messageid` int NOT NULL,
  `MA_name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `MA_attachment` longblob NOT NULL,
  PRIMARY KEY (`MA_messageattachmentid`),
  KEY `MA_messageid` (`MA_messageid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `network`;
CREATE TABLE `network` (
  `NE_networkid` int NOT NULL AUTO_INCREMENT,
  `NE_parent_networkid` int NOT NULL DEFAULT '0',
  `NE_personid` int NOT NULL DEFAULT '0',
  `NE_net` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  `NE_description` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`NE_networkid`),
  UNIQUE KEY `NE_net` (`NE_net`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `person`;
CREATE TABLE `person` (
  `PE_personid` int NOT NULL AUTO_INCREMENT,
  `PE_groupid` int NOT NULL DEFAULT '0',
  `PE_personaccountid` int NOT NULL,
  `PE_firstname` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  `PE_surname` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  `PE_degree_prefix` varchar(20) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_degree_suffix` varchar(20) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_gender` varchar(10) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_birthdate` date DEFAULT NULL,
  `PE_nick` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_email` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_tel` varchar(50) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_secondary_phone_number` varchar(50) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_icq` varchar(50) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_jabber` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_address` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_city` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_zip` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_username` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_password` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_ic` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_dic` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_shortcompanyname` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_companyname` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PE_status` int NOT NULL DEFAULT '0',
  `PE_registerdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `PE_lastloggedin` datetime DEFAULT NULL,
  `PE_uistate` text COLLATE utf8mb4_czech_ci,
  PRIMARY KEY (`PE_personid`),
  KEY `PE_surname` (`PE_surname`),
  KEY `PE_personaccountid` (`PE_personaccountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `personaccount`;
CREATE TABLE `personaccount` (
  `PA_personaccountid` int NOT NULL AUTO_INCREMENT,
  `PA_currency` varchar(10) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PA_startbalance` decimal(11,2) NOT NULL DEFAULT '0.00',
  `PA_balance` decimal(11,2) NOT NULL DEFAULT '0.00',
  `PA_income` decimal(11,2) NOT NULL,
  `PA_outcome` decimal(11,2) NOT NULL,
  `PA_variablesymbol` bigint NOT NULL DEFAULT '0',
  `PA_constantsymbol` bigint NOT NULL DEFAULT '0',
  `PA_specificsymbol` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`PA_personaccountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `personaccountentry`;
CREATE TABLE `personaccountentry` (
  `PN_personaccountentryid` int NOT NULL AUTO_INCREMENT,
  `PN_bankaccountentryid` int DEFAULT NULL,
  `PN_personaccountid` int NOT NULL,
  `PN_date` date NOT NULL,
  `PN_amount` decimal(10,2) NOT NULL,
  `PN_currency` varchar(10) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `PN_source` int NOT NULL,
  `PN_comment` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  PRIMARY KEY (`PN_personaccountentryid`),
  KEY `PN_bankaccountentryid` (`PN_bankaccountentryid`),
  KEY `PN_personaccountid` (`PN_personaccountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `RO_roleid` int NOT NULL AUTO_INCREMENT,
  `RO_name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  `RO_description` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`RO_roleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci COMMENT='User roles';

DROP TABLE IF EXISTS `rolemember`;
CREATE TABLE `rolemember` (
  `RM_rolememberid` int NOT NULL AUTO_INCREMENT,
  `RM_personid` int NOT NULL DEFAULT '0',
  `RM_roleid` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`RM_rolememberid`),
  KEY `RM_personid` (`RM_personid`),
  KEY `RM_roleid` (`RM_roleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
  `SE_time` int NOT NULL DEFAULT '0',
  `SE_sessionid` varchar(255) CHARACTER SET ascii NOT NULL,
  `SE_personid` int NOT NULL DEFAULT '0',
  `SE_acl` tinyint NOT NULL DEFAULT '0',
  `SE_username` varchar(25) COLLATE utf8mb4_czech_ci NOT NULL DEFAULT '',
  `SE_ip` varchar(15) COLLATE utf8mb4_czech_ci NOT NULL,
  PRIMARY KEY (`SE_sessionid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET SQL_MODE=@OLD_SQL_MODE;
