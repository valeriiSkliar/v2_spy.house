<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Log;

use function App\Helpers\validation_json;

/**
 * @deprecated This custom Resend library is deprecated.
 * Use the official Resend Laravel package instead: composer require resend/resend-laravel
 *
 * Migration guide:
 * - Replace App\Libraries\Resend with Resend\Laravel\Facades\Resend
 * - Use Resend::emails()->send() instead of send_email()
 * - Use Resend::contacts()->create() instead of add_contact()
 * - Use Resend::contacts()->update() instead of update_contact()
 * - Use Resend::contacts()->remove() instead of delete_contact()
 * - Use Resend::contacts()->get() instead of retrieve_contact()
 *
 * Official documentation: https://resend.com/docs/send-with-laravel
 */
class Resend
{
    private string $api_key;

    private string $audience_id;

    private string $api_url;

    private int $timeout;

    private bool $verify_ssl;

    public function __construct()
    {
        $this->api_key = config('resend.api_key');
        $this->audience_id = config('resend.audience_id');
        $this->api_url = config('resend.api_url', 'https://api.resend.com');
        $this->timeout = config('resend.timeout', 30);
        $this->verify_ssl = config('resend.verify_ssl', true);
    }

    /**
     * Send email via Resend API
     *
     * @deprecated Use Resend::emails()->send() from official package instead
     *
     * @param  array  $request_body
     * @return array
     */
    public function send_email($request_body = [])
    {
        try {
            // Validate required fields
            if (empty($request_body['email']) || empty($request_body['subject']) || empty($request_body['html'])) {
                return [
                    'status' => 'error',
                    'msg' => 'Missing required fields: email, subject, html',
                ];
            }

            // Prepare email data
            $data = [
                'from' => $request_body['from'] ?? config('resend.from'),
                'to' => [$request_body['email']],
                'subject' => trim($request_body['subject']),
                'html' => $request_body['html'],

                // 'bcc'       => $request_body['bcc'] ?? null,
                // 'cc'        => $request_body['cc'] ?? null,
                // 'reply_to'  => $request_body['reply_to'] ?? null,
                // 'headers'   => $request_body['headers'] ?? [],
                // 'tags'      => $request_body['tags'] ?? []
            ];

            // Add optional fields
            if (! empty($request_body['text'])) {
                $data['text'] = $request_body['text'];
            }
            if (! empty($request_body['bcc'])) {
                $data['bcc'] = is_array($request_body['bcc']) ? $request_body['bcc'] : [$request_body['bcc']];
            }
            if (! empty($request_body['cc'])) {
                $data['cc'] = is_array($request_body['cc']) ? $request_body['cc'] : [$request_body['cc']];
            }
            if (! empty($request_body['reply_to'])) {
                $data['reply_to'] = is_array($request_body['reply_to']) ? $request_body['reply_to'] : [$request_body['reply_to']];
            }
            if (! empty($request_body['headers']) && is_array($request_body['headers'])) {
                $data['headers'] = $request_body['headers'];
            }
            if (! empty($request_body['tags']) && is_array($request_body['tags'])) {
                $data['tags'] = $request_body['tags'];
            }

            // Prepare headers
            $headers = [
                'Authorization: Bearer '.$this->api_key,
                'Content-Type: application/json',
                'User-Agent: Laravel-Resend/1.0',
            ];

            // Add idempotency key if provided
            if (! empty($request_body['idempotency_key'])) {
                $headers[] = 'Idempotency-Key: '.$request_body['idempotency_key'];
            }

            // Отправляем запрос
            $response = $this->makeRequest('/emails', 'POST', $data, $headers);

            // $ch = curl_init('https://api.resend.com/emails');
            // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            // curl_setopt($ch, CURLOPT_POST, TRUE);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            // $body = curl_exec($ch);
            // $info = curl_getinfo($ch);
            // curl_close($ch);

            if ($response['success'] && ! empty($response['data']['id'])) {
                Log::info('Email sent successfully via Resend', [
                    'resend_id' => $response['data']['id'],
                    'to' => $request_body['email'],
                    'subject' => $request_body['subject'],
                ]);

                return [
                    'status' => 'success',
                    'id' => $response['data']['id'],
                    'msg' => 'Email sent successfully',
                ];
            }

            // Handle error response
            $errorMessage = $response['data']['message'] ?? $response['error'] ?? 'Unknown error';
            Log::error('Failed to send email via Resend', [
                'error' => $errorMessage,
                'to' => $request_body['email'],
                'subject' => $request_body['subject'],
                'http_code' => $response['http_code'] ?? null,
            ]);

            return [
                'status' => 'error',
                'msg' => 'Failed to send email',
                'error' => $errorMessage,
                'http_code' => $response['http_code'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Exception while sending email via Resend', [
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                'to' => $request_body['email'] ?? null,
            ]);

            return [
                'status' => 'error',
                'msg' => 'Exception while sending email',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add contact to Resend audience
     *
     * @deprecated Use Resend::contacts()->create() from official package instead
     */
    public function add_contact($request_body = [])
    {
        try {
            if (empty($request_body['email'])) {
                return [
                    'status' => 'error',
                    'msg' => 'Missing required field: email',
                ];
            }

            $data = [
                'email' => $request_body['email'],
                'first_name' => $request_body['first_name'] ?? '',
                'last_name' => $request_body['last_name'] ?? '',
                'unsubscribed' => $request_body['unsubscribed'] ?? false,
            ];

            $response = $this->makeRequest("/audiences/{$this->audience_id}/contacts", 'POST', $data);

            if ($response['success'] && ! empty($response['data']['id'])) {
                return [
                    'status' => 'success',
                    'id' => $response['data']['id'],
                    'msg' => 'Contact added successfully.',
                ];
            }

            return [
                'status' => 'error',
                'msg' => 'Failed to add contact',
                'error' => $response['data']['message'] ?? $response['error'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error('Exception while adding contact via Resend', [
                'error' => $e->getMessage(),
                'email' => $request_body['email'] ?? null,
            ]);

            return [
                'status' => 'error',
                'msg' => 'Exception while adding contact',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update contact in Resend audience
     *
     * @deprecated Use Resend::contacts()->update() from official package instead
     */
    public function update_contact($contact_id, $request_body = [])
    {
        $data = [
            'first_name' => $request_body['first_name'] ?? '',
            'last_name' => $request_body['last_name'] ?? '',
            'unsubscribed' => $request_body['unsubscribed'] ?? false,
        ];

        $ch = curl_init('https://api.resend.com/audiences/'.$this->audience_id.'/contacts/'.$contact_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$this->api_key,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['id'])) {
                    return [
                        'status' => 'success',
                        'id' => $decoded_body['id'],
                        'msg' => 'Contact updated successfully.',
                    ];
                }
            }
        }

        return [
            'status' => 'error',
            'msg' => 'Failed to update contact.',
        ];
    }

    /**
     * Delete contact from Resend audience
     *
     * @deprecated Use Resend::contacts()->remove() from official package instead
     */
    public function delete_contact($contact_id)
    {
        $ch = curl_init('https://api.resend.com/audiences/'.$this->audience_id.'/contacts/'.$contact_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$this->api_key,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['contact'])) {
                    return [
                        'status' => 'success',
                        'id' => $decoded_body['contact'],
                        'msg' => 'Contact deleted successfully.',
                    ];
                }
            }
        }

        return [
            'status' => 'error',
            'msg' => 'Failed to delete contact.',
        ];
    }

    /**
     * Retrieve contact from Resend audience
     *
     * @deprecated Use Resend::contacts()->get() from official package instead
     */
    public function retrieve_contact($contact_id)
    {
        $ch = curl_init('https://api.resend.com/audiences/'.$this->audience_id.'/contacts/'.$contact_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$this->api_key,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['id'])) {
                    return [
                        'status' => 'success',
                        'msg' => 'Contact retrieved successfully.',
                        'id' => $decoded_body['id'],
                        'email' => $decoded_body['email'],
                        'first_name' => $decoded_body['first_name'],
                        'last_name' => $decoded_body['last_name'],
                        'unsubscribed' => boolval($decoded_body['unsubscribed']),
                    ];
                }
            }
        }

        return [
            'status' => 'error',
            'msg' => 'Failed to retrieve contact.',
        ];
    }

    /**
     * Create broadcast in Resend
     *
     * @deprecated Use Resend::broadcasts()->create() from official package instead
     */
    public function create_broadcast($request_body = [])
    {
        $data = [
            'audience_id' => $request_body['audience_id'],
            'from' => config('resend.from'),
            'subject' => trim($request_body['subject']),
            'html' => $request_body['html'],
            'name' => $request_body['name'],
        ];

        $ch = curl_init('https://api.resend.com/broadcasts');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$this->api_key,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['id'])) {
                    return [
                        'status' => 'success',
                        'id' => $decoded_body['id'],
                        'msg' => 'Broadcast created successfully.',
                    ];
                }
            }
        }

        return [
            'status' => 'error',
            'msg' => 'Failed to create broadcast.',
        ];
    }

    /**
     * Send broadcast via Resend
     *
     * @deprecated Use Resend::broadcasts()->send() from official package instead
     */
    public function send_broadcast($broadcast_id)
    {
        $data = [
            'scheduled_at' => '',
        ];

        $ch = curl_init('https://api.resend.com/broadcasts/'.$broadcast_id.'/send');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$this->api_key,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['id'])) {
                    return [
                        'status' => 'success',
                        'id' => $decoded_body['id'],
                        'msg' => 'Broadcast sent successfully.',
                    ];
                }
            }
        }

        return [
            'status' => 'error',
            'msg' => 'Failed to send broadcast.',
        ];
    }

    /**
     * Delete broadcast from Resend
     *
     * @deprecated Use Resend::broadcasts()->remove() from official package instead
     */
    public function delete_broadcast($broadcast_id)
    {
        $ch = curl_init('https://api.resend.com/broadcasts/'.$broadcast_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$this->api_key,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                if (! empty($decoded_body['id'])) {
                    return [
                        'status' => 'success',
                        'id' => $decoded_body['id'],
                        'msg' => 'Broadcast deleted successfully.',
                    ];
                }
            }
        }

        return [
            'status' => 'error',
            'msg' => 'Failed to delete broadcast.',
        ];
    }

    /**
     * Retrieve email from Resend
     *
     * @deprecated Use Resend::emails()->get() from official package instead
     */
    public function retrieve_email($email_id)
    {
        $ch = curl_init('https://api.resend.com/emails/'.$email_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$this->api_key,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (in_array($info['http_code'], [200, 201])) {
            if (validation_json($body)) {
                $decoded_body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                if (isset($decoded_body['id'])) {
                    return [
                        'status' => 'success',
                        'msg' => 'Email retrieved successfully.',
                        'id' => $decoded_body['id'],
                        'email' => ! empty($decoded_body['to']) ? $decoded_body['to'][0] : null,
                    ];
                }
            }
        }

        return [
            'status' => 'error',
            'msg' => 'Failed to retrieve email.',
        ];
    }

    /**
     * Make HTTP request to Resend API
     */
    private function makeRequest(string $endpoint, string $method = 'GET', array $data = [], array $headers = []): array
    {
        $url = $this->api_url.$endpoint;

        $defaultHeaders = [
            'Authorization: Bearer '.$this->api_key,
            'Content-Type: application/json',
            'User-Agent: Laravel-Resend/1.0',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verify_ssl,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $method !== 'GET' ? json_encode($data) : null,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('cURL error: '.$error);
        }

        $decodedBody = json_decode($body, true);

        return [
            'success' => in_array($httpCode, [200, 201]),
            'http_code' => $httpCode,
            'data' => $decodedBody,
            'error' => ! in_array($httpCode, [200, 201]) ? ($decodedBody['message'] ?? 'HTTP error') : null,
        ];
    }
}
