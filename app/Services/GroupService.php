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
use App\Services\MemberService;
use App\Services\PlayerService;
use Illuminate\Contracts\Database\Eloquent\Builder;

class GroupService
{
    public function __construct(
        private MemberService $memberService,
        private PlayerService $playerService,
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
    public function addMember(Group $group, ValidatedMemberData $data)
    {
        return $this->memberService->createForGroup($group, $data);
    }

    /**
     * Remove a member from the group.
     * This method uses the injected MemberService to delete a member.
     *
     * @param Group $group The group to remove the member from.
     * @param Member $member The member to remove.
     */
    public function removeMember(Group $group, $member): void
    {
        $this->memberService->delete($member);
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
    public function addPlayer(Group $group, $member, ValidatedPlayerData $data)
    {
        return $this->playerService->createForMember($member, $data);
    }

    /**
     * Remove a player from a member in the group.
     * This method uses the injected PlayerService to delete a player.
     *
     * @param Group $group The group context.
     * @param Member $member The member context.
     * @param Player $player The player to remove.
     */
    public function removePlayer(Group $group, $member, $player): void
    {
        $this->playerService->delete($player);
    }

    /**
     * Filter groups based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param array $query An associative array of query parameters to filter groups.
     * @return Builder A query builder instance for the filtered groups.
     */
    public function query(array $query)
    {
        return Group::filter($query);
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

    /**
     * Find a group by its invite code.
     * This method retrieves a group using its unique invite code.
     *
     * @param string $inviteCode The invite code to search for.
     * @return Group|null The group instance if found, null otherwise.
     */
    public function findByInviteCode(string $inviteCode): ?Group
    {
        return Group::where('invite_code', $inviteCode)->first();
    }

    /**
     * Check if a user is already a member of a group.
     * This method determines if a specific user is already a member of the given group.
     *
     * @param Group $group The group to check membership in.
     * @param int $userId The ID of the user to check.
     * @return bool True if the user is already a member, false otherwise.
     */
    public static function isUserAlreadyMember(Group $group, int $userId): bool
    {
        return $group->members()->where('user_id', $userId)->exists();
    }
}