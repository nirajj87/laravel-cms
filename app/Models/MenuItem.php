<?php

namespace App\Models;

use App\Support\SafeHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'type',
        'url',
        'target_id',
        'sort_order',
        'enabled',
        'open_new_tab',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'open_new_tab' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function href(Tenant $tenant): string
    {
        return match ($this->type) {
            'home' => route('site.home', ['siteTenant' => $tenant->slug]),
            'login' => route('login'),
            'page' => $this->pageHref($tenant),
            'category' => $this->categoryHref($tenant),
            'content_type' => $this->typeHref($tenant),
            'categories' => route('site.home', ['siteTenant' => $tenant->slug]).'#categories',
            default => SafeHtml::url($this->url) ?? route('site.home', ['siteTenant' => $tenant->slug]),
        };
    }

    private function pageHref(Tenant $tenant): string
    {
        $slug = Page::query()->whereKey($this->target_id)->where('status', 'published')->value('slug');

        return $slug
            ? route('site.pages.show', ['siteTenant' => $tenant->slug, 'page' => $slug])
            : route('site.home', ['siteTenant' => $tenant->slug]);
    }

    private function categoryHref(Tenant $tenant): string
    {
        $slug = Category::query()->whereKey($this->target_id)->value('slug');

        return $slug
            ? route('site.categories.show', ['siteTenant' => $tenant->slug, 'topic' => $slug])
            : route('site.home', ['siteTenant' => $tenant->slug]);
    }

    private function typeHref(Tenant $tenant): string
    {
        $slug = ContentType::query()->whereKey($this->target_id)->where('is_active', true)->value('slug');

        return $slug
            ? route('site.types.show', ['siteTenant' => $tenant->slug, 'typeSlug' => $slug])
            : route('site.home', ['siteTenant' => $tenant->slug]);
    }
}
