<?php

namespace App\Services;

use App\DTO\ValidatedFollowData;
use App\DTO\ValidatedGroupData;
use App\DTO\ValidatedGroupSeasonFollowsData;
use App\DTO\ValidatedGroupPredictionScoringPolicyData;
use App\DTO\ValidatedGroupPoliciesData;
use App\DTO\ValidatedMemberData;
use App\DTO\ValidatedPlayerData;
use App\Models\Follow;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Services\Contracts\GroupCommandInterface;
use App\Services\Contracts\MemberCommandInterface;
use App\Services\Contracts\PlayerCommandInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Executes group lifecycle and nested membership operations from a single coordination point.
 * Orchestrates group, member, player, and follow actions to enforce consistent group administration behavior.
 */
class GroupCommandService implements GroupCommandInterface
{
    /**
     * Create a group coordinator that delegates member and player operations.
     *
     * @param  MemberCommandInterface  $memberCommandService  The service used for nested member operations.
     * @param  PlayerCommandInterface  $playerCommandService  The service used for nested player operations.
     */
    public function __construct(
        private MemberCommandInterface $memberCommandService,
        private PlayerCommandInterface $playerCommandService,
    ) {}

    /**
     * Persists a new group with normalized ownership and optional limit settings.
     *
     * @param  ValidatedGroupData  $data  Validated group data including name, owner_id, limits.
     * @return Group The created group instance.
     */
    public function create(ValidatedGroupData $data): Group
    {
        // Build a strict persistence payload from validated DTO input.
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

        // Persist group identity and optional limits in one write.
        return Group::create($groupData);
    }

    /**
     * Applies metadata and limit changes to an existing group.
     *
     * @param  Group  $group  The group to update.
     * @param  ValidatedGroupData  $data  Validated data containing group information to update.
     * @return Group The updated group instance.
     */
    public function update(Group $group, ValidatedGroupData $data): Group
    {
        // Construct a partial update payload so unchanged values remain untouched.
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

        // Persist all selected fields as a single update unit.
        $group->fill($updateData);
        $group->save();

        return $group;
    }

    /**
     * Applies only group-level optional prediction policy updates.
     *
     * @param  Group  $group  The group to update.
     * @param  ValidatedGroupPoliciesData  $data  Validated policy data to persist.
     * @return Group The updated group instance.
     */
    public function updatePolicies(Group $group, ValidatedGroupPoliciesData $data): Group
    {
        $groupSeasonFollow = $group->seasonFollows()
            ->where('season_id', $data->season_id)
            ->firstOrFail();

        $groupSeasonFollow->fill([
            'enabled_prediction_policies' => $data->enabled_prediction_policies,
        ]);
        $groupSeasonFollow->save();

        return $group->load('seasonFollows.season');
    }

    /**
     * Applies the selected scoring policy key for one followed season.
     *
     * @param  Group  $group  The group to update.
     * @param  ValidatedGroupPredictionScoringPolicyData  $data  Validated season scoring payload.
     * @return Group The updated group instance.
     */
    public function updatePredictionScoringPolicy(Group $group, ValidatedGroupPredictionScoringPolicyData $data): Group
    {
        $groupSeasonFollow = $group->seasonFollows()
            ->where('season_id', $data->season_id)
            ->firstOrFail();

        $groupSeasonFollow->fill([
            'prediction_scoring_policy' => $data->prediction_scoring_policy,
        ]);
        $groupSeasonFollow->save();

        return $group->load('seasonFollows.season');
    }

    /**
     * Synchronize the seasons explicitly followed by a group.
     *
     * @param  Group  $group  The group to update.
     * @param  ValidatedGroupSeasonFollowsData  $data  The normalized season-follow payload.
     * @return Group The updated group instance.
     */
    public function syncSeasonFollows(Group $group, ValidatedGroupSeasonFollowsData $data): Group
    {
        $selectedSeasonIds = array_values(array_unique(array_map('intval', $data->season_ids)));

        $existingSeasonFollows = $group->seasonFollows()->get()->keyBy('season_id');

        foreach ($selectedSeasonIds as $seasonId) {
            $groupSeasonFollow = $existingSeasonFollows->get($seasonId);

            if ($groupSeasonFollow) {
                continue;
            }

            $group->seasonFollows()->create([
                'season_id' => $seasonId,
            ]);
        }

        $group->seasonFollows()
            ->whereNotIn('season_id', $selectedSeasonIds)
            ->delete();

        return $group->load('seasonFollows.season');
    }

    /**
     * Removes a group record from persistence.
     *
     * @param  Group  $group  The group to delete.
     */
    public function delete(Group $group): void
    {
        Group::destroy($group->getKey());
    }

    /**
     * Delegates group member creation to the member command service.
     *
     * @param  Group  $group  The group to add the member to.
     * @param  ValidatedMemberData  $data  Validated member data.
     * @return Member The created member instance.
     */
    public function addMember(Group $group, ValidatedMemberData $data): Member
    {
        // Delegate member creation so role/status defaults stay centralized.
        return $this->memberCommandService->createForGroup($group, $data);
    }

    /**
     * Delegates group member removal to the member command service.
     *
     * @param  Group  $group  The group to remove the member from.
     * @param  Member  $member  The member to remove.
     */
    public function removeMember(Group $group, Member $member): void
    {
        // Guard aggregate boundaries to avoid deleting members from a different group.
        if ($member->group_id !== $group->id) {
            throw new DomainException('Member does not belong to the provided group.');
        }

        // Delegate deletion so admin-safety constraints stay in one service.
        $this->memberCommandService->delete($member);
    }

    /**
     * Delegates player creation within a group context to the player command service.
     *
     * @param  Group  $group  The group context.
     * @param  Member  $member  The member to add the player to.
     * @param  ValidatedPlayerData  $data  Validated player data.
     * @return Player The created player instance.
     */
    public function addPlayer(Group $group, Member $member, ValidatedPlayerData $data): Player
    {
        // Guard aggregate boundaries to ensure players are created under the target group.
        if ($member->group_id !== $group->id) {
            throw new DomainException('Member does not belong to the provided group.');
        }

        // Delegate player creation to keep roster rules centralized.
        return $this->playerCommandService->createForMember($member, $data);
    }

    /**
     * Remove a player from a member in the group.
     * This method uses the injected PlayerService to delete a player.
     *
     * @param  Group  $group  The group context.
     * @param  Member  $member  The member context.
     * @param  Player  $player  The player to remove.
     */
    public function removePlayer(Group $group, Member $member, Player $player): void
    {
        // Guard aggregate boundaries before deletion.
        if ($member->group_id !== $group->id || $player->member_id !== $member->id) {
            throw new DomainException('Player does not belong to the provided group member.');
        }

        // Delegate to player command service for consistent delete behavior.
        $this->playerCommandService->delete($player);
    }

    /**
     * Follow a team for a group.
     * This method creates a follow relationship between a group and a team.
     *
     * @param  Group  $group  The group to follow the team.
     * @param  ValidatedFollowData  $data  Validated follow data including team_id.
     * @return Follow The created follow instance.
     *
     * @throws DomainException If the group is already following this team or follow limits are exceeded.
     */
    public function followTeam(Group $group, ValidatedFollowData $data): Follow
    {
        // Execute checks and creation atomically to avoid race conditions under concurrent requests.
        return DB::transaction(function () use ($group, $data): Follow {
            $lockedGroup = Group::query()
                ->whereKey($group->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Prevent duplicate follows for the same team.
            $duplicateFollowExists = $lockedGroup->follows()
                ->where('team_id', $data->team_id)
                ->exists();

            if ($duplicateFollowExists) {
                throw new DomainException('This group is already following this team.');
            }

            // Enforce follow_limit before creating another follow record.
            $followCount = $lockedGroup->follows()->count();
            if ($followCount >= $lockedGroup->follow_limit) {
                throw new DomainException('This group has reached its follow limit.');
            }

            // Persist the team follow relationship.
            $follow = $lockedGroup->follows()->create([
                'team_id' => $data->team_id,
            ]);

            // Ensure selected active seasons are followed so this team can participate there.
            foreach ($data->season_ids as $seasonId) {
                $lockedGroup->seasonFollows()->firstOrCreate([
                    'season_id' => $seasonId,
                ]);
            }

            return $follow;
        });
    }

    /**
     * Remove a follow relationship.
     * This method removes the follow relationship for a group.
     *
     * @param  Group  $group  The group to unfollow.
     */
    public function removeFollow(Group $group, Follow $follow): void
    {
        // Scope deletion to the group relation to prevent cross-group removal.
        $group->follows()->whereKey($follow->id)->delete();
    }
}
