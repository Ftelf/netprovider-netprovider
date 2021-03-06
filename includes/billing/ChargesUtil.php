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
// | Authors: Lukas Dziadkowiec <stealth.home@seznam.cz>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <stealth.home@seznam.cz>
 */

global $core;
require_once($core->getAppRoot() . "includes/dao/BankAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/BankAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/EmailListDAO.php");
require_once($core->getAppRoot() . "includes/net/email/MimeDecode.php");

require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/InvoiceDAO.php");
require_once($core->getAppRoot() . "includes/dao/InvoiceNumberDAO.php");
require_once($core->getAppRoot() . "includes/dao/HasChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/utils/DateUtil.php");
require_once($core->getAppRoot() . "includes/invoice/InvoiceFactory.php");

/**
 * ChargesUtil
 */
class ChargesUtil {
    private $_messages = array();
    private $_advancePayments = 1;

    public function __construct() {
        global $core;

        $this->_advancePayments = $core->getProperty(Core::BLANK_CHARGES_ADVANCE_COUNT);
    }

    public function createBlankChargeEntries() {
        global $database, $eventCrossBar;

        $now = new DateUtil();

        $persons = PersonDAO::getPersonWithAccountArray();
        $charges = ChargeDAO::getChargeArray();

        // Iterate all active persons
        //
        foreach ($persons as &$person) {
            if ($person->PE_status == Person::STATUS_ACTIVE) {
                // get HasCharges for current person
                //
                $hasCharges = HasChargeDAO::getHasChargeArrayByPersonID($person->PE_personid);
                // Iterate all HasCharges for this person
                //
                foreach ($hasCharges as &$hasCharge) {
                    if ($hasCharge->HC_status == HasCharge::STATUS_ENABLED ||
                        $hasCharge->HC_status == HasCharge::STATUS_FORCE_DISABLED ||
                        $hasCharge->HC_status == HasCharge::STATUS_FORCE_ENABLED) {

                        if (isset($charges[$hasCharge->HC_chargeid])) {
                            $charge = $charges[$hasCharge->HC_chargeid];

                            $dateStart = new DateUtil($hasCharge->HC_datestart);
                            $dateEnd = new DateUtil($hasCharge->HC_dateend);
                            // Invalid date ?
                            //
                            if ($dateEnd->getTime() != null && $dateStart->after($dateEnd)) {
                                $msg = sprintf("PersonID: %s has chargeID: %d where start date is before start date", $person->PE_personid, $hasCharge->HC_chargeid);
                                $this->_messages[] = $msg;
                                $database->log($msg);
                                continue;
                            }

                            // get all ChargeEntries for this HasCharge
                            //
                            $chargeEntries = ChargeEntryDAO::getChargeEntryArrayByHasChargeID($hasCharge->HC_haschargeid);

                            if ($charge->CH_period == Charge::PERIOD_ONCE) {
                                // Process Period Once payment
                                //
                                if (!count($chargeEntries)) {
                                    // No ChargeEntry stored, crete new one
                                    //
                                    try {
                                        $database->startTransaction();
                                        $chargeEntry = new ChargeEntry();
                                        $chargeEntry->CE_haschargeid = $hasCharge->HC_haschargeid;
                                        $chargeEntry->CE_baseamount = $charge->CH_baseamount;
                                        $chargeEntry->CE_vat = $charge->CH_vat;
                                        $chargeEntry->CE_amount = $charge->CH_amount;
                                        $chargeEntry->CE_currency = $charge->CH_currency;
                                        $chargeEntry->CE_period_date = $hasCharge->HC_datestart;
                                        $chargeEntry->CE_writeoffoffset = $charge->CH_writeoffoffset;
                                        $chargeEntry->CE_realize_date = DateUtil::DB_NULL_DATE;
                                        $chargeEntry->CE_overdue = 0;
                                        $chargeEntry->CE_status = ChargeEntry::STATUS_PENDING;
                                        $database->insertObject("chargeentry", $chargeEntry, "CE_chargeentryid", false);

                                        $periodDate = new DateUtil($chargeEntry->CE_period_date);

                                        try {
                                            $invoiceNumber = InvoiceNumberDAO::getInvoiceByYear($periodDate->get(DateUtil::YEAR));
                                        } catch (Exception $e) {
                                            $invoiceNumber = new InvoiceNumber();
                                            $invoiceNumber->IV_year = $periodDate->get(DateUtil::YEAR);
                                            $invoiceNumber->IV_number = 1;

                                            $database->insertObject("invoicenumber", $invoiceNumber, "IV_invoicenumberid", false);
                                        }

                                        $invoice = new Invoice();
                                        $invoice->IN_invoicenumber = $periodDate->get(DateUtil::YEAR).$invoiceNumber->IV_number;
                                        $invoice->IN_personid = $person->PE_personid;
                                        $invoice->IN_chargeentryid = $chargeEntry->CE_chargeentryid;
                                        $invoice->IN_dateofpay = $chargeEntry->CE_period_date;
                                        $invoice->IN_invoicedate = $chargeEntry->CE_period_date;
                                        $invoice->IN_taxdate = $chargeEntry->CE_period_date;
                                        $invoice->IN_recommendedpaydate = $chargeEntry->CE_period_date;
                                        $invoice->IN_bankaccount = "";
                                        $invoice->IN_constantsymbol = $person->PA_constantsymbol;
                                        $invoice->IN_variablesymbol = $person->PA_variablesymbol;
                                        $invoice->IN_specificsymbol = $person->PA_specificsymbol;
                                        $invoice->IN_baseamount = $chargeEntry->CE_baseamount;
                                        $invoice->IN_amount = $chargeEntry->CE_amount;
                                        $invoice->IN_currency = $chargeEntry->CE_currency;
                                        $database->insertObject("invoice", $invoice, "IN_invoiceid", false);

                                        $invoiceNumber->IV_number++;
                                        $database->updateObject("invoicenumber", $invoiceNumber, "IV_invoicenumberid", false, false);

                                        $event = new InvoiceCreated($now, clone $person, "Invoice created", $invoice->IN_invoiceid);
                                        $eventCrossBar->dispatchEvent($event);

                                        $database->commit();
                                    } catch (Exception $e) {
                                        $database->rollback();
                                        $msg = "Charge::PERIOD_ONCE, Error creating chargeEntry: " . $e . ", " . $e->getMessage();
                                        $this->_messages[] = $msg;
                                        $database->log($msg, LOG::LEVEL_ERROR);
                                    }
                                }
                            } else if ($charge->CH_period == Charge::PERIOD_MONTHLY) {
                                // Process monthly payment
                                // tsStart is start date alinged to 1.day of month in case of any bogus data
                                if ($dateStart->get(DateUtil::DAY) != 1) {
                                    $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid start date";
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
                                    $mEndDate = new DateUtil();
                                    $mEndDate->setTime($mDateMax->getTime());
                                } else {
                                    $mEndDate = clone $dateEnd;
                                    if ($mEndDate->get(DateUtil::DAY) != 1) {
                                        $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid end date";
                                        $this->_messages[] = $msg;
                                        $database->log($msg);
                                        continue;
                                    }
                                    if ($mEndDate->after($mDateMax)) {
                                        $mEndDate->setTime($mDateMax->getTime());
                                    }
                                }
                                $floatingDate = clone $dateStart;

                                while (!$mEndDate->before($floatingDate)) {
                                    $found = false;
                                    foreach ($chargeEntries as $chargeEntry) {
                                        $ceDate = new DateUtil($chargeEntry->CE_period_date);
                                        if ($ceDate->get(DateUtil::DAY) != 1) {
                                            $msg = "ChargeEntry ID: $chargeEntry->CE_chargeentryid has invalid period date";
                                            $this->_messages[] = $msg;
                                            $database->log($msg);
                                            continue;
                                        }
                                        if ($floatingDate->compareTo($ceDate) == 0) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if (!$found) {
                                        // No ChargeEntry stored, crete new one
                                        //
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

                                            $periodDate = new DateUtil($chargeEntry->CE_period_date);

                                            try {
                                                $invoiceNumber = InvoiceNumberDAO::getInvoiceByYear($periodDate->get(DateUtil::YEAR));
                                            } catch (Exception $e) {
                                                $invoiceNumber = new InvoiceNumber();
                                                $invoiceNumber->IV_year = $periodDate->get(DateUtil::YEAR);
                                                $invoiceNumber->IV_number = 1;

                                                $database->insertObject("invoicenumber", $invoiceNumber, "IV_invoicenumberid", false);
                                            }

                                            $invoice = new Invoice();
                                            $invoice->IN_invoicenumber = $periodDate->get(DateUtil::YEAR).$invoiceNumber->IV_number;
                                            $invoice->IN_personid = $person->PE_personid;
                                            $invoice->IN_chargeentryid = $chargeEntry->CE_chargeentryid;
                                            $invoice->IN_dateofpay = $chargeEntry->CE_period_date;
                                            $invoice->IN_invoicedate = $chargeEntry->CE_period_date;
                                            $invoice->IN_taxdate = $chargeEntry->CE_period_date;
                                            $invoice->IN_recommendedpaydate = $chargeEntry->CE_period_date;
                                            $invoice->IN_bankaccount = "";
                                            $invoice->IN_constantsymbol = $person->PA_constantsymbol;
                                            $invoice->IN_variablesymbol = $person->PA_variablesymbol;
                                            $invoice->IN_specificsymbol = $person->PA_specificsymbol;
                                            $invoice->IN_baseamount = $chargeEntry->CE_baseamount;
                                            $invoice->IN_amount = $chargeEntry->CE_amount;
                                            $invoice->IN_currency = $chargeEntry->CE_currency;
                                            $database->insertObject("invoice", $invoice, "IN_invoiceid", false);

                                            $invoiceNumber->IV_number++;
                                            $database->updateObject("invoicenumber", $invoiceNumber, "IV_invoicenumberid", false, false);

                                            $event = new InvoiceCreated($now, clone $person, "Invoice created", $invoice->IN_invoiceid);
                                            $eventCrossBar->dispatchEvent($event);

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
                            } else if ($charge->CH_period == Charge::PERIOD_QUARTERLY) {
                                // Process quarterly payment
                                // tsStart is start date alinged to 1.day of year quarter in case of any bogus data
                                if (($dateStart->get(DateUtil::MONTH) - 1) % 3 != 0) {
                                    $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid start date";
                                    $this->_messages[] = $msg;
                                    $database->log($msg);
                                    continue;
                                }

                                if ($dateStart->get(DateUtil::DAY) != 1) {
                                    $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid start date";
                                    $this->_messages[] = $msg;
                                    $database->log($msg);
                                    continue;
                                }

                                $mDateMax = clone $now;
                                $mDateMax->set(DateUtil::SECONDS, 0);
                                $mDateMax->set(DateUtil::MINUTES, 0);
                                $mDateMax->set(DateUtil::HOUR, 0);
                                $mDateMax->set(DateUtil::DAY, 1);
                                // Align current day into quarters
                                $mDateMax->set(DateUtil::MONTH, floor(($mDateMax->get(DateUtil::MONTH) - 1) / 3) * 3 + 1 + $this->_advancePayments * 3);

                                if ($dateEnd->getTime() == null) {
                                    $mEndDate = new DateUtil();
                                    $mEndDate->setTime($mDateMax->getTime());
                                } else {
                                    $mEndDate = clone $dateEnd;
                                    if (($mEndDate->get(DateUtil::MONTH) - 1) % 3 != 0) {
                                        $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid end date";
                                        $this->_messages[] = $msg;
                                        $database->log($msg);
                                        continue;
                                    }

                                    if ($mEndDate->get(DateUtil::DAY) != 1) {
                                        $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid end date";
                                        $this->_messages[] = $msg;
                                        $database->log($msg);
                                        continue;
                                    }
                                    if ($mEndDate->after($mDateMax)) {
                                        $mEndDate->setTime($mDateMax->getTime());
                                    }
                                }
                                $floatingDate = clone $dateStart;

                                while (!$mEndDate->before($floatingDate)) {
                                    $found = false;
                                    foreach ($chargeEntries as $chargeEntry) {
                                        $ceDate = new DateUtil($chargeEntry->CE_period_date);

                                        if (($ceDate->get(DateUtil::MONTH) - 1) % 3 != 0) {
                                            $msg = "ChargeEntry ID: $chargeEntry->CE_chargeentryid has invalid period date";
                                            $this->_messages[] = $msg;
                                            $database->log($msg);
                                            continue;
                                        }

                                        if ($ceDate->get(DateUtil::DAY) != 1) {
                                            $msg = "ChargeEntry ID: $chargeEntry->CE_chargeentryid has invalid period date";
                                            $this->_messages[] = $msg;
                                            $database->log($msg);
                                            continue;
                                        }
                                        if ($floatingDate->compareTo($ceDate) == 0) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if (!$found) {
                                        // No ChargeEntry stored, crete new one
                                        //
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

                                            $periodDate = new DateUtil($chargeEntry->CE_period_date);

                                            try {
                                                $invoiceNumber = InvoiceNumberDAO::getInvoiceByYear($periodDate->get(DateUtil::YEAR));
                                            } catch (Exception $e) {
                                                $invoiceNumber = new InvoiceNumber();
                                                $invoiceNumber->IV_year = $periodDate->get(DateUtil::YEAR);
                                                $invoiceNumber->IV_number = 1;

                                                $database->insertObject("invoicenumber", $invoiceNumber, "IV_invoicenumberid", false);
                                            }

                                            $invoice = new Invoice();
                                            $invoice->IN_invoicenumber = $periodDate->get(DateUtil::YEAR).$invoiceNumber->IV_number;
                                            $invoice->IN_personid = $person->PE_personid;
                                            $invoice->IN_chargeentryid = $chargeEntry->CE_chargeentryid;
                                            $invoice->IN_dateofpay = $chargeEntry->CE_period_date;
                                            $invoice->IN_invoicedate = $chargeEntry->CE_period_date;
                                            $invoice->IN_taxdate = $chargeEntry->CE_period_date;
                                            $invoice->IN_recommendedpaydate = $chargeEntry->CE_period_date;
                                            $invoice->IN_bankaccount = "";
                                            $invoice->IN_constantsymbol = $person->PA_constantsymbol;
                                            $invoice->IN_variablesymbol = $person->PA_variablesymbol;
                                            $invoice->IN_specificsymbol = $person->PA_specificsymbol;
                                            $invoice->IN_baseamount = $chargeEntry->CE_baseamount;
                                            $invoice->IN_amount = $chargeEntry->CE_amount;
                                            $invoice->IN_currency = $chargeEntry->CE_currency;
                                            $database->insertObject("invoice", $invoice, "IN_invoiceid", false);

                                            $invoiceNumber->IV_number++;
                                            $database->updateObject("invoicenumber", $invoiceNumber, "IV_invoicenumberid", false, false);

                                            $event = new InvoiceCreated($now, clone $person, "Invoice created", $invoice->IN_invoiceid);
                                            $eventCrossBar->dispatchEvent($event);

                                            $database->commit();
                                        } catch (Exception $e) {
                                            $database->rollback();
                                            $msg = "Charge::PERIOD_QUARTERLY, Error creating chargeEntry: " . $e . ", " . $e->getMessage();
                                            $this->_messages[] = $msg;
                                            $database->log($msg, LOG::LEVEL_ERROR);
                                        }
                                    }
                                    $floatingDate->add(DateUtil::MONTH, 3);
                                }
                            } else if ($charge->CH_period == Charge::PERIOD_HALFYEARLY) {
                                // Process quarterly payment
                                // tsStart is start date alinged to 1.day of year half in case of any bogus data
                                if (($dateStart->get(DateUtil::MONTH) - 1) % 6 != 0) {
                                    $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid start date";
                                    $this->_messages[] = $msg;
                                    $database->log($msg);
                                    continue;
                                }

                                if ($dateStart->get(DateUtil::DAY) != 1) {
                                    $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid start date";
                                    $this->_messages[] = $msg;
                                    $database->log($msg);
                                    continue;
                                }

                                $mDateMax = clone $now;
                                $mDateMax->set(DateUtil::SECONDS, 0);
                                $mDateMax->set(DateUtil::MINUTES, 0);
                                $mDateMax->set(DateUtil::HOUR, 0);
                                $mDateMax->set(DateUtil::DAY, 1);
                                // Align current day into year half
                                $mDateMax->set(DateUtil::MONTH, floor(($mDateMax->get(DateUtil::MONTH) - 1) / 6) * 6 + 1 + $this->_advancePayments * 6);

                                if ($dateEnd->getTime() == null) {
                                    $mEndDate = new DateUtil();
                                    $mEndDate->setTime($mDateMax->getTime());
                                } else {
                                    $mEndDate = clone $dateEnd;
                                    if (($mEndDate->get(DateUtil::MONTH) - 1) % 6 != 0) {
                                        $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid end date";
                                        $this->_messages[] = $msg;
                                        $database->log($msg);
                                        continue;
                                    }

                                    if ($mEndDate->get(DateUtil::DAY) != 1) {
                                        $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid end date";
                                        $this->_messages[] = $msg;
                                        $database->log($msg);
                                        continue;
                                    }
                                    if ($mEndDate->after($mDateMax)) {
                                        $mEndDate->setTime($mDateMax->getTime());
                                    }
                                }
                                $floatingDate = clone $dateStart;

                                while (!$mEndDate->before($floatingDate)) {
                                    $found = false;
                                    foreach ($chargeEntries as $chargeEntry) {
                                        $ceDate = new DateUtil($chargeEntry->CE_period_date);

                                        if (($ceDate->get(DateUtil::MONTH) - 1) % 6 != 0) {
                                            $msg = "ChargeEntry ID: $chargeEntry->CE_chargeentryid has invalid period date";
                                            $this->_messages[] = $msg;
                                            $database->log($msg);
                                            continue;
                                        }

                                        if ($ceDate->get(DateUtil::DAY) != 1) {
                                            $msg = "ChargeEntry ID: $chargeEntry->CE_chargeentryid has invalid period date";
                                            $this->_messages[] = $msg;
                                            $database->log($msg);
                                            continue;
                                        }
                                        if ($floatingDate->compareTo($ceDate) == 0) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if (!$found) {
                                        // No ChargeEntry stored, crete new one
                                        //
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

                                            $periodDate = new DateUtil($chargeEntry->CE_period_date);

                                            try {
                                                $invoiceNumber = InvoiceNumberDAO::getInvoiceByYear($periodDate->get(DateUtil::YEAR));
                                            } catch (Exception $e) {
                                                $invoiceNumber = new InvoiceNumber();
                                                $invoiceNumber->IV_year = $periodDate->get(DateUtil::YEAR);
                                                $invoiceNumber->IV_number = 1;

                                                $database->insertObject("invoicenumber", $invoiceNumber, "IV_invoicenumberid", false);
                                            }

                                            $invoice = new Invoice();
                                            $invoice->IN_invoicenumber = $periodDate->get(DateUtil::YEAR).$invoiceNumber->IV_number;
                                            $invoice->IN_personid = $person->PE_personid;
                                            $invoice->IN_chargeentryid = $chargeEntry->CE_chargeentryid;
                                            $invoice->IN_dateofpay = $chargeEntry->CE_period_date;
                                            $invoice->IN_invoicedate = $chargeEntry->CE_period_date;
                                            $invoice->IN_taxdate = $chargeEntry->CE_period_date;
                                            $invoice->IN_recommendedpaydate = $chargeEntry->CE_period_date;
                                            $invoice->IN_bankaccount = "";
                                            $invoice->IN_constantsymbol = $person->PA_constantsymbol;
                                            $invoice->IN_variablesymbol = $person->PA_variablesymbol;
                                            $invoice->IN_specificsymbol = $person->PA_specificsymbol;
                                            $invoice->IN_baseamount = $chargeEntry->CE_baseamount;
                                            $invoice->IN_amount = $chargeEntry->CE_amount;
                                            $invoice->IN_currency = $chargeEntry->CE_currency;
                                            $database->insertObject("invoice", $invoice, "IN_invoiceid", false);

                                            $invoiceNumber->IV_number++;
                                            $database->updateObject("invoicenumber", $invoiceNumber, "IV_invoicenumberid", false, false);

                                            $event = new InvoiceCreated($now, clone $person, "Invoice created", $invoice->IN_invoiceid);
                                            $eventCrossBar->dispatchEvent($event);

                                            $database->commit();
                                        } catch (Exception $e) {
                                            $database->rollback();
                                            $msg = "Charge::PERIOD_HALFYEARLY, Error creating chargeEntry: " . $e . ", " . $e->getMessage();
                                            $this->_messages[] = $msg;
                                            $database->log($msg, LOG::LEVEL_ERROR);
                                        }
                                    }
                                    $floatingDate->add(DateUtil::MONTH, 6);
                                }
                            } else if ($charge->CH_period == Charge::PERIOD_YEARLY) {
                                // Process yarly payment
                                // tsStart is start date alinged to 1.day of year in case of any bogus data
                                if ($dateStart->get(DateUtil::DAY) != 1) {
                                    $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid start date";
                                    $this->_messages[] = $msg;
                                    $database->log($msg);
                                    continue;
                                }

                                $yEndDate = clone $dateEnd;
                                $yDateMax = clone $now;
                                $yDateMax->set(DateUtil::SECONDS, 0);
                                $yDateMax->set(DateUtil::MINUTES, 0);
                                $yDateMax->set(DateUtil::HOUR, 0);
                                $yDateMax->set(DateUtil::DAY, 1);
                                $yDateMax->add(DateUtil::YEAR, $this->_advancePayments);

                                if ($yEndDate->getTime() == null) {
                                    $yEndDate->setTime($yDateMax->getTime());
                                } else {
                                    if ($yEndDate->get(DateUtil::DAY) != 1) {
                                        $msg = "HasCharge ID: $hasCharge->HC_haschargeid has invalid end date";
                                        $this->_messages[] = $msg;
                                        $database->log($msg);
                                        continue;
                                    }
                                    if ($yEndDate->after($yDateMax)) {
                                        $yEndDate->setTime($yDateMax->getTime());
                                    }
                                }
                                $floatingDate = clone $dateStart;

                                while (!$yEndDate->before($floatingDate)) {
                                    $found = false;
                                    foreach ($chargeEntries as $chargeEntry) {
                                        $ceDate = new DateUtil($chargeEntry->CE_period_date);
                                        if ($ceDate->get(DateUtil::DAY) != 1) {
                                            $msg = "ChargeEntry ID: $chargeEntry->CE_chargeentryid has invalid period date";
                                            $this->_messages[] = $msg;
                                            $database->log($msg);
                                            continue;
                                        }
                                        if ($floatingDate->compareTo($ceDate) == 0) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if (!$found) {
                                        // No ChargeEntry stored, crete new one
                                        //
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

                                            $periodDate = new DateUtil($chargeEntry->CE_period_date);

                                            try {
                                                $invoiceNumber = InvoiceNumberDAO::getInvoiceByYear($periodDate->get(DateUtil::YEAR));
                                            } catch (Exception $e) {
                                                $invoiceNumber = new InvoiceNumber();
                                                $invoiceNumber->IV_year = $periodDate->get(DateUtil::YEAR);
                                                $invoiceNumber->IV_number = 1;

                                                $database->insertObject("invoicenumber", $invoiceNumber, "IV_invoicenumberid", false);
                                            }

                                            $invoice = new Invoice();
                                            $invoice->IN_invoicenumber = $periodDate->get(DateUtil::YEAR).$invoiceNumber->IV_number;
                                            $invoice->IN_personid = $person->PE_personid;
                                            $invoice->IN_chargeentryid = $chargeEntry->CE_chargeentryid;
                                            $invoice->IN_dateofpay = $chargeEntry->CE_period_date;
                                            $invoice->IN_invoicedate = $chargeEntry->CE_period_date;
                                            $invoice->IN_taxdate = $chargeEntry->CE_period_date;
                                            $invoice->IN_recommendedpaydate = $chargeEntry->CE_period_date;
                                            $invoice->IN_bankaccount = "";
                                            $invoice->IN_constantsymbol = $person->PA_constantsymbol;
                                            $invoice->IN_variablesymbol = $person->PA_variablesymbol;
                                            $invoice->IN_specificsymbol = $person->PA_specificsymbol;
                                            $invoice->IN_baseamount = $chargeEntry->CE_baseamount;
                                            $invoice->IN_amount = $chargeEntry->CE_amount;
                                            $invoice->IN_currency = $chargeEntry->CE_currency;
                                            $database->insertObject("invoice", $invoice, "IN_invoiceid", false);

                                            $invoiceNumber->IV_number++;
                                            $database->updateObject("invoicenumber", $invoiceNumber, "IV_invoicenumberid", false, false);

                                            $event = new InvoiceCreated($now, clone $person, "Invoice created", $invoice->IN_invoiceid);
                                            $eventCrossBar->dispatchEvent($event);

                                            $database->commit();
                                        } catch (Exception $e) {
                                            $database->rollback();
                                            $msg = "Charge::PERIOD_YEARLY, Error creating chargeEntry: " . $e . ", " . $e->getMessage();
                                            $this->_messages[] = $msg;
                                            $database->log($msg, LOG::LEVEL_ERROR);
                                        }
                                    }
                                    $floatingDate->add(DateUtil::YEAR, 1);
                                }
                            }
                        } else {
                            $msg = sprintf("PersonID: %s has non-existent chargeID: %d", $person->PE_personid, $hasCharge->HC_chargeid);
                            $this->_messages[] = $msg;
                            $database->log($msg);
                        }
                    }
                }
            }
        }
    }

    public function proceedCharges($fireDeadlineEvents = true) {
        global $database, $eventCrossBar;

        $now = new DateUtil();

        $persons = PersonDAO::getPersonArray();
        $personAccounts = PersonAccountDAO::getPersonAccountArray();
        $charges = ChargeDAO::getChargeArray();

        // Proceed all active persons
        //
        foreach ($persons as $person) {
            $hasCharges = HasChargeDAO::getHasChargeArrayByPersonID($person->PE_personid);

            if ($person->PE_status == Person::STATUS_ACTIVE) {
                if (isset($personAccounts[$person->PE_personaccountid])) {
                    $personAccount = $personAccounts[$person->PE_personaccountid];

                    // Proceed all HasCharges for person
                    foreach ($hasCharges as $hasCharge) {
                        if (isset($charges[$hasCharge->HC_chargeid])) {
                            $charge = $charges[$hasCharge->HC_chargeid];

                            // if Status is DISABLED and actual status differs then disable
                            // disabled HasCharges are discarded from billing
                            if ($hasCharge->HC_status == HasCharge::STATUS_DISABLED) {
                                if ($hasCharge->HC_actualstate != HasCharge::ACTUALSTATE_DISABLED) {
                                    $hasCharge->HC_actualstate = HasCharge::ACTUALSTATE_DISABLED;
                                    $database->updateObject("hascharge", $hasCharge, "HC_haschargeid", false, false);
                                }
                            } else {
                                // if hasCharge has not yet started and enabled, then disable it
                                $dateStart = new DateUtil($hasCharge->HC_datestart);
                                if ($now->before($dateStart)) {
                                    // This has charge is in future
                                    // check if it is enabled by any reason
                                    if ($hasCharge->HC_actualstate != HasCharge::ACTUALSTATE_DISABLED) {
                                        $hasCharge->HC_actualstate = HasCharge::ACTUALSTATE_DISABLED;
                                        $database->updateObject("hascharge", $hasCharge, "HC_haschargeid", false, false);
                                    }
                                } else {
                                    // This charge may be in present
                                    $dateEnd = new DateUtil($hasCharge->HC_dateend);

                                    $chargeIsInPresent = false;
                                    if ($charge->CH_period == Charge::PERIOD_ONCE) {
                                        $chargeIsInPresent = true;
                                    } else if ($dateEnd->getTime() == null) {
                                        $chargeIsInPresent = true;
                                    } else if ($charge->CH_period == Charge::PERIOD_MONTHLY) {
                                        // Process monthly payment
                                        $dateEnd->add(DateUtil::MONTH, 1);
                                        $chargeIsInPresent = $now->before($dateEnd);
                                    } else if ($charge->CH_period == Charge::PERIOD_QUARTERLY) {
                                        // Process quarterly payment
                                        $dateEnd->add(DateUtil::MONTH, 3);
                                        $chargeIsInPresent = $now->before($dateEnd);
                                    } else if ($charge->CH_period == Charge::PERIOD_HALFYEARLY) {
                                        // Process half-yearly payment
                                        $dateEnd->add(DateUtil::MONTH, 6);
                                        $chargeIsInPresent = $now->before($dateEnd);
                                    } else if ($charge->CH_period == Charge::PERIOD_YEARLY) {
                                        // Process yarly payment
                                        $dateEnd->add(DateUtil::YEAR, 1);
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
                                            //Time to pay bills
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
                                                    //TODO fix DATETIME 0000-00-00
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
                                            if ($charge->CH_period == Charge::PERIOD_ONCE) {
                                                $periodIsInPresent = true;
                                            } else if ($charge->CH_period == Charge::PERIOD_MONTHLY) {
                                                // Process monthly payment
                                                $endPeriodDate = clone $periodDate;
                                                $endPeriodDate->add(DateUtil::MONTH, 1);
                                                $periodIsInPresent = $now->before($endPeriodDate);
                                            } else if ($charge->CH_period == Charge::PERIOD_QUARTERLY) {
                                                // Process quarterly payment
                                                $endPeriodDate = clone $periodDate;
                                                $endPeriodDate->add(DateUtil::MONTH, 3);
                                                $periodIsInPresent = $now->before($endPeriodDate);
                                            } else if ($charge->CH_period == Charge::PERIOD_HALFYEARLY) {
                                                // Process half-yearly payment
                                                $endPeriodDate = clone $periodDate;
                                                $endPeriodDate->add(DateUtil::MONTH, 6);
                                                $periodIsInPresent = $now->before($endPeriodDate);
                                            } else if ($charge->CH_period == Charge::PERIOD_YEARLY) {
                                                // Process yarly payment
                                                $endPeriodDate = clone $periodDate;
                                                $endPeriodDate->add(DateUtil::YEAR, 1);
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
                            }
                        } else {
                            $msg = sprintf("PersonID: %s has non-existent chargeID: %d", $person->PE_personid, $hasCharge->HC_chargeid);
                            $this->_messages[] = $msg;
                            $database->log($msg);
                        }
                    }
                } else {
                    $msg = sprintf("PersonID: %s has non-existent personaccountID: %s", $person->PE_personid, $person->PE_personaccountid);
                    $this->_messages[] = $msg;
                    $database->log($msg);
                }
            } else if (	$person->PE_status == Person::STATUS_PASSIVE ||
                        $person->PE_status == Person::STATUS_DISCARTED) {

                foreach ($hasCharges as $hasCharge) {
                    if ($hasCharge->HC_actualstate != HasCharge::ACTUALSTATE_DISABLED) {
                        $hasCharge->HC_actualstate = HasCharge::ACTUALSTATE_DISABLED;
                        $database->updateObject("hascharge", $hasCharge, "HC_haschargeid", false, false);
                    }
                }
            }
        }
    }
} // End of ChargesUtil class
?>