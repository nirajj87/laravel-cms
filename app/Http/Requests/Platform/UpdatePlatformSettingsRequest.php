<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match ($this->route('group')) {
            'general' => [
                'platform_name' => ['required', 'string', 'max:120'],
                'tagline' => ['nullable', 'string', 'max:180'],
            ],
            'email' => [
                'mailer' => ['required', Rule::in(['log', 'smtp'])],
                'host' => ['nullable', 'string', 'max:255', 'required_if:mailer,smtp'],
                'port' => ['nullable', 'integer', 'min:1', 'max:65535', 'required_if:mailer,smtp'],
                'username' => ['nullable', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'max:255'],
                'from_address' => ['required', 'email', 'max:255'],
                'from_name' => ['required', 'string', 'max:120'],
            ],
            'seo' => [
                'default_title' => ['nullable', 'string', 'max:180'],
                'default_description' => ['nullable', 'string', 'max:300'],
                'default_keywords' => ['nullable', 'string', 'max:255'],
                'robots' => ['required', 'string', 'max:60'],
            ],
            'system' => [
                'support_email' => ['nullable', 'email', 'max:255'],
                'default_timezone' => ['required', 'timezone'],
                'backup_retention' => ['required', 'integer', 'min:1', 'max:100'],
            ],
            default => abort(404),
        };
    }
}
