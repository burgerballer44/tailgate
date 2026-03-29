<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedMemberData;
use App\Models\Group;
use App\Models\Member;

interface MemberCommandInterface
{
    public function createForGroup(Group $group, ValidatedMemberData $data): Member;

    public function update(Member $member, ValidatedMemberData $data): Member;

    public function delete(Member $member): void;
}
