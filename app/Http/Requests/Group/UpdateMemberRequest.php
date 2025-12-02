<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedMemberData;
use App\Http\Requests\FormRequest;
use App\Models\GroupRole;
use App\Rules\GroupAdminMinimum;
use Illuminate\Validation\Rules\Enum;

class UpdateMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', new Enum(GroupRole::class), new GroupAdminMinimum],
        ];
    }

    /**
     * Get the validated data as a ValidatedMemberData object.
     * This method is used to pass validated member data to the service layer.
     *
     * @return ValidatedMemberData The validated member data transfer object.
     */
    public function toDTO(): ValidatedMemberData
    {
        return ValidatedMemberData::fromArray($this->validated());
    }
}
