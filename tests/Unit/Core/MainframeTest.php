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

    public function testTimerStartStopMeasuresElapsedSeconds(): void
    {
        $mf = new MainFrame($this->db, 'com_admin', '', null);
        $mf->timerStart();
        usleep(2000); // 2 ms
        $mf->timerStop();
        $elapsed = $mf->getTimer();
        // >= 0 is near-tautological (time never runs backwards). Assert the real
        // contract instead: a strictly-positive float, in seconds. The < 1.0
        // upper bound guards a units bug (e.g. returning microseconds).
        $this->assertIsFloat($elapsed);
        $this->assertGreaterThan(0.0, $elapsed);
        $this->assertLessThan(1.0, $elapsed);
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
