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
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

global $core;
require_once($core->getAppRoot() . "/includes/tables/MessageAttachment.php");

/**
 *  MessageAttachmentDAO
 */
class MessageAttachmentDAO {
    static function getMessageAttachmentForMessageIDCount($messageid) {
        if (!$messageid) throw new Exception("no ID specified");

        global $database;

        $query = sprintf("SELECT count(*) FROM `messageattachment` WHERE MA_messageid='%s'", $messageid);

        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getMessageAttachmentArrayForAttachmentForMessageID($messageid) {
        global $database;

        $query = sprintf("SELECT * FROM `messageattachment` WHERE MA_messageid='%s'", $messageid);

        $database->setQuery($query);
        return $database->loadObjectList('MA_messageattachmentid');
    }
    static function getMessageAttachmentNamesArrayForAttachmentForMessageID($messageid) {
        global $database;

        $query = sprintf("SELECT MA_messageattachmentid, MA_messageid, MA_name, length(MA_attachment) as MA_attachment_length FROM `messageattachment` WHERE MA_messageid='%s'", $messageid);

        $database->setQuery($query);
        return $database->loadObjectList('MA_messageattachmentid');
    }
    static function getMessageAttachmentByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $log = new MessageAttachment();
        $query = sprintf("SELECT * FROM `messageattachment` WHERE `MA_messageattachmentid`='%s' LIMIT 1", $id);
        $database->setQuery($query);
        $database->loadObject($log);
        return $log;
    }
    static function removeAttachmentMessageByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = sprintf("DELETE FROM `messageattachment` WHERE `MA_messageattachmentid`='%s' LIMIT 1", $id);
        $database->setQuery($query);
        $database->query();
    }
    static function removeAttachmentMessageByMessageID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = sprintf("DELETE FROM `messageattachment` WHERE `MA_messageid`='%s'", $id);
        $database->setQuery($query);
        $database->query();
    }
} // End of MessageAttachmentDAO class
?>
