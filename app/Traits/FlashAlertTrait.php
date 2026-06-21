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
     * @param string $type Alert severity: 'success', 'info', 'warning', or 'error'.
     * @param string|array $message The main message content; accepts a string or pre-formatted array.
     * @param string $text Optional secondary body text displayed below the main message.
     * @param array $links Optional action links in the format [['text' => 'Label', 'link' => 'URL'], ...].
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
