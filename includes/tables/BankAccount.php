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
 *  BankAccount
 */
class BankAccount
{
    /**
     * @public int bankaccount id PK
     */
    public $BA_bankaccountid;
    /**
     * @public int bank name
     */
    public $BA_bankname;
    /**
     * @public int bank number
     */
    public $BA_banknumber;
    /**
     * @public varchar name of account provided by bank
     */
    public $BA_accountname;
    /**
     * @public int account number
     */
    public $BA_accountnumber;
    /**
     * @public varchar account iban
     */
    public $BA_iban;
    /**
     * @public varchar(10) currency of account
     */
    public $BA_currency;
    /**
     * @public DECIMAL (10,2) account balance at the start
     */
    public $BA_startbalance;
    /**
     * @public DECIMAL (10,2) total account income
     */
    public $BA_income;
    /**
     * @public DECIMAL (10,2) total account expenses
     */
    public $BA_expenses;
    /**
     * @public DECIMAL (10,2) charges included in the expenses
     */
    public $BA_includedcharges;
    /**
     * @public DECIMAL (10,2) account balance at the end
     */
    public $BA_balance;
    /**
     * @public DECIMAL (10,2) blocked amount
     */
    public $BA_blockedbalance;
    /**
     * @public int datasource, specify how data will be fetched
     */
    public $BA_datasource;
    /**
     * @public int datasource, specify type of data
     */
    public $BA_datasourcetype;
    /**
     * @public varchar(255) datasource variables
     */
    public $BA_emailserver;
    /**
     * @public varchar(255) datasource variables
     */
    public $BA_emailusername;
    /**
     * @public varchar(255) datasource variables
     */
    public $BA_emailpassword;
    /**
     * @public varchar(255) datasource variables
     */
    public $BA_emailsender;
    /**
     * @public varchar(255) datasource variables
     */
    public $BA_emailsubject;

    public const DATASOURCE_MANUAL = 1;
    public const DATASOURCE_EMAIL_CONTENT = 2;

    public static array $datasourceArray = [
        self::DATASOURCE_MANUAL,
        self::DATASOURCE_EMAIL_CONTENT
    ];

    public static function getLocalizedDatasource($datasource): string
    {
        return match ($datasource) {
            self::DATASOURCE_MANUAL => _("Manual"),
            self::DATASOURCE_EMAIL_CONTENT => _("EMail content"),
            default => "",
        };
    }

    public const DATASOURCE_TYPE_RB_ATTACHMENT_TXT = 1;
    public const DATASOURCE_TYPE_RB_ATTACHMENT_PDF = 4;
    public const DATASOURCE_TYPE_ISO_SEPA_XML = 5;

    public static array $datasourceTypesArray = [
        self::DATASOURCE_TYPE_RB_ATTACHMENT_TXT,
        self::DATASOURCE_TYPE_RB_ATTACHMENT_PDF,
        self::DATASOURCE_TYPE_ISO_SEPA_XML
    ];

    public static function getLocalizedDatasourceType($datasourceType): string
    {
        return match ($datasourceType) {
            self::DATASOURCE_TYPE_RB_ATTACHMENT_TXT => "RB TXT attachment",
            self::DATASOURCE_TYPE_RB_ATTACHMENT_PDF => "RB PDF attachment",
            self::DATASOURCE_TYPE_ISO_SEPA_XML => "ISO SEPA XML",
            default => "",
        };
    }

    public static array $CURRENCY_ARRAY = [
        "AED", //United Arab Emirates, Dirhams
        "AFN", //Afghanistan, Afghanis
        "ALL", //Albania, Leke
        "AMD", //Armenia, Drams
        "ANG", //Netherlands Antilles, Guilders (also called Florins)
        "AOA", //Angola, Kwanza
        "ARS", //Argentina, Pesos
        "AUD", //Australia, Dollars
        "AWG", //Aruba, Guilders (also called Florins)
        "AZN", //Azerbaijan, New Manats
        "BAM", //Bosnia and Herzegovina, Convertible Marka
        "BBD", //Barbados, Dollars
        "BDT", //Bangladesh, Taka
        "BGN", //Bulgaria, Leva
        "BHD", //Bahrain, Dinars
        "BIF", //Burundi, Francs
        "BMD", //Bermuda, Dollars
        "BND", //Brunei Darussalam, Dollars
        "BOB", //Bolivia, Bolivianos
        "BRL", //Brazil, Brazil Real
        "BSD", //Bahamas, Dollars
        "BTN", //Bhutan, Ngultrum
        "BWP", //Botswana, Pulas
        "BYR", //Belarus, Rubles
        "BZD", //Belize, Dollars
        "CAD", //Canada, Dollars
        "CDF", //Congo/Kinshasa, Congolese Francs
        "CHF", //Switzerland, Francs
        "CLP", //Chile, Pesos
        "CNY", //China, Yuan Renminbi
        "COP", //Colombia, Pesos
        "CRC", //Costa Rica, Colones
        "CUP", //Cuba, Pesos
        "CVE", //Cape Verde, Escudos
        "CYP", //Cyprus, Pounds (expires 2008-Jan-31)
        "CZK", //Czech Republic, Koruny
        "DJF", //Djibouti, Francs
        "DKK", //Denmark, Kroner
        "DOP", //Dominican Republic, Pesos
        "DZD", //Algeria, Algeria Dinars
        "EEK", //Estonia, Krooni
        "EGP", //Egypt, Pounds
        "ERN", //Eritrea, Nakfa
        "ETB", //Ethiopia, Birr
        "EUR", //Euro Member Countries, Euro
        "FJD", //Fiji, Dollars
        "FKP", //Falkland Islands (Malvinas), Pounds
        "GBP", //United Kingdom, Pounds
        "GEL", //Georgia, Lari
        "GGP", //Guernsey, Pounds
        "GHS", //Ghana, Cedis
        "GIP", //Gibraltar, Pounds
        "GMD", //Gambia, Dalasi
        "GNF", //Guinea, Francs
        "GTQ", //Guatemala, Quetzales
        "GYD", //Guyana, Dollars
        "HKD", //Hong Kong, Dollars
        "HNL", //Honduras, Lempiras
        "HRK", //Croatia, Kuna
        "HTG", //Haiti, Gourdes
        "HUF", //Hungary, Forint
        "IDR", //Indonesia, Rupiahs
        "ILS", //Israel, New Shekels
        "IMP", //Isle of Man, Pounds
        "INR", //India, Rupees
        "IQD", //Iraq, Dinars
        "IRR", //Iran, Rials
        "ISK", //Iceland, Kronur
        "JEP", //Jersey, Pounds
        "JMD", //Jamaica, Dollars
        "JOD", //Jordan, Dinars
        "JPY", //Japan, Yen
        "KES", //Kenya, Shillings
        "KGS", //Kyrgyzstan, Soms
        "KHR", //Cambodia, Riels
        "KMF", //Comoros, Francs
        "KPW", //Korea (North), Won
        "KRW", //Korea (South), Won
        "KWD", //Kuwait, Dinars
        "KYD", //Cayman Islands, Dollars
        "KZT", //Kazakhstan, Tenge
        "LAK", //Laos, Kips
        "LBP", //Lebanon, Pounds
        "LKR", //Sri Lanka, Rupees
        "LRD", //Liberia, Dollars
        "LSL", //Lesotho, Maloti
        "LTL", //Lithuania, Litai
        "LVL", //Latvia, Lati
        "LYD", //Libya, Dinars
        "MAD", //Morocco, Dirhams
        "MDL", //Moldova, Lei
        "MGA", //Madagascar, Ariary
        "MKD", //Macedonia, Denars
        "MMK", //Myanmar (Burma), Kyats
        "MNT", //Mongolia, Tugriks
        "MOP", //Macau, Patacas
        "MRO", //Mauritania, Ouguiyas
        "MTL", //Malta, Liri (expires 2008-Jan-31)
        "MUR", //Mauritius, Rupees
        "MVR", //Maldives (Maldive Islands), Rufiyaa
        "MWK", //Malawi, Kwachas
        "MXN", //Mexico, Pesos
        "MYR", //Malaysia, Ringgits
        "MZN", //Mozambique, Meticais
        "NAD", //Namibia, Dollars
        "NGN", //Nigeria, Nairas
        "NIO", //Nicaragua, Cordobas
        "NOK", //Norway, Krone
        "NPR", //Nepal, Nepal Rupees
        "NZD", //New Zealand, Dollars
        "OMR", //Oman, Rials
        "PAB", //Panama, Balboa
        "PEN", //Peru, Nuevos Soles
        "PGK", //Papua New Guinea, Kina
        "PHP", //Philippines, Pesos
        "PKR", //Pakistan, Rupees
        "PLN", //Poland, Zlotych
        "PYG", //Paraguay, Guarani
        "QAR", //Qatar, Rials
        "RON", //Romania, New Lei
        "RSD", //Serbia, Dinars
        "RUB", //Russia, Rubles
        "RWF", //Rwanda, Rwanda Francs
        "SAR", //Saudi Arabia, Riyals
        "SBD", //Solomon Islands, Dollars
        "SCR", //Seychelles, Rupees
        "SDG", //Sudan, Pounds
        "SEK", //Sweden, Kronor
        "SGD", //Singapore, Dollars
        "SHP", //Saint Helena, Pounds
        "SKK", //Slovakia, Koruny
        "SLL", //Sierra Leone, Leones
        "SOS", //Somalia, Shillings
        "SPL", //Seborga, Luigini
        "SRD", //Suriname, Dollars
        "STD", //São Tome and Principe, Dobras
        "SVC", //El Salvador, Colones
        "SYP", //Syria, Pounds
        "SZL", //Swaziland, Emalangeni
        "THB", //Thailand, Baht
        "TJS", //Tajikistan, Somoni
        "TMM", //Turkmenistan, Manats
        "TND", //Tunisia, Dinars
        "TOP", //Tonga, Pa'anga
        "TRY", //Turkey, New Lira
        "TTD", //Trinidad and Tobago, Dollars
        "TVD", //Tuvalu, Tuvalu Dollars
        "TWD", //Taiwan, New Dollars
        "TZS", //Tanzania, Shillings
        "UAH", //Ukraine, Hryvnia
        "UGX", //Uganda, Shillings
        "USD", //United States of America, Dollars
        "UYU", //Uruguay, Pesos
        "UZS", //Uzbekistan, Sums
        "VEB", //Venezuela, Bolivares (expires 2008-Jun-30)
        "VEF", //Venezuela, Bolivares Fuertes
        "VND", //Viet Nam, Dong
        "VUV", //Vanuatu, Vatu
        "WST", //Samoa, Tala
        "XAF", //Communauté Financière Africaine BEAC, Francs
        "XAG", //Silver, Ounces
        "XAU", //Gold, Ounces
        "XCD", //East Caribbean Dollars
        "XDR", //International Monetary Fund (IMF) Special Drawing Rights
        "XOF", //Communauté Financière Africaine BCEAO, Francs
        "XPD", //Palladium Ounces
        "XPF", //Comptoirs Français du Pacifique Francs
        "XPT", //Platinum, Ounces
        "YER", //Yemen, Rials
        "ZAR", //South Africa, Rand
        "ZMK", //Zambia, Kwacha
        "ZWD"  //Zimbabwe, Zimbabwe Dollars
    ];
} // End of BankAccount class
