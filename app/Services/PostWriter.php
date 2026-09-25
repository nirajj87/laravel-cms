<?php

namespace App\Services;

use App\Enums\FieldType;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\ContentTypeField;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\PostFieldValue;
use App\Models\User;
use App\Support\SiteTheme;
use App\Support\TenantSlug;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostWriter
{
    public function __construct(
        private readonly MediaLibrary $media,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $uploads
     */
    public function create(User $actor, array $data, array $uploads = []): Post
    {
        $tenant = current_tenant();
        abort_unless($tenant, 404);

        $type = ContentType::query()->with('fields')->whereKey($data['content_type_id'] ?? 0)->firstOrFail();
        $this->assertPublish($actor, PostStatus::from($data['status']));

        return DB::transaction(function () use ($actor, $data, $uploads, $tenant, $type) {
            $post = Post::query()->create([
                'tenant_id' => $tenant->id,
                'content_type_id' => $type->id,
                'user_id' => $actor->id,
                'title' => $data['title'],
                'slug' => TenantSlug::make('posts', $tenant->id, $data['title']),
                'status' => PostStatus::Draft,
            ]);

            $this->fill($post, $type, $actor, $data, $uploads);
            $this->activity->log('post.created', 'Created '.$post->title, $post, [
                'status' => $post->status->value,
                'type' => $type->slug,
            ], $tenant->id);

            return $post->fresh(['contentType.fields', 'values', 'categories']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $uploads
     */
    public function update(Post $post, User $actor, array $data, array $uploads = []): Post
    {
        $type = $post->contentType()->with('fields')->firstOrFail();
        $this->assertPublish($actor, PostStatus::from($data['status']));

        return DB::transaction(function () use ($post, $actor, $data, $uploads, $type) {
            $post->title = $data['title'];

            if (! empty($data['slug'])) {
                $post->slug = TenantSlug::make('posts', (int) $post->tenant_id, $data['slug'], $post->id);
            }

            $this->fill($post, $type, $actor, $data, $uploads);
            $this->activity->log('post.updated', 'Updated '.$post->title, $post, [
                'status' => $post->status->value,
            ], $post->tenant_id);

            return $post->fresh(['contentType.fields', 'values', 'categories']);
        });
    }

    public function delete(Post $post): void
    {
        $this->activity->log('post.deleted', 'Deleted '.$post->title, $post, [], $post->tenant_id);
        $post->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $uploads
     */
    private function fill(Post $post, ContentType $type, User $actor, array $data, array $uploads): void
    {
        $errors = [];
        $status = PostStatus::from($data['status']);

        foreach ($type->fields as $field) {
            if (! $field->enabled || $field->key === 'title' || ! $field->type->storesValue()) {
                continue;
            }

            if ($field->required && ! $this->present($field, $data, $uploads)) {
                $errors['fields.'.$field->key] = $field->label.' is required.';

                continue;
            }

            $formatError = $this->formatError($field, $data);

            if ($formatError) {
                $errors['fields.'.$field->key] = $formatError;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $post->status = $status;

        if ($status === PostStatus::Published) {
            $post->published_at ??= now();
            $post->scheduled_at = null;
        } elseif ($status === PostStatus::Scheduled) {
            $post->scheduled_at = $data['scheduled_at'];
            $post->published_at = null;
        } else {
            $post->scheduled_at = null;
        }

        $post->save();

        foreach ($type->fields as $field) {
            if (! $field->enabled || $field->key === 'title' || ! $field->type->storesValue()) {
                continue;
            }

            $stored = $this->normalize($field, $actor, $data, $uploads);

            $existing = PostFieldValue::query()
                ->where('post_id', $post->id)
                ->where('field_id', $field->id)
                ->first();

            if ($stored === null) {
                $existing?->delete();

                continue;
            }

            PostFieldValue::query()->updateOrCreate(
                ['post_id' => $post->id, 'field_id' => $field->id],
                ['value' => $stored],
            );
        }

        if ($type->categoryFieldEnabled()) {
            $ids = collect($data['categories'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $valid = Category::query()->whereIn('id', $ids)->pluck('id')->all();
            $post->categories()->sync($valid);
        }
    }

    private function assertPublish(User $actor, PostStatus $status): void
    {
        if (in_array($status, [PostStatus::Published, PostStatus::Scheduled], true) && ! $actor->hasPermission('posts.publish')) {
            throw ValidationException::withMessages([
                'status' => 'Publishing requires the posts.publish permission.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $uploads
     */
    private function present(ContentTypeField $field, array $data, array $uploads): bool
    {
        $upload = $uploads[$field->key] ?? null;

        if ($upload instanceof UploadedFile || (is_array($upload) && $this->hasFile($upload))) {
            return true;
        }

        if (! empty($data['remove'][$field->key])) {
            return false;
        }

        $value = $data['fields'][$field->key] ?? null;

        if ($field->type === FieldType::Button) {
            return trim((string) (is_array($value) ? ($value['url'] ?? '') : '')) !== '';
        }

        if ($field->type === FieldType::Boolean || ($field->type === FieldType::Checkbox && empty($field->options))) {
            return true;
        }

        return ! $this->blank($value);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatError(ContentTypeField $field, array $data): ?string
    {
        $value = $data['fields'][$field->key] ?? null;

        if ($this->blank($value)) {
            return null;
        }

        $text = is_string($value) ? trim($value) : null;

        return match ($field->type) {
            FieldType::Email => filter_var($text, FILTER_VALIDATE_EMAIL) ? null : $field->label.' must be an email address.',
            FieldType::Url, FieldType::VideoUrl, FieldType::ExternalLink => filter_var($text, FILTER_VALIDATE_URL) ? null : $field->label.' must be a valid URL.',
            FieldType::Button => $this->buttonError($field, $value),
            FieldType::Number, FieldType::Currency, FieldType::Price, FieldType::Discount => is_numeric($text) ? null : $field->label.' must be a number.',
            FieldType::Rating => is_numeric($text) && (float) $text >= 0 && (float) $text <= 5 ? null : $field->label.' must be between 0 and 5.',
            FieldType::Date, FieldType::DateTime => strtotime((string) $text) ? null : $field->label.' must be a valid date.',
            FieldType::Color => preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', (string) $text) ? null : $field->label.' must be a hex color.',
            FieldType::Phone => strlen((string) $text) <= 40 ? null : $field->label.' is too long.',
            FieldType::Select, FieldType::Radio => $this->optionError($field, [$text]),
            FieldType::MultiSelect => $this->optionError($field, is_array($value) ? $value : []),
            FieldType::Checkbox => filled($field->options) ? $this->optionError($field, is_array($value) ? $value : []) : null,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $uploads
     */
    private function normalize(ContentTypeField $field, User $actor, array $data, array $uploads): ?string
    {
        $removing = ! empty($data['remove'][$field->key]);
        $value = $data['fields'][$field->key] ?? null;

        if (in_array($field->type, [FieldType::Image, FieldType::File], true)) {
            $upload = $uploads[$field->key] ?? null;

            if ($upload instanceof UploadedFile) {
                $kind = $field->type === FieldType::Image ? 'image' : null;
                $asset = $this->media->store($upload, null, $actor, $kind, $field->label);

                return (string) $asset->id;
            }

            if ($removing) {
                return null;
            }

            return $this->mediaId($value);
        }

        if ($field->type === FieldType::Gallery) {
            $ids = [];

            if (! $removing) {
                foreach ((array) $value as $id) {
                    $mediaId = $this->mediaId($id);

                    if ($mediaId) {
                        $ids[] = (int) $mediaId;
                    }
                }
            }

            $files = $uploads[$field->key] ?? [];
            $files = $files instanceof UploadedFile ? [$files] : (array) $files;

            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $ids[] = $this->media->store($file, null, $actor, 'image', $field->label)->id;
                }
            }

            $ids = array_values(array_unique($ids));

            return $ids === [] ? null : json_encode($ids);
        }

        if ($field->type === FieldType::Tags) {
            $raw = is_array($value) ? $value : explode(',', (string) $value);
            $items = array_values(array_unique(array_filter(array_map(
                fn ($item) => trim((string) $item),
                $raw,
            ))));

            return $items === [] ? null : json_encode($items);
        }

        if ($field->type === FieldType::Button) {
            $url = trim((string) (is_array($value) ? ($value['url'] ?? '') : ''));
            $label = trim((string) (is_array($value) ? ($value['label'] ?? '') : ''));

            if ($url === '') {
                return null;
            }

            return json_encode([
                'url' => $url,
                'label' => $label !== '' ? $label : $field->label,
                'style' => SiteTheme::buttonStyle(is_array($value) ? ($value['style'] ?? null) : null),
                'new_tab' => filter_var(is_array($value) ? ($value['new_tab'] ?? false) : false, FILTER_VALIDATE_BOOL),
            ]);
        }

        if ($field->type === FieldType::MultiSelect || ($field->type === FieldType::Checkbox && filled($field->options))) {
            $items = array_values(array_filter(array_map(
                fn ($item) => trim((string) $item),
                (array) $value,
            )));

            return $items === [] ? null : json_encode($items);
        }

        if ($field->type === FieldType::Boolean || $field->type === FieldType::Checkbox) {
            return ! empty($value) ? '1' : '0';
        }

        if ($field->type === FieldType::RichText) {
            $html = $this->sanitizeHtml((string) $value);

            return trim(strip_tags($html)) === '' ? null : $html;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function buttonError(ContentTypeField $field, mixed $value): ?string
    {
        $url = trim((string) (is_array($value) ? ($value['url'] ?? '') : ''));

        if ($url === '') {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? null : $field->label.' needs a valid URL.';
    }

    /**
     * @param  list<mixed>  $values
     */
    private function optionError(ContentTypeField $field, array $values): ?string
    {
        $allowed = $field->options ?? [];

        if ($allowed === []) {
            return null;
        }

        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (! in_array((string) $value, $allowed, true)) {
                return $field->label.' has an unknown option.';
            }
        }

        return null;
    }

    private function mediaId(mixed $value): ?string
    {
        if (! is_numeric($value)) {
            return null;
        }

        return MediaAsset::query()->whereKey((int) $value)->exists() ? (string) (int) $value : null;
    }

    private function blank(mixed $value): bool
    {
        if (is_array($value)) {
            return array_filter($value, fn ($item) => $item !== null && $item !== '') === [];
        }

        return $value === null || trim((string) $value) === '';
    }

    /**
     * @param  array<mixed>  $files
     */
    private function hasFile(array $files): bool
    {
        foreach ($files as $file) {
            if ($file instanceof UploadedFile || (is_array($file) && $this->hasFile($file))) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><em><ul><ol><li><a><h2><h3><blockquote>');
        $html = preg_replace('/\s+on\w+\s*=\s*(["\']).*?\1/i', '', $html) ?? $html;
        $html = preg_replace('/href\s*=\s*(["\'])\s*javascript:[^"\']*\1/i', 'href="#"', $html) ?? $html;

        return $html;
    }
}
