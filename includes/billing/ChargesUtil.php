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
require_once $core->getAppRoot() . "includes/dao/BankAccountDAO.php";
require_once $core->getAppRoot() . "includes/dao/BankAccountEntryDAO.php";
require_once $core->getAppRoot() . "includes/dao/EmailListDAO.php";
require_once $core->getAppRoot() . "includes/net/email/MimeDecode.php";

require_once $core->getAppRoot() . "includes/dao/PersonDAO.php";
require_once $core->getAppRoot() . "includes/dao/PersonAccountDAO.php";
require_once $core->getAppRoot() . "includes/dao/ChargeDAO.php";
require_once $core->getAppRoot() . "includes/dao/ChargeEntryDAO.php";
require_once $core->getAppRoot() . "includes/dao/HasChargeDAO.php";
require_once $core->getAppRoot() . "includes/dao/PersonAccountDAO.php";
require_once $core->getAppRoot() . "includes/dao/PersonAccountEntryDAO.php";
require_once $core->getAppRoot() . "includes/utils/DateUtil.php";

/**
 * ChargesUtil
 */
class ChargesUtil
{
    private array $_messages = [];
    private $_advancePayments;
    private $_charges;

    public function __construct()
    {
        global $core;

        $this->_advancePayments = $core->getProperty(Core::BLANK_CHARGES_ADVANCE_COUNT);
        $this->_charges = ChargeDAO::getChargeArray();
    }

    public function createBlankChargeEntries()
    {
        $persons = PersonDAO::getPersonWithAccountArray();

        // Iterate all active persons
        foreach ($persons as &$person) {
            $this->createOrRemoveChargeEntriesForPerson($person);
        }
    }

    public function createOrRemoveChargeEntriesForPerson($person, $ignoreStatuses = false, $enableMessagesForEntries = false)
    {
        global $database;

        $now = new DateUtil();

        // Iterate persons
        if ($ignoreStatuses || $person->PE_status == Person::STATUS_ACTIVE) {

            // get HasCharges for current person
            $hasCharges = HasChargeDAO::getHasChargeArrayByPersonID($person->PE_personid);

            // Iterate all HasCharges for this person
            foreach ($hasCharges as &$hasCharge) {
                if (!$ignoreStatuses
                    && $hasCharge->HC_status != HasCharge::STATUS_ENABLED
                    && $hasCharge->HC_status != HasCharge::STATUS_FORCE_DISABLED
                    && $hasCharge->HC_status != HasCharge::STATUS_FORCE_ENABLED
                ) {
                    continue;
                }

                if (!isset($this->_charges[$hasCharge->HC_chargeid])) {
                    $msg = sprintf("PersonID: %s has non-existent chargeID: %d", $person->PE_personid, $hasCharge->HC_chargeid);
                    $this->_messages[] = $msg;
                    $database->log($msg);

                    continue;
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
                                    $database->log($msg, Log::LEVEL_INFO);
                                }

                                $database->commit();
                            } catch (Exception $e) {
                                $database->rollback();
                                $msg = "Charge::PERIOD_MONTHLY, Error creating chargeEntry: " . $e . ", " . $e->getMessage();
                                $this->_messages[] = $msg;
                                $database->log($msg, Log::LEVEL_ERROR);
                            }
                        }

                        $floatingDate->add(DateUtil::MONTH, 1);
                    }

                    $this->removeChangeEntriesOutOfScope($person, $chargeEntries, $dateStart, $dateEnd);
                }
            }
        }
    }

    private function validateChangeEntriesAndBuildMap($chargeEntries)
    {
        global $database;

        $chargeEntriesMap = [];

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

    private function removeChangeEntriesOutOfScope($person, $chargeEntries, $dateStart, $dateEnd)
    {
        global $database;

        $personAccount = PersonAccountDAO::getPersonAccountByID($person->PE_personaccountid);

        try {
            $database->startTransaction();

            foreach ($chargeEntries as $chargeEntry) {
                $ceDate = new DateUtil($chargeEntry->CE_period_date);

                if ($ceDate->before($dateStart) || ($dateEnd->getTime() != null && $ceDate->after($dateEnd))) {
                    // An open-ended charge has no upper bound; show it as such
                    // instead of the empty string getFormattedDate() returns (D4).
                    $dateEndDisplay = $dateEnd->getTime() != null
                        ? $dateEnd->getFormattedDate(DateUtil::FORMAT_MONTHLY)
                        : "\u{221E}"; // ∞
                    $msg = sprintf(_("Removing payment entry for user %s with date %s not between %s and %s"), "$person->PE_firstname $person->PE_surname", $ceDate->getFormattedDate(DateUtil::FORMAT_MONTHLY), $dateStart->getFormattedDate(DateUtil::FORMAT_MONTHLY), $dateEndDisplay);
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

    public function proceedCharges($fireDeadlineEvents = false)
    {
        $persons = PersonDAO::getPersonArray();

        // Proceed all active persons
        foreach ($persons as $person) {
            $this->proceedChargesForPerson($person, $fireDeadlineEvents);
        }
    }

    public function proceedChargesForPerson($person, $fireDeadlineEvents = false)
    {
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
                } elseif ($charge->CH_period == Charge::PERIOD_MONTHLY) {
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
                        if ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING
                            || $chargeEntry->CE_status == ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS
                        ) {

                            // Calculate overdue of payment in days
                            $overdue = intval(($now->getTime() - $writeOffDate->getTime()) / (24 * 60 * 60));

                            // Snapshot state that the block below mutates in memory,
                            // so a rolled-back transaction can be fully reverted and
                            // never leaks a phantom balance into the next entry (D2).
                            $accountBalanceBefore = $personAccount->PA_balance;
                            $accountOutcomeBefore = $personAccount->PA_outcome;
                            $entryStatusBefore    = $chargeEntry->CE_status;
                            $entryOverdueBefore   = $chargeEntry->CE_overdue;
                            $entryRealizeBefore   = $chargeEntry->CE_realize_date;

                            // check if enough money on PersonAccount
                            if ($personAccount->PA_balance < $chargeEntry->CE_amount) {
                                // There is no enough money on account
                                // Payment is pending, so mark that we can't get payment and compute overdue
                                $fundsSufficient = false;
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
                                $fundsSufficient = true;
                                $personAccount->PA_balance -= $chargeEntry->CE_amount;
                                $personAccount->PA_outcome += $chargeEntry->CE_amount;
                                $chargeEntry->CE_realize_date = $now->getFormattedDate(DateUtil::DB_DATE);

                                // Set pending days according to, if there was attempt to charge this payment before
                                $chargeEntry->CE_overdue = ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING) ? 0 : $overdue;
                                $chargeEntry->CE_status = ChargeEntry::STATUS_FINISHED;
                            }

                            try {
                                $database->startTransaction();
                                // Only the successful-collection path moves money, so
                                // persist the account only then — the insufficient-funds
                                // path left the balance untouched (D3).
                                if ($fundsSufficient) {
                                    $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);
                                }
                                $database->updateObject("chargeentry", $chargeEntry, "CE_chargeentryid", false, false);
                                $database->commit();
                            } catch (Exception $e) {
                                $database->rollback();

                                // Revert the in-memory mutations so the rolled-back
                                // deduction does not survive into the next entry (D2).
                                $personAccount->PA_balance = $accountBalanceBefore;
                                $personAccount->PA_outcome = $accountOutcomeBefore;
                                $chargeEntry->CE_status       = $entryStatusBefore;
                                $chargeEntry->CE_overdue      = $entryOverdueBefore;
                                $chargeEntry->CE_realize_date = $entryRealizeBefore;

                                $msg = "Error processing ChargeEntry: " . $e->getMessage();
                                $this->_messages[] = $msg;
                                $database->log($msg, Log::LEVEL_ERROR);
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
                            // Current period: each status contributes a verdict that is
                            // AND-accumulated into actualEntryToBeEnabled. Every status
                            // is listed explicitly so the mapping to the §5.4 decision
                            // table stays visible — a `&& true` branch is a real "this
                            // status does not disable" verdict, not a forgotten no-op.
                            if ($chargeEntry->CE_status == ChargeEntry::STATUS_FINISHED
                                || $chargeEntry->CE_status == ChargeEntry::STATUS_PENDING
                                || $chargeEntry->CE_status == ChargeEntry::STATUS_TESTINGFREEOFCHARGE
                            ) {
                                // Paid, still-open or free: current period is fine.
                                $actualEntryToBeEnabled = $actualEntryToBeEnabled && true;
                            } elseif ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS) {
                                // Unpaid: acceptable only while inside the tolerance window.
                                $actualEntryToBeEnabled = $actualEntryToBeEnabled && ($chargeEntry->CE_overdue <= $charge->CH_tolerance);
                            } elseif ($chargeEntry->CE_status == ChargeEntry::STATUS_DISABLED) {
                                // Explicitly disabled: forces the current period off.
                                $actualEntryToBeEnabled = $actualEntryToBeEnabled && false;
                            }
                            // Any other status (e.g. ERROR) matches no branch and is
                            // neutral — the flag is left unchanged. See §8 (D8).
                        } else {
                            // Past period: each status contributes a verdict that is
                            // AND-accumulated into sequencePayed. Same table mapping —
                            // every status kept as its own branch for readability.
                            if ($chargeEntry->CE_status == ChargeEntry::STATUS_FINISHED
                                || $chargeEntry->CE_status == ChargeEntry::STATUS_PENDING
                                || $chargeEntry->CE_status == ChargeEntry::STATUS_TESTINGFREEOFCHARGE
                            ) {
                                // Paid, still-open or free: sequence stays clean.
                                $sequencePayed = $sequencePayed && true;
                            } elseif ($chargeEntry->CE_status == ChargeEntry::STATUS_DISABLED) {
                                // Excluded from billing: counts as clean, never breaks
                                // the paid sequence.
                                $sequencePayed = $sequencePayed && true;
                            } elseif ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS) {
                                // Unpaid: clean only while inside the tolerance window.
                                $sequencePayed = $sequencePayed && ($chargeEntry->CE_overdue <= $charge->CH_tolerance);
                            }
                            // Any other status (e.g. ERROR) is neutral — flag unchanged. §8 (D8).
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
                    } elseif ($hasCharge->HC_status == HasCharge::STATUS_FORCE_DISABLED) {
                        $newActualState = HasCharge::ACTUALSTATE_DISABLED;
                    } elseif (!count($chargeEntries)) {
                        $newActualState = HasCharge::ACTUALSTATE_DISABLED;
                    } elseif ($hasCharge->HC_status == HasCharge::STATUS_ENABLED) {
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
                        $database->log($msg, Log::LEVEL_ERROR);
                    }
                }
            }
        } elseif ($person->PE_status == Person::STATUS_PASSIVE || $person->PE_status == Person::STATUS_DISCARTED
        ) {

            foreach ($hasCharges as $hasCharge) {
                if ($hasCharge->HC_actualstate != HasCharge::ACTUALSTATE_DISABLED) {
                    $hasCharge->HC_actualstate = HasCharge::ACTUALSTATE_DISABLED;
                    $database->updateObject("hascharge", $hasCharge, "HC_haschargeid", false, false);
                }
            }
        }
    }

    public function getMessages()
    {
        return $this->_messages;
    }
} // End of ChargesUtil class
