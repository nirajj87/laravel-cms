<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Enums\TenantStatus;
use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardStats
{
    /**
     * @return array<string, mixed>
     */
    public function tenant(Tenant $tenant): array
    {
        return Cache::remember('dashboard.tenant.'.$tenant->id, 120, function () use ($tenant) {
            $posts = Post::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id);

            return [
                'posts' => (clone $posts)->count(),
                'published' => (clone $posts)->where('status', PostStatus::Published)->count(),
                'drafts' => (clone $posts)->where('status', PostStatus::Draft)->count(),
                'categories' => Category::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
                'users' => User::query()->forTenant($tenant)->count(),
                'feedback' => Feedback::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
                'storage' => (int) MediaAsset::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->sum('size'),
                'chart' => $this->postSeries(
                    Post::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)
                ),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function platform(): array
    {
        return Cache::remember('dashboard.platform', 120, function () {
            $latest = Backup::query()->latest()->first();

            return [
                'tenants' => Tenant::query()->count(),
                'active_tenants' => Tenant::query()->where('status', TenantStatus::Active)->count(),
                'users' => User::query()->where('is_super_admin', false)->count(),
                'posts' => Post::withoutGlobalScope('tenant')->count(),
                'storage' => (int) MediaAsset::withoutGlobalScope('tenant')->sum('size'),
                'logins' => ActivityLog::query()->where('action', 'auth.login')->with('user:id,name')->latest()->limit(6)->get(),
                'backup' => $latest,
                'chart' => $this->postSeries(Post::withoutGlobalScope('tenant')),
            ];
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Post>  $query
     * @return array{labels: list<string>, values: list<int>}
     */
    private function postSeries($query): array
    {
        $start = Carbon::today()->subDays(6)->startOfDay();
        $rows = (clone $query)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('D');
            $values[] = (int) ($rows[$key] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
