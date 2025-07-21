<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Обработка отправки контактной формы
     */
    public function send(ContactFormRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            // Логируем получение контактной формы
            Log::info('Contact form submitted', [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Отправляем email администратору
            $adminEmail = config('mail.support_email');
            // $adminEmail = 'valeriisklyarov@gmail.com';


            Mail::html(
                view('emails.contact-form', $validatedData)->render(),
                function ($message) use ($validatedData, $adminEmail) {
                    $message->to($adminEmail)
                        ->subject('Новое сообщение с сайта от ' . $validatedData['name'])
                        ->replyTo($validatedData['email'], $validatedData['name']);
                }
            );

            return response()->json([
                'success' => true,
                'message' => trans('frontend.contact.success_message')
            ]);
        } catch (\Exception $e) {
            Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['g-recaptcha-response'])
            ]);

            return response()->json([
                'success' => false,
                'message' => trans('frontend.contact.error_message')
            ], 500);
        }
    }

    /**
     * Показать модальное окно контактов (если нужно)
     */
    public function show()
    {
        return view('modals.contact', [
            'managers' => [
                [
                    'name' => trans('main_page.modals.contact.manager_maksim'),
                    'telegram' => '@Max_spy_house',
                    'photo' => '/img/manager-1.png'
                ],
                [
                    'name' => trans('main_page.modals.contact.telegram_chat'),
                    'telegram' => '@spy_house_chat',
                    'photo' => '/img/manager-2.svg'
                ]
            ]
        ]);
    }
}
