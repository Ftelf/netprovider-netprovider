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
require_once($core->getAppRoot() . "includes/net/SSH2.php");
require_once($core->getAppRoot() . "includes/net/routeros_api.class.php");

/**
 * Executor
 * Factory class to execute commands locally or over ssh
 */
class Executor {
    const LOCAL_COMMAND = 1;
    const REMOTE_SSH2 = 2;
    const REMOTE_MIKROTIK_API = 3;

    const REMOTE_HOST = 1;
    const REMOTE_PORT = 2;
    const LOGIN = 3;
    const PASSWORD = 4;
    const SUDO_COMMAND = 5;

    private $_type = null;
    private $_execute = null;
    private $_remoteHost = null;
    private $_remotePort = null;
    private $_login = null;
    private $_password = null;

    private $_ssh2 = null;
    private $_sudo = null;

    public function __construct($type, $settings, $execute) {
        $this->_execute = $execute;

        switch ($type) {
            case self::LOCAL_COMMAND:
                $this->_type = $type;

                if (isset($settings[self::SUDO_COMMAND])) {
                    $this->_sudo = $settings[self::SUDO_COMMAND];
                }

                break;

            case self::REMOTE_SSH2:
                $this->_type = $type;

                if (!isset($settings) || !is_array($settings)) {
                    throw new Exception("Configuration settings for SSH2 must be specified");
                }

                if (!isset($settings[self::REMOTE_HOST])) {
                    throw new Exception("No host specified");
                }
                $this->_remoteHost = $settings[self::REMOTE_HOST];

                if (!isset($settings[self::REMOTE_PORT])) {
                    $this->_remotePort = 22;
                } else {
                    $this->_remotePort = $settings[self::REMOTE_PORT];
                }

                if (!isset($settings[self::LOGIN])) {
                    throw new Exception("Configuration settings for SSH2 must be specified");
                }
                $this->_login = $settings[self::LOGIN];

                if (!isset($settings[self::PASSWORD])) {
                    throw new Exception("Configuration settings for SSH2 must be specified");
                }
                $this->_password = $settings[self::PASSWORD];

                if (isset($settings[self::SUDO_COMMAND])) {
                    $this->_sudo = $settings[self::SUDO_COMMAND];
                }

                if ($this->_execute) {
                    try {
                        $this->_ssh2 = new SSH2($this->_remoteHost, $this->_remotePort);

                        $this->_ssh2->login($this->_login, $this->_password);
                    } catch (Exception $e) {
                        throw new Exception(sprintf(_("SSH2 login failed at %s@%s"), $this->_login, $this->_remoteHost));
                    }
                }
                break;
            case self::REMOTE_MIKROTIK_API:
                $this->_type = $type;

                if (!isset($settings) || !is_array($settings)) {
                    throw new Exception("Configuration settings for API must be specified");
                }

                if (!isset($settings[self::REMOTE_HOST])) {
                    throw new Exception("No host specified");
                }
                $this->_remoteHost = $settings[self::REMOTE_HOST];

                if (!isset($settings[self::LOGIN])) {
                    throw new Exception("Configuration settings for API must be specified");
                }
                $this->_login = $settings[self::LOGIN];

                if (!isset($settings[self::PASSWORD])) {
                    throw new Exception("Configuration settings for API must be specified");
                }
                $this->_password = $settings[self::PASSWORD];

                if ($this->_execute) {
                    $this->routerosApi = new RouterosApi();
                    $this->routerosApi->port = 8729;
                    $this->routerosApi->ssl = true;
//					$this->routerosApi->debug = true;

                    if ($this->routerosApi->connect($this->_remoteHost, $this->_login, $this->_password)) {
                    } else {
                        throw new Exception(sprintf(_("API login failed at %s@%s"), $this->_login, $this->_remoteHost));
                    }
                }
                break;
            default:
                throw new Exception("Unknown type defined");
        }
    }

    public function getType() {
        return $this->_type;
    }

    public function execute($command) {
        switch ($this->_type) {
            case self::LOCAL_COMMAND:
                if ($this->_execute) {
                    $output = null;
                    $returnVar = null;

                    exec(sprintf("%s %s", $this->_sudo, $command), $output, $returnVar);

                    return array(
                        sprintf("%s %s", $this->_sudo, $command),
                        implode("\n", $output),
                        $returnVar
                    );
                } else {
                    return array(
                        sprintf("%s %s", $this->_sudo, $command),
                        null,
                        null
                    );
                }
                break;

            case self::REMOTE_SSH2:
                if ($this->_execute) {
                    return $this->_ssh2->exec(sprintf("%s %s", $this->_sudo, $command));
                } else {
                    return array(
                        sprintf("ssh %s@%s %s %s", $this->_login, $this->_remoteHost, $this->_sudo, $command),
                        null,
                        null
                    );
                }

            case self::REMOTE_MIKROTIK_API:
                if ($this->_execute) {
                    for ($i = 0, $iMax = count($command); $i < $iMax; $i++) {
                        $this->routerosApi->write($command[$i], (($i + 1) === $iMax));
                    }

                    $read = $this->routerosApi->read(true);
//					echo "<pre>";
//					print_r($read);
//					echo "</pre>";
//					$result = $this->routerosApi->parse_response($read);
                    return array(
                            $command,
                            $read,
                            null
                        );
                } else {
                    return array(
                        $command,
                        null,
                        null
                    );
                }
                break;
        }
    }
    public function disconnect() {
        switch ($this->_type) {
            case self::LOCAL_COMMAND:
                if ($this->_execute) {
                } else {
                }
                break;

            case self::REMOTE_SSH2:
                if ($this->_execute) {
                    return $this->_ssh2->exec(sprintf("%s %s", $this->_sudo, 'exit'));
                } else {
                }

            case self::REMOTE_MIKROTIK_API:
                if ($this->_execute) {
                    $this->routerosApi->disconnect();
                } else {
                }
                break;
        }
    }
    /**
     * execute array of commands
     */
    public function executeArray($commands) {
        if (!isset($commands) || !is_array($commands)) {
            throw new Exception("parameter must be array");
        }

        $results = array();

        foreach ($commands as $command) {
            $results[] = $this->execute($command);
        }

        return $results;
    }
} // End of Executor class
?>
