<?php
/**
 * PEAR Mail stand-in for tests. Loaded by `require_once "Mail.php"` via
 * extended include_path. Real PEAR Mail not needed — tests mock EmailUtil.
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
