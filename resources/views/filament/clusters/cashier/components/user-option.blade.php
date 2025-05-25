<div class="flex items-center space-x-2">
    <div>
        <div class="font-medium text-gray-900 dark:text-white">
            {{ $user->profile->first_name }} {{ $user->profile->last_name }}
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $user->email }}
            @if($user->profile->phone_number)
                · {{ $user->profile->phone_number }}
            @endif
        </div>
    </div>
</div>
