<?php
/**
 * PEAR Mail_mime stand-in. See pear-shim/Mail.php.
 */

if (!class_exists('Mail_mime')) {
    class Mail_mime
    {
        public function __construct($eol = "\r\n") {}
        public function setTXTBody($body) {}
        public function setHTMLBody($body) {}
        public function addAttachment($file, $type = 'application/octet-stream', $name = '') {}
        public function get($params = []) { return ''; }
        public function headers($headers = []) { return $headers; }
    }
}
