<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedFollowData;
use App\DTO\ValidatedGroupData;
use App\DTO\ValidatedMemberData;
use App\DTO\ValidatedPlayerData;
use App\Models\Follow;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;

interface GroupCommandInterface
{
    public function create(ValidatedGroupData $data): Group;

    public function update(Group $group, ValidatedGroupData $data): Group;

    public function delete(Group $group): void;

    public function addMember(Group $group, ValidatedMemberData $data): Member;

    public function removeMember(Group $group, Member $member): void;

    public function addPlayer(Group $group, Member $member, ValidatedPlayerData $data): Player;

    public function removePlayer(Group $group, Member $member, Player $player): void;

    public function followTeam(Group $group, ValidatedFollowData $data): Follow;

    public function removeFollow(Group $group): void;
}
