<?php

namespace App\Services;

use App\Libraries\Resend;
use App\Models\EmailLog;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected $resend;

    public function __construct()
    {
        $this->resend = new Resend;
    }

    /**
     * Send email to user
     *
     * @param  string  $email
     * @param  string  $subject
     * @param  string  $template
     * @param  array  $data
     * @return bool
     */
    public function send($email, $subject, $template, $data = [])
    {
        try {
            // Render email template
            $html = view('emails.'.$template, $data)->render();

            // Send email via Resend
            $result = $this->resend->send_email([
                'email' => $email,
                'subject' => $subject,
                'html' => $html,
                'idempotency_key' => md5($email.$subject.time()),
            ]);

            // Log email
            $this->logEmail($email, $subject, $template, $result['status'] === 'success');

            return $result['status'] === 'success';
        } catch (\Exception $e) {
            Log::error('Email sending failed: '.$e->getMessage(), [
                'email' => $email,
                'subject' => $subject,
                'template' => $template,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send notification to user
     *
     * @param  mixed  $notifiable
     * @return bool
     */
    public function sendNotification($notifiable, Notification $notification)
    {
        try {
            $notifiable->notify($notification);

            return true;
        } catch (\Exception $e) {
            Log::error('Notification sending failed: '.$e->getMessage(), [
                'notifiable' => $notifiable,
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send email to multiple recipients
     *
     * @param  array  $emails
     * @param  string  $subject
     * @param  string  $template
     * @param  array  $data
     * @return array
     */
    public function sendBulk($emails, $subject, $template, $data = [])
    {
        $results = [];

        foreach ($emails as $email) {
            $results[$email] = $this->send($email, $subject, $template, $data);
        }

        return $results;
    }

    /**
     * Send email via broadcast (for newsletters)
     *
     * @param  string  $name
     * @param  string  $subject
     * @param  string  $template
     * @param  array  $data
     * @return array
     */
    public function sendBroadcast($name, $subject, $template, $data = [])
    {
        try {
            // Render email template
            $html = view('emails.'.$template, $data)->render();

            // Create broadcast
            $broadcast = $this->resend->create_broadcast([
                'audience_id' => config('resend.audience_id'),
                'name' => $name,
                'subject' => $subject,
                'html' => $html,
            ]);

            if ($broadcast['status'] === 'success') {
                // Send broadcast
                $result = $this->resend->send_broadcast($broadcast['id']);

                // Log broadcast
                $this->logBroadcast($name, $subject, $template, $result);

                return $result;
            }

            return [
                'status' => 'error',
                'msg' => 'Failed to create broadcast',
            ];
        } catch (\Exception $e) {
            Log::error('Broadcast sending failed: '.$e->getMessage(), [
                'name' => $name,
                'subject' => $subject,
                'template' => $template,
            ]);

            return [
                'status' => 'error',
                'msg' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add contact to audience
     *
     * @param  array  $contact
     * @return array
     */
    public function addContact($contact)
    {
        return $this->resend->add_contact($contact);
    }

    /**
     * Update contact in audience
     *
     * @param  string  $contactId
     * @param  array  $contact
     * @return array
     */
    public function updateContact($contactId, $contact)
    {
        return $this->resend->update_contact($contactId, $contact);
    }

    /**
     * Delete contact from audience
     *
     * @param  string  $contactId
     * @return array
     */
    public function deleteContact($contactId)
    {
        return $this->resend->delete_contact($contactId);
    }

    /**
     * Get contact info
     *
     * @param  string  $contactId
     * @return array
     */
    public function getContact($contactId)
    {
        return $this->resend->retrieve_contact($contactId);
    }

    /**
     * Check email delivery status
     *
     * @param  string  $emailId
     * @return array
     */
    public function checkEmailStatus($emailId)
    {
        return $this->resend->retrieve_email($emailId);
    }

    /**
     * Log email to database
     *
     * @param  string  $email
     * @param  string  $subject
     * @param  string  $template
     * @param  bool  $success
     * @return void
     */
    protected function logEmail($email, $subject, $template, $success)
    {
        try {
            EmailLog::create([
                'email' => $email,
                'subject' => $subject,
                'template' => $template,
                'status' => $success ? 'sent' : 'failed',
                'sent_at' => $success ? now() : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log email: '.$e->getMessage());
        }
    }

    /**
     * Log broadcast to database
     *
     * @param  string  $name
     * @param  string  $subject
     * @param  string  $template
     * @param  array  $result
     * @return void
     */
    protected function logBroadcast($name, $subject, $template, $result)
    {
        try {
            EmailLog::create([
                'email' => 'broadcast',
                'subject' => $subject,
                'template' => $template,
                'broadcast_name' => $name,
                'broadcast_id' => $result['id'] ?? null,
                'status' => $result['status'] === 'success' ? 'sent' : 'failed',
                'sent_at' => $result['status'] === 'success' ? now() : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log broadcast: '.$e->getMessage());
        }
    }
}
