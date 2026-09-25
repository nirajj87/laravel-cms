<?php

namespace App\Http\Requests\Tenant;

use App\Enums\FieldType;
use App\Models\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentTypeFieldRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:120'],
            'key' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/'],
            'type' => ['required', Rule::in(array_column(FieldType::cases(), 'value'))],
            'options' => ['nullable', 'string', 'max:1000'],
            'required' => ['nullable', 'boolean'],
        ];
    }
}
