<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\LayoutBlock;
use App\Support\SafeHtml;
use App\Support\SiteChrome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LayoutController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', LayoutBlock::class);

        return view('tenant.layout.index', [
            'blocks' => LayoutBlock::query()->orderBy('region')->orderBy('row')->orderBy('sort_order')->get(),
            'components' => config('site.components'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LayoutBlock::class);
        $data = $this->validated($request);
        $data['enabled'] = true;
        LayoutBlock::query()->create([
            'tenant_id' => current_tenant()->id,
            ...$data,
            'enabled' => true,
        ]);
        SiteChrome::forget();

        return back()->with('status', 'Section added.');
    }

    public function update(Request $request, LayoutBlock $layoutBlock): RedirectResponse
    {
        $this->authorize('update', $layoutBlock);
        $layoutBlock->fill($this->validated($request, $layoutBlock))->save();
        SiteChrome::forget();

        return back()->with('status', 'Layout saved.');
    }

    public function destroy(LayoutBlock $layoutBlock): RedirectResponse
    {
        $this->authorize('delete', $layoutBlock);
        $layoutBlock->delete();
        SiteChrome::forget();

        return back()->with('status', 'Section removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?LayoutBlock $block = null): array
    {
        $components = array_merge(...array_values(config('site.components')));
        $data = $request->validate([
            'region' => ['required', Rule::in(array_keys(config('site.components')))],
            'component' => ['required', Rule::in($components)],
            'row' => ['required', 'integer', 'min:1', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:50'],
            'col_desktop' => ['required', 'integer', 'min:1', 'max:4'],
            'col_tablet' => ['required', 'integer', 'min:1', 'max:2'],
            'col_mobile' => ['required', 'integer', 'min:1', 'max:1'],
            'enabled' => ['nullable', 'boolean'],
            'body' => ['nullable', 'string', 'max:5000'],
            'heading' => ['nullable', 'string', 'max:120'],
        ]);

        if (! in_array($data['component'], config('site.components.'.$data['region']), true)) {
            throw ValidationException::withMessages([
                'component' => 'That section does not belong in the '.$data['region'].'.',
            ]);
        }

        $data['enabled'] = $request->boolean('enabled', $block?->enabled ?? true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['settings'] = [
            'body' => SafeHtml::clean($data['body'] ?? ''),
            'heading' => trim(strip_tags((string) ($data['heading'] ?? ''))),
        ];
        unset($data['body'], $data['heading']);

        return $data;
    }
}
