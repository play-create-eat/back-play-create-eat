<div class="flex flex-col">
    <div class="font-medium text-gray-900 dark:text-white">
        {{ $name }}
    </div>
    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Price: {{ $price }} €
    </div>
    @if(!empty($features))
        <div class="mt-1 flex flex-wrap gap-1">
            @foreach($features as $feature)
                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                    {{ $feature }}
                </span>
            @endforeach
        </div>
    @endif
</div>

