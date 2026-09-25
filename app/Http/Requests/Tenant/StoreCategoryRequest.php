<?php

namespace App\Http\Requests\Tenant;

use App\Enums\CategoryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('categories.create') === true;
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->filled('slug') ? (string) $this->input('slug') : Str::slug((string) $this->input('name'));

        $this->merge([
            'slug' => Str::slug($slug),
            'parent_id' => $this->input('parent_id') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = current_tenant()?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:160', Rule::unique('categories', 'slug')->where('tenant_id', $tenantId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
            'status' => ['required', Rule::in(array_column(CategoryStatus::cases(), 'value'))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:10240'],
        ];
    }
}
