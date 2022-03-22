<?php
//
// +----------------------------------------------------------------------+
// | Ftelf ISP billing system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Ftelf ISP billing system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

global $core;
require_once($core->getAppRoot() . "includes/dao/BankAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/BankAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/EmailListDAO.php");
require_once($core->getAppRoot() . "includes/net/email/MimeDecode.php");

require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/HasChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/utils/DateUtil.php");

/**
 * ChargesUtil
 */
class ChargesUtil {
    private $_messages = array();
    private $_advancePayments = 1;
    private $_charges;

    public function __construct() {
        global $core;

        $this->_advancePayments = $core->getProperty(Core::BLANK_CHARGES_ADVANCE_COUNT);
        $this->_charges = ChargeDAO::getChargeArray();
    }

    public function createBlankChargeEntries() {
        global $database, $eventCrossBar;

        $now = new DateUtil();

        $persons = PersonDAO::getPersonWithAccountArray();

        // Iterate all active persons
        foreach ($persons as &$person) {
            $this->createOrRemoveChargeEntriesForPerson($person);
        }
    }

    public function createOrRemoveChargeEntriesForPerson($person, $ignoreStatuses = false, $enableMessagesForEntries = false) {
        global $database, $eventCrossBar;

        $now = new DateUtil();

        // Iterate persons
        if ($ignoreStatuses || $person->PE_status == Person::STATUS_ACTIVE) {

            // get HasCharges for current person
            $hasCharges = HasChargeDAO::getHasChargeArrayByPersonID($person->PE_personid);

            // Iterate all HasCharges for this person
            foreach ($hasCharges as &$hasCharge) {
                if (!$ignoreStatuses &&
                  $hasCharge->HC_status != HasCharge::STATUS_ENABLED &&
                  $hasCharge->HC_status != HasCharge::STATUS_FORCE_DISABLED &&
                  $hasCharge->HC_status != HasCharge::STATUS_FORCE_ENABLED) {
                    continue;
                }

                if (!isset($this->_charges[$hasCharge->HC_chargeid])) {
                    $msg = sprintf("PersonID: %s has non-existent chargeID: %d", $person->PE_personid, $hasCharge->HC_chargeid);
                    $this->_messages[] = $msg;
                    $database->log($msg);

                    return;
                }

                $charge = $this->_charges[$hasCharge->HC_chargeid];

                $dateStart = new DateUtil($hasCharge->HC_datestart);
                $dateEnd = new DateUtil($hasCharge->HC_dateend);

                // Invalid date ?
                if ($dateEnd->getTime() != null && $dateStart->after($dateEnd)) {
                    $msg = sprintf("PersonID: %s has chargeID: %d where start date is before end date", $person->PE_personid, $hasCharge->HC_chargeid);
                    $this->_messages[] = $msg;
                    $database->log($msg);
                    continue;
                }

                // get all ChargeEntries for this HasCharge
                $chargeEntries = ChargeEntryDAO::getChargeEntryArrayByHasChargeID($hasCharge->HC_haschargeid);
                $chargeEntriesMap = $this->validateChangeEntriesAndBuildMap($chargeEntries);

                if ($charge->CH_period == Charge::PERIOD_MONTHLY) {
                    // Process monthly payment
                    // tsStart is start date aligned to 1.day of month in case of any bogus data
                    if ($dateStart->get(DateUtil::DAY) != 1) {
                        $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid start date: $dateStart";
                        $this->_messages[] = $msg;
                        $database->log($msg);
                        continue;
                    }

                    $mDateMax = clone $now;
                    $mDateMax->set(DateUtil::SECONDS, 0);
                    $mDateMax->set(DateUtil::MINUTES, 0);
                    $mDateMax->set(DateUtil::HOUR, 0);
                    $mDateMax->set(DateUtil::DAY, 1);
                    $mDateMax->add(DateUtil::MONTH, $this->_advancePayments);

                    if ($dateEnd->getTime() == null) {
                        $mEndDate = clone $mDateMax;
                    } else {
                        $mEndDate = clone $dateEnd;
                        if ($mEndDate->get(DateUtil::DAY) != 1) {
                            $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid end date: $mEndDate";
                            $this->_messages[] = $msg;
                            $database->log($msg);
                            continue;
                        }

                        if ($mEndDate->after($mDateMax)) {
                            $mEndDate = clone $mDateMax;
                        }
                    }
                    $floatingDate = clone $dateStart;

                    while (!$mEndDate->before($floatingDate)) {
                        if (!isset($chargeEntriesMap[$floatingDate->getTime()])) {
                            // No ChargeEntry stored, create new one
                            try {
                                $database->startTransaction();

                                $chargeEntry = new ChargeEntry();
                                $chargeEntry->CE_haschargeid = $hasCharge->HC_haschargeid;
                                $chargeEntry->CE_baseamount = $charge->CH_baseamount;
                                $chargeEntry->CE_vat = $charge->CH_vat;
                                $chargeEntry->CE_amount = $charge->CH_amount;
                                $chargeEntry->CE_currency = $charge->CH_currency;
                                $chargeEntry->CE_period_date = $floatingDate->getFormattedDate(DateUtil::DB_DATE);
                                $chargeEntry->CE_writeoffoffset = $charge->CH_writeoffoffset;
                                $chargeEntry->CE_realize_date = DateUtil::DB_NULL_DATE;
                                $chargeEntry->CE_overdue = 0;
                                $chargeEntry->CE_status = ChargeEntry::STATUS_PENDING;
                                $database->insertObject("chargeentry", $chargeEntry, "CE_chargeentryid", false);

                                if ($enableMessagesForEntries) {
                                    $msg = sprintf(_("Adding payment entry for user %s with date %s"), "$person->PE_firstname $person->PE_surname", $floatingDate->getFormattedDate(DateUtil::FORMAT_MONTHLY));
                                    $this->_messages[] = $msg;
                                    $database->log($msg, LOG::LEVEL_INFO);
                                }

                                $database->commit();
                            } catch (Exception $e) {
                                $database->rollback();
                                $msg = "Charge::PERIOD_MONTHLY, Error creating chargeEntry: " . $e . ", " . $e->getMessage();
                                $this->_messages[] = $msg;
                                $database->log($msg, LOG::LEVEL_ERROR);
                            }
                        }

                        $floatingDate->add(DateUtil::MONTH, 1);
                    }

                    $this->removeChangeEntriesOutOfScope($person, $chargeEntries, $dateStart, $dateEnd);
                }
            }
        }
    }

    private function validateChangeEntriesAndBuildMap($chargeEntries) {
        global $database;

        $chargeEntriesMap = array();

        foreach ($chargeEntries as $chargeEntry) {
            $ceDate = new DateUtil($chargeEntry->CE_period_date);
            if ($ceDate->get(DateUtil::DAY) != 1) {
                $msg = "ChargeEntry ID: $chargeEntry->CE_chargeentryid has invalid period date: $ceDate";
                $this->_messages[] = $msg;
                $database->log($msg);
                continue;
            }

            $chargeEntriesMap[$ceDate->getTime()] = $chargeEntry;
        }

        return $chargeEntriesMap;
    }

    private function removeChangeEntriesOutOfScope($person, $chargeEntries, $dateStart, $dateEnd) {
        global $database, $appContext;

        $personAccount = PersonAccountDAO::getPersonAccountByID($person->PE_personaccountid);

        try {
            $database->startTransaction();

            foreach ($chargeEntries as $chargeEntry) {
                $ceDate = new DateUtil($chargeEntry->CE_period_date);

                if ($ceDate->before($dateStart) || ($dateEnd->getTime() != null && $ceDate->after($dateEnd))) {
                    $msg = sprintf(_("Removing payment entry for user %s with date %s not between %s and %s"), "$person->PE_firstname $person->PE_surname", $ceDate->getFormattedDate(DateUtil::FORMAT_MONTHLY), $dateStart->getFormattedDate(DateUtil::FORMAT_MONTHLY), $dateEnd->getFormattedDate(DateUtil::FORMAT_MONTHLY));
                    $this->_messages[] = $msg;
                    $database->log($msg);

                    if ($chargeEntry->CE_status == ChargeEntry::STATUS_FINISHED) {
                        $refundedAmount = $chargeEntry->CE_amount;
                        $personAccount->PA_balance += $refundedAmount;
                        $personAccount->PA_outcome -= $refundedAmount;
                    }

                    ChargeEntryDAO::removeChargeEntryByID($chargeEntry->CE_chargeentryid);
                }
            }

            $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);

            $database->commit();
        } catch (Exception $e) {
            $database->rollback();
            throw $e;
        }
    }

    public function proceedCharges($fireDeadlineEvents = false) {
        $persons = PersonDAO::getPersonArray();

        // Proceed all active persons
        foreach ($persons as $person) {
            $this->proceedChargesForPerson($person, $fireDeadlineEvents);
        }
    }

    public function proceedChargesForPerson($person, $fireDeadlineEvents = false) {
        global $database, $eventCrossBar;

        $now = new DateUtil();

        $hasCharges = HasChargeDAO::getHasChargeArrayByPersonID($person->PE_personid);

        if ($person->PE_status == Person::STATUS_ACTIVE) {
            $personAccount = null;
            try {
                $personAccount = PersonAccountDAO::getPersonAccountByID($person->PE_personaccountid);
            } catch (Exception $e) {
                $msg = sprintf("PersonID: %s has non-existent personaccountID: %s", $person->PE_personid, $person->PE_personaccountid);
                $this->_messages[] = $msg;
                $database->log($msg);

                return;
            }

            // Proceed all HasCharges for person
            foreach ($hasCharges as $hasCharge) {
                if (!isset($this->_charges[$hasCharge->HC_chargeid])) {
                    $msg = sprintf("PersonID: %s has non-existent chargeID: %d", $person->PE_personid, $hasCharge->HC_chargeid);
                    $this->_messages[] = $msg;
                    $database->log($msg);

                    continue;
                }

                $charge = $this->_charges[$hasCharge->HC_chargeid];

                // if Status is DISABLED and actual status differs then disable
                // disabled HasCharges are discarded from billing
                if ($hasCharge->HC_status == HasCharge::STATUS_DISABLED) {
                    if ($hasCharge->HC_actualstate != HasCharge::ACTUALSTATE_DISABLED) {
                        $hasCharge->HC_actualstate = HasCharge::ACTUALSTATE_DISABLED;
                        $database->updateObject("hascharge", $hasCharge, "HC_haschargeid", false, false);
                    }
                    continue;
                }

                // if hasCharge has not yet started and enabled, then disable it
                $dateStart = new DateUtil($hasCharge->HC_datestart);
                if ($now->before($dateStart)) {
                    // This has charge is in future
                    // check if it is enabled by any reason
                    if ($hasCharge->HC_actualstate != HasCharge::ACTUALSTATE_DISABLED) {
                        $hasCharge->HC_actualstate = HasCharge::ACTUALSTATE_DISABLED;
                        $database->updateObject("hascharge", $hasCharge, "HC_haschargeid", false, false);
                    }

                    continue;
                }

                // This charge may be in present
                $dateEnd = new DateUtil($hasCharge->HC_dateend);

                $chargeIsInPresent = false;
                if ($dateEnd->getTime() == null) {
                    $chargeIsInPresent = true;
                } else if ($charge->CH_period == Charge::PERIOD_MONTHLY) {
                    // Process monthly payment
                    $dateEnd->add(DateUtil::MONTH, 1);
                    $chargeIsInPresent = $now->before($dateEnd);
                }

                // Load ChargeEntries, must be sorted by date
                $chargeEntries = ChargeEntryDAO::getChargeEntryArrayByHasChargeID($hasCharge->HC_haschargeid);

                // Proceed all ChargeEntries for HasCharge
                $sequencePayed = true;
                $actualEntryToBeEnabled = true;
                foreach ($chargeEntries as $chargeEntry) {
                    // Calculate time of payment
                    $periodDate = new DateUtil($chargeEntry->CE_period_date);

                    $writeOffDate = clone $periodDate;
                    $writeOffDate->add(DateUtil::DAY, $chargeEntry->CE_writeoffoffset);

                    $toleranceDate = clone $periodDate;
                    $toleranceDate->add(DateUtil::DAY, $charge->CH_tolerance);

                    // Check if this ChargeEntry write-off refers to the future or not
                    // therefore shouldn't be payed right now
                    if (!$now->before($writeOffDate)) {
                        // Time to pay bills
                        if ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING ||
                          $chargeEntry->CE_status == ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS) {

                            // Calculate overdue of payment in days
                            $overdue = intval(($now->getTime() - $writeOffDate->getTime()) / (24 * 60 * 60));

                            // check if enough money on PersonAccount
                            if ($personAccount->PA_balance < $chargeEntry->CE_amount) {
                                // There is no enough money on account
                                // Payment is pending, so mark that we can't get payment and compute overdue
                                $chargeEntry->CE_status = ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS;
                                $chargeEntry->CE_overdue = $overdue;

                                if ($fireDeadlineEvents) {
                                    //FireEvent that payment has not been payed
                                    $event = new ChargePaymentDeadlineEvent($now, clone $person, "delayed payment", clone $charge, clone $periodDate, clone $writeOffDate, clone $toleranceDate);
                                    $event->hasCharge = clone $hasCharge;
                                    $event->chargeEntry = clone $chargeEntry;
                                    $eventCrossBar->dispatchEvent($event);
                                }
                            } else {
                                $personAccount->PA_balance -= $chargeEntry->CE_amount;
                                $personAccount->PA_outcome += $chargeEntry->CE_amount;
                                $chargeEntry->CE_realize_date = $now->getFormattedDate(DateUtil::DB_DATE);

                                // Set pending days according to, if there was attempt to charge this payment before
                                $chargeEntry->CE_overdue = ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING) ? 0 : $overdue;
                                $chargeEntry->CE_status = ChargeEntry::STATUS_FINISHED;
                            }

                            try {
                                $database->startTransaction();
                                $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);
                                $database->updateObject("chargeentry", $chargeEntry, "CE_chargeentryid", false, false);
                                $database->commit();
                            } catch (Exception $e) {
                                $database->rollback();
                                $msg = "Error processing ChargeEntry: " . $e->getMessage();
                                $this->_messages[] = $msg;
                                $database->log($msg, LOG::LEVEL_ERROR);
                            }
                        }
                    }

                    // Check if this ChargeEntry refers to the future or not
                    // therefore shouldn't be payed right now
                    if (!$now->before($periodDate)) {
                        // Compute if period is in present time
                        $periodIsInPresent = false;
                        if ($charge->CH_period == Charge::PERIOD_MONTHLY) {
                            // Process monthly payment
                            $endPeriodDate = clone $periodDate;
                            $endPeriodDate->add(DateUtil::MONTH, 1);
                            $periodIsInPresent = $now->before($endPeriodDate);
                        }
                        if ($periodIsInPresent) {
                            // We should take in place only ChargeEntries which are actual now
                            if ($chargeEntry->CE_status == ChargeEntry::STATUS_FINISHED ||
                              $chargeEntry->CE_status == ChargeEntry::STATUS_PENDING ||
                              $chargeEntry->CE_status == ChargeEntry::STATUS_TESTINGFREEOFCHARGE) {

                                // If chargeEntry is finished, pending or free, it should be enabled
                                $actualEntryToBeEnabled &= true;
                            } else if ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS) {

                                // If chargeEntry is pending with insufficient funds and in tolerance margin it should be enabled
                                $actualEntryToBeEnabled &= ($chargeEntry->CE_overdue <= $charge->CH_tolerance);
                            } else if ($chargeEntry->CE_status == ChargeEntry::STATUS_DISABLED) {

                                // If chargeEntry is ignored/disabled it should be disabled
                                $actualEntryToBeEnabled &= false;
                            }
                        } else {
                            //proceed sequence of charge entries
                            if ($chargeEntry->CE_status == ChargeEntry::STATUS_FINISHED ||
                              $chargeEntry->CE_status == ChargeEntry::STATUS_PENDING ||
                              $chargeEntry->CE_status == ChargeEntry::STATUS_TESTINGFREEOFCHARGE) {

                                // If chargeEntry is finished, pending or free it is clear sequence
                                $sequencePayed &= true;
                            } else if ($chargeEntry->CE_status == ChargeEntry::STATUS_DISABLED) {

                                // if chargeEntry is disabled it is clean sequence
                                $sequencePayed &= true;
                            } else if ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS) {

                                // If chargeEntry is pending with insufficient funds and in tolerance margin is is clear sequence, otherwise not
                                $sequencePayed &= ($chargeEntry->CE_overdue <= $charge->CH_tolerance);
                            }
                        }
                    }
                }

                // This code will switch HasCharge
                // if FORCE_ENABLED then force enable
                // if FORCE_DISABLED then force disable
                // if enabled but no HasChargeEntries then disable
                // if whole sequence is payed and actual entry is enable then enable
                // if enabled but out of time margin then disable
                $newActualState = null;
                if ($chargeIsInPresent) {
                    if ($hasCharge->HC_status == HasCharge::STATUS_FORCE_ENABLED) {
                        $newActualState = HasCharge::ACTUALSTATE_ENABLED;
                    } else if ($hasCharge->HC_status == HasCharge::STATUS_FORCE_DISABLED) {
                        $newActualState = HasCharge::ACTUALSTATE_DISABLED;
                    } else if (!count($chargeEntries)) {
                        $newActualState = HasCharge::ACTUALSTATE_DISABLED;
                    } else if ($hasCharge->HC_status == HasCharge::STATUS_ENABLED) {
                        if ($sequencePayed && $actualEntryToBeEnabled) {
                            $newActualState = HasCharge::ACTUALSTATE_ENABLED;
                        } else {
                            $newActualState = HasCharge::ACTUALSTATE_DISABLED;
                        }
                    }
                } else {
                    $newActualState = HasCharge::ACTUALSTATE_DISABLED;
                }
                if ($hasCharge->HC_actualstate != $newActualState) {
                    $hasCharge->HC_actualstate = $newActualState;
                    try {
                        $database->updateObject("hascharge", $hasCharge, "HC_haschargeid", false, false);
                    } catch (Exception $e) {
                        $msg = "Error processing HasCharge: " . $e->getMessage();
                        $this->_messages[] = $msg;
                        $database->log($msg, LOG::LEVEL_ERROR);
                    }
                }
            }
        } else if ($person->PE_status == Person::STATUS_PASSIVE ||
          $person->PE_status == Person::STATUS_DISCARTED) {

            foreach ($hasCharges as $hasCharge) {
                if ($hasCharge->HC_actualstate != HasCharge::ACTUALSTATE_DISABLED) {
                    $hasCharge->HC_actualstate = HasCharge::ACTUALSTATE_DISABLED;
                    $database->updateObject("hascharge", $hasCharge, "HC_haschargeid", false, false);
                }
            }
        }
    }

    public function getMessages() {
        return $this->_messages;
    }
} // End of ChargesUtil class
?>
