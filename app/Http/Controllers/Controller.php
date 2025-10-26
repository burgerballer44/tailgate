<?php

namespace App\Http\Controllers;

abstract class Controller
{
    // use AuthorizesRequests, ValidatesRequests;

    /**
     * Set a flash message to be displayed to the user.
     *
     * @param  string        $type    success, info, warning, or error.
     * @param  string|array  $message The main message content.
     * @param  string        $text    Additional text to display.
     * @param  array         $links   [['text' => 'Link Text', 'link' => 'URL'], ...]
     */
    public function setFlashAlert(
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