<?php

namespace App\Services;

use App\DTO\ValidatedMemberData;
use App\DTO\ValidatedPlayerData;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\User;
use App\Services\Contracts\MemberCommandInterface;
use App\Services\Contracts\PlayerCommandInterface;

class MemberCommandService implements MemberCommandInterface
{
    public function __construct(
        private PlayerCommandInterface $playerCommandService
    ) {}

    /**
     * Create a new member for a specific group.
     * This method handles member creation logic within a group context.
     *
     * @param  Group  $group  The group to add the member to.
     * @param  ValidatedMemberData  $data  Validated member data including user_id and role.
     * @return Member The created member instance.
     */
    public function createForGroup(Group $group, ValidatedMemberData $data): Member
    {
        $memberData = [
            'user_id' => $data->user_id,
            'role' => $data->role?->value ?? GroupRole::GROUP_MEMBER->value,
            'status' => $data->status?->value ?? MemberStatus::APPROVED->value,
        ];

        $member = $group->members()->create($memberData);

        // retrieve the user to get their name for the player
        $user = User::find($data->user_id);

        // create a ValidatedPlayerData DTO for the player service
        $playerData = ValidatedPlayerData::fromArray([
            'player_name' => $user->name,
        ]);

        // create a new player associated with the member using the PlayerService
        $this->playerCommandService->createForMember($member, $playerData);

        return $member;
    }

    /**
     * Update a member's information.
     * This method modifies member details.
     *
     * @param  Member  $member  The member to update.
     * @param  ValidatedMemberData  $data  Validated data containing member information to update.
     * @return Member The updated member instance.
     */
    public function update(Member $member, ValidatedMemberData $data): Member
    {
        $updateData = [];

        if ($data->role !== null) {
            $updateData['role'] = $data->role->value;
        }

        if ($data->status !== null) {
            $updateData['status'] = $data->status->value;
        }

        $member->fill($updateData);
        $member->save();

        return $member;
    }

    /**
     * Delete a member from the system.
     * This method permanently removes the member.
     *
     * @param  Member  $member  The member to delete.
     *
     * @throws \Exception If deleting would violate admin minimum requirements.
     */
    public function delete(Member $member): void
    {
        $group = $member->group;

        if (
            $group->admin->count() == Group::MIN_NUMBER_ADMINS &&
            $group->admin->first()->id == $member->id
        ) {
            throw new \Exception('Group admin minimum reached. Please update a different member to the Group Admin role before removing this member.');
        }

        $member->delete();
    }
}
