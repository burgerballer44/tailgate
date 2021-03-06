<?php

namespace Tailgate\Infrastructure\Service\DataTransformer;

use Tailgate\Domain\Service\DataTransformer\FollowDataTransformerInterface;
use Tailgate\Domain\Model\Group\FollowView;

class FollowViewArrayDataTransformer implements FollowDataTransformerInterface
{
    public function read(FollowView $followView)
    {
        return [
            'groupId' => $followView->getGroupId(),
            'followId' => $followView->getFollowId(),
            'teamId' => $followView->getTeamId(),
            'seasonId' => $followView->getSeasonId(),
            'groupName' => $followView->getGroupName(),
            'teamDesignation' => $followView->getTeamDesignation(),
            'teamMascot' => $followView->getTeamMascot(),
            'seasonName' => $followView->getSeasonName()
        ];
    }
}
