<?php

namespace App\Http\Requests\Traits;

use App\Models\GroupRole;
use App\Models\MemberStatus;
use App\Rules\GroupAdminMinimum;
use App\Rules\GroupMemberLimit;
use App\Rules\MustNotBeAMember;
use Illuminate\Validation\Rules\Enum;

trait MemberValidationRulesTrait
{
    /**
     * Get the base validation rules for member fields.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    protected function baseMemberRules(): array
    {
        return [
            'role' => ['required', new Enum(GroupRole::class)],
            'status' => ['nullable', new Enum(MemberStatus::class)],
        ];
    }

    /**
     * Get the validation rules for creating a member.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    protected function createMemberRules(): array
    {
        return array_merge($this->baseMemberRules(), [
            'user_id' => ['required', 'exists:users,id', new MustNotBeAMember, new GroupMemberLimit],
            'status' => ['required', new Enum(MemberStatus::class)],
        ]);
    }

    /**
     * Get the validation rules for updating a member.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    protected function updateMemberRules(): array
    {
        return array_merge($this->baseMemberRules(), [
            'role' => ['required', new Enum(GroupRole::class), new GroupAdminMinimum],
        ]);
    }
}