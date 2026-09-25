<?php

namespace App\Jobs;

use App\Mail\WorkspaceMessage;
use App\Models\EmailTemplate;
use App\Models\Tenant;
use App\Support\EmailSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendWorkspaceEmail implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, scalar|null>  $data
     */
    public function __construct(
        public int $tenantId,
        public string $template,
        public string $to,
        public array $data = [],
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        $template = $tenant
            ? EmailTemplate::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('key', $this->template)
                ->where('enabled', true)
                ->first()
            : null;

        if (! $tenant || ! $template || ! filter_var($this->to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $data = array_change_key_case($this->data);
        $data['tenant'] ??= $tenant->name;
        $settings = EmailSettings::settings($tenant, true);
        $from = $settings['from_email'] ?: null;

        Mail::mailer(EmailSettings::mailerName($tenant))->to($this->to)->send(new WorkspaceMessage(
            EmailSettings::render($template->subject, $data),
            EmailSettings::render($template->body, $data),
            $from,
            $settings['from_name'] ?: $tenant->name,
        ));
    }
}
