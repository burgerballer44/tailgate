<?php

namespace App\Http\Controllers;

use App\Traits\FlashAlertTrait;

abstract class Controller
{
    use FlashAlertTrait;

    // use AuthorizesRequests, ValidatesRequests;
}