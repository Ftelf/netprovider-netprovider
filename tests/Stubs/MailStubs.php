<?php
/**
 * Minimal stand-ins for the PEAR `Mail.php` / `Mail/mime.php` packages so
 * EmailUtil can be loaded in tests without installing PEAR. EmailUtil
 * itself is rarely instantiated from tests (Mockery handles the type),
 * but its top-of-file require_once needs these classes to exist on disk.
 *
 * Provides only the surface AccountEntryUtil + EmailUtil rely on.
 */

if (!class_exists('Mail')) {
    class Mail
    {
        public static function factory($driver, $params = [])
        {
            return new self();
        }

        public function send($recipients, $headers, $body)
        {
            return true;
        }
    }
}

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
