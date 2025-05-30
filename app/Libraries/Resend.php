<?php

namespace App\Libraries;

use function App\Helpers\validation_json;
use Illuminate\Support\Facades\Log;

class Resend
{
    private $api_key;
    private $audience_id;


    public function __construct()
    {
        $this->api_key      = config('resend.api_key');
        $this->audience_id  = config('resend.audience_id');
    }


    /**
     * Send email via Resend API
     * 
     * @param array $request_body
     * @return array
     */
    public function send_email($request_body = [])
    {
        try {
            // Валидация обязательных полей
            if (empty($request_body['email']) || empty($request_body['subject']) || empty($request_body['html'])) {
                return [
                    'status' => 'error',
                    'msg' => 'Missing required fields: email, subject, html'
                ];
            }

            // Формируем данные для отправки
            $data = [
                'from'      => config('resend.from'),
                'to'        => [$request_body['email']],
                'subject'   => trim($request_body['subject']),
                'html'      => $request_body['html'],
                'bcc'       => $request_body['bcc'] ?? null,
                'cc'        => $request_body['cc'] ?? null,
                'reply_to'  => $request_body['reply_to'] ?? null,
                'headers'   => $request_body['headers'] ?? [],
                'tags'      => $request_body['tags'] ?? []
            ];

            // Добавляем idempotency key если передан
            $headers = [
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json'
            ];

            if (!empty($request_body['idempotency_key'])) {
                $headers[] = 'Idempotency-Key: ' . $request_body['idempotency_key'];
            }

            // Отправляем запрос
            $ch = curl_init('https://api.resend.com/emails');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            $body = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            // Обработка ответа
            if (in_array($info['http_code'], [200, 201])) {
                if (validation_json($body)) {
                    $decoded_body = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
                    if (!empty($decoded_body['id'])) {
                        return [
                            'status' => 'success',
                            'id' => $decoded_body['id'],
                            'msg' => 'Email sent successfully'
                        ];
                    }
                }
            }

            // Детальное логирование ошибки
            $error_details = [
                'request' => $data,
                'response' => $body,
                'http_code' => $info['http_code']
            ];

            if (validation_json($body)) {
                $error_response = json_decode($body, TRUE);
                $error_details['error_message'] = $error_response['message'] ?? 'Unknown error';
                $error_details['error_name'] = $error_response['name'] ?? 'Unknown error type';
            }

            Log::error('Failed to send email via Resend', $error_details);

            return [
                'status' => 'error',
                'msg' => 'Failed to send email',
                'error' => $error_details['error_message'] ?? $body
            ];
        } catch (\Exception $e) {
            // Логируем исключение
            Log::error('Exception while sending email via Resend', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'msg' => 'Exception while sending email',
                'error' => $e->getMessage()
            ];
        }
    }


    public function add_contact($request_body = [])
    {
        try {
            // Валидация обязательных полей
            if (empty($request_body['email'])) {
                return [
                    'status' => 'error',
                    'msg' => 'Missing required field: email'
                ];
            }

            $data = [
                'email'         => $request_body['email'],
                'first_name'    => $request_body['first_name'] ?? '',
                'last_name'     => $request_body['last_name'] ?? '',
                'unsubscribed'  => $request_body['unsubscribed'] ?? FALSE,
            ];

            $ch = curl_init('https://api.resend.com/audiences/' . $this->audience_id . '/contacts');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            $body = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            if (in_array($info['http_code'], [200, 201])) {
                if (validation_json($body)) {
                    $decoded_body = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
                    if (! empty($decoded_body['id'])) {
                        return [
                            'status'    => 'success',
                            'id'        => $decoded_body['id'],
                            'msg'       => 'Contact added successfully.'
                        ];
                    }
                }
            }

            // Детальное логирование ошибки
            $error_details = [
                'request' => $data,
                'response' => $body,
                'http_code' => $info['http_code'],
                'audience_id' => $this->audience_id
            ];

            if (validation_json($body)) {
                $error_response = json_decode($body, TRUE);
                $error_details['error_message'] = $error_response['message'] ?? 'Unknown error';
                $error_details['error_name'] = $error_response['name'] ?? 'Unknown error type';
            }

            Log::error('Failed to add contact via Resend', $error_details);

            return [
                'status' => 'error',
                'msg' => 'Failed to add contact',
                'error' => $error_details['error_message'] ?? $body,
                'http_code' => $info['http_code']
            ];
        } catch (\Exception $e) {
            // Логируем исключение
            Log::error('Exception while adding contact via Resend', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'msg' => 'Exception while adding contact',
                'error' => $e->getMessage()
            ];
        }
    }


    public function update_contact($contact_id, $request_body = [])
    {
        $data = [
            'first_name'    => $request_body['first_name'] ?? '',
            'last_name'     => $request_body['last_name'] ?? '',
            'unsubscribed'  => $request_body['unsubscribed'] ?? FALSE,
        ];

        $ch = curl_init('https://api.resend.com/audiences/' . $this->audience_id . '/contacts/' . $contact_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['id'])) {
                    return [
                        'status'    => 'success',
                        'id'        => $decoded_body['id'],
                        'msg'       => 'Contact updated successfully.'
                    ];
                }
            }
        }

        return [
            'status'    => 'error',
            'msg'       => 'Failed to update contact.'
        ];
    }


    public function delete_contact($contact_id)
    {
        $ch = curl_init('https://api.resend.com/audiences/' . $this->audience_id . '/contacts/' . $contact_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['contact'])) {
                    return [
                        'status'    => 'success',
                        'id'        => $decoded_body['contact'],
                        'msg'       => 'Contact deleted successfully.'
                    ];
                }
            }
        }

        return [
            'status'    => 'error',
            'msg'       => 'Failed to delete contact.'
        ];
    }


    public function retrieve_contact($contact_id)
    {
        $ch = curl_init('https://api.resend.com/audiences/' . $this->audience_id . '/contacts/' . $contact_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['id'])) {
                    return [
                        'status'        => 'success',
                        'msg'           => 'Contact retrieved successfully.',
                        'id'            => $decoded_body['id'],
                        'email'         => $decoded_body['email'],
                        'first_name'    => $decoded_body['first_name'],
                        'last_name'     => $decoded_body['last_name'],
                        'unsubscribed'  => boolval($decoded_body['unsubscribed'])
                    ];
                }
            }
        }

        return [
            'status'    => 'error',
            'msg'       => 'Failed to retrieve contact.'
        ];
    }


    public function create_broadcast($request_body = [])
    {
        $data = [
            'audience_id'   => $request_body['audience_id'],
            'from'          => config('resend.from'),
            'subject'       => trim($request_body['subject']),
            'html'          => $request_body['html'],
            'name'          => $request_body['name'],
        ];


        $ch = curl_init('https://api.resend.com/broadcasts');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['id'])) {
                    return [
                        'status'    => 'success',
                        'id'        => $decoded_body['id'],
                        'msg'       => 'Broadcast created successfully.'
                    ];
                }
            }
        }

        return [
            'status'    => 'error',
            'msg'       => 'Failed to create broadcast.'
        ];
    }


    public function send_broadcast($broadcast_id)
    {
        $data = [
            'scheduled_at' => '',
        ];

        $ch = curl_init('https://api.resend.com/broadcasts/' . $broadcast_id . '/send');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['id'])) {
                    return [
                        'status'    => 'success',
                        'id'        => $decoded_body['id'],
                        'msg'       => 'Broadcast sent successfully.'
                    ];
                }
            }
        }

        return [
            'status'    => 'error',
            'msg'       => 'Failed to send broadcast.'
        ];
    }


    public function delete_broadcast($broadcast_id)
    {
        $ch = curl_init('https://api.resend.com/broadcasts/' . $broadcast_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['id'])) {
                    return [
                        'status' => 'success',
                        'id'     => $decoded_body['id'],
                        'msg'    => 'Broadcast deleted successfully.'
                    ];
                }
            }
        }

        return [
            'status'    => 'error',
            'msg'       => 'Failed to delete broadcast.'
        ];
    }


    public function retrieve_email($email_id)
    {
        $ch = curl_init('https://api.resend.com/emails/' . $email_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
                if (isset($decoded_body['id'])) {
                    return [
                        'status'    => 'success',
                        'msg'       => 'Email retrieved successfully.',
                        'id'        => $decoded_body['id'],
                        'email'     => ! empty($decoded_body['to']) ? $decoded_body['to'][0] : NULL,
                    ];
                }
            }
        }

        return [
            'status'    => 'error',
            'msg'       => 'Failed to retrieve email.'
        ];
    }
}
