<?php

/** ensure this file is being included by a parent file */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

global $core;
require_once("configuration.html.php");

showConfiguration();

function showConfiguration() {
    global $database, $mainframe, $acl, $core;

    HTML_Configuration::showConfiguration(parse_ini_file($core->getAppRoot() . 'config/netprovider.ini', true));
}
