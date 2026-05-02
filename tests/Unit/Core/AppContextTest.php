<?php
/**
 * AppContext tests — flash-message + per-request param container.
 */

class AppContextTest extends TestCase
{
    public function testDefaultOptionIsAdmin(): void
    {
        $ctx = new AppContext();
        $this->assertSame('com_admin', $ctx->getOption());
    }

    public function testSetGetOption(): void
    {
        $ctx = new AppContext();
        $ctx->setOption('com_person');
        $this->assertSame('com_person', $ctx->getOption());
    }

    public function testSetGetParamAndCleanParams(): void
    {
        $ctx = new AppContext();
        $ctx->setParam('id', 7);
        $ctx->setParam('name', 'foo');
        $this->assertSame(7,    $ctx->getParam('id'));
        $this->assertSame('foo', $ctx->getParam('name'));

        $ctx->cleanParams();
        $this->assertNull(@ $ctx->getParam('id'));
    }

    public function testInsertMessageAndCleanMessages(): void
    {
        $ctx = new AppContext();
        $ctx->insertMessage('hello');
        $ctx->insertMessage('world');
        $this->assertSame(['hello', 'world'], $ctx->getMessages());

        $ctx->cleanMessages();
        $this->assertSame([], $ctx->getMessages());
    }

    public function testInsertMessagesMergesArrays(): void
    {
        $ctx = new AppContext();
        $ctx->insertMessage('one');
        $ctx->insertMessages(['two', 'three']);
        $this->assertSame(['one', 'two', 'three'], $ctx->getMessages());
    }
}
