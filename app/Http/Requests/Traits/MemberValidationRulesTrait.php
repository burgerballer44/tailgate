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
     * Defines shared validation rules for  fields.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function baseMemberRules(): array
    {
        return [
            'role' => ['required', new Enum(GroupRole::class)],
            'status' => ['nullable', new Enum(MemberStatus::class)],
        ];
    }

    /**
     * Defines validation rules used when creating a .
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function createMemberRules(): array
    {
        return array_merge($this->baseMemberRules(), [
            'user_id' => ['required', 'exists:users,id', new MustNotBeAMember, new GroupMemberLimit],
            'status' => ['required', new Enum(MemberStatus::class)],
        ]);
    }

    /**
     * Defines validation rules used when updating a .
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function updateMemberRules(): array
    {
        return array_merge($this->baseMemberRules(), [
            'role' => ['required', new Enum(GroupRole::class), new GroupAdminMinimum],
        ]);
    }
}
