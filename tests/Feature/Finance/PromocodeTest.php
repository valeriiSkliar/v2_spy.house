<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PromocodeStatus;
use App\Finance\Models\Promocode;
use App\Finance\Models\PromocodeActivation;
use App\Finance\Services\PromocodeService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PromocodeTest extends TestCase
{
    use RefreshDatabase;

    private PromocodeService $promocodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promocodeService = new PromocodeService;
    }

    public function test_promocode_creation_with_valid_data(): void
    {
        $creator = User::factory()->create();

        $promocodeData = [
            'promocode' => 'TEST2024',
            'discount' => 15.50,
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => now(),
            'date_end' => now()->addDays(30),
            'max_per_user' => 1,
        ];

        $promocode = $this->promocodeService->createPromocode($promocodeData, $creator->id);

        $this->assertDatabaseHas('promocodes', [
            'promocode' => 'TEST2024',
            'discount' => 15.50,
            'status' => PromocodeStatus::ACTIVE->value,
            'created_by_user_id' => $creator->id,
        ]);

        $this->assertEquals('TEST2024', $promocode->promocode);
        $this->assertEquals(15.50, $promocode->discount);
    }

    public function test_promocode_auto_generation_when_not_provided(): void
    {
        $creator = User::factory()->create();

        $promocodeData = [
            'discount' => 20.00,
            'status' => PromocodeStatus::ACTIVE,
        ];

        $promocode = $this->promocodeService->createPromocode($promocodeData, $creator->id);

        $this->assertNotEmpty($promocode->promocode);
        $this->assertEquals(6, strlen($promocode->promocode));
        $this->assertTrue(ctype_alnum($promocode->promocode));
    }

    public function test_promocode_validation_success(): void
    {
        $user = User::factory()->create();
        $promocode = Promocode::factory()->create([
            'promocode' => 'VALID20',
            'discount' => 20.00,
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => now()->subDay(),
            'date_end' => now()->addDays(30),
            'max_per_user' => 1,
        ]);

        $result = $this->promocodeService->validatePromocode('VALID20', $user->id, 100.00);

        $this->assertTrue($result['valid']);
        $this->assertEquals($promocode->id, $result['promocode_id']);
        $this->assertEquals(20.00, $result['discount_percentage']);
        $this->assertEquals(20.00, $result['discount_amount']);
        $this->assertEquals(100.00, $result['original_amount']);
        $this->assertEquals(80.00, $result['final_amount']);
    }

    public function test_promocode_validation_fails_for_nonexistent_code(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Промокод не найден');

        $this->promocodeService->validatePromocode('NONEXISTENT', $user->id, 100.00);
    }

    public function test_promocode_validation_fails_for_inactive_status(): void
    {
        $user = User::factory()->create();
        Promocode::factory()->create([
            'promocode' => 'INACTIVE',
            'status' => PromocodeStatus::INACTIVE,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Промокод неактивен');

        $this->promocodeService->validatePromocode('INACTIVE', $user->id, 100.00);
    }

    public function test_promocode_validation_fails_for_expired_code(): void
    {
        $user = User::factory()->create();
        Promocode::factory()->create([
            'promocode' => 'EXPIRED',
            'status' => PromocodeStatus::ACTIVE,
            'date_end' => now()->subDay(),
        ]);

        $this->expectException(ValidationException::class);

        $this->promocodeService->validatePromocode('EXPIRED', $user->id, 100.00);
    }

    public function test_promocode_validation_fails_when_max_per_user_exceeded(): void
    {
        $user = User::factory()->create();
        $promocode = Promocode::factory()->create([
            'promocode' => 'LIMITED',
            'status' => PromocodeStatus::ACTIVE,
            'max_per_user' => 1,
        ]);

        // Create existing activation
        PromocodeActivation::factory()->create([
            'promocode_id' => $promocode->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Вы уже использовали этот промокод максимальное количество раз');

        $this->promocodeService->validatePromocode('LIMITED', $user->id, 100.00);
    }

    public function test_promocode_application_creates_activation(): void
    {
        $user = User::factory()->create();
        $promocode = Promocode::factory()->create([
            'promocode' => 'APPLY20',
            'discount' => 20.00,
            'status' => PromocodeStatus::ACTIVE,
            'max_per_user' => 1,
            'count_activation' => 0,
        ]);

        $result = $this->promocodeService->applyPromocode(
            'APPLY20',
            $user->id,
            100.00,
            '127.0.0.1',
            'Test User Agent'
        );

        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('activation_id', $result);

        // Check activation was created
        $this->assertDatabaseHas('promocode_activations', [
            'promocode_id' => $promocode->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test User Agent',
        ]);

        // Check activation count was incremented
        $promocode->refresh();
        $this->assertEquals(1, $promocode->count_activation);
    }

    public function test_promocode_discount_calculation(): void
    {
        $promocode = Promocode::factory()->create([
            'discount' => 25.50,
        ]);

        $discountAmount = $promocode->calculateDiscountAmount(200.00);
        $finalAmount = $promocode->calculateFinalAmount(200.00);

        $this->assertEquals(51.00, $discountAmount);
        $this->assertEquals(149.00, $finalAmount);
    }

    public function test_promocode_scope_active(): void
    {
        Promocode::factory()->create(['status' => PromocodeStatus::ACTIVE]);
        Promocode::factory()->create(['status' => PromocodeStatus::INACTIVE]);
        Promocode::factory()->create(['status' => PromocodeStatus::EXPIRED]);

        $activePromocodes = Promocode::active()->get();

        $this->assertCount(1, $activePromocodes);
        $this->assertEquals(PromocodeStatus::ACTIVE, $activePromocodes->first()->status);
    }

    public function test_promocode_scope_valid(): void
    {
        // Valid promocode (active and within date range)
        Promocode::factory()->create([
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => now()->subDay(),
            'date_end' => now()->addDay(),
        ]);

        // Invalid - inactive
        Promocode::factory()->create([
            'status' => PromocodeStatus::INACTIVE,
            'date_start' => now()->subDay(),
            'date_end' => now()->addDay(),
        ]);

        // Invalid - expired
        Promocode::factory()->create([
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => now()->subDays(5),
            'date_end' => now()->subDay(),
        ]);

        $validPromocodes = Promocode::valid()->get();

        $this->assertCount(1, $validPromocodes);
    }

    public function test_user_promocode_stats(): void
    {
        $user = User::factory()->create();
        $promocode = Promocode::factory()->create(['discount' => 10.00]);

        // Create some activations
        PromocodeActivation::factory()->count(3)->create([
            'user_id' => $user->id,
            'promocode_id' => $promocode->id,
        ]);

        $stats = $this->promocodeService->getUserPromocodeStats($user->id);

        $this->assertEquals(3, $stats['total_activations']);
        $this->assertArrayHasKey('total_saved', $stats);
        $this->assertArrayHasKey('activations', $stats);
        $this->assertCount(3, $stats['activations']);
    }

    public function test_abuse_detection(): void
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.100';

        // Create many recent activations from same IP
        PromocodeActivation::factory()->count(15)->create([
            'ip_address' => $ipAddress,
            'created_at' => now()->subHours(12),
        ]);

        $isAbuse = $this->promocodeService->checkForAbuse($ipAddress, $user->id);

        $this->assertTrue($isAbuse);
    }

    public function test_promocode_find_by_code(): void
    {
        $promocode = Promocode::factory()->create(['promocode' => 'FINDME']);

        $found = Promocode::findByCode('FINDME');
        $notFound = Promocode::findByCode('NOTEXIST');

        $this->assertNotNull($found);
        $this->assertEquals('FINDME', $found->promocode);
        $this->assertNull($notFound);
    }

    public function test_promocode_unique_code_generation(): void
    {
        $code1 = Promocode::generateUniqueCode();
        $code2 = Promocode::generateUniqueCode();

        $this->assertNotEquals($code1, $code2);
        $this->assertEquals(6, strlen($code1));
        $this->assertEquals(6, strlen($code2));
        $this->assertTrue(ctype_alnum($code1));
        $this->assertTrue(ctype_alnum($code2));
    }
}
