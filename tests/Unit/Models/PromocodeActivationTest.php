<?php

namespace Tests\Unit\Models;

use App\Finance\Models\Payment;
use App\Finance\Models\Promocode;
use App\Finance\Models\PromocodeActivation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromocodeActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_promocode_activation_can_be_created_with_factory(): void
    {
        $activation = PromocodeActivation::factory()->create();

        $this->assertInstanceOf(PromocodeActivation::class, $activation);
        $this->assertDatabaseHas('promocode_activations', [
            'id' => $activation->id,
            'promocode_id' => $activation->promocode_id,
            'user_id' => $activation->user_id,
        ]);
    }

    public function test_promocode_activation_belongs_to_promocode(): void
    {
        $promocode = Promocode::factory()->create();
        $activation = PromocodeActivation::factory()->create([
            'promocode_id' => $promocode->id,
        ]);

        $this->assertInstanceOf(Promocode::class, $activation->promocode);
        $this->assertEquals($promocode->id, $activation->promocode->id);
    }

    public function test_promocode_activation_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $activation = PromocodeActivation::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $activation->user);
        $this->assertEquals($user->id, $activation->user->id);
    }

    public function test_promocode_activation_belongs_to_payment(): void
    {
        $payment = Payment::factory()->create();
        $activation = PromocodeActivation::factory()->create([
            'payment_id' => $payment->id,
        ]);

        $this->assertInstanceOf(Payment::class, $activation->payment);
        $this->assertEquals($payment->id, $activation->payment->id);
    }

    public function test_promocode_activation_can_exist_without_payment(): void
    {
        $activation = PromocodeActivation::factory()->create([
            'payment_id' => null,
        ]);

        $this->assertNull($activation->payment_id);
        $this->assertNull($activation->payment);
    }

    public function test_scope_by_user_filters_activations_correctly(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PromocodeActivation::factory()->count(3)->create(['user_id' => $user1->id]);
        PromocodeActivation::factory()->count(2)->create(['user_id' => $user2->id]);

        $user1Activations = PromocodeActivation::byUser($user1->id)->get();
        $user2Activations = PromocodeActivation::byUser($user2->id)->get();

        $this->assertCount(3, $user1Activations);
        $this->assertCount(2, $user2Activations);

        // Проверяем что все активации принадлежат правильному пользователю
        $user1Activations->each(function ($activation) use ($user1) {
            $this->assertEquals($user1->id, $activation->user_id);
        });
    }

    public function test_scope_by_promocode_filters_activations_correctly(): void
    {
        $promocode1 = Promocode::factory()->create();
        $promocode2 = Promocode::factory()->create();

        PromocodeActivation::factory()->count(4)->create(['promocode_id' => $promocode1->id]);
        PromocodeActivation::factory()->count(1)->create(['promocode_id' => $promocode2->id]);

        $promocode1Activations = PromocodeActivation::byPromocode($promocode1->id)->get();
        $promocode2Activations = PromocodeActivation::byPromocode($promocode2->id)->get();

        $this->assertCount(4, $promocode1Activations);
        $this->assertCount(1, $promocode2Activations);
    }

    public function test_scope_with_payments_filters_only_paid_activations(): void
    {
        $payment = Payment::factory()->create();

        // Активации с платежами
        PromocodeActivation::factory()->count(2)->create(['payment_id' => $payment->id]);

        // Активации без платежей
        PromocodeActivation::factory()->count(3)->create(['payment_id' => null]);

        $paidActivations = PromocodeActivation::withPayments()->get();

        $this->assertCount(2, $paidActivations);
        $paidActivations->each(function ($activation) {
            $this->assertNotNull($activation->payment_id);
        });
    }

    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $promocode = Promocode::factory()->create();
        $user = User::factory()->create();
        $payment = Payment::factory()->create();

        $attributes = [
            'promocode_id' => $promocode->id,
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Browser',
        ];

        $activation = new PromocodeActivation($attributes);

        $this->assertEquals($promocode->id, $activation->promocode_id);
        $this->assertEquals($user->id, $activation->user_id);
        $this->assertEquals($payment->id, $activation->payment_id);
        $this->assertEquals('192.168.1.100', $activation->ip_address);
        $this->assertEquals('Mozilla/5.0 Test Browser', $activation->user_agent);
    }

    public function test_unique_constraint_prevents_duplicate_activations(): void
    {
        $promocode = Promocode::factory()->create();
        $user = User::factory()->create();
        $payment = Payment::factory()->create();

        // Создаем первую активацию
        PromocodeActivation::factory()->create([
            'promocode_id' => $promocode->id,
            'user_id' => $user->id,
            'payment_id' => $payment->id,
        ]);

        // Попытка создать дублирующую активацию должна вызвать ошибку
        $this->expectException(\Illuminate\Database\QueryException::class);

        PromocodeActivation::factory()->create([
            'promocode_id' => $promocode->id,
            'user_id' => $user->id,
            'payment_id' => $payment->id,
        ]);
    }

    public function test_activation_can_be_created_with_same_promocode_user_but_different_payment(): void
    {
        $promocode = Promocode::factory()->create();
        $user = User::factory()->create();
        $payment1 = Payment::factory()->create();
        $payment2 = Payment::factory()->create();

        // Создаем активации с одинаковым промокодом и пользователем, но разными платежами
        $activation1 = PromocodeActivation::factory()->create([
            'promocode_id' => $promocode->id,
            'user_id' => $user->id,
            'payment_id' => $payment1->id,
        ]);

        $activation2 = PromocodeActivation::factory()->create([
            'promocode_id' => $promocode->id,
            'user_id' => $user->id,
            'payment_id' => $payment2->id,
        ]);

        $this->assertNotEquals($activation1->id, $activation2->id);
        $this->assertEquals($payment1->id, $activation1->payment_id);
        $this->assertEquals($payment2->id, $activation2->payment_id);
    }

    public function test_activation_handles_ipv4_addresses(): void
    {
        $activation = PromocodeActivation::factory()->create([
            'ip_address' => '192.168.1.100',
        ]);

        $this->assertEquals('192.168.1.100', $activation->ip_address);
    }

    public function test_activation_handles_ipv6_addresses(): void
    {
        $activation = PromocodeActivation::factory()->create([
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
        ]);

        $this->assertEquals('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $activation->ip_address);
    }

    public function test_activation_handles_long_user_agent(): void
    {
        $longUserAgent = str_repeat('Mozilla/5.0 (Very Long User Agent) ', 50);

        $activation = PromocodeActivation::factory()->create([
            'user_agent' => $longUserAgent,
        ]);

        $this->assertEquals($longUserAgent, $activation->user_agent);
    }

    public function test_activation_cascades_delete_when_promocode_deleted(): void
    {
        $promocode = Promocode::factory()->create();
        $activation = PromocodeActivation::factory()->create([
            'promocode_id' => $promocode->id,
        ]);

        $activationId = $activation->id;

        // Удаляем промокод
        $promocode->delete();

        // Активация должна быть удалена каскадно
        $this->assertDatabaseMissing('promocode_activations', ['id' => $activationId]);
    }

    public function test_activation_cascades_delete_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $activation = PromocodeActivation::factory()->create([
            'user_id' => $user->id,
        ]);

        $activationId = $activation->id;

        // Удаляем пользователя
        $user->delete();

        // Активация должна быть удалена каскадно
        $this->assertDatabaseMissing('promocode_activations', ['id' => $activationId]);
    }

    public function test_activation_sets_payment_id_to_null_when_payment_deleted(): void
    {
        $payment = Payment::factory()->create();
        $activation = PromocodeActivation::factory()->create([
            'payment_id' => $payment->id,
        ]);

        // Удаляем платеж
        $payment->delete();

        // Обновляем модель из БД
        $activation->refresh();

        // payment_id должен быть установлен в null
        $this->assertNull($activation->payment_id);
    }

    public function test_activation_creation_timestamps(): void
    {
        $activation = PromocodeActivation::factory()->create();

        $this->assertNotNull($activation->created_at);
        $this->assertNotNull($activation->updated_at);

        // Проверяем что timestamps находятся в разумных пределах (в течение последней минуты)
        $this->assertTrue(
            $activation->created_at->diffInSeconds(now()) < 60,
            'Created at timestamp should be recent'
        );
        $this->assertTrue(
            $activation->updated_at->diffInSeconds(now()) < 60,
            'Updated at timestamp should be recent'
        );

        // Проверяем что created_at и updated_at одинаковы при создании
        $this->assertEquals(
            $activation->created_at->toDateTimeString(),
            $activation->updated_at->toDateTimeString()
        );
    }

    public function test_multiple_activations_for_same_user_different_promocodes(): void
    {
        $user = User::factory()->create();
        $promocode1 = Promocode::factory()->create();
        $promocode2 = Promocode::factory()->create();

        $activation1 = PromocodeActivation::factory()->create([
            'user_id' => $user->id,
            'promocode_id' => $promocode1->id,
        ]);

        $activation2 = PromocodeActivation::factory()->create([
            'user_id' => $user->id,
            'promocode_id' => $promocode2->id,
        ]);

        $userActivations = PromocodeActivation::byUser($user->id)->get();

        $this->assertCount(2, $userActivations);
        $this->assertTrue($userActivations->contains('id', $activation1->id));
        $this->assertTrue($userActivations->contains('id', $activation2->id));
    }

    public function test_scope_chaining_works_correctly(): void
    {
        $user = User::factory()->create();
        $promocode = Promocode::factory()->create();
        $payment = Payment::factory()->create();

        // Создаем активацию с платежом
        PromocodeActivation::factory()->create([
            'user_id' => $user->id,
            'promocode_id' => $promocode->id,
            'payment_id' => $payment->id,
        ]);

        // Создаем активацию без платежа
        PromocodeActivation::factory()->create([
            'user_id' => $user->id,
            'promocode_id' => $promocode->id,
            'payment_id' => null,
        ]);

        // Комбинируем scope'ы
        $userPaidActivations = PromocodeActivation::byUser($user->id)
            ->withPayments()
            ->get();

        $this->assertCount(1, $userPaidActivations);
        $this->assertNotNull($userPaidActivations->first()->payment_id);
    }

    public function test_activation_handles_edge_case_ip_addresses(): void
    {
        $edgeCaseIPs = [
            '0.0.0.0',
            '127.0.0.1',
            '255.255.255.255',
            '::1',
            '::',
        ];

        foreach ($edgeCaseIPs as $ip) {
            $activation = PromocodeActivation::factory()->create([
                'ip_address' => $ip,
            ]);

            $this->assertEquals($ip, $activation->ip_address);
        }
    }

    public function test_activation_performance_with_large_dataset(): void
    {
        $promocode = Promocode::factory()->create();

        // Создаем много активаций с уникальными пользователями для избежания constraint violations
        $activations = collect();
        for ($i = 0; $i < 100; $i++) { // Уменьшаем количество для стабильности теста
            $user = User::factory()->create([
                'messenger_contact' => '@test_user_'.$i, // Обеспечиваем уникальность
            ]);
            $activations->push(PromocodeActivation::factory()->create([
                'promocode_id' => $promocode->id,
                'user_id' => $user->id,
            ]));
        }

        $startTime = microtime(true);

        // Выполняем запрос с scope
        $promocodeActivations = PromocodeActivation::byPromocode($promocode->id)->get();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // в миллисекундах

        $this->assertCount(100, $promocodeActivations);
        $this->assertLessThan(500, $executionTime, 'Запрос должен выполняться менее чем за 500ms');
    }
}
