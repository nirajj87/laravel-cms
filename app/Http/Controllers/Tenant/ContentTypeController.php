<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\FieldType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreContentTypeFieldRequest;
use App\Http\Requests\Tenant\StoreContentTypeRequest;
use App\Http\Requests\Tenant\UpdateContentTypeRequest;
use App\Models\ContentType;
use App\Services\ActivityLogger;
use App\Services\ContentCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentTypeController extends Controller
{
    public function __construct(
        private readonly ContentCatalog $catalog,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', ContentType::class);

        return view('tenant.posts.types.index', [
            'types' => ContentType::query()->withCount(['posts', 'fields'])->orderBy('name')->get(),
            'hub' => $this->hub(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ContentType::class);

        return view('tenant.posts.types.create', [
            'presets' => config('content.presets', []),
            'hub' => $this->hub(),
        ]);
    }

    public function store(StoreContentTypeRequest $request): RedirectResponse
    {
        $type = $this->catalog->createType(
            current_tenant(),
            $request->validated('name'),
            $request->validated('preset') ?: 'custom',
            $request->validated('description'),
        );

        return redirect()->route($this->hub().'.edit', $type)->with('status', 'Form created.');
    }

    public function edit(ContentType $contentType): View
    {
        $this->authorize('update', $contentType);

        return view('tenant.posts.types.edit', [
            'type' => $contentType->load('fields'),
            'fieldTypes' => FieldType::cases(),
            'hub' => $this->hub(),
        ]);
    }

    public function update(UpdateContentTypeRequest $request, ContentType $contentType): RedirectResponse
    {
        $contentType->fill([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'is_active' => $request->boolean('is_active'),
        ])->save();

        $this->catalog->syncFields($contentType->load('fields'), $request->input('fields', []));
        $this->activity->log('content_type.updated', 'Updated content type '.$contentType->name, $contentType, [], $contentType->tenant_id);

        return redirect()->route($this->hub().'.edit', $contentType)->with('status', 'Form saved.');
    }

    public function storeField(StoreContentTypeFieldRequest $request, ContentType $contentType): RedirectResponse
    {
        $this->catalog->addField($contentType, $request->all());

        return redirect()->route($this->hub().'.edit', $contentType)->with('status', 'Field added.');
    }

    public function destroyField(ContentType $contentType, int $field): RedirectResponse
    {
        $this->authorize('update', $contentType);
        $fieldModel = $contentType->fields()->whereKey($field)->firstOrFail();

        if ($fieldModel->is_system) {
            return back()->withErrors(['field' => 'The title field stays on every form.']);
        }

        $fieldModel->delete();

        return redirect()->route($this->hub().'.edit', $contentType)->with('status', 'Field removed.');
    }

    public function destroy(ContentType $contentType): RedirectResponse
    {
        $this->authorize('delete', $contentType);

        if ($contentType->posts()->exists()) {
            return back()->withErrors(['type' => 'Delete the posts in this form before deleting it.']);
        }

        $this->activity->log('content_type.deleted', 'Deleted content type '.$contentType->name, $contentType, [], $contentType->tenant_id);
        $contentType->delete();

        return redirect()->route($this->hub().'.index')->with('status', 'Form deleted.');
    }

    private function hub(): string
    {
        return request()->routeIs('tenant.form-builder.*')
            ? 'tenant.form-builder'
            : 'tenant.posts.types';
    }
}
