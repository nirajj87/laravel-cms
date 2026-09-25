<?php

namespace App\Http\Requests\Tenant;

use App\Enums\UserStatus;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.create') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
