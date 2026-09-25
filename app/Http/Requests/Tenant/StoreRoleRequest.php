<?php

namespace App\Http\Requests\Tenant;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('roles.create') === true;
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->filled('slug') ? (string) $this->input('slug') : Str::slug((string) $this->input('name'));

        $this->merge(['slug' => strtolower($slug)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('roles', 'slug')->where('tenant_id', $this->tenant()->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,slug'],
        ];
    }

    private function tenant(): Tenant
    {
        $current = current_tenant() ?? $this->user()?->tenant;
        abort_unless($current, 403);

        return $current;
    }
}
