<?php
/**
 * SessionDAO tests — login session management.
 */

class SessionDAOTest extends TestCase
{
    public function testRemoveTimeoutedSessionDeletesEachReturnedRow(): void
    {
        $s1 = new Session(); $s1->SE_sessionid = 'aaa';
        $s2 = new Session(); $s2->SE_sessionid = 'bbb';
        $this->db->seedObjectList([$s1, $s2]);

        $removed = SessionDAO::removeTimeoutedSession(1800);
        $this->assertCount(2, $removed);

        $deletes = array_filter($this->db->recordedQueries, fn($q) => str_starts_with($q, 'DELETE'));
        $this->assertCount(2, $deletes);
    }

    public function testRemoveTimeoutedSessionWithNoRowsReturnsEmpty(): void
    {
        $this->db->seedObjectList([]);
        $this->assertSame([], SessionDAO::removeTimeoutedSession());
    }

    public function testCheckSessionThrowsWhenNull(): void
    {
        $this->expectException(Exception::class);
        SessionDAO::checkSession(null);
    }

    public function testCheckSessionMatchesSessionFields(): void
    {
        $s = new Session();
        $s->SE_sessionid = 'abc';
        $s->SE_username = 'lukas';
        $s->SE_personid = 1;
        $this->db->seedResult(1);
        SessionDAO::checkSession($s);
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString("`SE_sessionid`='abc'", $sql);
        $this->assertStringContainsString("`SE_username`='lukas'", $sql);
        $this->assertStringContainsString("`SE_personid`='1'", $sql);
    }

    public function testUpdateSessionTimeoutThrowsOnNull(): void
    {
        $this->expectException(Exception::class);
        SessionDAO::updateSessionTimeout(null);
    }

    public function testUpdateSessionTimeoutIssuesUpdate(): void
    {
        SessionDAO::updateSessionTimeout('xyz');
        $this->assertStringContainsString("UPDATE `session` SET `SE_time`=", $this->db->lastQuery());
        $this->assertStringContainsString("WHERE `SE_sessionid`='xyz'", $this->db->lastQuery());
    }

    public function testRemoveSessionByPersonID(): void
    {
        SessionDAO::removeSessionByPersonID(5);
        $this->assertStringContainsString("DELETE FROM `session` WHERE `SE_personid`='5'", $this->db->lastQuery());
    }
}
