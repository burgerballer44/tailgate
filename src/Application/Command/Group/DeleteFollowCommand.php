<?php

namespace Tailgate\Application\Command\Group;

class DeleteFollowCommand
{
    private $groupId;
    private $followId;

    public function __construct($groupId, $followId)
    {
        $this->groupId = $groupId;
        $this->followId = $followId;
    }

    public function getGroupId()
    {
        return $this->groupId;
    }

    public function getFollowId()
    {
        return $this->followId;
    }
}
