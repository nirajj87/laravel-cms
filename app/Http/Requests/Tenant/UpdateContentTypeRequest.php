<?php

namespace App\Http\Requests\Tenant;

use App\Models\ContentType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('contentType');

        return $type instanceof ContentType && $this->user()?->can('update', $type) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'fields' => ['nullable', 'array'],
            'fields.*.label' => ['nullable', 'string', 'max:120'],
            'fields.*.enabled' => ['nullable', 'boolean'],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'fields.*.options' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
