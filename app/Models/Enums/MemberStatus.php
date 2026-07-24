<?php

namespace App\Models\Enums;

use App\Traits\EnumToArray;

enum MemberStatus: string
{
    use EnumToArray;

    // Awaiting approval from a group admin.
    case PENDING = 'Pending';

    // Approved and able to participate in the group.
    case APPROVED = 'Approved';

    // Rejected by the group, but retained for history and auditing.
    case REJECTED = 'Rejected';

    // Left voluntarily by the member and no longer active in the group.
    case LEFT = 'Left';

    // Removed by a group admin and no longer active in the group.
    case REMOVED = 'Removed';
}
