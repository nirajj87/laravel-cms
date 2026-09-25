<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('media.create') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480'],
            'folder_id' => ['nullable', 'integer'],
            'alt' => ['nullable', 'string', 'max:180'],
            'caption' => ['nullable', 'string', 'max:500'],
        ];
    }
}
