-- phpMyAdmin SQL Dump
-- version 2.10.3
-- http://www.phpmyadmin.net
-- 
-- Počítač: localhost
-- Vygenerováno: Sobota 01. března 2008, 15:58
-- Verze MySQL: 5.0.45
-- Verze PHP: 5.2.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- Databáze: `netprovider2`
-- 
CREATE DATABASE `netprovider2` DEFAULT CHARACTER SET utf8 COLLATE utf8_czech_ci;
USE `netprovider2`;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `bankaccount`
-- 

CREATE TABLE IF NOT EXISTS `bankaccount` (
  `BA_bankaccountid` int(11) NOT NULL auto_increment,
  `BA_bankname` varchar(255) collate utf8_czech_ci NOT NULL,
  `BA_accountname` varchar(255) collate utf8_czech_ci NOT NULL,
  `BA_accountnumber` bigint(11) NOT NULL,
  `BA_banknumber` int(11) NOT NULL,
  `BA_iban` varchar(255) collate utf8_czech_ci NOT NULL,
  `BA_currency` varchar(10) collate utf8_czech_ci NOT NULL,
  `BA_startbalance` decimal(11,2) NOT NULL,
  `BA_income` decimal(11,2) NOT NULL default '0.00',
  `BA_expenses` decimal(11,2) NOT NULL default '0.00',
  `BA_includedcharges` decimal(11,2) NOT NULL default '0.00',
  `BA_balance` decimal(11,2) NOT NULL default '0.00',
  `BA_blockedbalance` decimal(11,2) NOT NULL default '0.00',
  `BA_datasourcetype` int(11) NOT NULL,
  `BA_emailserver` varchar(255) collate utf8_czech_ci default NULL,
  `BA_emailusername` varchar(255) collate utf8_czech_ci default NULL,
  `BA_emailpassword` varchar(255) collate utf8_czech_ci default NULL,
  `BA_emailsender` varchar(255) collate utf8_czech_ci default NULL,
  `BA_emailsubject` varchar(255) collate utf8_czech_ci default NULL,
  PRIMARY KEY  (`BA_bankaccountid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Bank account' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `bankaccountentry`
-- 

CREATE TABLE IF NOT EXISTS `bankaccountentry` (
  `BE_bankaccountentryid` int(11) NOT NULL auto_increment,
  `BE_bankaccountid` int(11) NOT NULL,
  `BE_personaccountentryid` int(11) default NULL,
  `BE_datetime` datetime NOT NULL,
  `BE_note` varchar(255) collate utf8_czech_ci NOT NULL,
  `BE_comment` varchar(255) collate utf8_czech_ci default NULL,
  `BE_accountname` varchar(255) collate utf8_czech_ci NOT NULL,
  `BE_accountnumber` varchar(255) collate utf8_czech_ci NOT NULL,
  `BE_banknumber` int(11) NOT NULL,
  `BE_writeoff_date` date NOT NULL,
  `BE_typeoftransaction` int(11) NOT NULL default '0',
  `BE_variablesymbol` bigint(20) NOT NULL default '0',
  `BE_constantsymbol` bigint(20) NOT NULL default '0',
  `BE_specificsymbol` bigint(20) NOT NULL default '0',
  `BE_amount` decimal(11,2) NOT NULL,
  `BE_charge` decimal(11,2) NOT NULL,
  `BE_message` varchar(255) collate utf8_czech_ci NOT NULL,
  `BE_status` int(11) NOT NULL default '0',
  `BE_identifycode` int(11) NOT NULL default '0',
  PRIMARY KEY  (`BE_bankaccountentryid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `charge`
-- 

CREATE TABLE IF NOT EXISTS `charge` (
  `CH_chargeid` int(11) NOT NULL auto_increment,
  `CH_name` varchar(255) collate utf8_czech_ci default NULL,
  `CH_description` varchar(255) collate utf8_czech_ci default NULL,
  `CH_period` int(11) default NULL,
  `CH_amount` decimal(10,2) default NULL,
  `CH_currency` varchar(10) collate utf8_czech_ci default NULL,
  `CH_tolerance` int(11) default NULL,
  `CH_type` int(11) default NULL,
  `CH_internetid` int(11) default NULL,
  PRIMARY KEY  (`CH_chargeid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `chargeentry`
-- 

CREATE TABLE IF NOT EXISTS `chargeentry` (
  `CE_chargeentryid` int(11) NOT NULL auto_increment,
  `CE_haschargeid` int(11) NOT NULL,
  `CE_period_date` date NOT NULL,
  `CE_realize_date` date NOT NULL,
  `CE_overdue` int(11) NOT NULL,
  `CE_amount` decimal(10,2) NOT NULL,
  `CE_status` int(11) NOT NULL,
  PRIMARY KEY  (`CE_chargeentryid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `configuration`
-- 

CREATE TABLE IF NOT EXISTS `configuration` (
  `CO_configurationid` int(11) NOT NULL auto_increment,
  `CO_name` varchar(255) collate utf8_czech_ci NOT NULL,
  `CO_value` varchar(255) collate utf8_czech_ci default NULL,
  PRIMARY KEY  (`CO_configurationid`),
  UNIQUE KEY `CO_name` (`CO_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `emaillist`
-- 

CREATE TABLE IF NOT EXISTS `emaillist` (
  `EL_emaillistid` int(11) NOT NULL auto_increment,
  `EL_bankaccountid` int(11) NOT NULL,
  `EL_name` varchar(255) collate utf8_czech_ci NOT NULL,
  `EL_currency` varchar(10) collate utf8_czech_ci NOT NULL,
  `EL_year` int(11) NOT NULL,
  `EL_no` int(11) NOT NULL,
  `EL_datefrom` datetime NOT NULL,
  `EL_dateto` datetime NOT NULL,
  `EL_list` text collate utf8_czech_ci NOT NULL,
  `EL_entrycount` int(11) NOT NULL default '0',
  `EL_status` int(11) NOT NULL,
  PRIMARY KEY  (`EL_emaillistid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `group`
-- 

CREATE TABLE IF NOT EXISTS `group` (
  `GR_groupid` int(11) NOT NULL auto_increment,
  `GR_name` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `GR_acl` int(11) NOT NULL default '0',
  `GR_level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`GR_groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='group list' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `hascharge`
-- 

CREATE TABLE IF NOT EXISTS `hascharge` (
  `HC_haschargeid` int(11) NOT NULL auto_increment,
  `HC_chargeid` int(11) NOT NULL,
  `HC_personid` int(11) NOT NULL,
  `HC_datestart` date NOT NULL,
  `HC_dateend` date NOT NULL,
  `HC_status` int(11) NOT NULL,
  `HC_actualstate` int(11) NOT NULL default '0',
  PRIMARY KEY  (`HC_haschargeid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `hasmanagednetwork`
-- 

CREATE TABLE IF NOT EXISTS `hasmanagednetwork` (
  `MN_hasmanagednetworkid` int(11) NOT NULL auto_increment,
  `MN_networkdeviceid` int(11) NOT NULL,
  `MN_networkid` int(11) NOT NULL,
  PRIMARY KEY  (`MN_hasmanagednetworkid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `internet`
-- 

CREATE TABLE IF NOT EXISTS `internet` (
  `IN_internetid` int(11) NOT NULL auto_increment,
  `IN_name` varchar(255) collate utf8_czech_ci NOT NULL,
  `IN_description` varchar(255) collate utf8_czech_ci NOT NULL,
  `IN_dnl_rate` int(11) NOT NULL,
  `IN_dnl_ceil` int(11) NOT NULL,
  `IN_upl_rate` int(11) NOT NULL,
  `IN_upl_ceil` int(11) NOT NULL,
  `IN_prio` int(11) NOT NULL,
  PRIMARY KEY  (`IN_internetid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `ip`
-- 

CREATE TABLE IF NOT EXISTS `ip` (
  `IP_ipid` int(11) NOT NULL auto_increment,
  `IP_networkid` int(11) NOT NULL default '0',
  `IP_personid` int(11) NOT NULL default '0',
  `IP_address` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `IP_dns` varchar(255) collate utf8_czech_ci NOT NULL default '',
  PRIMARY KEY  (`IP_ipid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `ipaccount`
-- 

CREATE TABLE IF NOT EXISTS `ipaccount` (
  `IA_ipaccountid` int(11) NOT NULL auto_increment,
  `IA_ipid` int(11) NOT NULL,
  `IA_datetime` datetime NOT NULL,
  `IA_bytes_in` bigint(20) NOT NULL,
  `IA_packets_in` bigint(20) NOT NULL,
  `IA_bytes_out` bigint(20) NOT NULL,
  `IA_packets_out` bigint(20) NOT NULL,
  PRIMARY KEY  (`IA_ipaccountid`),
  KEY `IA_ipid` (`IA_ipid`),
  KEY `IA_datetime` (`IA_datetime`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `ipaccountabs`
-- 

CREATE TABLE IF NOT EXISTS `ipaccountabs` (
  `IB_ipaccountabsid` int(11) NOT NULL auto_increment,
  `IB_ipid` int(11) NOT NULL,
  `IB_bytes_in` bigint(20) NOT NULL,
  `IB_packets_in` bigint(20) NOT NULL,
  `IB_bytes_out` bigint(20) NOT NULL,
  `IB_packets_out` bigint(20) NOT NULL,
  PRIMARY KEY  (`IB_ipaccountabsid`),
  UNIQUE KEY `IA_ipid` (`IB_ipid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `log`
-- 

CREATE TABLE IF NOT EXISTS `log` (
  `LO_logid` int(10) NOT NULL auto_increment,
  `LO_personid` int(10) NOT NULL default '0',
  `LO_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `LO_log` text collate utf8_czech_ci NOT NULL,
  PRIMARY KEY  (`LO_logid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `message`
-- 

CREATE TABLE IF NOT EXISTS `message` (
  `ME_messageid` int(11) NOT NULL auto_increment,
  `ME_personid` int(11) NOT NULL,
  `ME_datetime` datetime NOT NULL,
  `ME_subject` varchar(255) collate utf8_czech_ci NOT NULL,
  `ME_body` text collate utf8_czech_ci NOT NULL,
  `ME_status` int(11) NOT NULL,
  PRIMARY KEY  (`ME_messageid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `network`
-- 

CREATE TABLE IF NOT EXISTS `network` (
  `NE_networkid` int(11) NOT NULL auto_increment,
  `NE_parent_networkid` int(11) NOT NULL default '0',
  `NE_personid` int(11) NOT NULL default '0',
  `NE_net` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `NE_description` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `NE_networkdeviceid` int(11) default NULL,
  PRIMARY KEY  (`NE_networkid`),
  UNIQUE KEY `NE_net` (`NE_net`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `networkdevice`
-- 

CREATE TABLE IF NOT EXISTS `networkdevice` (
  `ND_networkdeviceid` int(11) NOT NULL auto_increment,
  `ND_name` varchar(255) collate utf8_czech_ci NOT NULL,
  `ND_vendor` varchar(255) collate utf8_czech_ci NOT NULL,
  `ND_type` varchar(255) collate utf8_czech_ci NOT NULL,
  `ND_platform` int(11) NOT NULL,
  `ND_description` varchar(255) collate utf8_czech_ci NOT NULL,
  `ND_managementInterfaceId` int(11) default NULL,
  `ND_login` varchar(255) collate utf8_czech_ci default NULL,
  `ND_password` varchar(255) collate utf8_czech_ci default NULL,
  `ND_useCommandSudo` tinyint(1) NOT NULL,
  `ND_commandSudo` varchar(255) collate utf8_czech_ci NOT NULL,
  `ND_commandIptables` varchar(255) collate utf8_czech_ci NOT NULL,
  `ND_commandIp` varchar(255) collate utf8_czech_ci NOT NULL,
  `ND_commandTc` varchar(255) collate utf8_czech_ci NOT NULL,
  `ND_qosEnabled` tinyint(1) NOT NULL,
  `ND_ipFilterEnabled` tinyint(1) NOT NULL,
  `ND_qosBandwidthDownload` bigint(20) NOT NULL default '0',
  `ND_qosBandwidthUpload` bigint(20) NOT NULL default '0',
  `ND_lanInterfaceid` int(11) NOT NULL,
  `ND_wanInterfaceid` int(11) NOT NULL,
  PRIMARY KEY  (`ND_networkdeviceid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `networkdeviceinterface`
-- 

CREATE TABLE IF NOT EXISTS `networkdeviceinterface` (
  `NI_networkdeviceinterfaceid` int(11) NOT NULL auto_increment,
  `NI_networkdeviceid` int(11) NOT NULL,
  `NI_ipid` int(11) default NULL,
  `NI_ifname` varchar(255) collate utf8_czech_ci NOT NULL,
  `NI_description` varchar(255) collate utf8_czech_ci NOT NULL,
  PRIMARY KEY  (`NI_networkdeviceinterfaceid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `networkdeviceproperty`
-- 

CREATE TABLE IF NOT EXISTS `networkdeviceproperty` (
  `NP_networkdevicepropertyid` int(11) NOT NULL auto_increment,
  `NP_networkdeviceid` int(11) NOT NULL,
  `NP_name` varchar(255) collate utf8_czech_ci NOT NULL,
  `NP_value` varchar(255) collate utf8_czech_ci NOT NULL,
  PRIMARY KEY  (`NP_networkdevicepropertyid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `networkdevicewirelessinterface`
-- 

CREATE TABLE IF NOT EXISTS `networkdevicewirelessinterface` (
  `NW_networkdevicewirelessinterfaceid` int(11) NOT NULL auto_increment,
  `NW_networkdeviceid` int(11) NOT NULL,
  `NW_ipid` int(11) default NULL,
  `NW_ifname` varchar(255) collate utf8_czech_ci NOT NULL,
  `NW_band` int(11) NOT NULL,
  `NW_frequency` int(11) NOT NULL,
  `NW_mode` int(11) NOT NULL,
  `NW_ssid` varchar(255) collate utf8_czech_ci NOT NULL,
  `NW_mac` varchar(17) collate utf8_czech_ci NOT NULL,
  `NW_description` varchar(255) collate utf8_czech_ci NOT NULL,
  PRIMARY KEY  (`NW_networkdevicewirelessinterfaceid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `person`
-- 

CREATE TABLE IF NOT EXISTS `person` (
  `PE_personid` int(11) NOT NULL auto_increment,
  `PE_groupid` int(10) NOT NULL default '0',
  `PE_personaccountid` int(11) NOT NULL,
  `PE_firstname` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `PE_surname` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `PE_degree_prefix` varchar(20) collate utf8_czech_ci default NULL,
  `PE_degree_suffix` varchar(20) collate utf8_czech_ci default NULL,
  `PE_gender` varchar(10) collate utf8_czech_ci default NULL,
  `PE_birthdate` date default NULL,
  `PE_nick` varchar(255) collate utf8_czech_ci default NULL,
  `PE_email` varchar(255) collate utf8_czech_ci default NULL,
  `PE_tel` varchar(50) collate utf8_czech_ci default NULL,
  `PE_icq` varchar(50) collate utf8_czech_ci default NULL,
  `PE_jabber` varchar(255) collate utf8_czech_ci default NULL,
  `PE_address` varchar(255) collate utf8_czech_ci default NULL,
  `PE_city` varchar(255) collate utf8_czech_ci default NULL,
  `PE_zip` varchar(255) collate utf8_czech_ci default NULL,
  `PE_username` varchar(255) collate utf8_czech_ci default NULL,
  `PE_password` varchar(255) collate utf8_czech_ci default NULL,
  `PE_status` int(11) NOT NULL default '0',
  `PE_registerdate` datetime NOT NULL default '0000-00-00 00:00:00',
  `PE_uistate` text collate utf8_czech_ci,
  PRIMARY KEY  (`PE_personid`),
  KEY `PE_surname` (`PE_surname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `personaccount`
-- 

CREATE TABLE IF NOT EXISTS `personaccount` (
  `PA_personaccountid` int(11) NOT NULL auto_increment,
  `PA_currency` varchar(10) collate utf8_czech_ci default NULL,
  `PA_startbalance` decimal(11,2) NOT NULL default '0.00',
  `PA_balance` decimal(11,2) NOT NULL default '0.00',
  `PA_income` decimal(11,2) NOT NULL,
  `PA_outcome` decimal(11,2) NOT NULL,
  `PA_variablesymbol` bigint(20) NOT NULL default '0',
  `PA_constantsymbol` bigint(20) NOT NULL default '0',
  `PA_specificsymbol` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`PA_personaccountid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `personaccountentry`
-- 

CREATE TABLE IF NOT EXISTS `personaccountentry` (
  `PN_personaccountentryid` int(11) NOT NULL auto_increment,
  `PN_bankaccountentryid` int(11) default NULL,
  `PN_personaccountid` int(11) NOT NULL,
  `PN_date` date NOT NULL,
  `PN_amount` decimal(10,2) NOT NULL,
  `PN_source` int(11) NOT NULL,
  `PN_comment` varchar(255) collate utf8_czech_ci default NULL,
  PRIMARY KEY  (`PN_personaccountentryid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `role`
-- 

CREATE TABLE IF NOT EXISTS `role` (
  `RO_roleid` int(11) NOT NULL auto_increment,
  `RO_name` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `RO_description` varchar(255) collate utf8_czech_ci NOT NULL default '',
  PRIMARY KEY  (`RO_roleid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='User roles' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `rolemember`
-- 

CREATE TABLE IF NOT EXISTS `rolemember` (
  `RM_rolememberid` int(11) NOT NULL auto_increment,
  `RM_personid` int(11) NOT NULL default '0',
  `RM_roleid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`RM_rolememberid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Struktura tabulky `session`
-- 

CREATE TABLE IF NOT EXISTS `session` (
  `SE_time` int(11) NOT NULL default '0',
  `SE_sessionid` varchar(255) character set ascii NOT NULL,
  `SE_personid` int(11) NOT NULL default '0',
  `SE_acl` tinyint(4) NOT NULL default '0',
  `SE_username` varchar(25) collate utf8_czech_ci NOT NULL default '',
  `SE_ip` varchar(15) collate utf8_czech_ci NOT NULL,
  PRIMARY KEY  (`SE_sessionid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
