<?php
/**
 * CommanderCrossbar tests.
 *
 * The constructor loads persons + IPs + networks then instantiates a
 * platform-specific commander that connects to a real device. For unit
 * tests we only verify:
 *   1. an unknown platform raises an exception, and
 *   2. delegation methods forward to the inner commander.
 *
 * Both are achieved by bypassing the constructor (reflection) and
 * injecting a Mockery double into the private $commander slot.
 */

class CommanderCrossbarTest extends TestCase
{
    private function newWithCommander($commanderMock): CommanderCrossbar
    {
        $r = new \ReflectionClass(CommanderCrossbar::class);
        $instance = $r->newInstanceWithoutConstructor();
        $p = $r->getProperty('commander');
        $p->setAccessible(true);
        $p->setValue($instance, $commanderMock);
        return $instance;
    }

    public function testSynchronizeFilterDelegates(): void
    {
        $commander = Mockery::mock();
        $commander->shouldReceive('synchronizeFilter')->once()->andReturn(['ok']);
        $cb = $this->newWithCommander($commander);
        $this->assertSame(['ok'], $cb->synchronizeFilter());
    }

    public function testIpFilterDownDelegates(): void
    {
        $commander = Mockery::mock();
        $commander->shouldReceive('getIPFilterDown')->once()->andReturn(['down']);
        $cb = $this->newWithCommander($commander);
        $this->assertSame(['down'], $cb->ipFilterDown());
    }

    public function testIpFilterUpDelegates(): void
    {
        $commander = Mockery::mock();
        $commander->shouldReceive('getIPFilterUp')->once()->andReturn(['up']);
        $cb = $this->newWithCommander($commander);
        $this->assertSame(['up'], $cb->ipFilterUp());
    }

    public function testAccountIPDelegates(): void
    {
        $commander = Mockery::mock();
        $commander->shouldReceive('accountIP')->once();
        $cb = $this->newWithCommander($commander);
        $cb->accountIP();
        $this->assertTrue(true); // expectation enforced by Mockery
    }

    public function testUnknownPlatformThrows(): void
    {
        global $core;
        $core->setProperty(Core::NETWORK_DEVICE_PLATFORM, 'NETWARE-3.12');
        // Drive the loop with empty result sets so we reach the platform check.
        $this->db->seedObjectList([]); // PersonDAO::getPersonArray
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Unknown platform/');
        new CommanderCrossbar();
        // Restore default for other tests.
        $core->setProperty(Core::NETWORK_DEVICE_PLATFORM, 'LINUX');
    }
}
