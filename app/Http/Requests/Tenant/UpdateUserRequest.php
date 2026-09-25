<?php

namespace App\Http\Requests\Tenant;

use App\Enums\UserStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.edit') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $member = $this->route('member') ?? $this->route('tenantUser');
        $userId = $member instanceof User ? $member->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('tenant_id', $this->tenant()->id)],
        ];
    }

    private function tenant(): Tenant
    {
        $tenant = $this->route('tenant');

        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        $current = current_tenant() ?? $this->user()?->tenant;
        abort_unless($current, 403);

        return $current;
    }
}
