<?php

namespace App\Http\Requests\Platform;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => strtolower((string) $this->input('slug')),
            'domain' => $this->filled('domain') ? strtolower((string) $this->input('domain')) : null,
            'subdomain' => $this->filled('subdomain') ? strtolower((string) $this->input('subdomain')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->route('tenant');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'domain' => ['nullable', 'string', 'max:255', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', Rule::unique('tenants', 'domain')->ignore($tenant->id), Rule::notIn([strtolower((string) config('tenancy.base_domain'))])],
            'subdomain' => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('tenants', 'subdomain')->ignore($tenant->id), Rule::notIn(config('tenancy.reserved_subdomains'))],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(TenantStatus::class)],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:512'],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', 'exists:modules,slug'],
        ];
    }
}
