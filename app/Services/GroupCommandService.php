<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\DTO\ValidatedFollowData;
use App\DTO\ValidatedGroupData;
use App\DTO\ValidatedMemberData;
use App\DTO\ValidatedPlayerData;
use App\Services\Contracts\GroupCommandInterface;
use App\Services\Contracts\MemberCommandInterface;
use App\Services\Contracts\PlayerCommandInterface;

class GroupCommandService implements GroupCommandInterface
{
    public function __construct(
        private MemberCommandInterface $memberCommandService,
        private PlayerCommandInterface $playerCommandService,
    ) {}

    /**
     * Create a new group with the provided data.
     * This method handles group creation logic.
     *
     * @param ValidatedGroupData $data Validated group data including name, owner_id, limits.
     * @return Group The created group instance.
     */
    public function create(ValidatedGroupData $data): Group
    {
        $groupData = [
            'name' => $data->name,
            'owner_id' => $data->owner_id,
        ];

        if ($data->member_limit !== null) {
            $groupData['member_limit'] = $data->member_limit;
        }

        if ($data->player_limit !== null) {
            $groupData['player_limit'] = $data->player_limit;
        }

        return Group::create($groupData);
    }

    /**
     * Update a group's information.
     * This method modifies group details.
     *
     * @param Group $group The group to update.
     * @param ValidatedGroupData $data Validated data containing group information to update.
     * @return Group The updated group instance.
     */
    public function update(Group $group, ValidatedGroupData $data): Group
    {
        $updateData = [];

        if ($data->name !== null) {
            $updateData['name'] = $data->name;
        }

        if ($data->member_limit !== null) {
            $updateData['member_limit'] = $data->member_limit;
        }

        if ($data->player_limit !== null) {
            $updateData['player_limit'] = $data->player_limit;
        }

        if ($data->owner_id !== null) {
            $updateData['owner_id'] = $data->owner_id;
        }

        $group->fill($updateData);
        $group->save();

        return $group;
    }

    /**
     * Delete a group from the system.
     * This method permanently removes the group.
     *
     * @param Group $group The group to delete.
     */
    public function delete(Group $group): void
    {
        $group->delete();
    }

    /**
     * Add a member to the group.
     * This method uses the injected MemberService to create a member.
     *
     * @param Group $group The group to add the member to.
     * @param ValidatedMemberData $data Validated member data.
     * @return Member The created member instance.
     */
    public function addMember(Group $group, ValidatedMemberData $data): Member
    {
        return $this->memberCommandService->createForGroup($group, $data);
    }

    /**
     * Remove a member from the group.
     * This method uses the injected MemberService to delete a member.
     *
     * @param Group $group The group to remove the member from.
     * @param Member $member The member to remove.
     */
    public function removeMember(Group $group, Member $member): void
    {
        $this->memberCommandService->delete($member);
    }

    /**
     * Add a player to a member in the group.
     * This method uses the injected PlayerService to create a player.
     *
     * @param Group $group The group context.
     * @param Member $member The member to add the player to.
     * @param ValidatedPlayerData $data Validated player data.
     * @return Player The created player instance.
     */
    public function addPlayer(Group $group, Member $member, ValidatedPlayerData $data): Player
    {
        return $this->playerCommandService->createForMember($member, $data);
    }

    /**
     * Remove a player from a member in the group.
     * This method uses the injected PlayerService to delete a player.
     *
     * @param Group $group The group context.
     * @param Member $member The member context.
     * @param Player $player The player to remove.
     */
    public function removePlayer(Group $group, Member $member, Player $player): void
    {
        $this->playerCommandService->delete($player);
    }

    /**
     * Follow a team for a group.
     * This method creates a follow relationship between a group and a team for a specific season.
     *
     * @param Group $group The group to follow the team.
     * @param ValidatedFollowData $data Validated follow data including team_id and season_id.
     * @return Follow The created follow instance.
     * @throws \Exception If the group is already following a team.
     */
    public function followTeam(Group $group, ValidatedFollowData $data): Follow
    {
        if ($group->follow) {
            throw new \Exception('This group is already following a team.');
        }

        return $group->follow()->create([
            'team_id' => $data->team_id,
            'season_id' => $data->season_id,
        ]);
    }

    /**
     * Remove a follow relationship.
     * This method removes the follow relationship for a group.
     *
     * @param Group $group The group to unfollow.
     */
    public function removeFollow(Group $group): void
    {
        if ($group->follow) {
            $group->follow->delete();
        }
    }
}