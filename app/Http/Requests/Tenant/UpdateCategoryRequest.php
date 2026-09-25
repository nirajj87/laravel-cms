<?php

namespace App\Http\Requests\Tenant;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category
            && $this->user()?->can('update', $category) === true;
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
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:160', Rule::unique('categories', 'slug')->where('tenant_id', $tenantId)->ignore($category)],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
            'status' => ['required', Rule::in(array_column(CategoryStatus::cases(), 'value'))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:10240'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $category = $this->route('category');
            $parentId = $this->input('parent_id');

            if (! $category instanceof Category || ! $parentId) {
                return;
            }

            if ((int) $parentId === (int) $category->id || in_array((int) $parentId, $category->descendantIds(), true)) {
                $validator->errors()->add('parent_id', 'Choose a parent that is not this category or one of its children.');
            }
        });
    }
}
