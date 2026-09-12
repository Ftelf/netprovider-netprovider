<?php
/**
 * Tests for Database — mysqli wrapper.
 *
 * Strategy: real Database connects to MySQL in its constructor. Tests
 * use ReflectionClass::newInstanceWithoutConstructor() and inject a
 * lightweight FakeMysqli double, then assert SQL/object behaviour.
 *
 * A hand-rolled fake is used rather than a Mockery mock of \mysqli:
 * mysqli exposes errno/error/insert_id as native (read-only to userland
 * writes) properties, so a mock can't set them to drive error paths.
 */

class DatabaseTest extends TestCase
{
    private function newDatabaseWithMockedMysqli(object $m): Database
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
        $db = $this->newDatabaseWithMockedMysqli(new FakeMysqli());
        $this->assertSame("'O\\'Brien'", $db->quote("O'Brien"));
    }

    public function testSetQueryStoresSql(): void
    {
        $db = $this->newDatabaseWithMockedMysqli(new FakeMysqli());
        $db->setQuery('SELECT 1');
        $this->assertStringContainsString('SELECT 1', $db->getQuery());
    }

    public function testQueryThrowsOnFailure(): void
    {
        $m = new FakeMysqli(false);
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
        $m = new FakeMysqli(true);
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
        $this->assertStringContainsString('`name`', $sql);
        $this->assertStringContainsString("'Foo'",  $sql);
        // Null-valued fields (incl. the null PK `id`) are skipped entirely,
        // so the DB auto-assigns the key — hence no `id` column and no NULL.
        $this->assertStringNotContainsString('`id`', $sql);
        $this->assertStringNotContainsString('NULL', $sql);
        $this->assertStringNotContainsString('_internal', $sql);
        $this->assertStringNotContainsString('arrField', $sql);
        $this->assertStringNotContainsString('nullField', $sql);
        // insert_id is written back onto the key field afterwards.
        $this->assertSame(42, $obj->id);
    }

    public function testUpdateObjectBuildsExpectedSql(): void
    {
        $db = $this->newDatabaseWithMockedMysqli(new FakeMysqli(true));

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
        $db = $this->newDatabaseWithMockedMysqli(new FakeMysqli(true));
        $obj = new class { public $name = 'Bar'; };
        $this->expectException(Exception::class);
        $db->updateObject('mytable', $obj, 'id');
    }

    public function testQueryBatchRollsBackOnFailure(): void
    {
        // START TRANSACTION ok, first stmt ok, second fails, ROLLBACK ok
        $m = new FakeMysqli([
            'START TRANSACTION;' => true,
            'OK;'                => true,
            'BAD;'               => false,
            'ROLLBACK;'          => true,
        ]);
        $m->errno = 1;
        $m->error = 'fail';
        $db = $this->newDatabaseWithMockedMysqli($m);
        $this->expectException(Exception::class);
        $db->query_batch(['OK;', 'BAD;']);
    }
}

/**
 * Minimal stand-in for \mysqli usable by Database via reflection injection.
 * Provides writable error/insert_id fields (which native mysqli forbids)
 * and a query() whose result is either a fixed bool or a per-SQL map.
 */
class FakeMysqli
{
    public int $errno = 0;
    public string $error = '';
    public int $insert_id = 0;

    /** @var array<int,string> SQL passed to query(), in order. */
    public array $queries = [];

    /** @var bool|array<string,bool> */
    private $queryReturn;

    /** @param bool|array<string,bool> $queryReturn */
    public function __construct($queryReturn = true)
    {
        $this->queryReturn = $queryReturn;
    }

    public function escape_string($text): string
    {
        return addslashes((string) $text);
    }

    public function query($sql)
    {
        $this->queries[] = $sql;
        if (is_array($this->queryReturn)) {
            return $this->queryReturn[$sql] ?? false;
        }
        return $this->queryReturn;
    }
}
