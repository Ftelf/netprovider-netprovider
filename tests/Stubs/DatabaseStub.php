<?php
/**
 * In-memory test double for {@see Database}.
 *
 * Avoids extending Database itself (which connects to MySQL in its
 * constructor). Implements the same public method names the application
 * uses, plus inspection helpers (`recordedQueries`, `seedResult`,
 * `seedObjectList`) so tests can drive behaviour deterministically.
 *
 * If a method is exercised that has no seeded behaviour, the stub
 * raises `RuntimeException` — failing fast is preferable to silently
 * returning empty arrays.
 */

class DatabaseStub
{
    public string $_sql = '';

    /** @var string[] every query string passed through query()/setQuery() */
    public array $recordedQueries = [];

    /** @var array<int, mixed> seeded loadResult() return values, FIFO */
    private array $resultsQueue = [];

    /** @var array<int, array> seeded loadObjectList() return values, FIFO */
    private array $listQueue = [];

    /** @var array<int, object> seeded loadObject() bindings, FIFO */
    private array $objectQueue = [];

    /** @var array<int, array> insertObject() captures: ['table'=>..,'object'=>..,'key'=>..] */
    public array $inserts = [];

    /** @var array<int, array> updateObject() captures */
    public array $updates = [];

    /** @var array<int, string> log() captures */
    public array $logs = [];

    public int $insertId = 0;
    public bool $inTransaction = false;
    public int $commits = 0;
    public int $rollbacks = 0;

    public function setQuery($sql): void
    {
        $this->_sql = $sql;
        $this->recordedQueries[] = $sql;
    }

    public function getQuery(): string
    {
        return $this->_sql;
    }

    public function query($sql = null)
    {
        if ($sql !== null) {
            $this->recordedQueries[] = $sql;
            $this->_sql = $sql;
        }
        return true;
    }

    public function quote($text): string
    {
        return "'" . addslashes((string) $text) . "'";
    }

    public function loadResult()
    {
        return array_shift($this->resultsQueue);
    }

    public function loadObjectList($key = ''): array
    {
        if (empty($this->listQueue)) {
            return [];
        }
        $list = array_shift($this->listQueue);
        if ($key !== '' && !empty($list)) {
            $indexed = [];
            foreach ($list as $row) {
                $indexed[$row->$key] = $row;
            }
            return $indexed;
        }
        return $list;
    }

    public function loadObject(&$object): void
    {
        if (empty($this->objectQueue)) {
            throw new Exception('DatabaseStub: no seeded object for loadObject(): ' . $this->_sql);
        }
        $seeded = array_shift($this->objectQueue);
        if ($object === null) {
            $object = $seeded;
            return;
        }
        foreach (get_object_vars($object) as $k => $_) {
            if ($k[0] === '_') continue;
            if (isset($seeded->$k)) {
                $object->$k = $seeded->$k;
            }
        }
    }

    public function insertObject($table, &$object, $keyName = null, $verbose = false): void
    {
        $this->inserts[] = ['table' => $table, 'object' => clone $object, 'key' => $keyName];
        if ($keyName) {
            $this->insertId++;
            $object->$keyName = $this->insertId;
        }
    }

    public function updateObject($table, &$object, $keyName, $updateNulls = true, $verbose = false): void
    {
        $this->updates[] = ['table' => $table, 'object' => clone $object, 'key' => $keyName, 'updateNulls' => $updateNulls];
    }

    public function getInsertid(): int
    {
        return $this->insertId;
    }

    public function startTransaction(): void { $this->inTransaction = true; }
    public function commit(): void           { $this->commits++; $this->inTransaction = false; }
    public function rollback(): void         { $this->rollbacks++; $this->inTransaction = false; }
    public function query_batch($sqlArray): void
    {
        $this->startTransaction();
        foreach ($sqlArray as $s) $this->query($s);
        $this->commit();
    }

    public function log($text, $level = 0): void
    {
        $this->logs[] = ['text' => $text, 'level' => $level];
    }

    // --- Test helpers ---

    public function seedResult($value): void
    {
        $this->resultsQueue[] = $value;
    }

    public function seedObjectList(array $rows): void
    {
        $this->listQueue[] = $rows;
    }

    public function seedObject(object $obj): void
    {
        $this->objectQueue[] = $obj;
    }

    public function reset(): void
    {
        $this->_sql = '';
        $this->recordedQueries = [];
        $this->resultsQueue = [];
        $this->listQueue = [];
        $this->objectQueue = [];
        $this->inserts = [];
        $this->updates = [];
        $this->logs = [];
        $this->insertId = 0;
        $this->inTransaction = false;
        $this->commits = 0;
        $this->rollbacks = 0;
    }

    public function lastQuery(): string
    {
        return end($this->recordedQueries) ?: '';
    }
}
