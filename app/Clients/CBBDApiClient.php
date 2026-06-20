<?php

namespace App\Clients;

class CBBDApiClient extends BaseCollegeDataApiClient
{
    protected function providerCode(): string
    {
        return 'CBBD';
    }
}