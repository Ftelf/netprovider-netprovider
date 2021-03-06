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

/**
 * NetworkDeviceWirelessInterface
 */
class NetworkDeviceWirelessInterface {
    /** @var int networkdevicewireless ID PK */
    var $NW_networkdevicewirelessinterfaceid = null;
    /** @var int NetworkDevice ID FK */
    var $NW_networkdeviceid = null;
    /** @var int ip ID FK */
    var $NW_ipid = null;
    /** @var varchar(255) description */
    var $NW_ifname = null;
    /** @var int frequencyband */
    var $NW_band = null;
    /** @var int frequency */
    var $NW_frequency = null;
    /** @var int mode */
    var $NW_mode = null;
    /** @var varchar(255) ssid */
    var $NW_ssid = null;
    /** @var varchar(255) mac */
    var $NW_mac = null;
    /** @var varchar(255) description */
    var $NW_description = null;

    const BAND_2GHz_80211B = 1;
    const BAND_2GHz_80211BG = 2;
    const BAND_2GHz_80211G = 3;
    const BAND_2GHz_80211G_TURBO = 4;
    const BAND_5GHz_80211A_5Mhz = 5;
    const BAND_5GHz_80211A_10Mhz = 6;
    const BAND_5GHz_80211A = 7;
    const BAND_5GHz_80211A_TURBO = 8;

    public static $BAND_ARRAY = array(
        1, //2Ghz 802.11b
        2, //2Ghz 802.11b/g
        3, //2Ghz 802.11g
        4, //2Ghz 802.11g turbo
        5, //5Ghz 802.11a 5MHz kanál
        6, //5Ghz 802.11a 10MHz kanál
        7, //5Ghz 802.11a
        8  //5Ghz 802.11a turbo
    );

    public static function getLocalizedBand($band) {
        switch ($band) {
            case self::BAND_2GHz_80211B :
                return _("2.4Ghz 802.11b");

            case self::BAND_2GHz_80211BG :
                return _("2.4Ghz 802.11b/g");

            case self::BAND_2GHz_80211G :
                return _("2.4Ghz 802.11g");

            case self::BAND_2GHz_80211G_TURBO :
                return _("2.4Ghz 802.11g turbo");

            case self::BAND_5GHz_80211A_5Mhz :
                return _("5Ghz 802.11a 5MHz kanál");

            case self::BAND_5GHz_80211A_10Mhz :
                return _("5Ghz 802.11a 10MHz kanál");

            case self::BAND_5GHz_80211A :
                return _("5Ghz 802.11a");

            case self::BAND_5GHz_80211A_TURBO :
                return _("5Ghz 802.11a turbo");
        }
    }

    const MODE_UNDEFINED = 0;
    const MODE_AP_BRIDGE = 1;
    const MODE_STATION = 2;
    const MODE_WDS_BRIDGE = 3;

    public static $MODE_ARRAY = array(
        0, //Undefined
        1, //AP Bridge
        2, //Client
        3  //WDS Bridge
    );

    public static function getLocalizedMode($mode) {
        switch ($mode) {
            case self::MODE_UNDEFINED :
                return _("Undefined");

            case self::MODE_AP_BRIDGE :
                return _("AP Bridge");

            case self::MODE_STATION :
                return _("Client");

            case self::MODE_WDS_BRIDGE :
                return _("WDS bridge");
        }
    }

    public static function getFrequencyConstants() {
        return array(
            2312 => "N/A",
            2317 => "N/A",
            2322 => "N/A",
            2327 => "N/A",
            2332 => "N/A",
            2337 => "N/A",
            2342 => "N/A",
            2347 => "N/A",
            2352 => "N/A",
            2357 => "N/A",
            2362 => "N/A",
            2367 => "N/A",
            2372 => "N/A",
            2377 => "N/A",

            2412 => 1,
            2417 => 2,
            2422 => 3,
            2427 => 4,
            2432 => 5,
            2437 => 6,
            2442 => 7,
            2447 => 8,
            2452 => 9,
            2457 => 10,
            2462 => 11,
            2467 => 12,
            2472 => 13,
            2484 => 14,

            2512 => 15,
            2532 => 16,
            2552 => 17,
            2572 => 18,
            2592 => 19,
            2612 => 20,
            2632 => 21,
            2652 => 22,
            2672 => 23,
            2692 => 24,
            2712 => 25,
            2732 => 26,

            4920 => "N/A",
            4925 => "N/A",
            4930 => "N/A",
            4935 => "N/A",
            4940 => "N/A",
            4945 => "N/A",
            4950 => "N/A",
            4955 => "N/A",
            4960 => "N/A",
            4965 => "N/A",
            4970 => "N/A",
            4975 => "N/A",
            4980 => "N/A",
            4985 => "N/A",
            4990 => "N/A",
            4995 => "N/A",
            5000 => "N/A",
            5005 => "N/A",
            5010 => "N/A",
            5015 => "N/A",
            5020 => "N/A",
            5025 => "N/A",
            5030 => "N/A",
            5035 => "N/A",
            5040 => "N/A",
            5045 => "N/A",
            5050 => "N/A",
            5055 => "N/A",
            5060 => "N/A",
            5065 => "N/A",
            5070 => "N/A",
            5075 => "N/A",
            5080 => "N/A",
            5085 => "N/A",
            5090 => "N/A",
            5095 => "N/A",
            5100 => "N/A",
            5105 => "N/A",
            5110 => "N/A",
            5115 => "N/A",
            5120 => "N/A",
            5125 => "N/A",
            5130 => "N/A",
            5135 => "N/A",
            5140 => "N/A",
            5145 => "N/A",
            5150 => "N/A",
            5155 => "N/A",
            5160 => "N/A",
            5165 => "N/A",
            5170 => 34,
            5175 => 35,
            5180 => 36,
            5185 => 37,
            5190 => 38,
            5195 => 39,
            5200 => 40,
            5205 => 41,
            5210 => 42,
            5215 => 43,
            5220 => 44,
            5225 => 45,
            5230 => 46,
            5235 => 47,
            5240 => 48,
            5245 => 49,
            5250 => 50,
            5255 => 51,
            5260 => 52,
            5265 => 53,
            5270 => 54,
            5275 => 55,
            5280 => 56,
            5285 => 57,
            5290 => 58,
            5295 => 59,
            5300 => 60,
            5305 => 61,
            5310 => 62,
            5315 => 63,
            5320 => 64,
            5325 => 65,
            5330 => 66,
            5335 => 67,
            5340 => 68,
            5345 => 69,
            5350 => 70,
            5355 => 71,
            5360 => 72,
            5365 => 73,
            5370 => 74,
            5375 => 75,
            5380 => 76,
            5385 => 77,
            5390 => 78,
            5395 => 79,
            5400 => 80,
            5405 => 81,
            5410 => 82,
            5415 => 83,
            5420 => 84,
            5425 => 85,
            5430 => 86,
            5435 => 87,
            5440 => 88,
            5445 => 89,
            5450 => 90,
            5455 => 91,
            5460 => 92,
            5465 => 93,
            5470 => 94,
            5475 => 95,
            5480 => 96,
            5485 => 97,
            5490 => 98,
            5495 => 99,
            5500 => 100,
            5505 => 101,
            5510 => 102,
            5515 => 103,
            5520 => 104,
            5525 => 105,
            5530 => 106,
            5535 => 107,
            5540 => 108,
            5545 => 109,
            5550 => 110,
            5555 => 111,
            5560 => 112,
            5565 => 113,
            5570 => 114,
            5575 => 115,
            5580 => 116,
            5585 => 117,
            5590 => 118,
            5595 => 119,
            5600 => 120,
            5605 => 121,
            5610 => 122,
            5615 => 123,
            5620 => 124,
            5625 => 125,
            5630 => 126,
            5635 => 127,
            5640 => 128,
            5645 => 129,
            5650 => 130,
            5655 => 131,
            5660 => 132,
            5665 => 133,
            5670 => 134,
            5675 => 135,
            5680 => 136,
            5685 => 137,
            5690 => 138,
            5695 => 139,
            5700 => 140,
            5705 => 141,
            5710 => 142,
            5715 => 143,
            5720 => 144,
            5725 => 145,
            5730 => 146,
            5735 => 147,
            5740 => 148,
            5745 => 149,
            5750 => 150,
            5755 => 151,
            5760 => 152,
            5765 => 153,
            5770 => 154,
            5775 => 155,
            5780 => 156,
            5785 => 157,
            5790 => 158,
            5795 => 159,
            5800 => 160,
            5805 => "N/A",
            5810 => "N/A",
            5815 => "N/A",
            5820 => "N/A",
            5825 => "N/A",
            5830 => "N/A",
            5835 => "N/A",
            5840 => "N/A",
            5845 => "N/A",
            5850 => "N/A",
            5855 => "N/A",
            5860 => "N/A",
            5865 => "N/A",
            5870 => "N/A",
            5875 => "N/A",
            5880 => "N/A",
            5885 => "N/A",
            5890 => "N/A",
            5895 => "N/A",
            5900 => "N/A",
            5905 => "N/A",
            5910 => "N/A",
            5915 => "N/A",
            5920 => "N/A",
            5925 => "N/A",
            5930 => "N/A",
            5935 => "N/A",
            5940 => "N/A",
            5945 => "N/A",
            5950 => "N/A",
            5955 => "N/A",
            5960 => "N/A",
            5965 => "N/A",
            5970 => "N/A",
            5975 => "N/A",
            5980 => "N/A",
            5985 => "N/A",
            5990 => "N/A",
            5995 => "N/A",
            6000 => "N/A",
            6005 => "N/A",
            6010 => "N/A",
            6015 => "N/A",
            6020 => "N/A",
            6025 => "N/A",
            6030 => "N/A",
            6035 => "N/A",
            6040 => "N/A",
            6045 => "N/A",
            6050 => "N/A",
            6055 => "N/A",
            6060 => "N/A",
            6065 => "N/A",
            6070 => "N/A",
            6075 => "N/A",
            6080 => "N/A",
            6085 => "N/A",
            6090 => "N/A",
            6095 => "N/A",
            6100 => "N/A"
        );
    }
} // End of NetworkDeviceWirelessInterface class
?>