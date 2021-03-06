<?php

namespace Tailgate\Domain\Model\Group;

class GroupView
{
    private $groupId;
    private $name;
    private $inviteCode;
    private $ownerId;
    private $follow;
    private $members = [];
    private $players = [];
    private $scores = [];

    public function __construct($groupId, $name, $inviteCode, $ownerId)
    {
        $this->groupId = $groupId;
        $this->name = $name;
        $this->inviteCode = $inviteCode;
        $this->ownerId = $ownerId;
    }

    public function getGroupId()
    {
        return $this->groupId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getInviteCode()
    {
        return $this->inviteCode;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function getFollow()
    {
        return $this->follow;
    }

    public function getMembers()
    {
        return $this->members;
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function getScores()
    {
        return $this->scores;
    }

    public function addFollowView($followView)
    {
        $this->follow = $followView;
    }

    public function addMemberViews($memberView)
    {
        if (is_array($memberView)) {
            $this->members = $memberView;
        } else {
            $this->members[] = $memberView;
        }
    }

    public function addPlayerViews($playerView)
    {
        if (is_array($playerView)) {
            $this->players = $playerView;
        } else {
            $this->players[] = $playerView;
        }
    }

    public function addScoreViews($scoreView)
    {
        if (is_array($scoreView)) {
            $this->scores = $scoreView;
        } else {
            $this->scores[] = $scoreView;
        }
    }
}
