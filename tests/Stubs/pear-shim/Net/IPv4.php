<?php
/**
 * PEAR Net_IPv4 stand-in for tests. The real package provides IPv4
 * subnet math; LinuxCommander pulls it in but our tests don't exercise
 * that path, so a minimal class is enough.
 */

if (!class_exists('Net_IPv4')) {
    class Net_IPv4
    {
        public $ip;
        public $netmask;
        public $bitmask;
        public $network;
        public $broadcast;

        public static function parseAddress($address)
        {
            return new self();
        }

        public static function ipInNetwork($ip, $network)
        {
            return false;
        }
    }
}
