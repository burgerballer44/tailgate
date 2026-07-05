<?php

namespace App\Http\Requests\Traits;

use App\Models\GroupRole;
use App\Models\MemberStatus;
use App\Rules\GroupAdminMinimum;
use App\Rules\GroupMemberLimit;
use App\Rules\MustNotBeAMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;

trait MemberValidationRulesTrait
{
    /**
     * Define base validation rules for group member data.
     *
     * Validates that the member role is valid and, if provided, the status is a valid enum value.
     *
     * @return array<string, ValidationRule|array|string> The base member field validation rules.
     */
    protected function baseMemberRules(): array
    {
        return [
            'role' => ['required', new Enum(GroupRole::class)],
            'status' => ['nullable', new Enum(MemberStatus::class)],
        ];
    }

    /**
     * Define validation rules for creating a group member.
     *
     * Ensures the user exists, is not already a member of the group, and the group has not
     * reached its member limit. Status is required when creating a member.
     *
     * @return array<string, ValidationRule|array|string> The member creation validation rules.
     */
    protected function createMemberRules(): array
    {
        return array_merge($this->baseMemberRules(), [
            'user_id' => ['required', 'exists:users,id', new MustNotBeAMember, new GroupMemberLimit],
            'status' => ['required', new Enum(MemberStatus::class)],
        ]);
    }

    /**
     * Define validation rules for updating a group member.
     *
     * Allows changing a member's role while enforcing a minimum admin requirement to prevent
     * removing all administrators from the group.
     *
     * @return array<string, ValidationRule|array|string> The member update validation rules.
     */
    protected function updateMemberRules(): array
    {
        return array_merge($this->baseMemberRules(), [
            'role' => ['required', new Enum(GroupRole::class), new GroupAdminMinimum],
        ]);
    }
}
