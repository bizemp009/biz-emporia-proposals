{{-- Accessible table view of a chart's data. Expects $rows = [{label, display}], $head = [col1, col2]. --}}
<details class="group mt-3 text-xs">
    <summary class="cursor-pointer list-none font-semibold text-slate-500 hover:text-slate-800 [&::-webkit-details-marker]:hidden">
        <span class="group-open:hidden">View as table</span><span class="hidden group-open:inline">Hide table</span>
    </summary>
    <table class="mt-2 w-full text-left">
        <thead class="text-slate-500"><tr><th class="py-1 font-semibold">{{ $head[0] }}</th><th class="py-1 text-right font-semibold">{{ $head[1] }}</th></tr></thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
            @foreach ($rows as $r)
                <tr><td class="py-1">{{ $r['label'] }}</td><td class="py-1 text-right">{{ $r['display'] ?? $r['value'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
</details>
