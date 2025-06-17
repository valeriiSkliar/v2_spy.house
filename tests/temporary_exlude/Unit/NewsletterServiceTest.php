<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\NewsletterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Resend\Laravel\Facades\Resend;
use Tests\TestCase;

class NewsletterServiceTest extends TestCase
{
    use RefreshDatabase;

    private NewsletterService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new NewsletterService;

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'is_newsletter_subscribed' => true,
            'email_contact_id' => 'resend_contact_123',
            'unsubscribe_hash' => 'test_hash_123',
        ]);
    }

    /** @test */
    public function it_successfully_unsubscribes_user()
    {
        // Мокаем Resend::contacts()->remove()
        Resend::shouldReceive('contacts->remove')
            ->once()
            ->with(config('services.resend.audience_id'), $this->user->email_contact_id)
            ->andReturn(['success' => true]);

        Log::shouldReceive('info')->times(2); // Для двух лог записей

        $result = $this->service->unsubscribeUser($this->user);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('steps', $result);

        // Проверяем, что пользователь обновился в БД
        $this->user->refresh();
        $this->assertFalse($this->user->is_newsletter_subscribed);
        $this->assertNull($this->user->email_contact_id);
    }

    /** @test */
    public function it_handles_resend_api_error_but_updates_database()
    {
        // Мокаем ошибку Resend API
        Resend::shouldReceive('contacts->remove')
            ->once()
            ->andThrow(new \Exception('Resend API error'));

        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->once();
        Log::shouldReceive('warning')->once(); // Для partial success

        $result = $this->service->unsubscribeUser($this->user);

        $this->assertTrue($result['success']); // Partial success
        $this->assertTrue($result['partial']);
        $this->assertArrayHasKey('errors', $result);

        // Проверяем, что пользователь всё равно обновился в БД
        $this->user->refresh();
        $this->assertFalse($this->user->is_newsletter_subscribed);
        $this->assertNull($this->user->email_contact_id);
    }

    /** @test */
    public function it_handles_database_error()
    {
        // Мокаем успешный Resend
        Resend::shouldReceive('contacts->remove')
            ->once()
            ->andReturn(['success' => true]);

        // Мокаем ошибку БД
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        // Переопределяем update метод чтобы кинуть исключение
        $user = Mockery::mock($this->user)->makePartial();
        $user->shouldReceive('update')->andThrow(new \Exception('Database error'));

        Log::shouldReceive('info')->once(); // Для Resend success
        Log::shouldReceive('error')->times(2); // Для database error и общей ошибки

        $result = $this->service->unsubscribeUser($user);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_successfully_subscribes_user()
    {
        $unsubscribedUser = User::factory()->create([
            'email' => 'new@example.com',
            'is_newsletter_subscribed' => false,
            'email_contact_id' => null,
        ]);

        // Мокаем Resend::contacts()->create()
        Resend::shouldReceive('contacts->create')
            ->once()
            ->andReturn(['id' => 'new_contact_456']);

        Log::shouldReceive('info')->once();

        $result = $this->service->subscribeUser($unsubscribedUser);

        $this->assertTrue($result['success']);
        $this->assertEquals('new_contact_456', $result['contact_id']);

        // Проверяем обновление в БД
        $unsubscribedUser->refresh();
        $this->assertTrue($unsubscribedUser->is_newsletter_subscribed);
        $this->assertEquals('new_contact_456', $unsubscribedUser->email_contact_id);
    }

    /** @test */
    public function it_skips_subscription_for_already_subscribed_user()
    {
        $result = $this->service->subscribeUser($this->user);

        $this->assertTrue($result['success']);
        $this->assertEquals('User already subscribed', $result['message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
