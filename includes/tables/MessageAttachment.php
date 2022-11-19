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
