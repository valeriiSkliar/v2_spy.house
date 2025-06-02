<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NewsletterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class UnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $unsubscribeHash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unsubscribeHash = Str::random(32);

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'is_newsletter_subscribed' => true,
            'email_contact_id' => 'resend_contact_123',
            'unsubscribe_hash' => $this->unsubscribeHash,
        ]);
    }

    /** @test */
    public function it_shows_unsubscribe_page_with_valid_hash()
    {
        $response = $this->get(route('unsubscribe.show', $this->unsubscribeHash));

        $response->assertStatus(200);
        $response->assertViewIs('unsubscribe.show');
        $response->assertViewHas('user', $this->user);
        $response->assertViewHas('isValidHash', true);
    }

    /** @test */
    public function it_shows_invalid_hash_message_for_wrong_hash()
    {
        $response = $this->get(route('unsubscribe.show', 'invalid_hash'));

        $response->assertStatus(200);
        $response->assertViewIs('unsubscribe.show');
        $response->assertViewHas('isValidHash', false);
    }

    /** @test */
    public function it_shows_invalid_hash_for_already_unsubscribed_user()
    {
        $this->user->update(['is_newsletter_subscribed' => false]);

        $response = $this->get(route('unsubscribe.show', $this->unsubscribeHash));

        $response->assertStatus(200);
        $response->assertViewHas('isValidHash', false);
    }

    /** @test */
    public function it_successfully_unsubscribes_user()
    {
        // Мокаем NewsletterService
        $mockService = Mockery::mock(NewsletterService::class);
        $mockService->shouldReceive('unsubscribeUser')
            ->once()
            ->with(Mockery::type(User::class))
            ->andReturn(['success' => true]);

        $this->app->instance(NewsletterService::class, $mockService);

        $response = $this->postJson(route('unsubscribe.process', $this->unsubscribeHash));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Вы успешно отписались от рассылки'
        ]);
    }

    /** @test */
    public function it_returns_error_for_invalid_hash_on_unsubscribe()
    {
        $response = $this->postJson(route('unsubscribe.process', 'invalid_hash'));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Неверная ссылка для отписки или пользователь уже отписан'
        ]);
    }

    /** @test */
    public function it_handles_service_error_gracefully()
    {
        // Мокаем NewsletterService с ошибкой
        $mockService = Mockery::mock(NewsletterService::class);
        $mockService->shouldReceive('unsubscribeUser')
            ->once()
            ->with(Mockery::type(User::class))
            ->andReturn([
                'success' => false,
                'error' => 'Resend API error'
            ]);

        $this->app->instance(NewsletterService::class, $mockService);

        $response = $this->postJson(route('unsubscribe.process', $this->unsubscribeHash));

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Произошла ошибка при отписке. Попробуйте позже.'
        ]);
    }

    /** @test */
    public function it_shows_success_page()
    {
        $response = $this->get(route('unsubscribe.success'));

        $response->assertStatus(200);
        $response->assertViewIs('unsubscribe.success');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
