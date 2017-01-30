<?php
//
// +----------------------------------------------------------------------+
// | Stealth ISP QOS system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Stealth ISP QOS system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <stealth.home@seznam.cz>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <stealth.home@seznam.cz>
 */

global $core;
require_once($core->getAppRoot() . "/includes/tables/Message.php");
require_once($core->getAppRoot() . "/includes/utils/DateUtil.php");
require_once($core->getAppRoot() . "/includes/dao/MessageAttachmentDAO.php");

/**
 *  MessageDAO
 */
class MessageDAO {
    static function getMessageCount($personid=null, $dateFrom="0000-00-00 00:00:00", $dateTo="0000-00-00 00:00:00") {
        global $database;

        $query = "SELECT count(*) FROM `message` WHERE 1";

        if ($personid != "" && $personid != "0") {
            $query .= " AND `ME_personid`='$personid'";
        }
        if ($dateFrom != DateUtil::DB_NULL_DATETIME) {
            $query .= " AND `ME_datetime`>='$dateFrom'";
        }
        if ($dateTo != DateUtil::DB_NULL_DATETIME) {
            $query .= " AND `ME_datetime`<'$dateTo'";
        }

        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getMessageArray($personid=null, $dateFrom="0000-00-00 00:00:00", $dateTo="0000-00-00 00:00:00", $limitstart=null, $limit=null) {
        global $database;

        $query = "SELECT * FROM `message` WHERE 1";

        if ($personid != "" && $personid != "0") {
            $query .= " AND `ME_personid`='$personid'";
        }
        if ($dateFrom != DateUtil::DB_NULL_DATETIME) {
            $query .= " AND `ME_datetime`>='$dateFrom'";
        }
        if ($dateTo != DateUtil::DB_NULL_DATETIME) {
            $query .= " AND `ME_datetime`<'$dateTo'";
        }
        $query .= " ORDER BY `ME_datetime` ASC";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart, $limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList('ME_messageid');
    }
    static function getPendingMessageArray() {
        global $database;
        $query = "SELECT * FROM `message` WHERE ME_status!=" . Message::STATUS_SENDED;
        $database->setQuery($query);
        return $database->loadObjectList('ME_messageid');
    }
    static function getMessageByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $message = new Message();
        $query = "SELECT * FROM `message` WHERE `ME_messageid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($message);
        return $message;
    }
    static function removeMessageByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `message` WHERE `ME_messageid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
    static function removeMessageByPersonID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `message` WHERE `ME_personid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of MessageDAO class
?>