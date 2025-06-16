<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseTestingExampleTest extends TestCase
{
    use RefreshDatabase;

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
     * Test that database is properly reset between tests.
     */
    public function test_database_is_reset_between_tests(): void
    {
        // Assert that users table is empty initially
        $this->assertDatabaseCount('users', 0);

        // Create a user
        User::factory()->create(['email' => 'test@example.com']);

        // Assert user was created
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    /**
     * Test that we can run a specific seeder for test data.
     */
    public function test_can_seed_testing_data(): void
    {
        // Run the testing seeder
        $this->seed(TestingSeeder::class);

        // Assert test users were created
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);

        // Check user count
        $this->assertDatabaseCount('users', 2);
    }

    /**
     * Test that factories work correctly with SQLite.
     */
    public function test_factories_work_with_sqlite(): void
    {
        // Create multiple users using factory
        $users = User::factory()->count(3)->create();

        // Assert users were created
        $this->assertDatabaseCount('users', 3);

        // Test that each user has required fields
        foreach ($users as $user) {
            $this->assertNotNull($user->name);
            $this->assertNotNull($user->email);
            $this->assertNotNull($user->password);
        }
    }

    /**
     * Test database relationships work correctly.
     */
    public function test_database_relationships_work(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertIsString($user->name);
        $this->assertIsString($user->email);
    }

    /**
     * Test database transactions are properly handled.
     */
    public function test_database_transactions(): void
    {
        // Start with empty database
        $this->assertDatabaseCount('users', 0);

        try {
            DB::transaction(function () {
                User::factory()->create(['email' => 'first@example.com']);
                User::factory()->create(['email' => 'second@example.com']);

                // This should rollback the transaction
                throw new \Exception('Test rollback');
            });
        } catch (\Exception $e) {
            // Transaction should have been rolled back
        }

        // Assert database is still empty after rollback
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Test that we can use model assertions.
     */
    public function test_model_assertions(): void
    {
        $user = User::factory()->create(['email' => 'model@example.com']);

        // Assert model exists in database
        $this->assertModelExists($user);

        // Delete the user
        $user->delete();

        // Assert model no longer exists
        $this->assertModelMissing($user);
    }
}
