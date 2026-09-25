<?php

namespace App\Support;

use App\Enums\FieldType;
use App\Models\ContentType;
use App\Models\ContentTypeField;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\Tenant;

class ContentPresenter
{
    /** @var array<int, MediaAsset|null> */
    private array $media = [];

    /**
     * @return array<string, mixed>|null
     */
    public function present(Post $post, ContentTypeField $field): ?array
    {
        if (! $field->enabled || ! $field->type->storesValue() || $field->key === 'title') {
            return null;
        }

        $value = $post->valueFor($field);

        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        return match ($field->type) {
            FieldType::RichText => ['kind' => 'html', 'label' => $field->label, 'html' => $this->sanitize((string) $value)],
            FieldType::LongText => ['kind' => 'text', 'label' => $field->label, 'text' => trim(strip_tags((string) $value))],
            FieldType::Image => $this->imageItem($field, $value),
            FieldType::Gallery => $this->galleryItem($field, $value),
            FieldType::File => $this->fileItem($field, $value),
            FieldType::Button => $this->buttonItem($field, $value),
            FieldType::Tags => ['kind' => 'text', 'label' => $field->label, 'text' => implode(', ', (array) $value)],
            FieldType::Url, FieldType::ExternalLink, FieldType::Email, FieldType::Phone => $this->linkItem($field, (string) $value),
            FieldType::VideoUrl => $this->videoItem($field, (string) $value),
            FieldType::Color => ['kind' => 'color', 'label' => $field->label, 'color' => (string) $value],
            FieldType::Boolean, FieldType::Checkbox => $this->choiceItem($field, $value),
            FieldType::MultiSelect => ['kind' => 'text', 'label' => $field->label, 'text' => implode(', ', (array) $value)],
            FieldType::Price, FieldType::Currency => ['kind' => 'text', 'label' => $field->label, 'text' => number_format((float) $value, 2)],
            FieldType::Discount => ['kind' => 'text', 'label' => $field->label, 'text' => $this->discount((string) $value)],
            FieldType::Rating => ['kind' => 'text', 'label' => $field->label, 'text' => $value.'/5'],
            default => ['kind' => 'text', 'label' => $field->label, 'text' => trim(strip_tags((string) $value))],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function card(Post $post, ContentType $type, Tenant $tenant): array
    {
        $image = null;

        foreach (['thumbnail', 'logo'] as $key) {
            $field = $type->fields->firstWhere('key', $key);

            if ($field?->enabled) {
                $image = $this->imageUrl($post, $field);

                if ($image) {
                    break;
                }
            }
        }

        $excerptField = $type->fields->firstWhere('key', 'short_description');
        $excerpt = $excerptField?->enabled ? $this->scalar($post, $excerptField) : null;

        if ($excerpt === null || $excerpt === '') {
            $description = $type->fields->first(fn (ContentTypeField $field) => $field->enabled && $field->key === 'description');
            $raw = $description ? $this->scalar($post, $description) : null;
            $excerpt = $raw ? mb_strimwidth($raw, 0, 140, '…') : null;
        }

        $author = $type->fields->first(fn (ContentTypeField $field) => $field->enabled && $field->key === 'author');
        $price = $type->fields->first(fn (ContentTypeField $field) => $field->enabled && in_array($field->type, [FieldType::Price, FieldType::Currency], true));
        $discount = $type->fields->first(fn (ContentTypeField $field) => $field->enabled && $field->type === FieldType::Discount);
        $buttonField = $type->fields->first(fn (ContentTypeField $field) => $field->enabled && $field->type === FieldType::Button);
        $button = $buttonField ? $post->valueFor($buttonField) : null;
        $linkField = $type->fields->first(fn (ContentTypeField $field) => $field->enabled && in_array($field->type, [FieldType::ExternalLink, FieldType::Url], true));
        $external = $linkField ? $post->valueFor($linkField) : null;
        $detail = $type->hasDetailPage();
        $detailUrl = $detail
            ? route('site.content.show', ['siteTenant' => $tenant->slug, 'entry' => $post->slug])
            : null;
        $buttonUrl = is_array($button) ? ($button['url'] ?? null) : (is_string($external) ? $external : null);

        return [
            'title' => $post->title,
            'mark' => $this->mark($post->title),
            'category' => $post->relationLoaded('categories') ? $post->categories->first()?->name : null,
            'image' => $image,
            'excerpt' => $excerpt,
            'author' => $author ? $this->scalar($post, $author) : null,
            'price' => $price ? $this->scalar($post, $price) : null,
            'discount' => $discount ? $this->scalar($post, $discount) : null,
            'rating' => $this->rating($post, $type),
            'href' => $detailUrl ?: $buttonUrl,
            'label' => is_array($button) ? ($button['label'] ?? 'Open') : 'View',
            'button_url' => $buttonUrl,
            'button_label' => is_array($button) ? ($button['label'] ?? $buttonField?->label ?? 'Open') : 'Open',
            'button_style' => SiteTheme::buttonStyle(is_array($button) ? ($button['style'] ?? null) : null),
            'button_target' => SiteTheme::target(is_array($button) ? ($button['new_tab'] ?? false) : false),
        ];
    }

    private function mark(string $title): string
    {
        $letters = '';

        foreach (preg_split('/\s+/', trim($title)) ?: [] as $part) {
            if ($part === '') {
                continue;
            }

            $letters .= mb_strtoupper(mb_substr($part, 0, 1));

            if (mb_strlen($letters) >= 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : '•';
    }

    public function imageUrl(Post $post, ContentTypeField $field): ?string
    {
        $value = $post->valueFor($field);

        return is_numeric($value) ? $this->urlForMedia((int) $value) : null;
    }

    private function scalar(Post $post, ContentTypeField $field): ?string
    {
        $value = $post->valueFor($field);

        if (! is_scalar($value) || $value === '') {
            return null;
        }

        return match ($field->type) {
            FieldType::Price, FieldType::Currency => number_format((float) $value, 2),
            FieldType::Discount => $this->discount((string) $value),
            default => trim(strip_tags((string) $value)),
        };
    }

    private function rating(Post $post, ContentType $type): ?string
    {
        $field = $type->fields->first(fn (ContentTypeField $field) => $field->enabled && $field->type === FieldType::Rating);

        if (! $field) {
            return null;
        }

        $value = $post->valueFor($field);

        return is_numeric($value) ? $value.'/5' : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function imageItem(ContentTypeField $field, mixed $value): ?array
    {
        $url = is_numeric($value) ? $this->urlForMedia((int) $value) : null;

        if (! $url) {
            return null;
        }

        $asset = $this->findMedia((int) $value);

        return [
            'kind' => 'image',
            'label' => $field->label,
            'url' => $url,
            'alt' => $asset?->alt ?: $field->label,
            'caption' => $asset?->caption,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function galleryItem(ContentTypeField $field, mixed $value): ?array
    {
        $links = [];

        foreach ((array) $value as $id) {
            if (! is_numeric($id)) {
                continue;
            }

            $url = $this->urlForMedia((int) $id);

            if ($url) {
                $asset = $this->findMedia((int) $id);
                $links[] = ['url' => $url, 'alt' => $asset?->alt ?: $field->label];
            }
        }

        return $links === [] ? null : ['kind' => 'gallery', 'label' => $field->label, 'links' => $links];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fileItem(ContentTypeField $field, mixed $value): ?array
    {
        if (! is_numeric($value)) {
            return null;
        }

        $asset = $this->findMedia((int) $value);

        if (! $asset) {
            return null;
        }

        return [
            'kind' => 'file',
            'label' => $field->label,
            'url' => $asset->url(),
            'text' => $asset->original_name,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buttonItem(ContentTypeField $field, mixed $value): ?array
    {
        if (! is_array($value) || empty($value['url'])) {
            return null;
        }

        return [
            'kind' => 'button',
            'label' => $field->label,
            'url' => $value['url'],
            'text' => $value['label'] ?? $field->label,
            'style' => SiteTheme::buttonStyle($value['style'] ?? null),
            'target' => SiteTheme::target($value['new_tab'] ?? false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function linkItem(ContentTypeField $field, string $value): array
    {
        $href = match ($field->type) {
            FieldType::Email => 'mailto:'.$value,
            FieldType::Phone => 'tel:'.$value,
            default => $value,
        };

        return ['kind' => 'link', 'label' => $field->label, 'url' => $href, 'text' => $value];
    }

    /**
     * @return array<string, mixed>
     */
    private function videoItem(ContentTypeField $field, string $value): array
    {
        $embed = $this->embed($value);

        if ($embed) {
            return ['kind' => 'video', 'label' => $field->label, 'embed' => $embed];
        }

        return ['kind' => 'link', 'label' => $field->label, 'url' => $value, 'text' => $value];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function choiceItem(ContentTypeField $field, mixed $value): ?array
    {
        if (is_array($value)) {
            return $value === [] ? null : ['kind' => 'text', 'label' => $field->label, 'text' => implode(', ', $value)];
        }

        if ((string) $value !== '1') {
            return null;
        }

        return ['kind' => 'text', 'label' => $field->label, 'text' => 'Yes'];
    }

    private function discount(string $value): string
    {
        $number = rtrim(rtrim(number_format((float) $value, 2), '0'), '.');

        return $number.'%';
    }

    private function embed(string $url): ?string
    {
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $url, $matches)) {
            return 'https://www.youtube.com/embed/'.$matches[1];
        }

        if (preg_match('~vimeo\.com/(\d+)~', $url, $matches)) {
            return 'https://player.vimeo.com/video/'.$matches[1];
        }

        return null;
    }

    private function urlForMedia(int $id): ?string
    {
        return $this->findMedia($id)?->url();
    }

    private function findMedia(int $id): ?MediaAsset
    {
        if (! array_key_exists($id, $this->media)) {
            $this->media[$id] = MediaAsset::query()->find($id);
        }

        return $this->media[$id];
    }

    private function sanitize(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><em><ul><ol><li><a><h2><h3><blockquote>');
        $html = preg_replace('/\s+on\w+\s*=\s*(["\']).*?\1/i', '', $html) ?? $html;

        return preg_replace('/href\s*=\s*(["\'])\s*javascript:[^"\']*\1/i', 'href="#"', $html) ?? $html;
    }
}
