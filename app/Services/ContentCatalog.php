<?php

namespace App\Services;

use App\Enums\CategoryStatus;
use App\Enums\FieldType;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\ContentTypeField;
use App\Models\Post;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantSlug;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContentCatalog
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly ModuleRegistry $modules,
        private readonly PostWriter $posts,
    ) {}

    public function ensureStarterContent(Tenant $tenant): void
    {
        if ($tenant->hasModule('posts')) {
            $exists = ContentType::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->exists();

            if (! $exists) {
                foreach (config('content.starters', []) as $key) {
                    $preset = config('content.presets.'.$key);

                    if (is_array($preset)) {
                        $this->createType($tenant, $preset['name'], $key);
                    }
                }
            }
        }

        if ($tenant->hasModule('categories')) {
            $exists = Category::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->exists();

            if (! $exists) {
                $this->seedCategories($tenant);
            }
        }

        app(SiteCatalog::class)->ensure($tenant);
        app(EmailCatalog::class)->ensure($tenant);
    }

    public function createType(Tenant $tenant, string $name, ?string $presetKey = null, ?string $description = null): ContentType
    {
        $preset = $presetKey ? config('content.presets.'.$presetKey) : null;
        $fields = is_array($preset) ? $preset['fields'] : config('content.presets.custom.fields');
        $slugSource = is_array($preset) ? ($preset['slug'] ?? $name) : $name;

        $type = ContentType::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => TenantSlug::make('content_types', $tenant->id, $slugSource),
            'description' => $description ?? (is_array($preset) ? ($preset['description'] ?? null) : null),
            'is_active' => true,
        ]);

        foreach ($fields as $index => $field) {
            $type->fields()->create([
                'key' => $field['key'],
                'label' => $field['label'],
                'type' => $field['type'],
                'enabled' => $field['enabled'] ?? true,
                'required' => $field['required'] ?? false,
                'is_system' => $field['system'] ?? false,
                'sort_order' => $index,
                'options' => $field['options'] ?? null,
                'searchable' => FieldType::from($field['type'])->isSearchable(),
            ]);
        }

        $this->activity->log('content_type.created', 'Created content type '.$type->name, $type, [], $tenant->id);

        return $type->load('fields');
    }

    public function addField(ContentType $type, array $data): ContentTypeField
    {
        $fieldType = FieldType::from($data['type']);
        $key = match ($fieldType) {
            FieldType::Category => 'category',
            FieldType::DetailPage => 'detail_page',
            default => Str::slug((string) ($data['key'] ?: $data['label']), '_'),
        };

        if ($key === '' || $key === 'title' || $type->fields()->where('key', $key)->exists()) {
            throw ValidationException::withMessages(['key' => 'Choose a field key that is not already used.']);
        }

        $options = $this->options($data['options'] ?? null);

        if ($fieldType->needsOptions() && $options === []) {
            throw ValidationException::withMessages(['options' => 'Add at least one option.']);
        }

        return $type->fields()->create([
            'key' => $key,
            'label' => $data['label'],
            'type' => $fieldType,
            'enabled' => true,
            'required' => (bool) ($data['required'] ?? false),
            'is_system' => false,
            'sort_order' => ((int) $type->fields()->max('sort_order')) + 1,
            'options' => $options ?: null,
            'searchable' => $fieldType->isSearchable(),
        ]);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $payload
     */
    public function syncFields(ContentType $type, array $payload): void
    {
        foreach ($type->fields as $field) {
            $input = $payload[$field->id] ?? [];
            $field->label = trim((string) ($input['label'] ?? $field->label)) ?: $field->label;
            $field->enabled = $field->is_system ? true : (bool) ($input['enabled'] ?? false);
            $field->required = $field->key === 'title' ? true : (bool) ($input['required'] ?? false);
            $field->sort_order = (int) ($input['sort_order'] ?? $field->sort_order);

            if ($field->type->needsOptions() || $field->type === FieldType::Checkbox) {
                $options = $this->options($input['options'] ?? null);

                if ($field->type->needsOptions() && $options === []) {
                    throw ValidationException::withMessages([
                        'fields.'.$field->id.'.options' => $field->label.' needs at least one option.',
                    ]);
                }

                $field->options = $options ?: null;
            }

            $field->save();
        }
    }

    public function grantTemplatePermissions(Tenant $tenant): void
    {
        foreach (array_keys(config('roles.templates', [])) as $slug) {
            $role = Role::query()->where('tenant_id', $tenant->id)->where('slug', $slug)->first();

            if (! $role || ! $role->is_system) {
                continue;
            }

            $ids = $this->modules->permissionsForTemplate($slug, $tenant)->pluck('id')->all();
            $role->permissions()->syncWithoutDetaching($ids);
        }

        User::query()->forTenant($tenant)->each(fn (User $user) => $user->forgetPermissionCache());
    }

    public function seedSamplePosts(Tenant $tenant): void
    {
        if (Post::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $owner = User::query()->where('tenant_id', $tenant->id)->orderBy('id')->first();

        if (! $owner) {
            return;
        }

        $book = $this->type($tenant, 'book');
        $tool = $this->type($tenant, 'ai-tool');
        $technology = $this->categoryId($tenant, 'technology');
        $aiTools = $this->categoryId($tenant, 'ai-tools');

        if ($book) {
            $this->posts->create($owner, [
                'content_type_id' => $book->id,
                'title' => 'The Calm Machine',
                'status' => 'published',
                'categories' => array_filter([$technology]),
                'fields' => [
                    'author' => 'Northwind Press',
                    'price' => '24.00',
                    'discount' => '10',
                    'rating' => '4',
                    'short_description' => 'A field guide to practical artificial intelligence.',
                    'description' => '<p>A sample book stored as structured content.</p>',
                    'buy_now' => ['url' => 'https://example.com/books/calm-machine', 'label' => 'Buy now'],
                ],
            ]);
        }

        if ($tool) {
            $this->posts->create($owner, [
                'content_type_id' => $tool->id,
                'title' => 'Northwind Writer',
                'status' => 'published',
                'categories' => array_filter([$aiTools]),
                'fields' => [
                    'short_description' => 'A sample writing tool for the workspace.',
                    'pricing' => 'Free',
                    'rating' => '5',
                    'website_url' => 'https://example.com/tools/writer',
                    'use_tool' => ['url' => 'https://example.com/tools/writer', 'label' => 'Use tool'],
                ],
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public function options(?string $raw): array
    {
        return collect(explode(',', (string) $raw))
            ->map(fn ($option) => trim($option))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function seedCategories(Tenant $tenant): void
    {
        foreach (config('content.category_tree', []) as $parentName => $children) {
            $parent = $this->createCategory($tenant, (string) $parentName, null, 0);

            foreach (array_values($children) as $index => $child) {
                $this->createCategory($tenant, (string) $child, $parent->id, $index);
            }
        }

        $this->activity->log('category.seeded', 'Seeded starter categories', null, [], $tenant->id);
    }

    private function createCategory(Tenant $tenant, string $name, ?int $parentId, int $sort): Category
    {
        return Category::query()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => TenantSlug::make('categories', $tenant->id, $name),
            'status' => CategoryStatus::Active,
            'sort_order' => $sort,
        ]);
    }

    private function type(Tenant $tenant, string $slug): ?ContentType
    {
        return ContentType::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->first();
    }

    private function categoryId(Tenant $tenant, string $slug): ?int
    {
        $id = Category::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->value('id');

        return $id ? (int) $id : null;
    }
}
