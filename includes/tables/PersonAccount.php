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
 * PersonAccountEntry
 */
class PersonAccount {
    /** @var int personaccount id PK */
    var $PA_personaccountid = null;
    /** @var int person id FK */
    var $PA_currency = null;
    /** @var DECIMAL(10,2) person account start balance */
    var $PA_startbalance = null;
    /** @var DECIMAL(10,2) person account balance */
    var $PA_balance = null;
    /** @var DECIMAL(10,2) person account total income */
    var $PA_income = null;
    /** @var DECIMAL(10,2) person account total outcome */
    var $PA_outcome = null;
    /** @var integer variable code, that identifies incoming payment */
    var $PA_variablesymbol = null;
    /** @var integer constant code, that identifies incoming payment */
    var $PA_constantsymbol = null;
    /** @var integer specific code, that identifies incoming payment */
    var $PA_specificsymbol = null;
} // End of PersonAccountEntry class
?>
