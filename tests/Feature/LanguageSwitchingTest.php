<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class LanguageSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_switch_language()
    {
        // Создаем пользователя с русской локалью
        $user = User::factory()->create([
            'preferred_locale' => 'ru'
        ]);

        // Авторизуемся
        $this->actingAs($user);

        // Переключаем язык на английский
        $response = $this->get('/language/en');

        // Проверяем редирект
        $response->assertRedirect();

        // Обновляем пользователя из базы
        $user->refresh();

        // Проверяем, что локаль обновилась в профиле
        $this->assertEquals('en', $user->preferred_locale);

        // Проверяем, что локаль установлена в сессии
        $this->assertEquals('en', Session::get('locale'));
    }

    public function test_guest_user_can_switch_language()
    {
        // Переключаем язык для неавторизованного пользователя
        $response = $this->get('/language/ru');

        // Проверяем редирект
        $response->assertRedirect();

        // Проверяем, что локаль установлена в сессии
        $this->assertEquals('ru', Session::get('locale'));
    }

    public function test_invalid_locale_returns_error()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Пытаемся установить несуществующий язык
        $response = $this->get('/language/invalid');

        // Проверяем, что есть сообщение об ошибке
        $response->assertSessionHas('error');
    }
}
