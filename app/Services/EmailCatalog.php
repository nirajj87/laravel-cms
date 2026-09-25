<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\Tenant;

class EmailCatalog
{
    public function ensure(Tenant $tenant): void
    {
        foreach (config('growth.email_templates', []) as $key => $template) {
            EmailTemplate::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'key' => $key],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'enabled' => true,
                ],
            );
        }
    }
}
