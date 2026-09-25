<ul class="divide-y divide-stone-100">
    @forelse ($activity as $entry)
        <li class="py-3">
            <p class="text-sm font-medium">{{ $entry->description ?: $entry->action }}</p>
            <p class="mt-1 text-xs text-slate-500">
                {{ $entry->user?->name ?? 'System' }}
                @if ($entry->tenant)
                    · {{ $entry->tenant->name }}
                @endif
                · {{ $entry->created_at?->diffForHumans() }}
            </p>
        </li>
    @empty
        <li class="py-6 text-sm text-slate-500">No activity yet.</li>
    @endforelse
</ul>
