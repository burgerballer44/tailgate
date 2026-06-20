<?php

namespace App\Clients;

/**
 * API client for the CollegeFootballData (CFBD) provider.
 *
 * @see https://collegefootballdata.com
 */
class CFBDApiClient extends BaseCollegeDataApiClient
{
    /**
     * Returns the provider code used in exception messages for this client.
     *
     * @return string The CollegeFootballData provider code.
     */
    protected function providerCode(): string
    {
        return 'CFBD';
    }
}