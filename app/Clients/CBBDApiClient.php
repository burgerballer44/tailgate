<?php

namespace App\Clients;

/**
 * API client for the CollegeBasketballData (CBBD) provider.
 *
 * @see https://collegebasketballdata.com
 */
class CBBDApiClient extends BaseCollegeDataApiClient
{
    /**
     * Returns the provider code used in exception messages for this client.
     *
     * @return string The CollegeBasketballData provider code.
     */
    protected function providerCode(): string
    {
        return 'CBBD';
    }
}
