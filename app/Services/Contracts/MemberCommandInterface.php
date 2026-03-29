<?php

namespace App\Services\Contracts;

use App\Models\Group;
use App\Models\Member;
use App\DTO\ValidatedMemberData;

interface MemberCommandInterface
{
    public function createForGroup(Group $group, ValidatedMemberData $data): Member;
    public function update(Member $member, ValidatedMemberData $data): Member;
    public function delete(Member $member): void;
}