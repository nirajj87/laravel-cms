@foreach ($nodes as $node)
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 py-3" style="padding-left: {{ $depth * 1.25 }}rem">
        <div class="min-w-0">
            <p class="font-medium">{{ $node->name }}</p>
            <p class="text-xs text-slate-500">/{{ $node->slug }} · {{ $node->status->label() }} · order {{ $node->sort_order }}</p>
        </div>
        <div class="flex gap-2">
            @permission('categories.edit')
                <a class="btn btn-secondary" href="{{ route('tenant.categories.edit', $node) }}">Edit</a>
            @endpermission
            @permission('categories.delete')
                <form method="POST" action="{{ route('tenant.categories.destroy', $node) }}" onsubmit="return confirm('Delete this category?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">Delete</button>
                </form>
            @endpermission
        </div>
    </div>
    @if ($node->children->isNotEmpty())
        @include('tenant.categories._branch', ['nodes' => $node->children, 'depth' => $depth + 1])
    @endif
@endforeach
