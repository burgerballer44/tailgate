<?php

namespace App\Models;

use App\Traits\EnumToArray;

enum MemberStatus: string
{
    use EnumToArray;

    /** Awaiting approval from a group admin. */
    case PENDING = 'Pending';

    /** Approved and able to participate in the group. */
    case APPROVED = 'Approved';

    /** Rejected by the group, but retained for history and auditing. */
    case REJECTED = 'Rejected';
}
