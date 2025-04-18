<div>
    <p class="font-medium mb-2 text-base">{{$user->profile->first_name}} {{$user->profile->last_name}} - {{$user->family->name}}</p>
    <div class="flex items-center text-xs gap-3 mb-2 text-gray-700">
        <span>{{$user->profile->phone_number}}</span>
        <span>[ {{$user->email}} ]</span>
    </div>
    <div class="flex items-center font-medium gap-3 text-xs">
        <span>
            Star Points: <span class="text-primary-600 font-mono">{{$user->family->main_wallet->balanceFloat}}</span>
        </span>
        <span>
            Loyalty Points: <span class="text-primary-600 font-mono">{{$user->family->loyalty_wallet->balanceFloat}}</span>
        </span>
    </div>
</div>
