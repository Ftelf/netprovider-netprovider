<?php
/**
 * Ftelf ISP billing system
 * This source file is part of Ftelf ISP billing system
 * see LICENSE for licence details.
 * php version 8.1.12
 *
 * @category Helper
 * @package  NetProvider
 * @author   Lukas Dziadkowiec <i.ftelf@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     https://www.ovjih.net
 */

global $core;
require_once $core->getAppRoot() . "/includes/tables/Message.php";
require_once $core->getAppRoot() . "/includes/utils/DateUtil.php";
require_once $core->getAppRoot() . "/includes/dao/MessageAttachmentDAO.php";

/**
 *  MessageDAO
 */
class MessageDAO
{
    public static function getMessageCount($personid = null, $dateFrom = "0000-00-00 00:00:00", $dateTo = "0000-00-00 00:00:00")
    {
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

    public static function getMessageArray($personid = null, $dateFrom = "0000-00-00 00:00:00", $dateTo = "0000-00-00 00:00:00", $limitstart = null, $limit = null): array
    {
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

    public static function getPendingMessageArray(): array
    {
        global $database;
        $query = "SELECT * FROM `message` WHERE ME_status!=" . Message::STATUS_SENDED;
        $database->setQuery($query);
        return $database->loadObjectList('ME_messageid');
    }

    public static function getMessageByID($id): Message
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $message = new Message();
        $query = "SELECT * FROM `message` WHERE `ME_messageid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($message);
        return $message;
    }

    public static function removeMessageByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `message` WHERE `ME_messageid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }

    public static function removeMessageByPersonID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `message` WHERE `ME_personid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of MessageDAO class
