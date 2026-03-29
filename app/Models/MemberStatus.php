<?php

namespace App\Models;

use App\Traits\EnumToArray;

enum MemberStatus: string
{
    use EnumToArray;

    case PENDING = 'Pending'; // awaiting approval
    case APPROVED = 'Approved'; // approved member
    case REJECTED = 'Rejected'; // rejected member, but we keep the record
}
