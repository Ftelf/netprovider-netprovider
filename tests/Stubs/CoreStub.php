<?php
/**
 * Test double for {@see Core}.
 *
 * Mirrors the real Core API surface used across the codebase
 * (`getAppRoot`, `getProperty`, `getBooleanProperty`) without touching the
 * filesystem (`config/netprovider.ini`), gettext, or locale state.
 *
 * Tests can override individual properties via `setProperty()`.
 */

require_once realpath(__DIR__ . '/../../includes/Core.php');

class CoreStub extends Core
{
    private string $rootOverride;
    private array $properties = [];

    public function __construct(string $appRoot)
    {
        // Bypass Core's filesystem-dependent constructor.
        $this->rootOverride = rtrim($appRoot, '/') . '/';
        $this->properties  = self::defaults();
    }

    public function getAppRoot(): string
    {
        return $this->rootOverride;
    }

    public function getProperty($property)
    {
        if (!array_key_exists($property, $this->properties)) {
            throw new PropertyException("Misconfigured property: $property");
        }
        return $this->properties[$property];
    }

    public function getBooleanProperty($property)
    {
        return (bool) $this->getProperty($property);
    }

    public function setProperty(string $key, $value): void
    {
        $this->properties[$key] = $value;
    }

    public function setProperties(array $kv): void
    {
        $this->properties = array_merge($this->properties, $kv);
    }

    /**
     * Reasonable defaults used across test scenarios.
     */
    public static function defaults(): array
    {
        return [
            Core::DATABASE_HOST                     => 'localhost',
            Core::DATABASE_NAME                     => 'netprovider_test',
            Core::DATABASE_USERNAME                 => 'test',
            Core::DATABASE_PASSWORD                 => 'test',
            Core::BLANK_CHARGES_ADVANCE_COUNT       => 3,
            Core::ENABLE_VAT_PAYER_SPECIFICS        => false,
            Core::ALLOW_FIRM_REGISTRATION           => false,
            Core::SMTP_SERVER                       => 'smtp.example.com',
            Core::SMTP_PORT                         => 25,
            Core::SMTP_AUTH                         => false,
            Core::SMTP_USERNAME                     => '',
            Core::SMTP_PASSWORD                     => '',
            Core::SMTP_FROM                         => 'noreply@example.com',
            Core::SUPERVISOR_EMAIL                  => 'supervisor@example.com',
            Core::SEND_EMAIL_ON_CRITICAL_ERROR      => false,
            Core::UI_TITLE                          => 'NetProvider Test',
            Core::UI_VENDOR                         => 'Test',
            Core::UI_LOCALE                         => 'en_US.UTF-8',
            Core::SMS_USERNAME                      => '',
            Core::SMS_PASSWORD                      => '',
            Core::NETWORK_DEVICE_PLATFORM           => 'LINUX',
            Core::NETWORK_DEVICE_HOST               => '127.0.0.1',
            Core::NETWORK_DEVICE_PORT               => 22,
            Core::NETWORK_DEVICE_LOGIN              => 'root',
            Core::NETWORK_DEVICE_PASSWORD           => 'pw',
            Core::NETWORK_DEVICE_WAN_INTERFACE      => 'eth0',
            Core::NETWORK_DEVICE_COMMAND_SUDO       => 'sudo',
            Core::NETWORK_DEVICE_COMMAND_IPTABLES   => '/sbin/iptables',
            Core::NETWORK_DEVICE_IP_ACCOUNTING      => false,
            Core::NETWORK_DEVICE_IP_FILTER          => false,
            Core::SYSTEM_DEBUG                      => false,
        ];
    }
}
