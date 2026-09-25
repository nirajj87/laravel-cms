<?php

namespace App\Http\Requests\Tenant;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('posts.create') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = current_tenant()?->id;

        return [
            'content_type_id' => ['required', 'integer', Rule::exists('content_types', 'id')->where('tenant_id', $tenantId)],
            'title' => ['required', 'string', 'max:180'],
            'status' => ['required', Rule::in(array_column(PostStatus::cases(), 'value'))],
            'scheduled_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
        ];
    }
}
