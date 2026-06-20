<?php

namespace App\Clients;

class CFBDApiClient extends BaseCollegeDataApiClient
{
    protected function providerCode(): string
    {
        return 'CFBD';
    }
}