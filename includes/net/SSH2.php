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
 * SSH2
 */
class SSH2 {
    private $con;
    private $host;
    private $port;
    private $login;

    public function __construct($host = NULL, $port = 22) {
        $this->host = $host;
        $this->port = $port;

        $this->con = ssh2_connect($this->host, $this->port);
        // connection attempt successful ?
        if ($this->con === false) {
            $this->con = NULL;
            throw new SSH2Exception("Could not connect to host '" . $this->host . "' on port " . $this->port);
        }
    }

    public function login($login = NULL, $password = NULL) {
        if (empty ($login)) {
            throw new IllegalArgumentException("Login failed, no username supplied");
        }
        $this->login = $login;

        // try to log in
        if (!ssh2_auth_password($this->con, $this->login, $password)) {
            $this->con = NULL;
            throw new SSH2Exception("Password authentication failed for user '" . $this->login . "'");
        }
    }

    // returns an array like: array('stdout goes here', 'stderr')
    public function exec($cmd) {
        if (empty ($this->con)) {
            throw new SSH2Exception("Exec failed, no connection available");
        }
        // execute the command
        if (!$stdout_stream = ssh2_exec($this->con, $cmd)) {
            echo "Failed to execute SSH2 command: $cmd";
        }

        $err_stream = ssh2_fetch_stream($stdout_stream, SSH2_STREAM_STDERR);

        stream_set_blocking($err_stream, true);
        stream_set_blocking($stdout_stream, true);

        $result_err = stream_get_contents($err_stream);
        $result_dio = stream_get_contents($stdout_stream);

        // close the streams
        fclose($stdout_stream);
        fclose($err_stream);

        return array (
            $cmd,
            $result_dio,
            $result_err
        );
    }
}

class SSH2Exception extends Exception {
}
class IllegalArgumentException extends Exception {
}
?>
