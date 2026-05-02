<?php
/**
 * LinuxCommander tests — config validation only.
 *
 * The constructor connects to a real SSH host. Tests therefore only
 * exercise the synchronous validation branches that fire BEFORE the
 * SSH attempt.
 */

class LinuxCommanderTest extends TestCase
{
    public function testThrowsWhenHostMissing(): void
    {
        global $core;
        $core->setProperty(Core::NETWORK_DEVICE_HOST, '');
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No host specified');
        new LinuxCommander([], false);
    }

    public function testThrowsWhenLoginMissing(): void
    {
        global $core;
        $core->setProperty(Core::NETWORK_DEVICE_LOGIN, '');
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/API/');
        new LinuxCommander([], false);
    }

    public function testThrowsWhenPasswordMissing(): void
    {
        global $core;
        $core->setProperty(Core::NETWORK_DEVICE_PASSWORD, '');
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/SSH2/');
        new LinuxCommander([], false);
    }
}
