<?php
/**
 * RouterOSCommander tests — config validation only.
 *
 * Real construct connects to the RouterOS API. Tests cover the
 * pre-connect validation: missing host / login / password each throw.
 */

class RouterOSCommanderTest extends TestCase
{
    public function testThrowsWhenHostMissing(): void
    {
        global $core;
        $core->setProperty(Core::NETWORK_DEVICE_HOST, '');
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Host/');
        new RouterOSCommander([]);
    }

    public function testThrowsWhenLoginMissing(): void
    {
        global $core;
        $core->setProperty(Core::NETWORK_DEVICE_LOGIN, '');
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Login/');
        new RouterOSCommander([]);
    }

    public function testThrowsWhenPasswordMissing(): void
    {
        global $core;
        $core->setProperty(Core::NETWORK_DEVICE_PASSWORD, '');
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Password/');
        new RouterOSCommander([]);
    }
}
