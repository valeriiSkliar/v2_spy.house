<?php

namespace App\Services\Frontend;

use Illuminate\Support\Facades\Session;

class Toast
{
    /**
     * Add a toast message to the session.
     *
     * @param string $message The toast message
     * @param string $type The type of toast (e.g., success, error, info)
     * @param array $options Additional options (e.g., duration)
     */
    public static function add(string $message, string $type = 'info', array $options = [])
    {
        $toasts = Session::get('toasts', []);
        $toasts[] = [
            'message' => $message,
            'type' => $type,
            'options' => $options
        ];
        Session::flash('toasts', $toasts);
    }

    /**
     * Add a success toast.
     *
     * @param string $message The success message
     */
    public static function success(string $message)
    {
        self::add($message, 'success');
    }

    /**
     * Add an error toast.
     *
     * @param string $message The error message
     */
    public static function error(string $message)
    {
        self::add($message, 'error');
    }

    /**
     * Add an info toast.
     *
     * @param string $message The info message
     */
    public static function info(string $message)
    {
        self::add($message, 'info');
    }

    /**
     * Add a warning toast.
     *
     * @param string $message The warning message
     */
    public static function warning(string $message)
    {
        self::add($message, 'warning');
    }
}
