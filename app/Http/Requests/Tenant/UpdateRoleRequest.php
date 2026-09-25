<?php

namespace App\Http\Requests\Tenant;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('roles.edit') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,slug'],
            'slug' => [
                Rule::excludeIf($role->is_system),
                'sometimes',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('roles', 'slug')->where('tenant_id', $this->tenant()->id)->ignore($role->id),
            ],
        ];
    }

    private function tenant(): Tenant
    {
        $current = current_tenant() ?? $this->user()?->tenant;
        abort_unless($current, 403);

        return $current;
    }
}
