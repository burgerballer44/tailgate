<?php

namespace App\Services\Contracts;

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

/**
 * Defines write operations for groups and group-owned resources.
 *
 * Implementations coordinate group updates, membership changes, player
 * management, follows, and policy configuration.
 */
interface GroupCommandInterface
{
    /**
     * Create a new group from validated input.
     *
     * @param  ValidatedGroupData  $data  The normalized group payload.
     * @return Group The created group instance.
     */
    public function create(ValidatedGroupData $data): Group;

    /**
     * Update an existing group.
     *
     * @param  Group  $group  The group to update.
     * @param  ValidatedGroupData  $data  The normalized group payload.
     * @return Group The updated group instance.
     */
    public function update(Group $group, ValidatedGroupData $data): Group;

    /**
     * Update optional prediction policies for a followed season in a group.
     *
     * @param  Group  $group  The group to update.
     * @param  ValidatedGroupPoliciesData  $data  The normalized season policy payload.
     * @return Group The updated group instance.
     */
    public function updatePolicies(Group $group, ValidatedGroupPoliciesData $data): Group;

    /**
     * Update the selected prediction scoring policy for a followed season.
     *
     * @param  Group  $group  The group to update.
     * @param  ValidatedGroupPredictionScoringPolicyData  $data  The normalized season scoring payload.
     * @return Group The updated group instance.
     */
    public function updatePredictionScoringPolicy(Group $group, ValidatedGroupPredictionScoringPolicyData $data): Group;

    /**
     * Sync the explicit seasons followed by a group.
     *
     * @param  Group  $group  The group to update.
     * @param  ValidatedGroupSeasonFollowsData  $data  The normalized season-follow payload.
     * @return Group The updated group instance.
     */
    public function syncSeasonFollows(Group $group, ValidatedGroupSeasonFollowsData $data): Group;

    /**
     * Delete a group.
     *
     * @param  Group  $group  The group to delete.
     */
    public function delete(Group $group): void;

    /**
     * Add a member to a group.
     *
     * @param  Group  $group  The group that will own the member.
     * @param  ValidatedMemberData  $data  The normalized membership payload.
     * @return Member The created member instance.
     */
    public function addMember(Group $group, ValidatedMemberData $data): Member;

    /**
     * Remove a member from a group.
     *
     * @param  Group  $group  The group context for the removal.
     * @param  Member  $member  The member to remove.
     */
    public function removeMember(Group $group, Member $member): void;

    /**
     * Add a player to a member within a group.
     *
     * @param  Group  $group  The group context for the player.
     * @param  Member  $member  The member who owns the player.
     * @param  ValidatedPlayerData  $data  The normalized player payload.
     * @return Player The created player instance.
     */
    public function addPlayer(Group $group, Member $member, ValidatedPlayerData $data): Player;

    /**
     * Remove a player from a member within a group.
     *
     * @param  Group  $group  The group context for the removal.
     * @param  Member  $member  The member who owns the player.
     * @param  Player  $player  The player to remove.
     */
    public function removePlayer(Group $group, Member $member, Player $player): void;

    /**
     * Add a team follow for a group.
     *
     * @param  Group  $group  The group that will follow the team.
     * @param  ValidatedFollowData  $data  The normalized follow payload.
     * @return Follow The created follow instance.
     */
    public function followTeam(Group $group, ValidatedFollowData $data): Follow;

    /**
     * Remove a follow from a group.
     *
     * @param  Group  $group  The group context for the removal.
     * @param  Follow  $follow  The follow to remove.
     */
    public function removeFollow(Group $group, Follow $follow): void;
}
