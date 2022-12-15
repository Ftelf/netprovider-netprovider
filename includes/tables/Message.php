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
 * Message
 */
class Message
{
    /**
     * @var int charge id PK
     */
    public $ME_messageid;
    /**
     * @var int person id FK
     */
    public $ME_personid;
    /**
     * @var datetime datetime
     */
    public $ME_datetime;
    /**
     * @var varchar(255) subject of the message
     */
    public $ME_subject;
    /**
     * @var text body of the message
     */
    public $ME_body;
    /**
     * @var int status of the message
     */
    public $ME_status;

    public const STATUS_PENDING = 1;
    public const STATUS_SENDED = 2;
    public const STATUS_CANNOT_BE_SEND = 3;

    public static array $STATUS_ARRAY = [
        self::STATUS_PENDING,
        self::STATUS_SENDED,
        self::STATUS_CANNOT_BE_SEND
    ];

    public static array $statusLocalization = [
        self::STATUS_PENDING => "Pending",
        self::STATUS_SENDED => "Sent",
        self::STATUS_CANNOT_BE_SEND => "Cannot be sent"
    ];

    public static function getLocalizedStatus($status): string
    {
        return _(self::$statusLocalization[$status] ?? '');
    }
} // End of Message class
