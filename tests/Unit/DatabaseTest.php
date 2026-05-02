<?php
/**
 * Tests for Database — mysqli wrapper.
 *
 * Strategy: real Database connects to MySQL in its constructor. Tests
 * use ReflectionClass::newInstanceWithoutConstructor() and inject a
 * Mockery-mocked mysqli, then assert SQL/object behaviour.
 */

class DatabaseTest extends TestCase
{
    private function newDatabaseWithMockedMysqli(\mysqli $m): Database
    {
        $r = new \ReflectionClass(Database::class);
        $db = $r->newInstanceWithoutConstructor();
        $prop = $r->getProperty('_mysqli');
        $prop->setAccessible(true);
        $prop->setValue($db, $m);
        return $db;
    }

    public function testQuoteEscapesAndWraps(): void
    {
        $m = Mockery::mock(\mysqli::class);
        $m->shouldReceive('escape_string')->with("O'Brien")->andReturn("O\\'Brien");
        $db = $this->newDatabaseWithMockedMysqli($m);
        $this->assertSame("'O\\'Brien'", $db->quote("O'Brien"));
    }

    public function testSetQueryStoresSql(): void
    {
        $m = Mockery::mock(\mysqli::class);
        $db = $this->newDatabaseWithMockedMysqli($m);
        $db->setQuery('SELECT 1');
        $this->assertStringContainsString('SELECT 1', $db->getQuery());
    }

    public function testQueryThrowsOnFailure(): void
    {
        $m = Mockery::mock(\mysqli::class);
        $m->shouldReceive('query')->andReturn(false);
        $m->errno = 1064;
        $m->error = 'syntax error';
        $db = $this->newDatabaseWithMockedMysqli($m);
        $db->setQuery('BAD SQL');
        $this->expectException(Exception::class);
        $db->query();
    }

    public function testBindArrayToObjectCopiesMatchingPublicFields(): void
    {
        $obj = new class {
            public $id;
            public $name;
            public $_skipMe;
        };
        Database::bindArrayToObject(
            ['id' => 7, 'name' => 'X', '_skipMe' => 'no', 'extra' => 'ignored'],
            $obj
        );
        $this->assertSame(7,   $obj->id);
        $this->assertSame('X', $obj->name);
        $this->assertNull($obj->_skipMe, 'Underscore-prefixed fields are skipped');
    }

    public function testBindArrayToObjectThrowsOnInvalidInput(): void
    {
        $obj = new stdClass();
        $this->expectException(Exception::class);
        Database::bindArrayToObject('not an array', $obj);
    }

    public function testBindIsAliasForBindArrayToObject(): void
    {
        $obj = new class { public $a; };
        Database::bind(['a' => 1], $obj);
        $this->assertSame(1, $obj->a);
    }

    public function testInsertObjectBuildsExpectedSqlAndAssignsKey(): void
    {
        $m = Mockery::mock(\mysqli::class);
        $m->shouldReceive('escape_string')->andReturnUsing(fn($s) => addslashes((string) $s));
        $m->shouldReceive('query')->andReturn(true);
        $m->insert_id = 42;
        $db = $this->newDatabaseWithMockedMysqli($m);

        $obj = new class {
            public $id   = null;
            public $name = 'Foo';
            public $_internal = 'skip';
            public $arrField  = []; // skipped: arrays excluded
            public $nullField = null;
        };
        $db->insertObject('mytable', $obj, 'id');

        $r = new \ReflectionProperty(Database::class, '_sql');
        $r->setAccessible(true);
        $sql = $r->getValue($db);

        $this->assertStringContainsString('INSERT INTO `mytable`', $sql);
        $this->assertStringContainsString('`id`',   $sql);
        $this->assertStringContainsString('`name`', $sql);
        $this->assertStringContainsString('NULL',   $sql);
        $this->assertStringContainsString("'Foo'",  $sql);
        $this->assertStringNotContainsString('_internal', $sql);
        $this->assertStringNotContainsString('arrField', $sql);
        $this->assertStringNotContainsString('nullField', $sql);
        $this->assertSame(42, $obj->id);
    }

    public function testUpdateObjectBuildsExpectedSql(): void
    {
        $m = Mockery::mock(\mysqli::class);
        $m->shouldReceive('escape_string')->andReturnUsing(fn($s) => addslashes((string) $s));
        $m->shouldReceive('query')->andReturn(true);
        $db = $this->newDatabaseWithMockedMysqli($m);

        $obj = new class {
            public $id   = 10;
            public $name = 'Bar';
        };
        $db->updateObject('mytable', $obj, 'id');

        $r = new \ReflectionProperty(Database::class, '_sql');
        $r->setAccessible(true);
        $sql = $r->getValue($db);

        $this->assertStringContainsString('UPDATE `mytable` SET', $sql);
        $this->assertStringContainsString("`name`='Bar'", $sql);
        $this->assertStringContainsString("WHERE id='10'", $sql);
    }

    public function testUpdateObjectThrowsWhenNoKey(): void
    {
        $m = Mockery::mock(\mysqli::class);
        $m->shouldReceive('escape_string')->andReturnUsing(fn($s) => addslashes((string) $s));
        $db = $this->newDatabaseWithMockedMysqli($m);
        $obj = new class { public $name = 'Bar'; };
        $this->expectException(Exception::class);
        $db->updateObject('mytable', $obj, 'id');
    }

    public function testQueryBatchRollsBackOnFailure(): void
    {
        $m = Mockery::mock(\mysqli::class);
        // START TRANSACTION ok, first stmt ok, second fails, ROLLBACK ok
        $m->shouldReceive('query')->with('START TRANSACTION;')->andReturn(true);
        $m->shouldReceive('query')->with('OK;')->andReturn(true);
        $m->shouldReceive('query')->with('BAD;')->andReturn(false);
        $m->shouldReceive('query')->with('ROLLBACK;')->andReturn(true);
        $m->errno = 1; $m->error = 'fail';
        $db = $this->newDatabaseWithMockedMysqli($m);
        $this->expectException(Exception::class);
        $db->query_batch(['OK;', 'BAD;']);
    }
}
