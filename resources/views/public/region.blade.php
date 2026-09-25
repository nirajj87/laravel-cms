@php
    $grouped = ($blocks[$region] ?? collect())->where('enabled', true)->groupBy('row')->sortKeys();
    $skipComponents = ['search', 'category_filter'];
@endphp
@if (! empty($bare))
    @foreach ($grouped as $row)
        @foreach ($row as $block)
            @continue(in_array($block->component, $skipComponents, true))
            @include('public.block', ['block' => $block])
        @endforeach
    @endforeach
@else
    @foreach ($grouped as $row)
        @php
            $visible = $row->reject(fn ($block) => in_array($block->component, $skipComponents, true));
        @endphp
        @continue($visible->isEmpty())
        <div class="site-row">
            @foreach ($visible as $block)
                <div class="site-span" style="grid-column: span {{ max(1, min(4, (int) $block->col_desktop)) }}; --span-tablet: {{ max(1, min(2, (int) $block->col_tablet)) }};">
                    @include('public.block', ['block' => $block])
                </div>
            @endforeach
        </div>
    @endforeach
@endif
