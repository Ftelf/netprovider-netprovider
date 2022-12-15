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
class PersonAccount
{
    /**
     * @var int personaccount id PK
     */
    public $PA_personaccountid;
    /**
     * @var int person id FK
     */
    public $PA_currency;
    /**
     * @var DECIMAL(10,2) person account start balance
     */
    public $PA_startbalance;
    /**
     * @var DECIMAL(10,2) person account balance
     */
    public $PA_balance;
    /**
     * @var DECIMAL(10,2) person account total income
     */
    public $PA_income;
    /**
     * @var DECIMAL(10,2) person account total outcome
     */
    public $PA_outcome;
    /**
     * @var integer variable code, that identifies incoming payment
     */
    public $PA_variablesymbol;
    /**
     * @var integer constant code, that identifies incoming payment
     */
    public $PA_constantsymbol;
    /**
     * @var integer specific code, that identifies incoming payment
     */
    public $PA_specificsymbol;
} // End of PersonAccountEntry class
