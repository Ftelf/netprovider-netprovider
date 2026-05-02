<?php
/**
 * Base TestCase: wires Mockery teardown and makes a fresh
 * DatabaseStub available to every test as `$this->db`,
 * pointing the global $database at it.
 */

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected DatabaseStub $db;

    protected function setUp(): void
    {
        parent::setUp();
        global $database, $core;
        $this->db = new DatabaseStub();
        $database = $this->db;
        if (!isset($core)) {
            $core = new CoreStub(NP_PROJECT_ROOT);
        } elseif ($core instanceof CoreStub) {
            // Reset properties to defaults so tests don't leak config tweaks.
            $core->setProperties(CoreStub::defaults());
        }
    }

    protected function tearDown(): void
    {
        if (class_exists(\Mockery::class)) {
            \Mockery::close();
        }
        parent::tearDown();
    }
}
