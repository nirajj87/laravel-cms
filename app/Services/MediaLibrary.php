<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\MediaFolder;
use App\Models\Post;
use App\Models\PostFieldValue;
use App\Models\User;
use App\Support\TenantSlug;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaLibrary
{
    private const BLOCKED = [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'exe', 'sh', 'bat', 'cmd', 'com',
        'htaccess', 'js', 'html', 'htm', 'svg', 'xhtml',
    ];

    public function __construct(private readonly ActivityLogger $activity) {}

    public function store(UploadedFile $file, ?int $folderId, ?User $actor, ?string $onlyKind = null, ?string $alt = null, ?string $caption = null): MediaAsset
    {
        $tenant = current_tenant();
        abort_unless($tenant, 404);

        if (! $file->isValid()) {
            throw ValidationException::withMessages(['file' => 'The upload did not finish. Try again.']);
        }

        if ($file->getSize() > 20 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'Files must be 20 MB or smaller.']);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        if ($extension === '' || in_array($extension, self::BLOCKED, true)) {
            throw ValidationException::withMessages(['file' => 'This file type is not allowed.']);
        }

        if ($folderId) {
            $folder = MediaFolder::query()->whereKey($folderId)->first();

            if (! $folder) {
                throw ValidationException::withMessages(['folder_id' => 'Choose a folder in this workspace.']);
            }
        }

        $mime = (string) $file->getMimeType();
        $kind = $this->kind($mime, $extension);

        if ($onlyKind && $kind !== $onlyKind) {
            throw ValidationException::withMessages(['file' => 'Choose an '.$onlyKind.' file.']);
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs('tenants/'.$tenant->id.'/media', $filename, 'public');
        $dimensions = $this->maybeCompress($path, $extension);

        $asset = MediaAsset::query()->create([
            'tenant_id' => $tenant->id,
            'folder_id' => $folderId,
            'user_id' => $actor?->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'extension' => $extension,
            'kind' => $kind,
            'size' => (int) Storage::disk('public')->size($path),
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'alt' => $alt,
            'caption' => $caption,
        ]);

        $this->activity->log('media.uploaded', 'Uploaded '.$asset->original_name, $asset, [
            'kind' => $asset->kind,
            'path' => $asset->path,
        ], $tenant->id);

        return $asset;
    }

    public function update(MediaAsset $asset, array $data): MediaAsset
    {
        if (! empty($data['folder_id'])) {
            $folder = MediaFolder::query()->whereKey($data['folder_id'])->first();

            if (! $folder) {
                throw ValidationException::withMessages(['folder_id' => 'Choose a folder in this workspace.']);
            }
        }

        $asset->fill([
            'alt' => $data['alt'] ?? null,
            'caption' => $data['caption'] ?? null,
            'folder_id' => $data['folder_id'] ?: null,
        ])->save();

        $this->activity->log('media.updated', 'Updated '.$asset->original_name, $asset, [], $asset->tenant_id);

        return $asset;
    }

    public function delete(MediaAsset $asset): void
    {
        $id = (string) $asset->id;
        $postIds = Post::query()->select('id');

        PostFieldValue::query()
            ->whereIn('post_id', $postIds)
            ->where(function ($query) use ($id) {
                $query->where('value', $id)->orWhere('value', 'like', '%"'.$id.'"%');
            })
            ->get()
            ->each(function (PostFieldValue $value) use ($id) {
                if ($value->value === $id) {
                    $value->delete();

                    return;
                }

                $decoded = json_decode((string) $value->value, true);

                if (! is_array($decoded) || array_key_exists('url', $decoded)) {
                    return;
                }

                $filtered = array_values(array_filter(
                    $decoded,
                    fn ($item) => (string) $item !== $id,
                ));

                if ($filtered === []) {
                    $value->delete();

                    return;
                }

                $value->update(['value' => json_encode($filtered)]);
            });

        Storage::disk($asset->disk)->delete($asset->path);
        $this->activity->log('media.deleted', 'Deleted '.$asset->original_name, $asset, [], $asset->tenant_id);
        $asset->delete();
    }

    public function createFolder(string $name, ?int $parentId = null): MediaFolder
    {
        $tenant = current_tenant();
        abort_unless($tenant, 404);

        if ($parentId && ! MediaFolder::query()->whereKey($parentId)->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'Choose a folder in this workspace.']);
        }

        $folder = MediaFolder::query()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => TenantSlug::make('media_folders', $tenant->id, $name),
        ]);

        $this->activity->log('media.folder_created', 'Created folder '.$folder->name, $folder, [], $tenant->id);

        return $folder;
    }

    public function deleteFolder(MediaFolder $folder): void
    {
        if ($folder->assets()->exists() || $folder->children()->exists()) {
            throw ValidationException::withMessages([
                'folder' => 'Move files and nested folders out before deleting this folder.',
            ]);
        }

        $this->activity->log('media.folder_deleted', 'Deleted folder '.$folder->name, $folder, [], $folder->tenant_id);
        $folder->delete();
    }

    /**
     * @return array{width: int|null, height: int|null}
     */
    private function maybeCompress(string $path, string $extension): array
    {
        $absolute = Storage::disk('public')->path($path);
        $info = @getimagesize($absolute);
        $dimensions = [
            'width' => $info[0] ?? null,
            'height' => $info[1] ?? null,
        ];

        if (! $info || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) || ! function_exists('imagecreatetruecolor')) {
            return $dimensions;
        }

        try {
            $image = match ($extension) {
                'png' => @imagecreatefrompng($absolute),
                'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolute) : false,
                default => @imagecreatefromjpeg($absolute),
            };

            if (! $image) {
                return $dimensions;
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $max = 1920;

            if ($width > $max) {
                $newWidth = $max;
                $newHeight = max(1, (int) round($height * ($max / $width)));
                $resized = imagecreatetruecolor($newWidth, $newHeight);

                if ($extension === 'png') {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                }

                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
                $dimensions = ['width' => $newWidth, 'height' => $newHeight];
            }

            if ($extension === 'png') {
                imagepng($image, $absolute, 6);
            } elseif ($extension === 'webp' && function_exists('imagewebp')) {
                imagewebp($image, $absolute, 82);
            } else {
                imagejpeg($image, $absolute, 82);
            }

            imagedestroy($image);
        } catch (\Throwable) {
            return $dimensions;
        }

        return $dimensions;
    }

    private function kind(string $mime, string $extension): string
    {
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        $documents = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf', 'odt'];

        if (in_array($extension, $documents, true) || str_contains($mime, 'pdf') || str_starts_with($mime, 'text/')) {
            return 'document';
        }

        return 'other';
    }
}
