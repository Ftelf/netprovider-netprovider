<?php
/**
 * MainFrame tests — request routing helper.
 */

class MainframeTest extends TestCase
{
    public function testGetPathResolvesValidModule(): void
    {
        $mf = new MainFrame($this->db, 'com_admin', '', null);
        $path = $mf->getPath();
        $this->assertStringEndsWith('modules/com_admin/admin.index.php', $path);
        $this->assertTrue(file_exists($path));
    }

    public function testGetPathFallsBackToAdminWhenModuleMissing(): void
    {
        $mf = new MainFrame($this->db, 'com_does_not_exist', '', null);
        $path = $mf->getPath();
        $this->assertStringContainsString('com_admin', $path);
    }

    public function testTimerStartStopProducesNonNegativeValue(): void
    {
        $mf = new MainFrame($this->db, 'com_admin', '', null);
        $mf->timerStart();
        usleep(1000);
        $mf->timerStop();
        $this->assertGreaterThanOrEqual(0.0, $mf->getTimer());
    }

    public function testSetGetMessages(): void
    {
        $mf = new MainFrame($this->db, 'com_admin', '', null);
        $mf->setMessages(['a', 'b']);
        $this->assertSame(['a', 'b'], $mf->getMessages());
    }

    public function testGetMsgPanelEmitsHtmlWhenMessagesPresent(): void
    {
        $mf = new MainFrame($this->db, 'com_admin', '', null);
        $mf->setMessages(['hello']);
        ob_start();
        $mf->getMsgPanel();
        $out = ob_get_clean();
        $this->assertStringContainsString('hello', $out);
        $this->assertStringContainsString('<div', $out);
    }

    public function testGetMsgPanelOutputsNothingWhenNoMessages(): void
    {
        $mf = new MainFrame($this->db, 'com_admin', '', null);
        ob_start();
        $mf->getMsgPanel();
        $out = ob_get_clean();
        $this->assertSame('', $out);
    }
}
