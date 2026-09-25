<?php

namespace App\Http\Requests\Tenant;

use App\Models\MediaAsset;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $asset = $this->route('mediaAsset');

        return $asset instanceof MediaAsset && $this->user()?->can('update', $asset) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'folder_id' => ['nullable', 'integer'],
            'alt' => ['nullable', 'string', 'max:180'],
            'caption' => ['nullable', 'string', 'max:500'],
        ];
    }
}
