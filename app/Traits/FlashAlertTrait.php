<?php

namespace App\Traits;

/**
 * Centralizes flash-alert payload construction for user-facing feedback messages.
 */
trait FlashAlertTrait
{
    /**
     * Stores a normalized alert payload in session flash data for the next response.
     *
     * @param  string  $type  success, info, warning, or error.
     * @param  string|array  $message  The main message content.
     * @param  string  $text  Additional text to display.
     * @param  array  $links  [['text' => 'Link Text', 'link' => 'URL'], ...]
     * @return void
     */
    protected function setFlashAlert(
        string $type,
        string|array $message,
        string $text = '',
        array $links = []
    ) {
        session()->flash('alert', [
            'type' => $type,
            'message' => $message,
            'text' => $text,
            'links' => $links,
        ]);
    }
}
