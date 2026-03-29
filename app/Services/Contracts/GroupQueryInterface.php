<?php

namespace App\Services\Contracts;

use App\Models\Group;
use Illuminate\Contracts\Database\Eloquent\Builder;

interface GroupQueryInterface
{
    public function query(array $query): Builder;

    public function findByInviteCode(string $inviteCode): ?Group;

    public function isUserAlreadyMember(Group $group, int $userId): bool;

    public function isGroupMemberLimitReached(Group $group): bool;
}
