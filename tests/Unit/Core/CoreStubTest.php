<?php
/**
 * CoreStub sanity tests — confirms the test stub honours Core's
 * contract well enough to substitute it across test cases.
 */

class CoreStubTest extends TestCase
{
    public function testGetAppRootReturnsTrailingSlash(): void
    {
        $core = new CoreStub('/some/path');
        $this->assertSame('/some/path/', $core->getAppRoot());
    }

    public function testGetPropertyReturnsConfiguredValue(): void
    {
        $core = new CoreStub(NP_PROJECT_ROOT);
        $this->assertSame('LINUX', $core->getProperty(Core::NETWORK_DEVICE_PLATFORM));
    }

    public function testGetPropertyThrowsOnUnknownKey(): void
    {
        $core = new CoreStub(NP_PROJECT_ROOT);
        $this->expectException(PropertyException::class);
        $core->getProperty('No Such Property');
    }

    public function testGetBooleanProperty(): void
    {
        $core = new CoreStub(NP_PROJECT_ROOT);
        $core->setProperty(Core::SMTP_AUTH, 1);
        $this->assertTrue($core->getBooleanProperty(Core::SMTP_AUTH));
        $core->setProperty(Core::SMTP_AUTH, 0);
        $this->assertFalse($core->getBooleanProperty(Core::SMTP_AUTH));
    }

    public function testIsInstanceOfCoreSoTypeHintsHold(): void
    {
        $this->assertInstanceOf(Core::class, new CoreStub(NP_PROJECT_ROOT));
    }
}
