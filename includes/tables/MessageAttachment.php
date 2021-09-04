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

/**
 * MessageAttachment
 */
class MessageAttachment {
    /** @var int charge id PK */
    var $MA_messageattachmentid = null;
    /** @var int person id FK */
    var $MA_messageid = null;
    /** @var datetime datetime */
    var $MA_name = null;
    /** @var datetime datetime */
    var $MA_attachment = null;
} // End of MessageAttachment class
?>
