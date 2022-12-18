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
require_once $core->getAppRoot() . "/includes/tables/MessageAttachment.php";

/**
 *  MessageAttachmentDAO
 */
class MessageAttachmentDAO
{
    public static function getMessageAttachmentForMessageIDCount($messageid)
    {
        if (!$messageid) {
            throw new Exception("no ID specified");
        }

        global $database;

        $query = sprintf("SELECT count(*) FROM `messageattachment` WHERE MA_messageid='%s'", $messageid);

        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getMessageAttachmentArrayForAttachmentForMessageID($messageid): array
    {
        global $database;

        $query = sprintf("SELECT * FROM `messageattachment` WHERE MA_messageid='%s'", $messageid);

        $database->setQuery($query);
        return $database->loadObjectList('MA_messageattachmentid');
    }

    public static function getMessageAttachmentNamesArrayForAttachmentForMessageID($messageid): array
    {
        global $database;

        $query = sprintf("SELECT MA_messageattachmentid, MA_messageid, MA_name, length(MA_attachment) as MA_attachment_length FROM `messageattachment` WHERE MA_messageid='%s'", $messageid);

        $database->setQuery($query);
        return $database->loadObjectList('MA_messageattachmentid');
    }

    public static function getMessageAttachmentByID($id): MessageAttachment
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $messageAttachment = new MessageAttachment();
        $query = sprintf("SELECT * FROM `messageattachment` WHERE `MA_messageattachmentid`='%s' LIMIT 1", $id);
        $database->setQuery($query);
        $database->loadObject($messageAttachment);
        return $messageAttachment;
    }

    public static function removeAttachmentMessageByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = sprintf("DELETE FROM `messageattachment` WHERE `MA_messageattachmentid`='%s' LIMIT 1", $id);
        $database->setQuery($query);
        $database->query();
    }

    public static function removeAttachmentMessageByMessageID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = sprintf("DELETE FROM `messageattachment` WHERE `MA_messageid`='%s'", $id);
        $database->setQuery($query);
        $database->query();
    }
} // End of MessageAttachmentDAO class
