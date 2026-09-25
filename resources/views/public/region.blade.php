@php
    $grouped = ($blocks[$region] ?? collect())->where('enabled', true)->groupBy('row')->sortKeys();
@endphp
@if (! empty($bare))
    @foreach ($grouped as $row)
        @foreach ($row as $block)
            @include('public.block', ['block' => $block])
        @endforeach
    @endforeach
@else
    @foreach ($grouped as $row)
        <div class="site-row">
            @foreach ($row as $block)
                <div class="site-span" style="grid-column: span {{ max(1, min(4, (int) $block->col_desktop)) }}; --span-tablet: {{ max(1, min(2, (int) $block->col_tablet)) }};">
                    @include('public.block', ['block' => $block])
                </div>
            @endforeach
        </div>
    @endforeach
@endif
