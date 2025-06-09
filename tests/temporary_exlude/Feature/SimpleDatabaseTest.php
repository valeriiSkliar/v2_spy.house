<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

class SimpleDatabaseTest extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Test basic database connection without migrations.
     */
    public function test_database_connection(): void
    {
        // Test that we can connect to the database without running migrations
        $result = DB::select('SELECT 1 as test');

        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result[0]->test);
    }

    /**
     * Test basic query execution without migrations.
     */
    public function test_basic_query(): void
    {
        // Test basic table operations
        DB::statement('CREATE TEMPORARY TABLE test_table (id INTEGER, name TEXT)');
        DB::insert('INSERT INTO test_table (id, name) VALUES (?, ?)', [1, 'test']);

        $result = DB::select('SELECT * FROM test_table WHERE id = ?', [1]);

        $this->assertNotEmpty($result);
        $this->assertEquals('test', $result[0]->name);
    }
}
