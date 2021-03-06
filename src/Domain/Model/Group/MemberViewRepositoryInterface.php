<?php

namespace Tailgate\Domain\Model\Group;

use Tailgate\Domain\Model\User\UserId;
use Tailgate\Domain\Model\Group\GroupId;
use Tailgate\Domain\Model\Group\MemberId;

interface MemberViewRepositoryInterface
{
    public function get(MemberId $id);
    public function getAllByGroup(GroupId $id);
    public function getAllByUser(UserId $id);
}
